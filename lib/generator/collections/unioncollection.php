<?php

namespace Maximaster\Coupanda\Generator\Collections;

class UnionCollection extends SymbolsCollection
{
    private $collections = [];

    public function addCollection(SymbolsCollectionInterface $collection)
    {
        // При добавлении новой коллекции сбрасываем все данные, чтобы вызвать пересчет
        $this->count = 0;
        $this->symbols = '';
        $this->collections[] = $collection;
    }

    public function getCount()
    {
        if ($this->count > 0) {
            return $this->count;
        }

        $count = 0;
        foreach ($this->collections as $collection) {
            $count += $collection->getCount();
        }

        $this->count = $count;

        return $this->count;
    }

    public function getSymbols()
    {
        if (strlen($this->symbols) > 0) {
            return $this->symbols;
        }

        $symbols = '';
        foreach ($this->collections as $collection) {
            /** @var SymbolsCollectionInterface $collection */
            $symbols .= $collection->getSymbols();
        }
        $this->symbols = $symbols;

        return $this->symbols;
    }
}