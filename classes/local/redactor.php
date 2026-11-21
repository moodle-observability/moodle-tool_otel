<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace tool_otel\local;

/**
 * Removes attributes that may carry personal or secret data before a span is exported.
 *
 * @package    tool_otel
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class redactor {
    /** Attribute names (or parts of names) that are never exported. */
    public const BLOCKED = ['email', 'password', 'passwd', 'token', 'secret', 'sesskey', 'cookie', 'username', 'user.name',
        'user.full_name', 'user.id', 'client.address', 'user_agent', 'firstname', 'lastname', 'phone', 'idnumber'];

    /** Query string parameters whose values are replaced. */
    public const FILTERED_PARAMS = ['token', 'wstoken', 'password', 'sesskey', 'key', 'secret', 'code', 'ticket'];

    /**
     * Attributes without the blocked names.
     *
     * @param array $attributes Attributes.
     * @return array
     */
    public static function clean(array $attributes): array {
        foreach (array_keys($attributes) as $name) {
            $lower = strtolower((string) $name);
            foreach (self::BLOCKED as $blocked) {
                if (str_contains($lower, $blocked)) {
                    unset($attributes[$name]);
                    break;
                }
            }
        }
        return $attributes;
    }

    /**
     * Path of a request URI, without the query string.
     *
     * @param string $uri Request URI.
     * @return string
     */
    public static function path(string $uri): string {
        $path = parse_url($uri, PHP_URL_PATH);
        return is_string($path) && $path !== '' ? $path : '/';
    }
}
