<?php

namespace Maximaster\Coupanda;

/**
 * @var \CAdminTabControl $tabControl
 */
?>

<form name="coupon_generator_form">
    <input type="hidden" name="ajax_action" value="generation_start">
    <input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
    <?=bitrix_sessid_post();?>

    <tr>
        <td>
            <?=getHint('discount_id', 'Все сгенерированные купоны попадут в тот пул, который выбран в данной настройке. Под пулом купонов понимается Правило работы корзины');?>
            <label for="discount_id">Выбрать пул:</label></td>
        <td>
            <select name="DISCOUNT_ID" id="discount_id">
                <?if (!empty($arResult['DISCOUNTS'])):?>
                    <?foreach ($arResult['DISCOUNTS'] as $discount):?>
                        <option value="<?=$discount['ID']?>">[<?=$discount['ID']?>] <?=$discount['NAME']?></option>
                    <?endforeach;?>
                <?else:?>
                    <option>Нет доступных пулов</option>
                <?endif;?>
            </select>
        </td>
    </tr>
    <tr>
        <td></td>
        <td>или  <a href="<?=$arResult['LINKS']['new_discount']?>">создать новый пул</a></td>
    </tr>
    <tr>
        <td>
            <?=getHint('template', 'Шаблон представляет из себя набор символов, в котором некоторые символы являются статическими, а некоторые - динамическими. Подробнее о шаблонах');?>
            <label for="template">Шаблон купона:</label></td>
        <td>
            <input type="text" id="template" name="TEMPLATE">
        </td>
    </tr>
    <tr>
        <td></td>
        <td>
            <?=\BeginNote();?>
            Шаблон купона - это простой набор символов. Часть символов являются зарезервированными, и они могут быть
            использованы в шаблоне для формирования динамически генерируемой части. Перечень зарезервированных символов:<br>
            & - английская буква<br>
            @ - русская буква<br>
            # - число от 0 до 9<br>
            <?=\EndNote();?>
        </td>
    </tr>
    <tr>
        <td>
            <?=getHint('count', 'Сколько купонов нужно сгенерировать по выбранному шаблону');?>
            <label for="count">Количество для генерации:</label></td>
        <td>
            <input type="text" id="count" name="COUNT">
        </td>
    </tr>
    <tr>
        <td>
            <?=getHint('coupon_type', 'Подробнее о типах купонов можно прочитать в <a href="https://dev.1c-bitrix.ru/learning/course/?COURSE_ID=42&LESSON_ID=3453" target="_blank">документации</a>');?>
            <label for="coupon_type">Тип купона:</label></td>
        <td>
            <select name="COUPON_TYPE" id="coupon_type">
                <?foreach ($arResult['COUPON_TYPES'] as $typeCode => $type):?>
                    <option value="<?=$typeCode?>"><?=$type?></option>
                <?endforeach;?>
            </select>
        </td>
    </tr>
    <tr>
        <td>
            <?=getHint('coupon_period', 'Период жизни купона выставляется с помощью двух дат - даты начала и даты окончания. Купон считается неограниченным по времени жизни с той стороны, с которой дата не заполнена');?>
            <label for="coupon_period">Период жизни купона:</label>
        </td>
        <td>
            <?=\CalendarPeriod("ACTIVE_FROM", null, "ACTIVE_TO", null, "coupon_generator_form", "N", "", "", 20);?>
        </td>
    </tr>
    <tr>
        <td>
            <?=getHint('max_use_count', 'Сколько раз покупатели можно воспользоваться купоном. Актуально только для многоразовых купонов');?>
            <label for="max_use_count">Максимальное количество использований:</label></td>
        <td>
            <input type="text" id="max_use_count" name="MAX_USE_COUNT">
        </td>
    </tr>
    <tr>
        <td>
            <?=getHint('active', 'Все купоны из пула могут быть либо активными, либо неактивными');?>
            <label for="active">Активность:</label></td>
        <td>
            <input type="checkbox" id="active" name="ACTIVE" checked>
        </td>
    </tr>
    <tr>
        <td>
            <?=getHint('user', 'Все купоны из пула будут принадлежать одному человеку и только он сможет ими воспользоваться');?>
            <label for="user">Владелец:</label></td>
        <td>
            <?=\FindUserID('USER', null, '', 'coupon_generator_form');?>
        </td>
    </tr>
    <tr>
        <td></td>
        <td>
            <input type="button" id="js-coupon-generation-preview" value="Превью">
            <input type="button" class="adm-btn-save" id="js-start-coupon-generation" value="Начать генерацию">
        </td>
    </tr>

</form>