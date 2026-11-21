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

use tool_otel\local\config;
use tool_otel\local\tracer;

/**
 * Hook callbacks.
 *
 * @package    tool_otel
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class hook_callbacks {
    /**
     * Starts tracing of web requests when prepend.php did not (configuration from the settings or the environment).
     *
     * @param \core\hook\after_config $hook Hook.
     * @return void
     */
    public static function after_config(\core\hook\after_config $hook): void {
        if (CLI_SCRIPT || tracer::is_active() || during_initial_install() || defined('ABORT_AFTER_CONFIG')) {
            return;
        }
        try {
            if (tracer::start(config::load())) {
                tracer::start_request($_SERVER);
            }
        } catch (\Throwable $e) {
            debugging('tool_otel: could not start tracing: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
