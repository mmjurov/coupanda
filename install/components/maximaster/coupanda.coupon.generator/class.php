<?php

namespace Maximaster\Coupanda;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Internals\DiscountCouponTable;
use Bitrix\Sale\Internals\DiscountTable;

class CoupandaCouponGenerator extends \CBitrixComponent
{
    public function onPrepareComponentParams($params)
    {
        $params['CACHE_TIME'] = 0;
        $params['CACHE_TYPE'] = 'N';
        return parent::onPrepareComponentParams($params);
    }

    public function onIncludeComponentLang()
    {
        parent::onIncludeComponentLang();
        Loc::loadLanguageFile(__FILE__);
    }

    public function executeComponent()
    {
        $this->setFrameMode(false);

        try {
            $this->checkPermissions();
            $this->loadModules();
            if ($this->isAjaxRequest()) {
                //$this->handleAjax();
                return null;
            } else {
                return $this->handle();
            }
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {

            } else {
                \ShowError($e->getMessage());
            }
        }
    }

    protected function loadModules()
    {
        array_map(function ($moduleId) {
            if (!Loader::includeModule($moduleId)) {
                throw new SystemException('Модуль ' . $moduleId . ' не установлен');
            }
        }, ['sale', 'maximaster.coupanda']);

    }

    protected function checkPermissions()
    {
        global $APPLICATION;
        $permission = $APPLICATION->GetGroupRight('maximaster.coupanda');
        if ($permission < 'W') {
            throw new SystemException('Недостаточно прав для использования генератора');
        }
    }

    protected function isAjaxRequest()
    {
        return isset($this->request['ajax_action']) && $this->request->isPost() && $this->request->isAjaxRequest();
    }

    protected function handle()
    {
        $this->setPageParameters();
        $this->setAdminContextMenu();
        $this->arResult['DISCOUNTS'] = $this->getDiscountList();
        $this->arResult['COUPON_TYPES'] = $this->getCouponTypes();
        $this->arResult['LINKS'] = $this->getLinks();
        $this->includeComponentTemplate();
    }

    protected function setPageParameters()
    {
        global $APPLICATION;
        $APPLICATION->SetTitle('Генератор купонов');
    }

    protected function setAdminContextMenu()
    {
        if (!$this->request->isAdminSection()) {
            return;
        }

        /*$menu = array(
            array(
                "TEXT" => Loc::getMessage("BTN_TO_LIST"),
                "TITLE" => Loc::getMessage("BTN_TO_LIST"),
                "LINK" => "/bitrix/admin/coupons_list.php?lang=".LANG,
                "ICON" => "btn_list"
            ),
        );

        $menu[] = [
            "TEXT" => Loc::getMessage("BTN_TO_LIST"),
            "TITLE" => Loc::getMessage("BTN_TO_LIST"),
            "LINK" => "/bitrix/admin/coupons_list.php?lang=".LANG,
            "ICON" => ""
        ];
        $context = new \CAdminContextMenu($menu);
        $context->Show();
        */
    }

    protected function getDiscountList()
    {
        $q = DiscountTable::query()
            ->addOrder('ID', 'desc')
            ->setSelect(['ID', 'NAME', 'ACTIVE']);

        $discounts = [];
        $discountList = $q->exec();
        while ($discount = $discountList->fetch()) {
            $discounts[] = $discount;
        }

        return $discounts;
    }

    protected function getLinks()
    {
        return [
            'new_discount' => '/bitrix/admin/sale_discount_edit.php?lang=' . LANGUAGE_ID
        ];
    }

    protected function getCouponTypes()
    {
        return DiscountCouponTable::getCouponTypes(true);
    }
}