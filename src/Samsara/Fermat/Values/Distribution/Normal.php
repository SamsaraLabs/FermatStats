<?php

namespace Samsara\Fermat\Values\Distribution;

use ReflectionException;
use Samsara\Exceptions\UsageError\IntegrityConstraint;
use Samsara\Exceptions\UsageError\OptionalExit;
use Samsara\Fermat\Numbers;
use Samsara\Fermat\Types\Distribution;
use Samsara\Fermat\Provider\PolyfillProvider;
use Samsara\Fermat\Provider\SequenceProvider;
use Samsara\Fermat\Provider\StatsProvider;
use Samsara\Fermat\Types\Base\Interfaces\Numbers\DecimalInterface;
use Samsara\Fermat\Types\Base\Interfaces\Evaluateables\FunctionInterface;
use Samsara\Fermat\Types\Base\Interfaces\Numbers\NumberInterface;
use Samsara\Fermat\Values\ImmutableDecimal;

class Normal extends Distribution
{

    /**
     * @var ImmutableDecimal
     */
    private $mean;

    /**
     * @var ImmutableDecimal
     */
    private $sd;

    /**
     * Normal constructor.
     *
     * @param int|float|DecimalInterface $mean
     * @param int|float|DecimalInterface $sd
     *
     * @throws IntegrityConstraint
     * @throws ReflectionException
     */
    public function __construct($mean, $sd)
    {
        /** @var ImmutableDecimal $mean */
        $mean = Numbers::makeOrDont(Numbers::IMMUTABLE, $mean);
        /** @var ImmutableDecimal $sd   */
        $sd = Numbers::makeOrDont(Numbers::IMMUTABLE, $sd);

        $this->mean = $mean;
        $this->sd = $sd;
    }

    /**
     * @return ImmutableDecimal
     */
    public function getSD(): ImmutableDecimal
    {
        return $this->sd;
    }

    /**
     * @return ImmutableDecimal
     */
    public function getMean(): ImmutableDecimal
    {
        return $this->mean;
    }

    /**
     * @param $x
     *
     * @return ImmutableDecimal
     * @throws IntegrityConstraint
     * @throws ReflectionException
     */
    public function evaluateAt($x): ImmutableDecimal
    {

        $one = Numbers::makeOne();
        $twoPi = Numbers::make2Pi();
        $e = Numbers::makeE();
        $x = Numbers::makeOrDont(Numbers::IMMUTABLE, $x);

        // $left = 1 / ( sqrt(2pi * SD^2) )
        $left = $one->divide($twoPi->multiply($this->getSD()->pow(2))->sqrt());
        // $right = e^( -1*((x - SD)^2)/(2*SD^2) )
        $right = $e->pow($x->subtract($this->getMean())->pow(2)->divide($this->getSD()->pow(2)->multiply(2))->multiply(-1));

        /** @var ImmutableDecimal $value */
        $value = $left->multiply($right);

        return $value;

    }

    /**
     * @param int|float|DecimalInterface $p
     * @param int|float|DecimalInterface $x
     * @param int|float|DecimalInterface $mean
     *
     * @return Normal
     * @throws IntegrityConstraint
     * @throws OptionalExit
     * @throws ReflectionException
     */
    public static function makeFromMean($p, $x, $mean): Normal
    {
        $one = Numbers::makeOne();
        $p = Numbers::makeOrDont(Numbers::IMMUTABLE, $p);
        $x = Numbers::makeOrDont(Numbers::IMMUTABLE, $x);
        $mean = Numbers::makeOrDont(Numbers::IMMUTABLE, $mean);

        $z = StatsProvider::inverseNormalCDF($one->subtract($p));

        $sd = $x->subtract($mean)->divide($z);

        return new Normal($mean, $sd);
    }

    /**
     * @param int|float|DecimalInterface $p
     * @param int|float|DecimalInterface $x
     * @param int|float|DecimalInterface $sd
     *
     * @return Normal
     * @throws IntegrityConstraint
     * @throws OptionalExit
     * @throws ReflectionException
     */
    public static function makeFromSd($p, $x, $sd): Normal
    {
        $one = Numbers::makeOne();
        $p = Numbers::makeOrDont(Numbers::IMMUTABLE, $p);
        $x = Numbers::makeOrDont(Numbers::IMMUTABLE, $x);
        $sd = Numbers::makeOrDont(Numbers::IMMUTABLE, $sd);

        $z = StatsProvider::inverseNormalCDF($one->subtract($p));

        $mean = $x->subtract($z->multiply($sd));

        return new Normal($mean, $sd);
    }

    /**
     * @param int|float|DecimalInterface $x
     *
     * @return ImmutableDecimal
     * @throws IntegrityConstraint
     * @throws OptionalExit
     * @throws ReflectionException
     */
    public function cdf($x): ImmutableDecimal
    {
        $x = Numbers::makeOrDont(Numbers::IMMUTABLE, $x);

        $oneHalf = Numbers::make(Numbers::IMMUTABLE, '0.5');
        $one = Numbers::makeOne();
        $sqrtTwo = Numbers::make(Numbers::IMMUTABLE, 2)->sqrt();

        /** @var ImmutableDecimal $cdf */
        $cdf = $oneHalf->multiply($one->add(StatsProvider::gaussErrorFunction(
            $x->subtract($this->mean)->divide($this->sd->multiply($sqrtTwo))
        )));

        return $cdf;
    }

    /**
     * @param int|float|DecimalInterface $x1
     * @param null|int|float|DecimalInterface $x2
     *
     * @return ImmutableDecimal
     * @throws IntegrityConstraint
     * @throws ReflectionException
     */
    public function pdf($x1, $x2 = null): ImmutableDecimal
    {

        $x1 = Numbers::makeOrDont(Numbers::IMMUTABLE, $x1);

        if (is_null($x2)) {
            $separation = $x1->subtract($this->mean)->multiply(2)->abs();

            if ($this->mean->isLessThan($x1)) {
                $x2 = $x1->subtract($separation);
            } else {
                $x2 = $x1->add($separation);
            }
        }

        /** @var ImmutableDecimal $pdf */
        $pdf = $this->cdf($x1)->subtract($this->cdf($x2))->abs();

        return $pdf;
    }

    /**
     * @param FunctionInterface $function
     * @param $x
     * @return ImmutableDecimal
     * @throws IntegrityConstraint
     * @throws ReflectionException
     */
    public function cdfProduct(FunctionInterface $function, $x): ImmutableDecimal
    {

        $loop = 0;

        $cdf = Numbers::makeZero();

        while (true) {
            if (count($function->describeShape()) === 0) {
                break;
            }

            $cdf = $cdf->add($function->evaluateAt($x)->multiply(SequenceProvider::nthPowerNegativeOne($loop)));

            $function = $function->derivativeExpression();
        }

        /** @var ImmutableDecimal $cdf */
        $cdf = $cdf->multiply($this->cdf($x));

        return $cdf;

    }

    /**
     * @param FunctionInterface $function
     * @param $x1
     * @param $x2
     *
     * @return ImmutableDecimal
     * @throws IntegrityConstraint
     * @throws ReflectionException
     */
    public function pdfProduct(FunctionInterface $function, $x1, $x2): ImmutableDecimal
    {
        /** @var ImmutableDecimal $pdf */
        $pdf = $this->cdfProduct($function, $x2)->subtract($this->cdfProduct($function, $x1));

        return $pdf;
    }

    /**
     * @param int|float|DecimalInterface $x
     *
     * @return ImmutableDecimal
     * @throws IntegrityConstraint
     */
    public function percentBelowX($x): ImmutableDecimal
    {
        return $this->cdf($x);
    }

    /**
     * @param int|float|DecimalInterface $x
     *
     * @return ImmutableDecimal
     * @throws IntegrityConstraint
     * @throws ReflectionException
     */
    public function percentAboveX($x): ImmutableDecimal
    {
        $one = Numbers::makeOne();

        /** @var ImmutableDecimal $perc */
        $perc = $one->subtract($this->cdf($x));

        return $perc;
    }

    /**
     * @param int|float|DecimalInterface $x
     *
     * @return ImmutableDecimal
     * @throws IntegrityConstraint
     * @throws ReflectionException
     */
    public function zScoreOfX($x): ImmutableDecimal
    {
        /** @var ImmutableDecimal $x */
        $x = Numbers::makeOrDont(Numbers::IMMUTABLE, $x);

        /** @var ImmutableDecimal $z */
        $z = $x->subtract($this->mean)->divide($this->sd);

        return $z;
    }

    /**
     * @param int|float|DecimalInterface $z
     *
     * @return ImmutableDecimal
     * @throws IntegrityConstraint
     * @throws ReflectionException
     */
    public function xFromZScore($z): ImmutableDecimal
    {
        $z = Numbers::makeOrDont(Numbers::IMMUTABLE, $z);

        /** @var ImmutableDecimal $x */
        $x = $z->multiply($this->sd)->add($this->mean);

        return $x;
    }

    /**
     * @return ImmutableDecimal
     * @throws IntegrityConstraint
     * @throws ReflectionException
     */
    public function random(): ImmutableDecimal
    {
        $int1 = PolyfillProvider::randomInt(0, PHP_INT_MAX);
        $int2 = PolyfillProvider::randomInt(0, PHP_INT_MAX);

        $rand1 = Numbers::make(Numbers::IMMUTABLE, $int1, 20);
        $rand1 = $rand1->divide(PHP_INT_MAX);
        $rand2 = Numbers::make(Numbers::IMMUTABLE, $int2, 20);
        $rand2 = $rand2->divide(PHP_INT_MAX);

        $randomNumber = $rand1->ln()->multiply(-2)->sqrt()->multiply($rand2->multiply(Numbers::TAU)->cos(1, false));
        /** @var ImmutableDecimal $randomNumber */
        $randomNumber = $randomNumber->multiply($this->sd)->add($this->mean);

        return $randomNumber;
    }

    /**
     * @param int|float|NumberInterface $min
     * @param int|float|NumberInterface $max
     * @param int $maxIterations
     *
     * @return ImmutableDecimal
     * @throws OptionalExit
     * @throws IntegrityConstraint
     * @throws ReflectionException
     */
    public function rangeRandom($min = 0, $max = PHP_INT_MAX, int $maxIterations = 20): ImmutableDecimal
    {
        $i = 0;

        do {
            $randomNumber = $this->random();
            $i++;
        } while (($randomNumber->isGreaterThan($max) || $randomNumber->isLessThan($min)) && $i < $maxIterations);

        if ($randomNumber->isGreaterThan($max) || $randomNumber->isLessThan($min)) {
            throw new OptionalExit(
                'All random numbers generated were outside of the requested range',
                'A suitable random number, restricted by the $max ('.$max.') and $min ('.$min.'), could not be found within '.$maxIterations.' iterations'
            );
        }

        return $randomNumber;
    }

}