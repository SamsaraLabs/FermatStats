<?php

namespace Samsara\Fermat\Values\Distribution;

use PHPUnit\Framework\TestCase;
use Samsara\Exceptions\UsageError\IntegrityConstraint;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ExponentialTest extends TestCase
{

    public function testConstruct()
    {
        new Exponential(10);

        $this->expectException(IntegrityConstraint::class);

        new Exponential(-10);
    }

    public function testCdf()
    {
        $exp = new Exponential(10);

        $this->assertEquals('0.3934693402', $exp->cdf('0.05')->getValue());
    }

    public function testCdfException()
    {
        $exp = new Exponential(10);

        $this->expectException(IntegrityConstraint::class);

        $exp->cdf('-1');
    }

    public function testPdf()
    {
        $exp = new Exponential(10);

        $this->assertEquals('6.0653065971', $exp->pdf('0.05')->getValue());
    }

    public function testPdfException()
    {
        $exp = new Exponential(10);

        $this->expectException(IntegrityConstraint::class);

        $exp->pdf('-1');
    }

    public function testRangePdf()
    {
        $exp = new Exponential(10);
        
        $this->assertEquals('2.3865121854', $exp->rangePdf('0.05', '0.1')->getValue());
    }

    public function testRangePdfExceptionOne()
    {
        $exp = new Exponential(10);

        $this->expectException(IntegrityConstraint::class);

        $exp->rangePdf('-0.05', '0.1');
    }

    public function testRangePdfExceptionTwo()
    {
        $exp = new Exponential(10);

        $this->expectException(IntegrityConstraint::class);

        $exp->rangePdf('0.05', '-0.1');
    }

    public function testRangePdfExceptionThree()
    {
        $exp = new Exponential(10);

        $this->expectException(IntegrityConstraint::class);

        $exp->rangePdf('-0.05', '-0.1');
    }

}