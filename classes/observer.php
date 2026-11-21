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

namespace tool_otel;

use tool_otel\local\tracer;

/**
 * Event observers.
 *
 * @package    tool_otel
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class observer {
    /**
     * Names the root span after the web service function.
     *
     * @param \core\event\webservice_function_called $event Event.
     * @return void
     */
    public static function webservice_function_called(\core\event\webservice_function_called $event): void {
        $function = (string) ($event->other['function'] ?? '');
        if ($function === '' || !tracer::is_active()) {
            return;
        }
        tracer::rename_root('webservice ' . $function);
        tracer::set_root_attribute('moodle.webservice.function', $function);
    }
}
