<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Ghost\Runtime;

use Commune\Blueprint\Exceptions\IO\LoadDataFailException;
use Commune\Blueprint\Ghost\Cloner;
use Commune\Blueprint\Ghost\Memory\Memory;
use Commune\Blueprint\Ghost\Runtime\Process;
use Commune\Blueprint\Ghost\Runtime\Runtime;
use Commune\Blueprint\Ghost\Runtime\Trace;
use Commune\Blueprint\Ghost\Ucl;
use Commune\Contracts\Ghost\RuntimeDriver;
use Commune\Ghost\Memory\IMemory;
use Commune\Protocals\HostMsg\Convo\ContextMsg;
use Commune\Support\RunningSpy\Spied;
use Commune\Support\RunningSpy\SpyTrait;
use Commune\Blueprint\Exceptions\IO\SaveDataFailException;


/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class IRuntime implements Runtime, Spied
{
    use SpyTrait;

    /**
     * @var Cloner
     */
    protected $cloner;

    /**
     * @var RuntimeDriver
     */
    protected $driver;

    /**
     * @var string
     */
    protected $traceId;

    /**
     * @var string
     */
    protected $convoId;

    /*---- cached ----*/

    /**
     * @var Process|null
     */
    protected $process;

    /**
     * @var array|null
     */
    protected $convoMemories;

    /**
     * @var array
     */
    protected $longTermMemories = [];

    /**
     * @var Trace|null
     */
    protected $trace;

    /**
     * IRuntime constructor.
     * @param Cloner $cloner
     */
    public function __construct(Cloner $cloner)
    {
        $this->cloner = $cloner;

        // 无状态请求, 不生成 runtime driver
        if (! $this->cloner->isStateless()) {
            $this->driver = $cloner
                ->getContainer()
                ->make(RuntimeDriver::class);
        }

        $this->traceId = $cloner->getTraceId();
        $this->convoId = $cloner->getConversationId();

        static::addRunningTrace($this->traceId, $this->traceId);
    }

    /*---- memory ----*/

    public function findMemory(string $id, bool $longTerm, array $defaults): Memory
    {
        return $longTerm
            ? $this->findLongTermMemory($id, $defaults)
            : $this->findConvoMemory($id, $defaults);
    }

    protected function findConvoMemory(string $id, array $defaults) : Memory
    {
        // 一次性读取所有缓存
        if (!isset($this->convoMemories)) {
            $this->convoMemories = $this->ioFindSessionMemories();
        }

        // 不存在则生成
        return $this->convoMemories[$id]
            ?? $this->convoMemories[$id] = new IMemory($id, false, $defaults);
    }

    protected function findLongTermMemory(string $id, array $defaults) : Memory
    {
        if (isset($this->longTermMemories[$id])) {
            return $this->longTermMemories[$id];
        }

        // 一个一个读取
        $memory = $this->ioFindLongTermMemory($id);
        $memory = $memory ?? new IMemory($id, true, $defaults);

        return $this->longTermMemories[$id] = $memory;
    }


    /*---- process ----*/

    public function getCurrentProcess(): Process
    {
        if (isset($this->process)) {
            return $this->process;
        }

        // 从历史记忆中寻找.
        if (!$this->cloner->isStateless()) {
            $process = $this->ioFetchCurrentProcess();

            if (isset($process)) {
                // 生成一个新的 Snapshot
                return $this->process = $process
                    ->nextSnapshot($this->cloner->getTraceId());
            }
        }

        // 创建一个新的.
        $contextName = $this->cloner->scene->contextName;
        return $this->process = $this->createProcess($contextName);
    }

    public function setCurrentProcess(Process $process): void
    {
        $this->process = $process;
    }

    public function createProcess(string $contextUcl): Process
    {
        $root = Ucl::createFromUcl($this->cloner, $contextUcl);
        return new IProcess($this->convoId, $root, $this->cloner->getTraceId());
    }


    /*------ contextMsg ------*/

    public function toContextMsg(): ? ContextMsg
    {
        if (!isset($this->process)) {
            return null;
        }

        $waiter = $this->process->waiter;
        if (empty($waiter)) {
            return null;
        }

        // prev 不存在时.
        $prev = $this->process->prev;
        $prevWaiter =isset($prev) ? $prev->waiter : null;

        $changed = !isset($prevWaiter) || ($prevWaiter->await !== $waiter->await);

        if ($changed) {
            $ucl = $this->process->decodeUcl($waiter->await);
            $context = $this->cloner->getContext($ucl);
            return $context->toContextMsg();
        }

        return null;
    }


    /*------ save ------*/

    public function save(): void
    {
        if ($this->cloner->isStateless()) {
            return;
        }

        if (!isset($this->driver)) {
            return;
        }

        if (!isset($this->process)) {
            return;
        }

        $expire = $this->cloner->getSessionExpire();
        $success = $this->ioSaveLongTermMemories()
            && $this->ioSaveSessionMemories($expire)
            && $this->ioCacheProcess($expire);

        if (empty($success)) {
            throw new SaveDataFailException('cloner status');
        }

    }

    /*------ io ------*/

    protected function ioFetchCurrentProcess() : ? Process
    {
        try {
            if (!isset($this->driver)) {
                return null;
            }

            return $this->driver->fetchProcess(
                $this->cloner,
                $this->cloner->getConversationId()
            );

        } catch (\Exception $e) {
            throw new LoadDataFailException('current process', $e);
        }
    }

    protected function ioCacheProcess(int $expire) : bool
    {
        if (!isset($this->process)) {
            return true;
        }

        if (!isset($this->driver)) {
            return true;
        }

        try {

            return $this->driver->cacheProcess($this->cloner, $this->process, $expire);
        } catch (\Exception $e) {
            throw new SaveDataFailException('process', $e);
        }

    }

    protected function ioSaveSessionMemories(int $expire) : bool
    {

        if (!isset($this->driver)) {
            return true;
        }

        $memories = array_filter($this->convoMemories, function(Memory $memory){
            return $memory->isChanged();
        });

        if (empty($memories)) {
            return true;
        }

        try {
            return $this->driver->cacheSessionMemories(
                $this->cloner->getId(),
                $this->cloner->getConversationId(),
                $memories,
                $expire
            );

        } catch (\Exception $e) {
            throw new SaveDataFailException('session memories', $e);
        }
    }

    protected function ioFindSessionMemories() : array
    {
        if (!isset($this->driver)) {
            return [];
        }

        try {

            return $this->driver
                ->fetchSessionMemories(
                    $this->cloner->getId(),
                    $this->cloner->getConversationId()
                );
        } catch (\Exception $e) {
            throw new LoadDataFailException('session memories', $e);
        }
    }

    protected function ioSaveLongTermMemories() : bool
    {

        if (!isset($this->driver)) {
            return true;
        }

        $memories = array_filter($this->longTermMemories, function(Memory $memory) {
            return $memory->isChanged();
        });

        if (empty($memories)) {
            return true;
        }

        try {

            return $this->driver->saveLongTermMemories(
                $this->cloner->getId(),
                $memories
            );

        } catch (\Exception $e) {
            throw new SaveDataFailException('long term memory', $e);
        }

    }

    protected function ioFindLongTermMemory(string $id) : ? Memory
    {
        if (!isset($this->driver)) {
            return null;
        }

        try {
            return $this->driver->findLongTermMemories(
                $this->cloner->getId(),
                $id
            );

        // 请求级别的影响.
        } catch (\Exception $e) {
            throw new LoadDataFailException('long term memory', $e);
        }
    }

    public function __get($name)
    {
        if ($name === 'trace') {
            return $this->trace
                ?? $this->trace = new ITrace(
                    $this->cloner->config->maxRedirectTimes,
                    $this->cloner->logger,
                    $this->cloner->isDebugging()
                );
        }
        return null;
    }

    public function __destruct()
    {
        // 清空数据
        $this->driver = null;
        $this->cloner = null;
        $this->process = null;
        $this->longTermMemories = [];
        $this->convoMemories = null;

        static::removeRunningTrace($this->traceId);
    }
}