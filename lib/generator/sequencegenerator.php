<?php

namespace Maximaster\Coupanda\Generator;

use Maximaster\Coupanda\Generator\Collections\SymbolsCollectionInterface;
use Maximaster\Coupanda\Generator\Template\SequenceTemplateInterface;

class SequenceGenerator implements SequenceGeneratorInterface
{
    /** @var SequenceTemplateInterface */
    protected $template;
    /** @var SymbolsCollectionInterface[] */
    protected $collections = [];
    protected $generatedCodes = [];

    public function setTemplate(SequenceTemplateInterface $template)
    {
        $this->template = $template;
    }

    public function generateUniqueOne()
    {
        $generationsCount = 0;

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
            throw new SequenceGeneratorException('Не удалось сгенерировать достаточное количество последовательностей. Шаблон содержит недостаточное количество возможных вариаций для генерации. Попробуйте увеличить количество генерируемых символов');
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
