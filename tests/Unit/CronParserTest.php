<?php

declare(strict_types=1);

namespace CronDSL\Tests\Unit;

use CronDSL\Parser\CronParser;
use PHPUnit\Framework\TestCase;

class CronParserTest extends TestCase
{
    private CronParser $parser;

    protected function setUp(): void
    {
        $this->parser = new CronParser();
    }

    public function testBasicCronExpressions(): void
    {
        $this->assertEquals('*-*-* 02:30:00', $this->parser->parseToOnCalendar('30 2 * * *'));
        $this->assertEquals('*-*-* *:*/5:00', $this->parser->parseToOnCalendar('*/5 * * * *'));
        $this->assertEquals('Mon..Fri *-*-* 02:30:00', $this->parser->parseToOnCalendar('30 2 * * 1-5'));
    }

    public function testSpecialCronExpressions(): void
    {
        $this->assertEquals('*-*-* 00:00:00', $this->parser->parseToOnCalendar('@daily'));
        $this->assertEquals('*-*-* 00:00:00', $this->parser->parseToOnCalendar('@midnight'));
        $this->assertEquals('*-*-* *:00:00', $this->parser->parseToOnCalendar('@hourly'));
        $this->assertEquals('Sun *-*-* 00:00:00', $this->parser->parseToOnCalendar('@weekly'));
        $this->assertEquals('*-*-* 00:00:00', $this->parser->parseToOnCalendar('@monthly'));
        $this->assertEquals('*-*-* 00:00:00', $this->parser->parseToOnCalendar('@yearly'));
    }

    public function testDayOfWeekExpressions(): void
    {
        $this->assertEquals('Mon *-*-* 02:30:00', $this->parser->parseToOnCalendar('30 2 * * 1'));
        $this->assertEquals('Sun *-*-* 02:30:00', $this->parser->parseToOnCalendar('30 2 * * 0'));
        $this->assertEquals('Mon..Fri *-*-* 02:30:00', $this->parser->parseToOnCalendar('30 2 * * 1-5'));
    }

    public function testNamedDaysAndMonths(): void
    {
        $this->assertEquals('Mon *-*-* 02:30:00', $this->parser->parseToOnCalendar('30 2 * * mon'));
        $this->assertEquals('Sun *-*-* 02:30:00', $this->parser->parseToOnCalendar('30 2 * * sun'));
        $this->assertEquals('*-*-* 02:30:00', $this->parser->parseToOnCalendar('30 2 * jan *'));
        $this->assertEquals('*-*-* 02:30:00', $this->parser->parseToOnCalendar('30 2 * dec *'));
    }

    public function testStepExpressions(): void
    {
        $this->assertEquals('*-*-* *:*/5:00', $this->parser->parseToOnCalendar('*/5 * * * *'));
        $this->assertEquals('*-*-* 00:*:00', $this->parser->parseToOnCalendar('* */2 * * *'));
        $this->assertEquals('*-*-* 02:30:00', $this->parser->parseToOnCalendar('30 2 */3 * *'));
    }

    public function testComplexExpressions(): void
    {
        $this->assertEquals('Mon..Fri *-*-* 09:00:00', $this->parser->parseToOnCalendar('0 9 * * 1-5'));
        $this->assertEquals('*-*-* 15:30:00', $this->parser->parseToOnCalendar('30 15 * 1,6 *'));
        $this->assertEquals('*-*-* 12:00:00', $this->parser->parseToOnCalendar('0 12 1,15 * *'));
    }

    public function testInvalidExpressions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->parser->parseToOnCalendar('invalid cron');
    }

    public function testInvalidFieldCount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->parser->parseToOnCalendar('* * * *');
    }

    public function testInvalidFieldValues(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->parser->parseToOnCalendar('60 * * * *'); // Invalid minute
    }
}
