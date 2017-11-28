<?php

namespace Maximaster\Coupanda\Generator;

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
            throw new SequenceGeneratorException('Максимальное возможное количество купонов для генерации в рамках одного хита - 1000000');
        }

        if ($count > $this->combinationsCount) {
            throw new SequenceGeneratorException('Максимальное количество комбинаций символов для выбранного шаблона - ' . $this->combinationsCount);
        }

        do {
            $generatedCode = $this->generateOne();
            $generationsCount++;
            $alreadyExists = isset($this->generatedCodes[$generatedCode]);

            if ($generationsCount > 10) {
                throw new SequenceGeneratorException('Не удалось подобрать уникальную последовательность символов на протяжении десяти попыток. Попробуйте добавить в шаблон больше генерируемых символов');
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
            throw new SequenceGeneratorException('Не удалось сгенерировать указанное количество последовательностей. ' . $e->getMessage(), $e->getCode(), $e);
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
