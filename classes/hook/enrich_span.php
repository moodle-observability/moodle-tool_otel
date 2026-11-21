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
 * Lets plugins add attributes to a span before it ends (for example a tenant or a campus).
 *
 * Runs on the request path: keep callbacks cheap, without I/O. Personal data is removed afterwards by redact_span
 * and by the built-in redaction.
 *
 * @package    tool_otel
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\label('Allows plugins to add attributes to the spans of tool_otel before they end.')]
#[\core\attribute\tags('tracing')]
final class enrich_span {
    /** @var string Span name. */
    private string $name;

    /** @var array Attributes. */
    private array $attributes;

    /** @var bool Whether it is the root span of the request. */
    private bool $root;

    /**
     * Constructor.
     *
     * @param string $name Span name.
     * @param array $attributes Attributes.
     * @param bool $root Whether it is the root span of the request.
     */
    public function __construct(string $name, array $attributes, bool $root) {
        $this->name = $name;
        $this->attributes = $attributes;
        $this->root = $root;
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
     * Whether it is the root span of the request.
     *
     * @return bool
     */
    public function is_root(): bool {
        return $this->root;
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
     * Adds or replaces an attribute.
     *
     * @param string $name Name (prefer your component as prefix, for example "local_campus.name").
     * @param string|int|float|bool $value Value.
     * @return void
     */
    public function set_attribute(string $name, string|int|float|bool $value): void {
        $this->attributes[$name] = $value;
    }
}
