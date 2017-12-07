<?php

namespace Maximaster\Coupanda\Generator\Collections;

use Bitrix\Main\Localization\Loc;

class RussianLettersCollection extends LettersCollection
{
    public function __construct()
    {
        parent::__construct(Loc::getMessage('MAXIMASTER.COUPANDA:COLLECTION:CYRILLIC_UPPERCASE'));
    }
}
