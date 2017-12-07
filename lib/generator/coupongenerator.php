<?php

namespace Maximaster\Coupanda\Generator;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Maximaster\Coupanda\Orm\DiscountCouponTable;
use Maximaster\Coupanda\Process\Process;

class CouponGenerator
{
    protected $process;
    protected $commonCouponDefinition;

    public function __construct(SequenceGeneratorInterface $generator, Process $settings)
    {
        $this->process = $settings;
        $this->generator = $generator;
        $this->initCommonCouponDefinition();
    }

    protected function initCommonCouponDefinition()
    {
        $settings = $this->process->getSettings();
        $activeFrom = $settings->getActiveFrom();
        if (!is_null($activeFrom)) {
            $activeFrom = DateTime::createFromPhp($activeFrom);
        }

        $activeTo = $settings->getActiveTo();
        if (!is_null($activeTo)) {
            $activeTo = DateTime::createFromPhp($activeTo);
        }

        $coupon = [
            'DISCOUNT_ID' => $settings->getDiscountId(),
            'TYPE' => $settings->getType(),
            'ACTIVE' => $settings->getActive() ? 'Y' : 'N',
        ];

        if ($activeFrom) {
            $coupon['ACTIVE_FROM'] = $activeFrom;
        }

        if ($activeTo) {
            $coupon['ACTIVE_TO'] = $activeTo;
        }

        if ($settings->getMaxUseCount()) {
            $coupon['MAX_USE'] = $settings->getMaxUseCount();
        }

        if ($settings->getUserId()) {
            $coupon['USER_ID'] = $settings->getUserId();
        }

        if ($settings->getDescription()) {
            $coupon['DESCRIPTION'] = $settings->getDescription();
        }

        $coupon['MAXIMASTER_COUPANDA_PID'] = $this->process->getId();
        $this->commonCouponDefinition = $coupon;
    }

    protected function getCouponDefinition($code)
    {
        $coupon = $this->commonCouponDefinition;
        $coupon['COUPON'] = $code;
        return $coupon;
    }


    public function generate($countToGenerate = 1)
    {
        $result = new Result();
        if ($countToGenerate < 1) {
            $result->addError(new Error(Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR:COUNT_NOT_PROVIDED')));
            $result->setData([
                'COUPONS' => []
            ]);
            return $result;
        }

        $errors = [];
        $lastError = null;
        $generatedCodes = [];

        do {
            $code = $this->generator->generateUniqueOne();
            $createResult = $this->createDatabaseCoupon($code);
            if ($createResult->isSuccess()) {
                count($errors) > 0 && $errors = [];
                $countToGenerate--;
                $generatedCodes[] = $code;
            } else {
                /** @var Error $error */
                $lastError = $createResult->getErrorCollection()->current();
                $errors[] = $lastError;
            }
        } while ($countToGenerate > 0 || count($errors) >= 10);

        $result->setData([
            'COUPONS' => $generatedCodes
        ]);

        if (count($errors) > 0 && $lastError !== null) {
            $errorMessage = $lastError->getMessage();
            $result->addError(new Error(Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR:UNIQUE_LIMIT_EXCEEDED', [
                'CODE' => $code,
                'MESSAGE' => $errorMessage
            ])));
        }

        return $result;

    }

    protected function createDatabaseCoupon($code)
    {
        $couponDefinition = $this->getCouponDefinition($code);

        return DiscountCouponTable::add($couponDefinition);
    }
}
