<?php

namespace Maximaster\Coupanda\Generator\Collections;

interface SymbolsCollectionInterface
{
    public function getOne($number);
    public function getRandomOne();
    public function getCount();
    public function getSymbols();
}