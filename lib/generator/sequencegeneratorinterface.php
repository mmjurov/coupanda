<?php

namespace Maximaster\Coupanda\Generator;

use Maximaster\Coupanda\Generator\Template\SequenceTemplateInterface;

interface SequenceGeneratorInterface
{
    public function setTemplate(SequenceTemplateInterface $template);
    public function generateOne();
    public function generateUniqueOne();
    public function generateSeveral($count);
    public function generateUniqueSeveral($count);
    public function getGeneratedCount();
}
