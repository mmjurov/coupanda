<?php

namespace Maximaster\Coupanda\Generator\Collections;

class LettersCollection extends SymbolsCollection implements LettersCollectionInterface
{
    public function getLowerCaseOne($number)
    {
        return \ToLower(parent::getOne($number));
    }

    public function getRandomLowerCaseOne()
    {
        return \ToLower(parent::getRandomOne());
    }

    public function getUpperCaseOne($number)
    {
        return \ToUpper(parent::getOne($number));
    }

    public function getRandomUpperCaseOne()
    {
        return \ToUpper(parent::getRandomOne());
    }

    public function getOne($number)
    {
        return rand(0, 1) === 1 ? $this->getLowerCaseOne($number) : $this->getUpperCaseOne($number);
    }

    public function getRandomOne()
    {
        return rand(0, 1) === 1 ? $this->getRandomLowerCaseOne() : $this->getRandomUpperCaseOne();
    }

    public function getSymbols()
    {
        return \ToUpper(parent::getSymbols()) . \ToLower(parent::getSymbols());
    }
}