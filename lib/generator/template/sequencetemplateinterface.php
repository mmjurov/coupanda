<?php

namespace Maximaster\Coupanda\Generator\Template;

use Maximaster\Coupanda\Generator\Collections\SymbolsCollectionInterface;

interface SequenceTemplateInterface
{
    public function setTemplate($template);
    public function addPlaceholder($placeholder, SymbolsCollectionInterface $collection);
    public function removePlaceholder($placeholder);
    public function getPlaceholders();
    public function getPlaceholderCollection($placeholder);
    public function calculateCombinationsCount();
    public function isPlaceholder($symbol);
    public function getChunks();
}
