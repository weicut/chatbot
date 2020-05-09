<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Ghost\Dialog\IWithdraw;

use Commune\Blueprint\Ghost\Cloner;
use Commune\Blueprint\Ghost\Dialog;
use Commune\Blueprint\Ghost\Ucl;
use Commune\Ghost\Dialog\AbsDialogue;
use Commune\Blueprint\Ghost\Dialog\Withdraw\Cancel;

/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class ICancel extends AbsDialogue implements Cancel
{
    protected $to;

    public function __construct(Cloner $cloner, Ucl $ucl, Ucl $to = null)
    {
        $this->to = $to;
        parent::__construct($cloner, $ucl);
    }

    protected function runInterception(): ? Dialog
    {
        return null;
    }

    protected function runTillNext(): Dialog
    {
        $process = $this->getProcess();

        return $this->withdrawCanceling($this, $process)
            ?? $this->maybeRedirect()
            ?? $this->fallbackFlow($this, $process);
    }

    protected function maybeRedirect() : ? Dialog
    {
        if (isset($this->to)) {
            return $this->redirectTo($this->to);
        }

        return null;
    }

    protected function selfActivate(): void
    {
        $process = $this->getProcess();
        $process->addCanceling([$this->ucl]);
    }


}