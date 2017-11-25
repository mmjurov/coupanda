<?php

namespace Maximaster\Coupanda\Generator\Collections;

class SymbolsCollection implements SymbolsCollectionInterface
{
    protected $symbols = '';
    protected $count = 0;

    public function __construct($symbols = '')
    {
        if (strlen($symbols) > 0) {
            $this->symbols = $symbols;
        }
    }

    public function getCount()
    {
        if ($this->count === 0) {
            $this->count = strlen($this->getSymbols());
        }

        return $this->count;
    }

    public function getSymbols()
    {
        return $this->symbols;
    }

    public function getOne($number)
    {
        if (!is_int($number)) {
            throw new SymbolsCollectionException('Необходимо передать целое число');
        }

        if ($number > $this->getCount() - 1) {
            throw new SymbolsCollectionException("В наборе символов нет символа под номером {$number}");
        }

        return substr($this->getSymbols(), $number, 1);
    }

    public function getRandomOne()
    {
        $randomNumber = rand(0, $this->getCount() - 1);
        $letter =  $this->getOne($randomNumber);
        return $letter;
    }
}
