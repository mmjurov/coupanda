<?php

namespace Maximaster\Coupanda\Generator\Template;

use Maximaster\Coupanda\Generator\Collections\SymbolsCollectionInterface;

class SequenceTemplate implements SequenceTemplateInterface
{
    protected $template = '';
    protected $placeholders = [];
    protected $chunks = [];

    public function setTemplate($template)
    {
        $this->template = $template;
    }

    protected function symbolExists($symbol)
    {
        return strpos($this->template, $symbol) !== false;
    }

    public function addPlaceholder($placeholder, SymbolsCollectionInterface $collection)
    {
        if (!$this->symbolExists($placeholder)) {
            return $this;
        }

        $this->resetChunks();
        $this->placeholders[ $placeholder ] = $collection;
        return $this;
    }

    public function removePlaceholder($placeholder)
    {
        if ($this->isPlaceholder($placeholder)) {
            unset($this->placeholders[ $placeholder ]);
            $this->resetChunks();
        }

        return $this;
    }

    public function getPlaceholders()
    {
        return $this->placeholders;
    }

    /**
     * @param $placeholder
     * @return SymbolsCollectionInterface
     */
    public function getPlaceholderCollection($placeholder)
    {
        return $this->isPlaceholder($placeholder) ?
            $this->placeholders[ $placeholder ] :
            null;
    }

    public function calculateCombinationsCount()
    {
        $count = 0;
        $templateWord = $this->template;
        while (strlen($templateWord) > 0) {
            $symbol = substr($templateWord, 0, 1);
            if ($this->isPlaceholder($symbol)) {
                $collection = $this->getPlaceholderCollection($symbol);
                $count = $count === 0 ? $collection->getCount() : $count * $collection->getCount();
            }
            $templateWord = substr($templateWord, 1);
        }

        return $count;
    }

    public function isPlaceholder($symbol)
    {
        return isset($this->placeholders[ $symbol ]);
    }

    private function resetChunks()
    {
        $this->chunks = [];
    }

    public function getChunks()
    {
        if (empty($this->chunks)) {
            for ($i = 0; $i <= strlen($this->template); $i++) {

                $symbol = substr($this->template, $i, 1);
                if ($this->isPlaceholder($symbol)) {
                    $symbol = $this->getPlaceholderCollection($symbol);
                }

                $this->chunks[] = $symbol;
            }
        }

        return $this->chunks;
    }
}
