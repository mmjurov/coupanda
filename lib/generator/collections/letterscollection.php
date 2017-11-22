<?php

namespace Maximaster\Coupanda\Generator\Collections;

class LettersCollection extends SymbolsCollection implements LettersCollectionInterface
{
    public function getLowerCaseOne($number)
    {
        return \ToLower($this->getOne($number));
    }

    public function getRandomLowerCaseOne()
    {
        return \ToLower($this->getRandomOne());
    }

    public function getUpperCaseOne($number)
    {
        return \ToUpper($this->getOne($number));
    }

    public function getRandomUpperCaseOne()
    {
        return \ToUpper($this->getRandomOne());
    }

    public function getOne($number)
    {
        return rand(0, 1) == 1 ? $this->getLowerCaseOne($number) : $this->getUpperCaseOne($number);
    }

    public function getRandomOne()
    {
        return rand(0, 1) == 1 ? $this->getRandomLowerCaseOne() : $this->getRandomUpperCaseOne();
    }

    public function getCount()
    {
        return parent::getCount() * 2;
    }

    public function getSymbols()
    {
        return \ToUpper(parent::getSymbols()) . \ToLower(parent::getSymbols());
    }
}