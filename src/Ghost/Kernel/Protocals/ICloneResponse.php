<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Ghost\Kernel\Protocals;

use Commune\Blueprint\Exceptions\CommuneErrorCode;
use Commune\Blueprint\Kernel\Protocals\AppResponse;
use Commune\Blueprint\Kernel\Protocals\GhostResponse;
use Commune\Framework\Spy\SpyAgency;
use Commune\Protocals\Intercom\InputMsg;
use Commune\Protocals\Intercom\OutputMsg;
use Commune\Support\Utils\StringUtils;

/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class ICloneResponse implements GhostResponse
{

    /**
     * @var string
     */
    protected $traceId;


    /**
     * @var bool
     */
    protected $tiny;

    /**
     * @var bool
     */
    protected $async;

    /**
     * @var int
     */
    protected $errcode;

    /**
     * @var string
     */
    protected $errmsg;

    /**
     * @var InputMsg
     */
    protected $input;

    /**
     * @var InputMsg[]
     */
    protected $asyncInputs = [];

    /**
     * @var OutputMsg[]
     */
    protected $outputs = [];

    /**
     * ICloneResponse constructor.
     * @param string $traceId
     * @param bool $tiny
     * @param bool $async
     * @param int $errcode
     * @param string $errmsg
     * @param InputMsg|null $input
     * @param array $asyncInputs
     * @param array $outputs
     */
    public function __construct(
        string $traceId,
        bool $tiny,
        bool $async,
        int $errcode = AppResponse::SUCCESS,
        string $errmsg = '',
        InputMsg $input = null,
        array $asyncInputs = [],
        array $outputs = []
    )
    {
        $this->traceId = $traceId;
        $this->tiny = $tiny;
        $this->async = $async;
        $this->errcode = $errcode;

        $this->errmsg = empty($errmsg)
            ? AppResponse::DEFAULT_ERROR_MESSAGES[$errcode] ?? ''
            : $errmsg ;

        $this->input = $input;
        $this->asyncInputs = $asyncInputs;
        $this->outputs = $outputs;

        SpyAgency::incr(static::class);
    }

    public function isAsync(): bool
    {
        return $this->async;
    }


    public function requireTinyResponse(): bool
    {
        return $this->tiny;
    }


    public function getTraceId(): string
    {
        return $this->traceId;
    }


    public function getErrcode(): int
    {
        return $this->errcode;
    }

    public function getErrmsg(): string
    {
        return $this->errmsg;
    }

    public function isSuccess(): bool
    {
        return $this->errcode < CommuneErrorCode::FAILURE_CODE_START;
    }

    public function getInput(): InputMsg
    {
        return $this->input;
    }

    public function getAsyncInputs(): array
    {
        return $this->asyncInputs;
    }

    public function getOutputs(): array
    {
        return $this->outputs;
    }

    public function getProtocalId(): string
    {
        return StringUtils::namespaceSlashToDot(static::class);
    }

    public function setConvoId(string $convoId): void
    {
        $this->input->setConvoId($convoId);

        $this->asyncInputs = array_map(function(InputMsg $input) use ($convoId) {
            $input->setConvoId($convoId);
            return $input;
        }, $this->asyncInputs);

        $this->outputs = array_map(function(OutputMsg $output) use ($convoId) {
            $output->setConvoId($convoId);
            return $output;
        }, $this->asyncInputs);
    }


    public function __destruct()
    {
        SpyAgency::decr(static::class);
    }

}