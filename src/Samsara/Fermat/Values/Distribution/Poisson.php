<?php

namespace Samsara\Fermat\Values\Distribution;

use Samsara\Exceptions\SystemError\LogicalError\IncompatibleObjectState;
use Samsara\Exceptions\UsageError\IntegrityConstraint;
use Samsara\Exceptions\UsageError\OptionalExit;
use Samsara\Fermat\Numbers;
use Samsara\Fermat\Types\Distribution;
use Samsara\Fermat\Provider\PolyfillProvider;
use Samsara\Fermat\Types\Base\Interfaces\Numbers\DecimalInterface;
use Samsara\Fermat\Types\Base\Interfaces\Numbers\NumberInterface;
use Samsara\Fermat\Values\ImmutableDecimal;

class Poisson extends Distribution
{

    /**
     * @var ImmutableDecimal
     */
    private $lambda;

    /**
     * Poisson constructor.
     *
     * @param int|float|DecimalInterface $lambda
     *
     * @throws IntegrityConstraint
     */
    public function __construct($lambda)
    {
        $lambda = Numbers::makeOrDont(Numbers::IMMUTABLE, $lambda);

        if (!$lambda->isPositive()) {
            throw new IntegrityConstraint(
                'Lambda must be positive',
                'Provide a positive lambda',
                'Poisson distributions work on time to occurrence; the mean time to occurrence (lambda) must be positive'
            );
        }

        $this->lambda = $lambda;
    }

    /**
     * @param int|float|DecimalInterface $k
     *
     * @return ImmutableDecimal
     * @throws IntegrityConstraint
     * @throws IncompatibleObjectState
     */
    public function probabilityOfKEvents($k, int $scale = 10): ImmutableDecimal
    {

        return $this->pmf($k, $scale);
        
    }

    /**
     * @param int|float|DecimalInterface $x
     *
     * @return ImmutableDecimal
     * @throws IntegrityConstraint
     * @throws IncompatibleObjectState
     */
    public function cdf($x, int $scale = 10): ImmutableDecimal
    {

        $x = Numbers::makeOrDont(Numbers::IMMUTABLE, $x);

        if (!$x->isNatural()) {
            throw new IntegrityConstraint(
                'Only integers are valid x values for Poisson distributions',
                'Provide an integer value to calculate the CDF',
                'Poisson distributions describe discrete occurrences; only integers are valid x values'
            );
        }

        $internalScale = $scale + 2;

        $cumulative = Numbers::makeZero();

        for ($i = 0;$x->isGreaterThanOrEqualTo($i);$i++) {
            $cumulative = $cumulative->add($this->pmf($i, $internalScale))->truncateToScale($scale);
        }

        return $cumulative;

    }

    /**
     * @param float|int|DecimalInterface $x
     *
     * @return ImmutableDecimal
     * @throws IntegrityConstraint
     * @throws IncompatibleObjectState
     */
    public function pmf(float|int|DecimalInterface $x, int $scale = 10): ImmutableDecimal
    {
        $x = Numbers::makeOrDont(Numbers::IMMUTABLE, $x);

        if (!$x->isNatural()) {
            throw new IntegrityConstraint(
                'Only integers are valid x values for Poisson distributions',
                'Provide an integer value to calculate the PMF',
                'Poisson distributions describe discrete occurrences; only integers are valid x values'
            );
        }

        $internalScale = $scale + 2;

        $e = Numbers::makeE($internalScale);

        /** @var ImmutableDecimal $pmf */
        $pmf = $this->lambda->pow($x)->multiply($e->pow($this->lambda->multiply(-1)))->divide($x->factorial(), $internalScale)->truncateToScale($scale);

        return $pmf;
    }

    /**
     * @param int|float|DecimalInterface $x1
     * @param int|float|DecimalInterface $x2
     *
     * @return ImmutableDecimal
     * @throws IntegrityConstraint
     */
    public function rangePmf($x1, $x2): ImmutableDecimal
    {
        $x1 = Numbers::makeOrDont(Numbers::IMMUTABLE, $x1);
        $x2 = Numbers::makeOrDont(Numbers::IMMUTABLE, $x2);

        if ($x1->equals($x2)) {
            return Numbers::makeZero();
        } elseif ($x1->isGreaterThan($x2)) {
            $larger = $x1;
            $smaller = $x2;
        } else {
            $larger = $x2;
            $smaller = $x1;
        }

        if (!$larger->isNatural() || !$smaller->isNatural()) {
            throw new IntegrityConstraint(
                'Only integers are valid x values for Poisson distributions',
                'Provide integer values to calculate the range PMF',
                'Poisson distributions describe discrete occurrences; only integers are valid x values'
            );
        }

        $cumulative = Numbers::makeZero();

        for (;$larger->isGreaterThanOrEqualTo($smaller);$smaller = $smaller->add(1)) {
            $cumulative = $cumulative->add($this->pmf($smaller));
        }

        return $cumulative;
    }

    /**
     * @return ImmutableDecimal
     * @throws IntegrityConstraint
     * @throws IncompatibleObjectState
     *
     * @codeCoverageIgnore
     */
    public function random(): ImmutableDecimal
    {
        if ($this->lambda->isLessThanOrEqualTo(30)) {
            return $this->knuthRandom();
        } else {
            return $this->methodPARandom();
        }
    }

    /**
     * WARNING: This function is of very limited use with Poisson distributions, and may represent a SIGNIFICANT
     * performance hit for certain values of $min, $max, $lambda, and $maxIterations
     *
     * @param int|float|NumberInterface $min
     * @param int|float|NumberInterface $max
     * @param int $maxIterations
     *
     * @return ImmutableDecimal
     * @throws OptionalExit
     * @throws IntegrityConstraint
     * @throws IncompatibleObjectState
     *
     * @codeCoverageIgnore
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
        } else {
            return $randomNumber;
        }
    }

    /**
     * Method PA from The Computer Generation of Poisson Random Variables by A. C. Atkinson, 1979
     * Journal of the Royal Statistical Society Series C, Vol. 28, No. 1, Pages 29-35
     *
     * As described by John D. Cook: http://www.johndcook.com/blog/2010/06/14/generating-poisson-random-values/
     *
     * @return ImmutableDecimal
     * @throws IntegrityConstraint
     * @throws IncompatibleObjectState
     *
     * @codeCoverageIgnore
     */
    protected function methodPARandom(): ImmutableDecimal
    {
        /** @var ImmutableDecimal $c */
        $c = $this->lambda->pow(-1)->multiply(3.36)->multiply(-1)->add(0.767);
        /** @var ImmutableDecimal $beta */
        $beta = Numbers::makePi()->divide($this->lambda->multiply(3)->sqrt());
        /** @var ImmutableDecimal $alpha */
        $alpha = $this->lambda->multiply($beta);
        /** @var ImmutableDecimal $k */
        $k = $c->ln(20)->subtract($this->lambda)->subtract($beta->ln(20));
        /** @var ImmutableDecimal $one */
        $one = Numbers::makeOne();
        /** @var ImmutableDecimal $oneHalf */
        $oneHalf = Numbers::make(Numbers::IMMUTABLE, '0.5');
        /** @var ImmutableDecimal $e */
        $e = Numbers::makeE();

        while (true) {
            /** @var ImmutableDecimal $u */
            $u = PolyfillProvider::randomInt(0, PHP_INT_MAX) / PHP_INT_MAX;
            /** @var ImmutableDecimal $x */
            $x = $alpha->subtract($one->subtract($u)->divide($u)->ln(20)->divide($beta));
            /** @var ImmutableDecimal $n */
            $n = $x->add($oneHalf)->floor();

            if ($n->isNegative()) {
                continue;
            }

            /** @var ImmutableDecimal $v */
            $v = Numbers::make(Numbers::IMMUTABLE, PolyfillProvider::randomInt(0, PHP_INT_MAX))->divide(PHP_INT_MAX);
            /** @var ImmutableDecimal $y */
            $y = $alpha->subtract($beta->multiply($x));
            /** @var ImmutableDecimal $lhs */
            $lhs = $y->add($v->divide($e->pow($y)->add($one)->pow(2)));
            /** @var ImmutableDecimal $rhs */
            $rhs = $k->add($n->multiply($this->lambda->ln(20)))->subtract($n->factorial()->ln(20));

            if ($lhs->isLessThanOrEqualTo($rhs)) {
                return $n;
            }

            /*
             * At least attempt to free up some memory, since this particular method is extra hard on object instantiation
             */
            unset($u);
            unset($x);
            unset($n);
            unset($v);
            unset($y);
            unset($lhs);
            unset($rhs);
        }
    }

    /**
     * @return ImmutableDecimal
     * @throws IntegrityConstraint
     *
     * @codeCoverageIgnore
     */
    protected function knuthRandom(): ImmutableDecimal
    {
        /** @var ImmutableDecimal $L */
        $L = Numbers::makeE()->pow($this->lambda->multiply(-1));
        /** @var ImmutableDecimal $k */
        $k = Numbers::makeZero();
        /** @var ImmutableDecimal $p */
        $p = Numbers::makeOne();

        do {
            $k = $k->add(1);
            /** @var ImmutableDecimal $u */
            $u = PolyfillProvider::randomInt(0, PHP_INT_MAX) / PHP_INT_MAX;
            $p = $p->multiply($u);
        } while ($p->isGreaterThan($L));

        return $k->subtract(1);
    }

}