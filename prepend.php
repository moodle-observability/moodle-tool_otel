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

/**
 * Starts tracing before Moodle. In php.ini: auto_prepend_file=/path/to/moodle/public/admin/tool/otel/prepend.php
 *
 * PHP runs this file before every script, before config.php, so it cannot check MOODLE_INTERNAL. Without the
 * OTEL_EXPORTER_OTLP_ENDPOINT environment variable it does nothing and the plugin starts tracing from the
 * after_config hook instead (if enabled in its settings).
 *
 * @package    tool_otel
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.MoodleInternal.MoodleInternalGlobalState
// phpcs:disable moodle.Files.RequireLogin.Missing

if (PHP_SAPI === 'cli' || (string) getenv('OTEL_EXPORTER_OTLP_ENDPOINT') === '') {
    return;
}
// Opened directly by URL: nothing to do.
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    return;
}

require_once(__DIR__ . '/classes/local/config.php');
require_once(__DIR__ . '/classes/local/redactor.php');
require_once(__DIR__ . '/classes/local/tracer.php');
\tool_otel\local\tracer::start_from_prepend();
