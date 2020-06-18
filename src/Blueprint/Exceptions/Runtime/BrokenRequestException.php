<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Blueprint\Exceptions\Runtime;

use Commune\Blueprint\Exceptions\CommuneRuntimeException;
use Commune\Blueprint\Kernel\Protocals\AppResponse;

/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class BrokenRequestException extends CommuneRuntimeException
{
    public function __construct(string $message = "",  \Throwable $previous = null)
    {
        $message = empty($message)
            ? AppResponse::DEFAULT_ERROR_MESSAGES[AppResponse::HOST_REQUEST_FAIL]
            : $message;

        parent::__construct($message, AppResponse::HOST_REQUEST_FAIL, $previous);
    }


}