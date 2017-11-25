<?php

namespace Maximaster\Coupanda\Process;

use Bitrix\Main\Error;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Result;

class ProcessSettings
{
    /** @var string */
    protected $template;

    /** @var int */
    protected $count;

    /** @var int */
    protected $discountId;

    /** @var int */
    protected $type;

    /** @var bool */
    protected $active;

    /** @var \DateTime */
    protected $activeFrom;

    /** @var \DateTime */
    protected $activeTo;

    /** @var int */
    protected $maxUseCount;

    /** @var int */
    protected $userId;

    protected function __construct()
    {

    }

    public static function createFromRequest(HttpRequest $request)
    {
        $instance = new static();
        if (isset($request['TEMPLATE']) && strlen($request['TEMPLATE']) > 0) {
            $instance->template = (string)$request['TEMPLATE'];
        }

        if (isset($request['COUNT']) && is_numeric($request['COUNT']) && $request['COUNT'] > 0) {
            $instance->count = (int)$request['COUNT'];
        }

        if (isset($request['DISCOUNT_ID']) && is_numeric($request['DISCOUNT_ID']) && $request['DISCOUNT_ID'] > 0) {
            $instance->discountId = (int)$request['DISCOUNT_ID'];
        }

        if (isset($request['TYPE']) && is_numeric($request['TYPE']) && $request['TYPE'] > 0) {
            $instance->type = (int)$request['TYPE'];
        }

        if (isset($request['ACTIVE']) && strlen($request['ACTIVE']) > 0) {
            $instance->active = $request['ACTIVE'] === 'Y';
        }

        if (isset($request['ACTIVE_FROM']) && strlen($request['ACTIVE_FROM']) > 0) {
            $instance->activeFrom = new \DateTime($request['ACTIVE_FROM']);
        }

        if (isset($request['ACTIVE_TO']) && strlen($request['ACTIVE_TO']) > 0) {
            $instance->activeTo = new \DateTime($request['ACTIVE_TO']);
        }

        if (isset($request['MAX_USE_COUNT']) && is_numeric($request['MAX_USE_COUNT']) && $request['MAX_USE_COUNT'] > 0) {
            $instance->maxUseCount = (int)$request['MAX_USE_COUNT'];
        }

        if (isset($request['USER_ID']) && is_numeric($request['USER_ID']) && $request['USER_ID'] > 0) {
            $instance->userId = (int)$request['USER_ID'];
        }

        $instance->active = isset($request['ACTIVE']) && $request['ACTIVE'] == 'on';

        return $instance;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @return int
     */
    public function getDiscountId()
    {
        return $this->discountId;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    public function getActive()
    {
        return $this->active;
    }

    /**
     * @return \DateTime
     */
    public function getActiveFrom()
    {
        return $this->activeFrom;
    }

    /**
     * @return \DateTime
     */
    public function getActiveTo()
    {
        return $this->activeTo;
    }

    /**
     * @return int
     */
    public function getMaxUseCount()
    {
        return $this->maxUseCount;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    public function validate()
    {
        $result = new Result();
        if ($this->getTemplate() === null) {
            $result->addError(new Error('Указан пустой шаблон'));
        }

        $count = $this->getCount();
        if (!$count || $count <= 0) {
            $result->addError(new Error('Необходимо установить количество промокодов для генерации'));
        }

        if ($this->getDiscountId() === null) {
            $result->addError(new Error('Не задано правило обработки корзины'));
        }

        return $result;
    }
}
