<?php

namespace Maximaster\Coupanda;

use Bitrix\Main\Context;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Internals\DiscountCouponTable;
use Bitrix\Sale\Internals\DiscountTable;
use Maximaster\Coupanda\Http\JsonResponse;
use Maximaster\Coupanda\Process\ProcessProgress;
use Maximaster\Coupanda\Process\ProcessReport;
use Maximaster\Coupanda\Process\ProcessSettings;

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
            if ($this->isAjaxRequest() && $this->request->isPost()) {
                if (!check_bitrix_sessid()) {
                    throw new SystemException('Ваша сессия истекла');
                }
                $this->handleAjax();
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
        return isset($this->request['ajax_action']) && $this->request->isAjaxRequest();
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

    protected function handleAjax()
    {
        global $APPLICATION;
        $APPLICATION->RestartBuffer();
        $request = $this->request;
        $response = Context::getCurrent()->getResponse();
        $response->clear();
        $response->addHeader('Content-Type', 'application/json');

        $ajaxResponse = $this->handleAjaxAction($request);
        if (!($ajaxResponse instanceof JsonResponse)) {
            $ajaxResponse = new JsonResponse();
            $ajaxResponse->setStatus(500);
            $ajaxResponse->setMessage('Произошла неизвестная ошибка работы с модулем. Обратитесь в техническую поддержку');
        }

        $response->flush($ajaxResponse->render());

        \CMain::FinalActions();
        die;
    }

    protected function handleAjaxAction(HttpRequest $request)
    {
        $response = new JsonResponse();
        $action = $request['ajax_action'];

        $progress = new ProcessProgress();

        switch ($action) {
            case 'generation_preview':
                return $this->ajaxGenerationPreview($request);
                break;
            case 'generation_start':
                return $this->ajaxGenerationStart($progress, $request);
                break;
            case 'generation_step':
                return $this->ajaxGenerationProcess($progress);
                break;
            case 'generation_finish':
                return $this->ajaxGenerationFinish($progress);
                break;
        }

        $response->setStatus(400);
        $response->setMessage('Действие ' . $action . ' недоступно');
        return $response;
    }

    protected function ajaxGenerationProcess(ProcessProgress $progress)
    {
        $response = new JsonResponse();
        $stepCount = 1000;
        $stepTime = 5;

        if (!$progress->isInProgress()) {
            $response->setStatus(400);
            $response->setMessage('Попытка выполнить запрос на генерацию без инициализации. Попробуйте начать процесс заново');
            return $response;
        }

        $countToFinish = $progress->getSettings()->getCount() - $progress->getProcessedCount();
        $countToGenerate = $countToFinish > $stepCount ? $stepCount : $countToFinish;

        $timeStart = microtime(true);
        $totalTimeSpent = microtime(true) - $timeStart;

        $progress->incrementStep();

        while ($countToGenerate > 0 && $totalTimeSpent < $stepTime) {

            try {
                $progress->incrementProcessedCount();
                $countToGenerate--;
            } catch (\Exception $e) {
                $response->setStatus(500);
                $response->setMessage($e->getMessage());
                return $response;
            }

            $totalTimeSpent = microtime(true) - $timeStart;
        }

        $response->setStatus(201);
        $response->setPayload([
            'progress_html' => $this->renderProgressHtml($progress),
            'next_action' => $progress->getProgressPercentage() < 100 ? 'generation_step' : 'generation_finish',
            'report' => $this->getReport($progress),
        ]);

        return $response;
    }

    protected function ajaxGenerationStart(ProcessProgress $progress, HttpRequest $request)
    {
        $response = new JsonResponse();
        $settings = ProcessSettings::createFromRequest($request);
        $progress->setSettings($settings);

        if ($progress->getProcessedCount() > 0) {
            $progress->clear();
        }

        $progress->setStartDate(new \DateTime());

        // TODO Полная валидация формы
        $template = $settings->getTemplate();
        if ($template === null) {
            $response->setStatus(400);
            $response->setMessage('Указан пустой шаблон');
            return $response;
        }

        $count = $settings->getCount();
        if (!$count || $count <= 0) {
            $response->setStatus(400);
            $response->setMessage('Необходимо установить количество промокодов для генерации');
            return $response;
        }

        $progress->setSettings($settings);

        $response->setStatus(200);
        $response->setMessage('Инициализация успешно завершена');
        $response->setPayload([
            'init' => 'ok',
            'progress_html' => $this->renderProgressHtml($progress),
            'next_action' => 'generation_step',
            'report' => $this->getReport($progress),
        ]);

        return $response;
    }

    protected function ajaxGenerationFinish(ProcessProgress $progress)
    {
        $progress->setFinishDate(new \DateTime());
        $response = new JsonResponse();
        $response->setStatus(200);
        $response->setMessage('Процесс импорта успешно завершен');
        $response->setPayload([
            'progress_html' => $this->renderProgressHtml($progress),
            'report' => $this->getReport($progress),
        ]);

        $progress->clear();
        return $response;
    }

    protected function ajaxGenerationPreview(HttpRequest $request)
    {
        $template = $request->get('template');

        $preview = $template;
        $response = new JsonResponse();
        $response->setStatus(200);
        $response->setPayload([
            'preview' => $preview,
        ]);
        return $response;
    }

    protected function renderProgressHtml(ProcessProgress $progress)
    {
        $message = 'Сгенерировано #PROGRESS_VALUE# из #PROGRESS_TOTAL# (#PROGRESS_PERCENT#)';
        if ($progress->getProcessedCount() == 0) {
            $message = 'Инициализация ...';
        } elseif ($progress->getProgressPercentage() === 100) {
            $message = 'Процесс успешно завершен!';
        }

        $progressMessage = new \CAdminMessage([
            'PROGRESS_TOTAL' => $progress->getSettings()->getCount(),
            'PROGRESS_VALUE' => $progress->getProcessedCount(),
            'PROGRESS_TEMPLATE' => $message
        ]);

        return $progressMessage->_getProgressHtml();
    }

    protected function getReport(ProcessProgress $progress)
    {
        $report = new ProcessReport($progress);
        return $report->asArray();
    }
}
