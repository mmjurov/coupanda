<?php

namespace Maximaster\Coupanda\EventHandlers;

use Bitrix\Main\Context;
use Bitrix\Main\EventManager;

class EventHandlersRegistry
{
    public static function register()
    {
        $manager = EventManager::getInstance();
        $context = Context::getCurrent();
        if ($context->getRequest()->isAdminSection()) {
            $manager->addEventHandler('main', 'OnBuildGlobalMenu', array(
                OnBuildGlobalMenu::class, 'addGeneratorToMenu'
            ));
        }
    }
}