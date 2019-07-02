<?php


namespace Commune\Chatbot\OOHost\Directing\Backward;

use Commune\Chatbot\OOHost\Context\Definition;
use Commune\Chatbot\OOHost\Directing\Navigator;

class Quit extends FallbackNavigator
{

    public const EVENT = Definition::QUIT;

    public function then(): ? Navigator
    {
        $this->dialog
            ->session
            ->shouldQuit();

        return null;
    }


}