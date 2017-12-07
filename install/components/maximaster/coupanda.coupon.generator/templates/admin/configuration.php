<?php

namespace Maximaster\Coupanda\Generator\AdminTemplate;

use Bitrix\Main\Localization\Loc;

// TODO добавить описание купона в настройки

/**
 * @var \CAdminTabControl $tabControl
 */
?>

<form name="coupon_generator_form" id="js-generator-settings">
    <input type="hidden" name="ajax_action" value="generation_start">
    <input type="hidden" name="lang" value="<?=LANGUAGE_ID ?>">
    <?= bitrix_sessid_post(); ?>

    <tr>
        <td>
            <?= getHint('discount_id', Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.HINT_DISCOUNT')); ?>
            <label for="discount_id"><?=Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.LABEL_DISCOUNT')?>:</label></td>
        <td>
            <select name="DISCOUNT_ID" id="discount_id">
                <? if (!empty($arResult['DISCOUNTS'])):?>
                    <option>-- <?=Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.SELECT_DISCOUNT')?> --</option>
                    <? foreach ($arResult['DISCOUNTS'] as $discount):?>
                        <?$option = $discount['FORM_OPTION'];?>
                        <option
                            value="<?= $option['VALUE'] ?>"
                            <?= $option['SELECTED'] === true ? 'selected' : ''?>
                        >
                            <?= $option['NAME'] ?>
                        </option>
                    <? endforeach; ?>
                <? else:?>
                    <option><?=Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.NO_DISCOUNTS')?></option>
                <? endif; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td></td>
        <td>
            <?=Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.OR')?>
            <a href="<?= $arResult['LINKS']['new_discount'] ?>"><?=Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.CREATE_NEW_DISCOUNT')?></a>
        </td>
    </tr>
    <tr>
        <td>
            <?= getHint('template', Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.TEMPLATE_HINT')); ?>
            <label for="template"><?=Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.TEMPLATE_LABEL')?>:</label></td>
        <td>
            <input type="text" id="template" name="TEMPLATE">
            <input type="button" id="js-coupon-generation-preview" value="<?=Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.PREVIEW')?>">
        </td>
    </tr>
    <tr>
        <td></td>
        <td>
            <?= \BeginNote(); ?>
            <?=Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.TEMPLATE_NOTE')?>
            <?= \EndNote(); ?>
        </td>
    </tr>
    <tr>
        <td>
            <?= getHint('count', Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.COUNT_HINT')); ?>
            <label for="count"><?=Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.COUNT_LABEL')?>:</label></td>
        <td>
            <input type="text" id="count" name="COUNT">
        </td>
    </tr>
    <tr>
        <td>
            <?= getHint('active', Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.ACTIVE_HINT')); ?>
            <label for="active"><?=Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.ACTIVE_LABEL')?>:</label></td>
        <td>
            <input type="checkbox" id="active" name="ACTIVE" checked>
        </td>
    </tr>
    <tr>
        <td>
            <?= getHint('coupon_period', Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.ACTIVE_DATE_HINT')); ?>
            <label for="coupon_period"><?=Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.ACTIVE_DATE_LABEL')?>:</label>
        </td>
        <td>
            <?
            $calendar = new \CAdminCalendar();
            echo $calendar->CalendarPeriodCustom(
                'ACTIVE_FROM', 'ACTIVE_TO',
                '', '',
                true, 19, true,
                [
                    \CAdminCalendar::PERIOD_EMPTY => Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.ACTIVE_DATE_UNLIMITED'),
                    \CAdminCalendar::PERIOD_INTERVAL => Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.ACTIVE_DATE_INTERVAL')
                ],
                \CAdminCalendar::PERIOD_EMPTY
            );
            ?>
        </td>
    </tr>
    <tr>
        <td>
            <?= getHint('type', Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.TYPE_HINT')); ?>
            <label for="type"><?=Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.TYPE_LABEL')?>:</label></td>
        <td>
            <select name="TYPE" id="type" size="<?=count($arResult['COUPON_TYPES'])?>">
                <? foreach ($arResult['COUPON_TYPES'] as $type):?>
                    <option
                        value="<?= $type['VALUE'] ?>"
                        <?= $type['SELECTED'] === true ? 'selected' : ''?>><?= $type['NAME'] ?></option>
                <? endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td>
            <?= getHint('max_use_count', Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.MAX_USE_HINT')); ?>
            <label for="max_use_count"><?=Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.MAX_USE_LABEL')?>:</label></td>
        <td>
            <input type="text" id="max_use_count" name="MAX_USE_COUNT">
        </td>
    </tr>
    <tr>
        <td>
            <?= getHint('user', Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.USER_HINT')); ?>
            <label for="user"><?=Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.USER_LABEL')?>:</label></td>
        <td>
            <?=\FindUserID('USER_ID', null, '', 'coupon_generator_form'); ?>
        </td>
    </tr>
    <tr>
        <td>
            <?= getHint('description', Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.DESCRIPTION_HINT')); ?>
            <label for="description"><?=Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.DESCRIPTION_LABEL')?>:</label></td>
        <td>
            <textarea name="DESCRIPTION" id="description" cols="30" rows="5"></textarea>
        </td>
    </tr>
    <tr>
        <td></td>
        <td>
            <input type="submit" class="adm-btn-save" id="js-start-coupon-generation" value="<?=Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR_TEMPLATE.START')?>">
        </td>
    </tr>

</form>