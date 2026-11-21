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

namespace tool_otel\hook;

/**
 * Lets plugins remove or mask attributes before a span is exported, so no personal data leaves Moodle.
 *
 * Runs after enrich_span. The built-in redaction (tool_otel\local\redactor) still runs afterwards.
 *
 * @package    tool_otel
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\label('Allows plugins to remove or mask attributes of the spans of tool_otel before export.')]
#[\core\attribute\tags('tracing')]
final class redact_span {
    /** @var string Span name. */
    private string $name;

    /** @var array Attributes. */
    private array $attributes;

    /**
     * Constructor.
     *
     * @param string $name Span name.
     * @param array $attributes Attributes.
     */
    public function __construct(string $name, array $attributes) {
        $this->name = $name;
        $this->attributes = $attributes;
    }

    /**
     * Span name.
     *
     * @return string
     */
    public function get_span_name(): string {
        return $this->name;
    }

    /**
     * Current attributes.
     *
     * @return array
     */
    public function get_attributes(): array {
        return $this->attributes;
    }

    /**
     * Removes an attribute.
     *
     * @param string $name Name.
     * @return void
     */
    public function remove_attribute(string $name): void {
        unset($this->attributes[$name]);
    }

    /**
     * Replaces the value of an existing attribute (for example with "[Filtered]").
     *
     * @param string $name Name.
     * @param string|int|float|bool $value Value.
     * @return void
     */
    public function mask_attribute(string $name, string|int|float|bool $value = '[Filtered]'): void {
        if (array_key_exists($name, $this->attributes)) {
            $this->attributes[$name] = $value;
        }
    }
}
