<?php

namespace Samsara\Fermat\Values\Distribution;

use PHPUnit\Framework\TestCase;
use Samsara\Exceptions\UsageError\IntegrityConstraint;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PoissonTest extends TestCase
{

    public function testConstruct()
    {
        new Poisson(10);

        $this->expectException(IntegrityConstraint::class);

        new Poisson(-10);
    }

    public function testPmf()
    {
        $poisson = new Poisson(10);

        $this->assertEquals('0.0022699964', $poisson->pmf(2)->getValue());
        $this->assertEquals('0.0022699964', $poisson->probabilityOfKEvents(2)->getValue());
        $this->assertEquals('0.0075666548', $poisson->pmf(3)->getValue());
        $this->assertEquals('0.018916637', $poisson->pmf(4)->getValue());
    }

    public function testPmfException()
    {
        $poisson = new Poisson(10);

        $this->expectException(IntegrityConstraint::class);

        $poisson->pmf('2.5');
    }

    public function testCdf()
    {
        $poisson = new Poisson(10);

        $this->assertEquals('0.0027693955', $poisson->cdf(2)->getValue());
        $this->assertEquals('0.0103360504', $poisson->cdf(3)->getValue());
    }

    public function testCdfException()
    {
        $poisson = new Poisson(10);

        $this->expectException(IntegrityConstraint::class);

        $poisson->cdf('2.5');
    }

    public function testRangePmf()
    {
        $poisson = new Poisson(10);

        $this->assertEquals('0.0287532882', $poisson->rangePmf(2, 4)->getValue());
        $this->assertEquals('0.0287532882', $poisson->rangePmf(4, 2)->getValue());
        $this->assertEquals('0', $poisson->rangePmf(2, 2)->getValue());
    }

    public function testRangePmfExceptionOne()
    {
        $poisson = new Poisson(10);

        $this->expectException(IntegrityConstraint::class);

        $poisson->rangePmf('2.5', 4);
    }

    public function testRangePmfExceptionTwo()
    {
        $poisson = new Poisson(10);

        $this->expectException(IntegrityConstraint::class);

        $poisson->rangePmf(2, '4.5');
    }

    public function testRangePmfExceptionThree()
    {
        $poisson = new Poisson(10);

        $this->expectException(IntegrityConstraint::class);

        $poisson->rangePmf('2.5', '4.5');
    }

}