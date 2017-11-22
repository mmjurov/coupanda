<?php

namespace Maximaster\Coupanda\Generator\Collections;

interface LettersCollectionInterface extends SymbolsCollectionInterface
{
    public function getLowerCaseOne($number);
    public function getRandomLowerCaseOne();
    public function getUpperCaseOne($number);
    public function getRandomUpperCaseOne();
}