<?php

namespace Maximaster\Coupanda\Process;

use Bitrix\Main\Error;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Maximaster\Coupanda\Orm\DiscountCouponTable;

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

    /** @var string */
    protected $description;

    public function __toString()
    {
        return serialize($this);
    }

    protected function __construct()
    {

    }

    public static function createFromArray(array $data)
    {
        $instance = new static();

        $activeFrom = $data['ACTIVE_FROM'] instanceof \DateTime ? $data['ACTIVE_FROM'] : null;
        $activeTo = $data['ACTIVE_TO'] instanceof \DateTime ? $data['ACTIVE_TO'] : null;

        isset($data['TEMPLATE'])        && $instance->template = (string)$data['TEMPLATE'];
        isset($data['COUNT'])           && $instance->count = (int)$data['COUNT'];
        isset($data['DISCOUNT_ID'])     && $instance->discountId = (int)$data['DISCOUNT_ID'];
        isset($data['TYPE'])            && $instance->type = (int)$data['TYPE'];
        isset($data['ACTIVE_FROM'])     && $instance->activeFrom = $activeFrom;
        isset($data['ACTIVE_TO'])       && $instance->activeTo = $activeTo;
        isset($data['MAX_USE_COUNT'])   && $instance->maxUseCount = (int)$data['MAX_USE_COUNT'];
        isset($data['USER_ID'])         && $instance->userId = (int)$data['USER_ID'];
        isset($data['ACTIVE'])          && $instance->active = $data['ACTIVE'] === 'Y';
        isset($data['DESCRIPTION'])     && $instance->description = (string)$data['DESCRIPTION'];

        return $instance;
    }

    public static function createFromRequest(HttpRequest $request)
    {
        $data = [];

        if (isset($request['TEMPLATE']) && strlen($request['TEMPLATE']) > 0) {
            $data['TEMPLATE'] = (string)$request['TEMPLATE'];
        }

        if (isset($request['COUNT']) && is_numeric($request['COUNT']) && $request['COUNT'] > 0) {
            $data['COUNT'] = (int)$request['COUNT'];
        }

        if (isset($request['DISCOUNT_ID']) && is_numeric($request['DISCOUNT_ID']) && $request['DISCOUNT_ID'] > 0) {
            $data['DISCOUNT_ID'] = (int)$request['DISCOUNT_ID'];
        }

        if (isset($request['TYPE']) && is_numeric($request['TYPE']) && $request['TYPE'] > 0) {
            $data['TYPE'] = (int)$request['TYPE'];
        }

        if (isset($request['ACTIVE_FROM']) && strlen($request['ACTIVE_FROM']) > 0) {
            $data['ACTIVE_FROM'] = new \DateTime($request['ACTIVE_FROM']);
        }

        if (isset($request['ACTIVE_TO']) && strlen($request['ACTIVE_TO']) > 0) {
            $data['ACTIVE_TO'] = new \DateTime($request['ACTIVE_TO']);
        }

        if (isset($request['MAX_USE_COUNT']) && is_numeric($request['MAX_USE_COUNT']) && $request['MAX_USE_COUNT'] > 0) {
            $data['MAX_USE_COUNT'] = (int)$request['MAX_USE_COUNT'];
        }

        if (isset($request['USER_ID']) && is_numeric($request['USER_ID']) && $request['USER_ID'] > 0) {
            $data['USER_ID'] = (int)$request['USER_ID'];
        }

        if (isset($request['DESCRIPTION'])) {
            $data['DESCRIPTION'] = (string)$request['DESCRIPTION'];
        }

        $data['ACTIVE'] = isset($request['ACTIVE']) && $request['ACTIVE'] == 'on' ? 'Y' : 'N';

        return static::createFromArray($data);
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

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function validate()
    {
        $result = new Result();
        $template = $this->getTemplate();
        if ($template === null) {
            $result->addError(new Error(Loc::getMessage('MAXIMASTER.COUPANDA:PROCESS.VALIDATE:TEMPLATE_EMPTY')));
        }

        $count = $this->getCount();
        if (!$count || $count <= 0) {
            $result->addError(new Error(Loc::getMessage('MAXIMASTER.COUPANDA:PROCESS.VALIDATE:COUNT')));
        }

        if ($this->getDiscountId() === null) {
            $result->addError(new Error(Loc::getMessage('MAXIMASTER.COUPANDA:PROCESS.VALIDATE:DISCOUNT')));
        }

        $typeAvailable = in_array($this->getType(), [
            DiscountCouponTable::TYPE_BASKET_ROW,
            DiscountCouponTable::TYPE_ONE_ORDER,
            DiscountCouponTable::TYPE_MULTI_ORDER,
        ]);

        if (!$typeAvailable) {
            $result->addError(new Error(Loc::getMessage('MAXIMASTER.COUPANDA:PROCESS.VALIDATE:TYPE')));
        }

        $maxUse = $this->getMaxUseCount();
        if ($this->getType() == DiscountCouponTable::TYPE_MULTI_ORDER && $maxUse <= 0) {
            $result->addError(new Error(Loc::getMessage('MAXIMASTER.COUPANDA:PROCESS.VALIDATE:MAX_USE')));
        }

        return $result;
    }
}
