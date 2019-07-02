<?php


namespace Commune\Chatbot\App\Drivers\Demo;



use Commune\Chatbot\OOHost\Context\Context;
use Commune\Chatbot\OOHost\History\Breakpoint;
use Commune\Chatbot\OOHost\History\Yielding;
use Commune\Chatbot\OOHost\Session\Driver;
use Commune\Chatbot\OOHost\Session\Session;
use Commune\Chatbot\OOHost\Session\SessionData;
use Psr\Log\LoggerInterface;

class ArraySessionDriver implements Driver
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected static $sessionData = [];

    protected static $yielding = [];

    protected static $breakpoints = [];

    protected static $contexts = [];

    /**
     * ArraySessionDriver constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function saveSessionData(
        Session $session,
        SessionData $sessionData
    ): void
    {
        $type = $sessionData->getSessionDataType();
        $id = $sessionData->getSessionDataId();
        self::$sessionData[$type][$id] = serialize($sessionData);
    }

    public function findSessionData(
        string $id,
        string $dataType = ''
    ): ? SessionData
    {
        if (!isset(self::$sessionData[$dataType][$id])) {
            return null;
        }

        $content = self::$sessionData[$dataType][$id];
        $data = unserialize($content);

        return $data instanceof SessionData && $data->getSessionDataType() === $dataType
            ? $data
            : null;
    }


    public function saveYielding(Session $session, Yielding $yielding): void
    {
        $this->saveSessionData($session, $yielding);
    }

    public function findYielding(string $contextId): ? Yielding
    {
        return $this->findSessionData($contextId, SessionData::YIELDING_TYPE);
    }

    public function saveBreakpoint(Session $session, Breakpoint $breakpoint): void
    {
        $this->saveSessionData($session, $breakpoint);
    }

    public function findBreakpoint(Session $session, string $id): ? Breakpoint
    {
        return $this->findSessionData($id, SessionData::BREAK_POINT);
    }

    public function saveContext(Session $session, Context $context): void
    {
        $this->saveSessionData($session, $context);
    }

    public function findContext(Session $session, string $contextId): ? Context
    {
        return $this->findSessionData($contextId, SessionData::CONTEXT_TYPE);
    }

    public function __destruct()
    {
        $this->logger->debug(__METHOD__);
    }


}