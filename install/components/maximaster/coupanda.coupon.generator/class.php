<?php

namespace Maximaster\Coupanda;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Internals\DiscountCouponTable;
use Bitrix\Sale\Internals\DiscountTable;
use Maximaster\Coupanda\Compability\CompabilityChecker;
use Maximaster\Coupanda\Generator\Collections\DigitsCollection;
use Maximaster\Coupanda\Generator\Collections\EnglishLettersCollection;
use Maximaster\Coupanda\Generator\Collections\RussianLettersCollection;
use Maximaster\Coupanda\Generator\CouponGenerator;
use Maximaster\Coupanda\Generator\CouponGeneratorException;
use Maximaster\Coupanda\Generator\SequenceGenerator;
use Maximaster\Coupanda\Generator\Template\SequenceTemplate;
use Maximaster\Coupanda\Generator\Template\SequenceTemplateInterface;
use Maximaster\Coupanda\Http\JsonResponse;
use Maximaster\Coupanda\Log\FileLogger;
use Maximaster\Coupanda\Log\Logger;
use Maximaster\Coupanda\Log\LoggerFactory;
use Maximaster\Coupanda\Log\LoggerInterface;
use Maximaster\Coupanda\Log\NullLogger;
use Maximaster\Coupanda\Process\Process;
use Maximaster\Coupanda\Process\ProcessRepository;
use Maximaster\Coupanda\Process\ProcessSettings;

class CoupandaCouponGenerator extends \CBitrixComponent
{
    /** @var LoggerInterface $logger */
    protected $logger = null;

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
        $logger = $this->getLogger();
        $loggerContext = $this->getLoggerContext();

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
            $loggerContext['message'] = $e->getMessage();
            $loggerContext['code'] = $e->getCode();
            $logger->notice(
                Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:LOG.QUERY.ERROR') . ' [{code}] {message}',
                $loggerContext
            );

            \ShowError($e->getMessage());
            return null;
        }
    }

    protected function loadModules()
    {
        array_map(function ($moduleId) {
            if (!Loader::includeModule($moduleId)) {
                throw new SystemException(
                    Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:NEED.INSTALL.MODULE', ['MODULE' => $moduleId])
                );
            }
        }, ['sale', 'maximaster.coupanda']);

    }

    /**
     * @throws SystemException
     */
    protected function checkPermissions()
    {
        global $APPLICATION;
        $permission = $APPLICATION->GetGroupRight('maximaster.coupanda');
        if ($permission < 'W') {
            throw new SystemException(Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:NOT_ENOUGH_PERMISSIONS'));
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
        $APPLICATION->SetTitle(Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:TITLE'));
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

    /**
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     */
    protected function handleAjax()
    {
        global $APPLICATION;
        $APPLICATION->RestartBuffer();
        $request = $this->request;
        $response = Context::getCurrent()->getResponse();
        $response->clear();
        $response->addHeader('Content-Type', 'application/json');

        $logger = $this->getLogger();
        $loggerContext = $this->getLoggerContext();
        $logger->debug(Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:AJAX.REQUEST') . ' {request}', $loggerContext);

        try {
            if (!check_bitrix_sessid()) {
                throw new SystemException(Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:SESSION_EXPIRED'));
            }

            $ajaxResponse = $this->handleAjaxAction($request);
            if (!($ajaxResponse instanceof JsonResponse)) {
                throw new \LogicException(Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:UNKNOWN_ERROR'));
            }

            $loggerContext['response'] = $ajaxResponse->render();
            $logger->debug(Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:AJAX.RESPONSE') . ' {response}', $loggerContext);

        } catch (\Exception $e) {
            $loggerContext['message'] = $e->getMessage();
            $loggerContext['code'] = $e->getCode();
            $logger->notice(Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:AJAX.ERROR') . ' [{code}] {message}', $loggerContext);

            $ajaxResponse = new JsonResponse();
            $ajaxResponse->setStatus(500);
            $ajaxResponse->setMessage($e->getMessage());
        }

        $response->flush($ajaxResponse->render());

        \CMain::FinalActions();
        die;
    }

    /**
     * @param HttpRequest $request
     * @return JsonResponse
     * @throws \Bitrix\Main\DB\DbException
     */
    protected function handleAjaxAction(HttpRequest $request)
    {
        $action = $request['ajax_action'];

        switch ($action) {
            case 'generation_preview':
                return $this->ajaxGenerationPreview($request);
                break;
            case 'generation_start':
                return $this->ajaxGenerationStart($request);
                break;
            case 'generation_step':
                return $this->ajaxGenerationProcess($request);
                break;
            case 'generation_finish':
                return $this->ajaxGenerationFinish($request);
                break;
        }

        $response = new JsonResponse();
        $response->setStatus(400);
        $response->setMessage(Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:INVALID_ACTION', ['ACTION' => $action]));
        return $response;
    }

    /**
     * @param HttpRequest $request
     * @return JsonResponse
     * @throws \Bitrix\Main\DB\DbException
     */
    protected function ajaxGenerationProcess(HttpRequest $request)
    {
        $response = new JsonResponse();
        $stepCount = 1000;
        $stepTime = 5;

        $process = $this->getProcess($request);

        if (!$process->isInProgress()) {
            $response->setStatus(400);
            $response->setMessage(Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:NOT_INITIALIZED'));
            return $response;
        }

        $countToFinish = $process->getSettings()->getCount() - $process->getProcessedCount();
        $countToGenerate = $countToFinish > $stepCount ? $stepCount : $countToFinish;

        $timeStart = microtime(true);
        $totalTimeSpent = microtime(true) - $timeStart;

        $template = $process->getSettings()->getTemplate();
        $couponGenerator = $this->getCouponGenerator(
            $this->getSequenceGenerator($this->getSequenceTemplate($template)),
            $process
        );

        $generatedCoupons = [];

        try {
            while ($countToGenerate > 0 && $totalTimeSpent < $stepTime) {
                $generationResult = $couponGenerator->generate($countToGenerate > 10 ? 10 : $countToGenerate);
                $createdCoupons = $generationResult->getData()['COUPONS'];
                $process->incrementProcessedCount(count($createdCoupons));
                $generatedCoupons += $createdCoupons;
                $countToGenerate -= count($createdCoupons);
                if (!$generationResult->isSuccess()) {
                    throw new CouponGeneratorException(
                        $generationResult->getErrorCollection()->current()->getMessage()
                    );
                }
                $totalTimeSpent = microtime(true) - $timeStart;
            }

            $response->setStatus(201);
            $response->setPayload([
                'progress_html' => $this->renderProgressHtml($process),
                'next_action' => $process->getProgressPercentage() < 100 ? 'generation_step' : 'generation_finish',
                'report' => $this->getReport($process),
                //'coupons' => $generatedCoupons
            ]);

        } catch (\Exception $e) {
            $response->setStatus(500);
            $response->setMessage($e->getMessage());
            $response->setPayload([
                'progress_html' => $this->renderProgressHtml($process),
                'report' => $this->getReport($process),
            ]);
            $process->setFinishedAt(new DateTime());
        }

        ProcessRepository::save($process);

        return $response;
    }

    /**
     * @param HttpRequest $request
     * @return JsonResponse
     * @throws \Bitrix\Main\DB\DbException
     */
    protected function ajaxGenerationStart(HttpRequest $request)
    {
        $this->checkCompability();
        $response = new JsonResponse();
        $settings = ProcessSettings::createFromRequest($request);

        $settingsValidationResult = $settings->validate();
        if (!$settingsValidationResult->isSuccess()) {
            $response->setStatus(400);
            $message = implode('. ', $settingsValidationResult->getErrorMessages());
            $response->setMessage($message);
            return $response;
        }

        $process = new Process();
        $process->setStartedAt(new DateTime());
        $process->setSettings($settings);

        ProcessRepository::save($process);

        $response->setStatus(200);
        $response->setMessage(Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:INITIALIZED'));
        $response->setPayload([
            'pid' => $process->getId(),
            'progress_html' => $this->renderProgressHtml($process),
            'next_action' => 'generation_step',
            'report' => $this->getReport($process),
        ]);

        return $response;
    }

    /**
     * @param HttpRequest $request
     * @return JsonResponse
     * @throws \Bitrix\Main\DB\DbException
     */
    protected function ajaxGenerationFinish(HttpRequest $request)
    {
        $response = new JsonResponse();
        $process = $this->getProcess($request);

        if (!$process) {
            $response->setStatus(400);
            $response->setMessage(Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:PROCESS_NOT_FOUND'));
            return $response;
        }

        if ($process->getProgressPercentage() < 100) {
            $response->setStatus(400);
            $response->setMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:PROCESS_IN_PROGRESS');
            return $response;
        }

        $process->setFinishedAt(new DateTime());

        $response->setStatus(200);
        $response->setMessage(Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:PROCESS_COMPLETED'));
        $response->setPayload([
            'progress_html' => $this->renderProgressHtml($process),
            'report' => $this->getReport($process),
        ]);

        ProcessRepository::save($process);

        return $response;
    }

    protected function getProcess(HttpRequest $request)
    {
        $processId = $request->get('pid');
        if (!$processId) {
            throw new \LogicException(Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:NO_PID'));
        }

        return ProcessRepository::findOneById($processId);
    }

    protected function ajaxGenerationPreview(HttpRequest $request)
    {
        $template = $request->get('template');

        $response = new JsonResponse();

        if (strlen($template) === 0) {
            $response->setStatus(500);
            $response->setMessage(Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:NO_TEMPLATE'));
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

    protected function renderProgressHtml(Process $process)
    {
        $message = Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:PROGRESS_TEMPLATE');
        if ($process->getProcessedCount() == 0) {
            $message = Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:PROGRESS_INIT');
        } elseif ($process->getProgressPercentage() === 100) {
            $message = Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:PROGRESS_FINISHED');
        }

        $progressMessage = new \CAdminMessage([
            'PROGRESS_TOTAL' => $process->getSettings()->getCount(),
            'PROGRESS_VALUE' => $process->getProcessedCount(),
            'PROGRESS_TEMPLATE' => $message
        ]);

        return $progressMessage->_getProgressHtml();
    }

    protected function getReport(Process $process)
    {
        $startedAt = $process->getStartedAt();
        $startedAt = $startedAt === null ? : $startedAt->format('d.m.Y H:i:s');

        $finishedAt = $process->getFinishedAt();
        $finishedAt = $finishedAt === null ? : $finishedAt->format('d.m.Y H:i:s');
        return [
            [
                'code' => 'started_at',
                'name' => Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:REPORT_DATE_START'),
                'value' => $startedAt,
            ],
            [
                'code' => 'finished_at',
                'name' => Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:REPORT_DATE_FINISH'),
                'value' => $finishedAt,
            ],
            [
                'code' => 'count',
                'name' => Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:REPORT_COUNT'),
                'value' => $process->getProcessedCount(),
            ]
        ];
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

    protected function getCouponGenerator(SequenceGenerator $generator, Process $progress)
    {
        $couponGenerator = new CouponGenerator($generator, $progress);
        return $couponGenerator;
    }

    protected function getLogger()
    {
        $logLevelOption = Option::get('maximaster.coupanda', 'log_level', 'none');
        if ($this->logger === null) {
            switch ($logLevelOption) {
                case 'all':
                    $this->logger = new FileLogger();
                    break;
                case 'errors':
                    $this->logger = new FileLogger(true);
                    break;
                case 'none':
                default:
                    $this->logger = new NullLogger();
                    break;
            }
        }

        return $this->logger;
    }

    protected function getLoggerContext()
    {
        global $USER, $APPLICATION;
        $permission = $APPLICATION->GetGroupRight('maximaster.coupanda');
        $app = Application::getInstance();

        return [
            'user_id' => $USER->GetID(),
            'server' => $app->getContext()->getServer()->toArray(),
            'permission' => $permission,
            'request' => $this->request->toArray(),
        ];
    }

    protected function checkCompability()
    {
        $checker = new CompabilityChecker();
        $result = $checker->check();
        if (!$result->isSuccess()) {
            $message = implode('. ', $result->getErrorMessages());
            throw new \LogicException(Loc::getMessage('MAXIMASTER.COUPANDA:COUPON.GENERATOR:CANT_USE', ['MESSAGE' => $message]));
        }

        return true;
    }
}
