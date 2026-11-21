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

namespace tool_otel\fixtures;

use tool_otel\hook\enrich_span;
use tool_otel\hook\redact_span;

/**
 * Callbacks of the span hooks used by the tests.
 *
 * @package    tool_otel
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class span_callbacks {
    /**
     * Adds a campus attribute and a secret one (removed by redact).
     *
     * @param enrich_span $hook Hook.
     */
    public static function enrich(enrich_span $hook): void {
        $hook->set_attribute('local_campus.name', 'north');
        $hook->set_attribute('local_campus.secretcode', 'abc');
        $hook->set_attribute('local_campus.root', $hook->is_root());
    }

    /**
     * Removes and masks attributes.
     *
     * @param redact_span $hook Hook.
     */
    public static function redact(redact_span $hook): void {
        $hook->remove_attribute('local_campus.secretcode');
        $hook->mask_attribute('url.path');
    }

    /**
     * Always fails.
     *
     * @param enrich_span $hook Hook.
     */
    public static function fail(enrich_span $hook): void {
        throw new \moodle_exception('error');
    }
}
