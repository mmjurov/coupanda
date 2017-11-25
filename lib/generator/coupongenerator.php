<?php

namespace Maximaster\Coupanda\Generator;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Maximaster\Coupanda\Orm\DiscountCouponTable;
use Maximaster\Coupanda\Process\ProcessSettings;

class CouponGenerator
{
    protected $settings;
    protected $commonCouponDefinition;

    public function __construct(SequenceGeneratorInterface $generator, ProcessSettings $settings)
    {
        $this->settings = $settings;
        $this->generator = $generator;
        $this->initCommonCouponDefinition();
    }

    protected function initCommonCouponDefinition()
    {
        $activeFrom = $this->settings->getActiveFrom();
        if (!is_null($activeFrom)) {
            $activeFrom = DateTime::createFromPhp($activeFrom);
        }

        $activeTo = $this->settings->getActiveTo();
        if (!is_null($activeTo)) {
            $activeTo = DateTime::createFromPhp($activeTo);
        }

        $coupon = [
            'DISCOUNT_ID' => $this->settings->getDiscountId(),
            'TYPE' => $this->settings->getType(),
            'ACTIVE' => $this->settings->getActive() ? 'Y' : 'N',
        ];

        if ($activeFrom) {
            $coupon['ACTIVE_FROM'] = $activeFrom;
        }

        if ($activeTo) {
            $coupon['ACTIVE_TO'] = $activeTo;
        }

        if ($this->settings->getMaxUseCount()) {
            $coupon['MAX_USE'] = $this->settings->getMaxUseCount();
        }

        if ($this->settings->getUserId()) {
            $coupon['USER_ID'] = $this->settings->getUserId();
        }

        $this->commonCouponDefinition = $coupon;
    }

    protected function getCouponDefinition($code)
    {
        // TODO Ввести привязку к MAXIMASTER_COUPANDA_PID
        $coupon = $this->commonCouponDefinition;
        $coupon['COUPON'] = $code;
        return $coupon;
    }


    public function generate($countToGenerate = 1)
    {
        $result = new Result();
        if ($countToGenerate < 1) {
            $result->addError(new Error('Не указано количество для генерации купонов'));
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
            $message = $lastError->getMessage();
            $message = "Мы попытались сделать 10 попыток добавления разных купонов подряд, но судя по всему, лимит уникальности был исчерпан. Попробуйте сделать шаблон купона более уникальным. Последнее сообщение об ошибке при добавлении купона с кодом \"{$code}\": {$message}";
            $result->addError(new Error('Не удалось сгенерировать заданное количество купонов. ' . $message));
        }

        return $result;

    }

    protected function createDatabaseCoupon($code)
    {
        $couponDefinition = $this->getCouponDefinition($code);

        return DiscountCouponTable::add($couponDefinition);
    }
}
