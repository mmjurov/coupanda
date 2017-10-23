<?php

namespace Maximaster\Coupanda\Orm;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\TextField;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class ProcessTable extends DataManager
{
    public static function getTableName()
    {
        return 'maximaster_coupanda_process';
    }

    public static function getMap()
    {
        return [
            'ID' => new IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('PROCESS_ENTITY_ID_FIELD')
            ]),
            'STARTED_AT' => new DatetimeField('STARTED_AT', [
                'required' => true,
                'title' => Loc::getMessage('PROCESS_ENTITY_STARTED_AT_FIELD')
            ]),
            'FINISHED_AT' => new DatetimeField('FINISHED_AT', [
                'default_value' => null,
                'title' => Loc::getMessage('PROCESS_ENTITY_FINISHED_AT_FIELD')
            ]),
            'SETTINGS' => new TextField('SETTINGS', [
                'required' => true,
                'serialized' => true,
                'title' => Loc::getMessage('PROCESS_ENTITY_SETTINGS_FIELD')
            ]),
            'REPORT' => new TextField('REPORT', [
                'required' => true,
                'serialized' => true,
                'title' => Loc::getMessage('PROCESS_ENTITY_REPORT_FIELD')
            ]),
        ];
    }
}
