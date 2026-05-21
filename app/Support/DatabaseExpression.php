<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class DatabaseExpression
{
    public static function driver(): string
    {
        return DB::connection()->getDriverName();
    }

    public static function isPostgres(): bool
    {
        return self::driver() === 'pgsql';
    }

    public static function datePart(string $part, string $column): string
    {
        $part = strtoupper($part);

        return self::isPostgres()
            ? "EXTRACT({$part} FROM {$column})"
            : "{$part}({$column})";
    }

    public static function yearMonth(string $column): string
    {
        return self::isPostgres()
            ? "TO_CHAR({$column}, 'YYYY-MM')"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    public static function dateDiffDays(string $endColumn, string $startColumn): string
    {
        return self::isPostgres()
            ? "FLOOR(EXTRACT(EPOCH FROM ({$endColumn} - {$startColumn})) / 86400)"
            : "DATEDIFF({$endColumn}, {$startColumn})";
    }

    public static function ageYears(string $dateColumn): string
    {
        return self::isPostgres()
            ? "EXTRACT(YEAR FROM AGE(CURRENT_DATE, {$dateColumn}))"
            : "TIMESTAMPDIFF(YEAR, {$dateColumn}, CURDATE())";
    }

    public static function timestampDiffSeconds(string $startColumn, string $endColumn): string
    {
        return self::isPostgres()
            ? "EXTRACT(EPOCH FROM ({$endColumn} - {$startColumn}))"
            : "TIMESTAMPDIFF(SECOND, {$startColumn}, {$endColumn})";
    }

    public static function numericCast(string $expression): string
    {
        return self::isPostgres()
            ? "CAST({$expression} AS INTEGER)"
            : "CAST({$expression} AS UNSIGNED)";
    }

    public static function regexWhere(string $column, string $pattern): string
    {
        $quotedPattern = str_replace("'", "''", $pattern);

        return self::isPostgres()
            ? "{$column} ~ '{$quotedPattern}'"
            : "{$column} REGEXP '{$quotedPattern}'";
    }

    public static function orderedValues(string $column, array $values): string
    {
        $cases = [];

        foreach (array_values($values) as $index => $value) {
            $cases[] = 'WHEN ' . $column . ' = ' . self::stringLiteral((string) $value) . ' THEN ' . $index;
        }

        return 'CASE ' . implode(' ', $cases) . ' ELSE ' . count($values) . ' END';
    }

    public static function coalesceAsInteger(array $columns): string
    {
        $expressions = array_map(function (string $column) {
            $textExpression = self::isPostgres()
                ? "CAST({$column} AS TEXT)"
                : "CAST({$column} AS CHAR)";

            return self::numericCast("NULLIF({$textExpression}, '')");
        }, $columns);

        return 'COALESCE(' . implode(', ', $expressions) . ')';
    }

    public static function stringLiteral(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }
}
