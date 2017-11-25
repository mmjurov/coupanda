<?php

namespace Maximaster\Coupanda;

use Bitrix\Main\Context;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Internals\DiscountCouponTable;
use Bitrix\Sale\Internals\DiscountTable;
use Maximaster\Coupanda\Generator\Collections\DigitsCollection;
use Maximaster\Coupanda\Generator\Collections\EnglishLettersCollection;
use Maximaster\Coupanda\Generator\Collections\RussianLettersCollection;
use Maximaster\Coupanda\Generator\CouponGenerator;
use Maximaster\Coupanda\Generator\CouponGeneratorException;
use Maximaster\Coupanda\Generator\SequenceGenerator;
use Maximaster\Coupanda\Generator\Template\SequenceTemplate;
use Maximaster\Coupanda\Generator\Template\SequenceTemplateInterface;
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
        $isAjax = $this->isAjaxRequest() && $this->request->isPost();

        try {
            $this->checkPermissions();
            $this->loadModules();
            if ($isAjax) {
                $this->handleAjax();
                return null;
            } else {
                return $this->handle();
            }
        } catch (\Exception $e) {
            \ShowError($e->getMessage());
            return null;
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
            ->setSelect(['ID', 'NAME', 'ACTIVE', 'ACTIVE_FROM', 'ACTIVE_TO']);

        $defaultValue = '';
        $requestedValue = $this->request->get('DISCOUNT_ID');
        $selectedValue = $requestedValue ? $requestedValue : $defaultValue;

        $discounts = [];
        $discountList = $q->exec();
        $now = new DateTime();
        while ($discount = $discountList->fetch()) {

            $discountFormOption = [
                'NAME' => "[{$discount['ID']}] {$discount['NAME']}",
                'VALUE' => $discount['ID'],
                'SELECTED' => $discount['ID'] == $selectedValue,
            ];

            $discount['FORM_OPTION'] = $discountFormOption;

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
        $defaultValue = DiscountCouponTable::TYPE_ONE_ORDER;
        $requestedValue = $this->request->get('TYPE');
        $selectedValue = $requestedValue ? $requestedValue : $defaultValue;

        $types = DiscountCouponTable::getCouponTypes(true);
        $values = [];
        foreach ($types as $code => $name) {
            $values[] = [
                'VALUE' => $code,
                'NAME' => $name,
                'SELECTED' => $selectedValue == $code
            ];
        }

        return $values;
    }

    protected function handleAjax()
    {
        global $APPLICATION;
        $APPLICATION->RestartBuffer();
        $request = $this->request;
        $response = Context::getCurrent()->getResponse();
        $response->clear();
        $response->addHeader('Content-Type', 'application/json');

        try {
            if (!check_bitrix_sessid()) {
                throw new SystemException('Ваша сессия истекла');
            }

            $ajaxResponse = $this->handleAjaxAction($request);
            if (!($ajaxResponse instanceof JsonResponse)) {
                throw new \LogicException('Произошла неизвестная ошибка работы с модулем. Обратитесь в техническую поддержку');
            }

        } catch (\Exception $e) {
            $ajaxResponse = new JsonResponse();
            $ajaxResponse->setStatus(500);
            $ajaxResponse->setMessage($e->getMessage());
        }

        $response->flush($ajaxResponse->render());

        \CMain::FinalActions();
        die;
    }

    protected function handleAjaxAction(HttpRequest $request)
    {
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

        $response = new JsonResponse();
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

        $template = $progress->getSettings()->getTemplate();
        $couponGenerator = $this->getCouponGenerator(
            $this->getSequenceGenerator($this->getSequenceTemplate($template)),
            $progress
        );

        $generatedCoupons = [];
        while ($countToGenerate > 0 && $totalTimeSpent < $stepTime) {

            try {
                $generationResult = $couponGenerator->generate(10);
                $createdCoupons = $generationResult->getData()['COUPONS'];
                $progress->incrementProcessedCount(count($createdCoupons));
                $generatedCoupons += $createdCoupons;
                $countToGenerate -= count($createdCoupons);
                if (!$generationResult->isSuccess()) {
                    throw new CouponGeneratorException(
                        $generationResult->getErrorCollection()->current()->getMessage()
                    );
                }
            } catch (\Exception $e) {
                $response->setStatus(500);
                $response->setMessage($e->getMessage());
                $response->setPayload([
                    'progress_html' => $this->renderProgressHtml($progress),
                    'report' => $this->getReport($progress),
                ]);
                $progress->setFinishDate(new \DateTime());
                return $response;
            }

            $totalTimeSpent = microtime(true) - $timeStart;
        }

        $response->setStatus(201);
        $response->setPayload([
            'progress_html' => $this->renderProgressHtml($progress),
            'next_action' => $progress->getProgressPercentage() < 100 ? 'generation_step' : 'generation_finish',
            'report' => $this->getReport($progress),
            //'coupons' => $generatedCoupons
        ]);

        return $response;
    }

    protected function ajaxGenerationStart(ProcessProgress $progress, HttpRequest $request)
    {
        $response = new JsonResponse();
        $settings = ProcessSettings::createFromRequest($request);
        $settingsValidationResult = $settings->validate();
        if (!$settingsValidationResult->isSuccess()) {
            $response->setStatus(400);
            $message = implode('. ', $settingsValidationResult->getErrorMessages());
            $response->setMessage($message);
            return $response;
        }

        if ($progress->getProcessedCount() > 0) {
            $progress->clear();
        }

        $progress->setStartDate(new \DateTime());

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

        $response = new JsonResponse();

        if (strlen($template) === 0) {
            $response->setStatus(500);
            $response->setMessage('Указан пустой шаблон');
        } else {

            $generator = $this->getSequenceGenerator(
                $this->getSequenceTemplate($template)
            );

            $preview = $generator->generateSeveral(20);

            $response->setStatus(200);
            $response->setPayload([
                'preview' => $preview,
            ]);
        }

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

    protected function getSequenceTemplate($templateString)
    {
        $template = new SequenceTemplate();
        $template->setTemplate($templateString);
        $template->addPlaceholder('@', new RussianLettersCollection());
        $template->addPlaceholder('$', new EnglishLettersCollection());
        $template->addPlaceholder('#', new DigitsCollection());

        return $template;
    }

    protected function getSequenceGenerator(SequenceTemplateInterface $template)
    {
        $generator = new SequenceGenerator();
        $generator->setTemplate($template);
        return $generator;
    }

    protected function getCouponGenerator(SequenceGenerator $generator, ProcessProgress $progress)
    {
        $couponGenerator = new CouponGenerator($generator, $progress->getSettings());
        return $couponGenerator;
    }
}
