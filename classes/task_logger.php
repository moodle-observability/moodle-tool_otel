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

use core\task\database_logger;
use core\task\task_base;
use tool_otel\local\tracer;

/**
 * Task logger that keeps the core database log and records each task run as a span.
 *
 * Enable it in config.php: $CFG->task_log_class = '\\tool_otel\\task_logger';
 *
 * @package    tool_otel
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class task_logger implements \core\task\task_logger {
    #[\Override]
    public static function is_configured(): bool {
        return database_logger::is_configured();
    }

    #[\Override]
    public static function store_log_for_task(
        task_base $task,
        string $logpath,
        bool $failed,
        int $dbreads,
        int $dbwrites,
        float $timestart,
        float $timeend
    ) {
        database_logger::store_log_for_task($task, $logpath, $failed, $dbreads, $dbwrites, $timestart, $timeend);
        try {
            $type = $task instanceof \core\task\scheduled_task ? 'scheduled' : 'adhoc';
            tracer::record_task('\\' . get_class($task), $type, $failed, $dbreads, $dbwrites, $timestart, $timeend);
        } catch (\Throwable $e) {
            // Tracing must never break the task log.
            debugging('tool_otel: could not record the task span: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    #[\Override]
    public static function has_log_report(): bool {
        return database_logger::has_log_report();
    }

    #[\Override]
    public static function get_url_for_task_class(string $classname): \moodle_url {
        return database_logger::get_url_for_task_class($classname);
    }
}
