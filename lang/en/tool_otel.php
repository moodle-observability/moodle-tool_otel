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
 * English strings.
 *
 * @package    tool_otel
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['enabled'] = 'Enable tracing';
$string['enabled_desc'] = 'Create spans and export them through OTLP. Ignored when the environment variable OTEL_EXPORTER_OTLP_ENDPOINT is set (the environment wins).';
$string['endpoint'] = 'OTLP endpoint';
$string['endpoint_desc'] = 'Base URL of the OTLP/HTTP receiver, for example <code>http://otel-collector:4318</code>; <code>/v1/traces</code> is added.';
$string['headers'] = 'OTLP headers';
$string['headers_desc'] = 'Comma separated <code>name=value</code> pairs sent with every export, for example an API key. Prefer forcing them in config.php or setting OTEL_EXPORTER_OTLP_HEADERS.';
$string['pluginname'] = 'OpenTelemetry tracing';
$string['privacy:metadata'] = 'The OpenTelemetry tracing plugin does not store personal data. Spans sent to the configured backend contain no personal data: only whether the user is logged in, never who.';
$string['samplerate'] = 'Sampling rate';
$string['samplerate_desc'] = 'Fraction of the traces to keep, from 0 to 1. Upstream sampling decisions (traceparent) are respected.';
$string['servicename'] = 'Service name';
$string['servicename_desc'] = 'Value of the <code>service.name</code> resource attribute.';
$string['source'] = 'Active configuration';
$string['source_desc'] = 'Source: {$a->source}. Started before Moodle (auto_prepend_file): {$a->prepend}. Task spans: {$a->tasks}.';
$string['source_disabled'] = 'tracing disabled';
$string['source_environment'] = 'environment variables';
$string['source_settings'] = 'this page';
