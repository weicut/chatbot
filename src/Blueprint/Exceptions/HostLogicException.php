<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Blueprint\Exceptions;

use Throwable;
use Commune\Blueprint\Framework\Request\AppResponse;


/**
 * 机器人的逻辑错误, 不应该发生.
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class HostLogicException extends \LogicException
{
    public function __construct(
        string $message = "",
        int $code = AppResponse::HOST_LOGIC_ERROR,
        Throwable $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
    }

}