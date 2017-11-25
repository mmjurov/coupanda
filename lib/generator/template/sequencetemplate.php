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

    public function addPlaceholder($placeholder, SymbolsCollectionInterface $collection)
    {
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

    public function getPlaceholderCollection($placeholder)
    {
        return $this->isPlaceholder($placeholder) ?
            $this->placeholders[ $placeholder ] :
            null;
    }

    public function calculateCombinationsCount()
    {
        // TODO: Implement calculateCombinationsCount() method.
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
