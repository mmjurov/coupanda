<?php

namespace Maximaster\Coupanda\Orm;

use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class DiscountCouponTable extends \Bitrix\Sale\Internals\DiscountCouponTable
{
    public static function getMap()
    {
        $map = parent::getMap();

        // колонка с идентификатором процесса
        $map[] = new IntegerField(
            'MAXIMASTER_COUPANDA_PID',
            [
                'default_value' => null,
                'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_MAXIMASTER_COUPANDA_PID')
            ]
        );

        // ссылка на референс с таблицей процессов
        $map[] = new ReferenceField(
            'MAXIMASTER_COUPANDA_PROCESS',
            ProcessTable::class,
            ['=this.MAXIMASTER_COUPANDA_PID' => 'ref.ID'],
            ['join_type' => 'LEFT']
        );

        return $map;
    }
}
