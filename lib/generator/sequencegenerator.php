<?php

namespace Maximaster\Coupanda\Generator;

use Bitrix\Main\Localization\Loc;
use Maximaster\Coupanda\Generator\Collections\SymbolsCollectionInterface;
use Maximaster\Coupanda\Generator\Template\SequenceTemplateInterface;

class SequenceGenerator implements SequenceGeneratorInterface
{
    /** @var SequenceTemplateInterface */
    protected $template;
    protected $combinationsCount;
    /** @var SymbolsCollectionInterface[] */
    protected $collections = [];
    protected $generatedCodes = [];

    public function setTemplate(SequenceTemplateInterface $template)
    {
        $this->template = $template;
        $this->combinationsCount = $template->calculateCombinationsCount();
    }

    public function generateUniqueOne()
    {
        $generationsCount = 0;
        $count = $this->getGeneratedCount();
        if ($count > 1000000) {
            throw new SequenceGeneratorException(Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR:GENERATOR_LIMIT_EXCEEDED'));
        }

        if ($count > $this->combinationsCount) {
            throw new SequenceGeneratorException(Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR:MAX_GENERATION_COUNT', ['COUNT' => $this->combinationsCount]));
        }

        do {
            $generatedCode = $this->generateOne();
            $generationsCount++;
            $alreadyExists = isset($this->generatedCodes[$generatedCode]);

            if ($generationsCount > 10) {
                throw new SequenceGeneratorException(Loc::getMessage('MAXIMASTER.COUPANDA:GENERATOR:NOT_ENOUGH_UNIQUE'));
            }

        } while ($alreadyExists);

        $this->generatedCodes[$generatedCode] = $generatedCode;

        return $generatedCode;
    }

    public function generateOne()
    {
        $generatedCode = '';
        foreach ($this->template->getChunks() as $chunk) {

            $symbol = $chunk instanceof SymbolsCollectionInterface ?
                $chunk->getRandomOne() :
                $chunk;

            $generatedCode .= $symbol;
        }

        return $generatedCode;
    }

    public function generateSeveral($count)
    {
        $codes = [];
        while ($count > 0) {
            $codes[] = $this->generateOne();
            $count--;
        }

        return $codes;
    }

    public function generateUniqueSeveral($count)
    {
        try
        {
            $codes = [];
            while ($count > 0) {
                $codes[] = $this->generateUniqueOne();
                $count--;
            }
        } catch (SequenceGeneratorException $e) {
            throw new SequenceGeneratorException(
                Loc::getMessage(
                    'MAXIMASTER.COUPANDA:GENERATOR:CANT_GENERATE_PROVIDED_COUNT',
                    ['MESSAGE' => $e->getMessage()]
                ),
                $e->getCode(), $e
            );
        }

        return $codes;
    }

    public function getAllGeneratedCodes()
    {
        return $this->generatedCodes;
    }

    public function getGeneratedCount()
    {
        return count($this->generatedCodes);
    }
}
