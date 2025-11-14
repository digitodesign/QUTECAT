<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * DatabaseCompatibility Helper
 *
 * Provides MySQL-compatible functions for PostgreSQL.
 * This allows us to use the same query syntax across both database systems.
 */
class DatabaseCompatibility
{
    /**
     * Get the current date in database format.
     *
     * MySQL: CURDATE()
     * PostgreSQL: CURRENT_DATE
     *
     * @return string
     */
    public static function currentDate(): string
    {
        return self::isPostgreSQL() ? 'CURRENT_DATE' : 'CURDATE()';
    }

    /**
     * Get the current time in database format.
     *
     * MySQL: CURTIME()
     * PostgreSQL: CURRENT_TIME
     *
     * @return string
     */
    public static function currentTime(): string
    {
        return self::isPostgreSQL() ? 'CURRENT_TIME' : 'CURTIME()';
    }

    /**
     * Get the current datetime in database format.
     *
     * MySQL: NOW()
     * PostgreSQL: NOW() (same)
     *
     * @return string
     */
    public static function now(): string
    {
        return 'NOW()';
    }

    /**
     * Get date extraction function.
     *
     * MySQL: DATE(column)
     * PostgreSQL: DATE(column) (same)
     *
     * @param string $column
     * @return string
     */
    public static function date(string $column): string
    {
        return "DATE($column)";
    }

    /**
     * Get concat function with separator.
     *
     * MySQL: CONCAT_WS(separator, str1, str2, ...)
     * PostgreSQL: CONCAT_WS(separator, str1, str2, ...) (same since PG 9.1)
     *
     * @param string $separator
     * @param array $columns
     * @return string
     */
    public static function concatWs(string $separator, array $columns): string
    {
        $cols = implode(', ', $columns);
        return "CONCAT_WS('$separator', $cols)";
    }

    /**
     * Get IFNULL/COALESCE function.
     *
     * MySQL: IFNULL(column, default)
     * PostgreSQL: COALESCE(column, default)
     *
     * @param string $column
     * @param mixed $default
     * @return string
     */
    public static function ifNull(string $column, $default): string
    {
        $defaultValue = is_string($default) ? "'$default'" : $default;

        return self::isPostgreSQL()
            ? "COALESCE($column, $defaultValue)"
            : "IFNULL($column, $defaultValue)";
    }

    /**
     * Get GROUP_CONCAT/STRING_AGG function.
     *
     * MySQL: GROUP_CONCAT(column SEPARATOR ',')
     * PostgreSQL: STRING_AGG(column::text, ',')
     *
     * @param string $column
     * @param string $separator
     * @return string
     */
    public static function groupConcat(string $column, string $separator = ','): string
    {
        return self::isPostgreSQL()
            ? "STRING_AGG($column::text, '$separator')"
            : "GROUP_CONCAT($column SEPARATOR '$separator')";
    }

    /**
     * Get FIND_IN_SET equivalent.
     *
     * MySQL: FIND_IN_SET(needle, haystack)
     * PostgreSQL: needle = ANY(STRING_TO_ARRAY(haystack, ','))
     *
     * @param string $needle
     * @param string $haystack
     * @return string
     */
    public static function findInSet(string $needle, string $haystack): string
    {
        return self::isPostgreSQL()
            ? "$needle = ANY(STRING_TO_ARRAY($haystack, ','))"
            : "FIND_IN_SET($needle, $haystack)";
    }

    /**
     * Get LIMIT clause with offset.
     *
     * MySQL: LIMIT offset, count
     * PostgreSQL: LIMIT count OFFSET offset
     *
     * @param int $count
     * @param int $offset
     * @return string
     */
    public static function limit(int $count, int $offset = 0): string
    {
        if (self::isPostgreSQL()) {
            return $offset > 0 ? "LIMIT $count OFFSET $offset" : "LIMIT $count";
        }

        return $offset > 0 ? "LIMIT $offset, $count" : "LIMIT $count";
    }

    /**
     * Get REGEXP operator.
     *
     * MySQL: REGEXP or RLIKE
     * PostgreSQL: ~
     *
     * @return string
     */
    public static function regexpOperator(): string
    {
        return self::isPostgreSQL() ? '~' : 'REGEXP';
    }

    /**
     * Get case-insensitive REGEXP operator.
     *
     * MySQL: REGEXP (case-insensitive by default)
     * PostgreSQL: ~*
     *
     * @return string
     */
    public static function regexpOperatorInsensitive(): string
    {
        return self::isPostgreSQL() ? '~*' : 'REGEXP';
    }

    /**
     * Get JSON extract function.
     *
     * MySQL: JSON_EXTRACT(column, '$.key')
     * PostgreSQL: column->>'key' or column->'key'
     *
     * @param string $column
     * @param string $path
     * @param bool $asText
     * @return string
     */
    public static function jsonExtract(string $column, string $path, bool $asText = true): string
    {
        if (self::isPostgreSQL()) {
            $operator = $asText ? '->>' : '->';
            // Convert $.key to 'key'
            $key = str_replace('$.', '', $path);
            return "$column$operator'$key'";
        }

        return "JSON_EXTRACT($column, '$path')";
    }

    /**
     * Get UNSIGNED modifier for integers.
     *
     * MySQL: UNSIGNED
     * PostgreSQL: (not needed, use CHECK constraint instead)
     *
     * @return string
     */
    public static function unsigned(): string
    {
        return self::isPostgreSQL() ? '' : 'UNSIGNED';
    }

    /**
     * Get AUTO_INCREMENT syntax.
     *
     * MySQL: AUTO_INCREMENT
     * PostgreSQL: SERIAL or IDENTITY (handled by Laravel migrations)
     *
     * @return string
     */
    public static function autoIncrement(): string
    {
        return self::isPostgreSQL() ? '' : 'AUTO_INCREMENT';
    }

    /**
     * Check if using PostgreSQL.
     *
     * @return bool
     */
    public static function isPostgreSQL(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }

    /**
     * Check if using MySQL.
     *
     * @return bool
     */
    public static function isMySQL(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb']);
    }

    /**
     * Get database driver name.
     *
     * @return string
     */
    public static function getDriver(): string
    {
        return DB::connection()->getDriverName();
    }

    /**
     * Execute a raw query with database-specific syntax.
     *
     * @param string $mysqlQuery
     * @param string $postgresQuery
     * @return mixed
     */
    public static function rawQuery(string $mysqlQuery, string $postgresQuery)
    {
        $query = self::isPostgreSQL() ? $postgresQuery : $mysqlQuery;
        return DB::raw($query);
    }

    /**
     * Get UNIX_TIMESTAMP equivalent.
     *
     * MySQL: UNIX_TIMESTAMP(column)
     * PostgreSQL: EXTRACT(EPOCH FROM column)
     *
     * @param string|null $column
     * @return string
     */
    public static function unixTimestamp(?string $column = null): string
    {
        if (self::isPostgreSQL()) {
            return $column
                ? "EXTRACT(EPOCH FROM $column)"
                : "EXTRACT(EPOCH FROM NOW())";
        }

        return $column ? "UNIX_TIMESTAMP($column)" : "UNIX_TIMESTAMP()";
    }

    /**
     * Get YEAR function.
     *
     * MySQL: YEAR(column)
     * PostgreSQL: EXTRACT(YEAR FROM column)
     *
     * @param string $column
     * @return string
     */
    public static function year(string $column): string
    {
        return self::isPostgreSQL()
            ? "EXTRACT(YEAR FROM $column)"
            : "YEAR($column)";
    }

    /**
     * Get MONTH function.
     *
     * MySQL: MONTH(column)
     * PostgreSQL: EXTRACT(MONTH FROM column)
     *
     * @param string $column
     * @return string
     */
    public static function month(string $column): string
    {
        return self::isPostgreSQL()
            ? "EXTRACT(MONTH FROM $column)"
            : "MONTH($column)";
    }

    /**
     * Get DAY function.
     *
     * MySQL: DAY(column)
     * PostgreSQL: EXTRACT(DAY FROM column)
     *
     * @param string $column
     * @return string
     */
    public static function day(string $column): string
    {
        return self::isPostgreSQL()
            ? "EXTRACT(DAY FROM $column)"
            : "DAY($column)";
    }
}
