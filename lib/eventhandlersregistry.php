<?php

namespace Maximaster\Coupanda;

use Bitrix\Main\Context;
use Bitrix\Main\EventManager;
use Maximaster\Coupanda\EventHandlers\OnBuildGlobalMenu;

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