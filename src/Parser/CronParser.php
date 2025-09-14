<?php

declare(strict_types=1);

namespace CronDSL\Parser;

/**
 * Parses cron expressions and converts them to systemd OnCalendar format.
 */
class CronParser
{
    private const CRON_SPECIALS = [
        '@yearly' => '0 0 1 1 *',
        '@annually' => '0 0 1 1 *',
        '@monthly' => '0 0 1 * *',
        '@weekly' => '0 0 * * 0',
        '@daily' => '0 0 * * *',
        '@midnight' => '0 0 * * *',
        '@hourly' => '0 * * * *',
    ];

    private const DAYS_OF_WEEK = [
        'sun' => 0, 'sunday' => 0,
        'mon' => 1, 'monday' => 1,
        'tue' => 2, 'tuesday' => 2,
        'wed' => 3, 'wednesday' => 3,
        'thu' => 4, 'thursday' => 4,
        'fri' => 5, 'friday' => 5,
        'sat' => 6, 'saturday' => 6,
    ];

    private const MONTHS = [
        'jan' => 1, 'january' => 1,
        'feb' => 2, 'february' => 2,
        'mar' => 3, 'march' => 3,
        'apr' => 4, 'april' => 4,
        'may' => 5,
        'jun' => 6, 'june' => 6,
        'jul' => 7, 'july' => 7,
        'aug' => 8, 'august' => 8,
        'sep' => 9, 'september' => 9,
        'oct' => 10, 'october' => 10,
        'nov' => 11, 'november' => 11,
        'dec' => 12, 'december' => 12,
    ];

    public function parseToOnCalendar(string $cronExpression, ?string $timezone = null): string
    {
        // Handle special cron expressions
        if (isset(self::CRON_SPECIALS[$cronExpression])) {
            $cronExpression = self::CRON_SPECIALS[$cronExpression];
        }

        $fields = $this->parseCronFields($cronExpression);

        return $this->buildOnCalendar($fields, $timezone);
    }

    private function parseCronFields(string $cronExpression): array
    {
        $fields = preg_split('/\s+/', trim($cronExpression));

        if ($fields === false || count($fields) !== 5) {
            throw new \InvalidArgumentException("Invalid cron expression: {$cronExpression}");
        }

        return [
            'minute' => $this->parseField($fields[0], 0, 59),
            'hour' => $this->parseField($fields[1], 0, 23),
            'day' => $this->parseField($fields[2], 1, 31),
            'month' => $this->parseField($fields[3], 1, 12),
            'dow' => $this->parseField($fields[4], 0, 6),
        ];
    }

    private function parseField(string $field, int $min, int $max): array
    {
        if ($field === '*') {
            return ['*'];
        }

        $result = [];
        $parts = explode(',', $field);

        foreach ($parts as $part) {
            if (str_contains($part, '/')) {
                [$range, $step] = explode('/', $part, 2);
                $range = $range === '*' ? "{$min}-{$max}" : $range;
                $result[] = $this->expandRange($range, $min, $max, (int) $step);
            } elseif (str_contains($part, '-')) {
                $result[] = $this->expandRange($part, $min, $max);
            } else {
                $result[] = [$this->normalizeValue($part, $min, $max)];
            }
        }

        return array_merge(...$result);
    }

    private function expandRange(string $range, int $min, int $max, ?int $step = null): array
    {
        [$start, $end] = explode('-', $range, 2);
        $start = $this->normalizeValue($start, $min, $max);
        $end = $this->normalizeValue($end, $min, $max);
        $step = $step ?? 1;

        $result = [];
        for ($i = $start; $i <= $end; $i += $step) {
            $result[] = $i;
        }

        return $result;
    }

    private function normalizeValue(string $value, int $min, int $max): int
    {
        $value = strtolower(trim($value));

        // Handle month names
        if (isset(self::MONTHS[$value])) {
            return self::MONTHS[$value];
        }

        // Handle day of week names
        if (isset(self::DAYS_OF_WEEK[$value])) {
            return self::DAYS_OF_WEEK[$value];
        }

        $intValue = (int) $value;
        if ($intValue < $min || $intValue > $max) {
            throw new \InvalidArgumentException("Value {$value} is out of range [{$min}, {$max}]");
        }

        return $intValue;
    }

    private function buildOnCalendar(array $fields, ?string $timezone): string
    {
        $minute = $this->formatField($fields['minute'], 'minute');
        $hour = $this->formatField($fields['hour'], 'hour');
        $day = $this->formatField($fields['day'], 'day');
        $month = $this->formatField($fields['month'], 'month');
        $dow = $this->formatField($fields['dow'], 'dow');

        // Build the OnCalendar expression
        $calendar = '';

        // Handle day of week constraints
        if ($dow !== '*') {
            $calendar .= $this->formatDayOfWeek($dow) . ' ';
        }

        // Add date part
        $calendar .= '*-*-*';

        // Add time part
        if ($hour !== '*' && $minute !== '*') {
            $calendar .= ' ' . sprintf('%02d:%02d:00', $hour, $minute);
        } elseif ($hour !== '*') {
            $calendar .= ' ' . sprintf('%02d:*:00', $hour);
        } elseif ($minute !== '*') {
            $calendar .= ' *:' . sprintf('%02d:00', $minute);
        } else {
            $calendar .= ' *:*:00';
        }

        // Handle step values for minutes
        if (count($fields['minute']) > 1 && $this->isStepPattern($fields['minute'])) {
            $step = $this->getStepValue($fields['minute']);
            $calendar = str_replace('*:' . sprintf('%02d:00', $fields['minute'][0]), '*:*/' . $step . ':00', $calendar);
        }

        return $calendar;
    }

    private function formatField(array $values, string $type): string
    {
        if (count($values) === 1 && $values[0] === '*') {
            return '*';
        }

        if (count($values) === 1) {
            return (string) $values[0];
        }

        return implode(',', $values);
    }

    private function formatDayOfWeek(string $dow): string
    {
        $days = explode(',', $dow);
        $dayNames = [];

        foreach ($days as $day) {
            $dayNames[] = $this->getDayName((int) $day);
        }

        if (count($dayNames) === 1) {
            return $dayNames[0];
        }

        // Check if it's a consecutive range
        if ($this->isConsecutiveRange($days)) {
            return $dayNames[0] . '..' . $dayNames[count($dayNames) - 1];
        }

        return implode('..', $dayNames);
    }

    private function isConsecutiveRange(array $days): bool
    {
        if (count($days) < 2) {
            return false;
        }

        sort($days);
        for ($i = 1; $i < count($days); $i++) {
            if ($days[$i] - $days[$i - 1] !== 1) {
                return false;
            }
        }

        return true;
    }

    private function getDayName(int $day): string
    {
        $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        return $dayNames[$day];
    }

    private function isStepPattern(array $values): bool
    {
        if (count($values) < 2) {
            return false;
        }

        $step = $values[1] - $values[0];
        for ($i = 1; $i < count($values); $i++) {
            if ($values[$i] - $values[$i - 1] !== $step) {
                return false;
            }
        }

        return true;
    }

    private function getStepValue(array $values): int
    {
        return $values[1] - $values[0];
    }
}
