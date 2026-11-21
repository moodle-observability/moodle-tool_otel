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
 * Admin settings.
 *
 * @package    tool_otel
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('tool_otel', get_string('pluginname', 'tool_otel'));
    $ADMIN->add('tools', $settings);

    if ($ADMIN->fulltree) {
        $config = \tool_otel\local\config::load();
        $a = (object) [
            'source' => get_string('source_' . $config->source, 'tool_otel'),
            'prepend' => get_string(\tool_otel\local\tracer::started_by_prepend() ? 'yes' : 'no'),
            'tasks' => get_string(($CFG->task_log_class ?? '') === '\\tool_otel\\task_logger' ? 'yes' : 'no'),
        ];
        $settings->add(new admin_setting_heading(
            'tool_otel/source',
            get_string('source', 'tool_otel'),
            get_string('source_desc', 'tool_otel', $a)
        ));
        $settings->add(new admin_setting_configcheckbox(
            'tool_otel/enabled',
            get_string('enabled', 'tool_otel'),
            get_string('enabled_desc', 'tool_otel'),
            0
        ));
        $settings->add(new admin_setting_configtext(
            'tool_otel/endpoint',
            get_string('endpoint', 'tool_otel'),
            get_string('endpoint_desc', 'tool_otel'),
            'http://localhost:4318',
            PARAM_URL
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'tool_otel/headers',
            get_string('headers', 'tool_otel'),
            get_string('headers_desc', 'tool_otel'),
            ''
        ));
        $settings->add(new admin_setting_configtext(
            'tool_otel/samplerate',
            get_string('samplerate', 'tool_otel'),
            get_string('samplerate_desc', 'tool_otel'),
            '1',
            PARAM_FLOAT
        ));
        $settings->add(new admin_setting_configtext(
            'tool_otel/servicename',
            get_string('servicename', 'tool_otel'),
            get_string('servicename_desc', 'tool_otel'),
            'moodle',
            PARAM_TEXT
        ));
    }
}
