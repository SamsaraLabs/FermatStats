<?php

namespace Samsara\Fermat\Types;

use Samsara\Fermat\Types\NumberCollection;
use Samsara\Fermat\Values\ImmutableDecimal;

abstract class Distribution
{

    abstract public function random(): ImmutableDecimal;

    /**
     * @param int $sampleSize
     * @return NumberCollection
     */
    public function randomSample(int $sampleSize = 10): NumberCollection
    {
        $sample = new NumberCollection();

        for ($i = 1;$i < $sampleSize;$i++) {
            $sample->push($this->random());
        }

        return $sample;
    }

     abstract public function rangeRandom($min = 0, $max = PHP_INT_MAX, int $maxIterations = 20): ImmutableDecimal;

}