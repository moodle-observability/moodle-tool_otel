# Changelog

## 0.1.0 - 2026-11-21

First release (alpha).

- Spike: the OpenTelemetry PHP SDK 1.17 runs with the Moodle bootstrap through manual instrumentation; dependencies
  shipped in `vendor/`, reusing the Guzzle, PSR and Symfony packages of Moodle core.
- Root span per web request (route, method, status, logged in yes/no, database aggregates, memory) with W3C
  context propagation and no personal data; named after the function for web service calls.
- Task spans with the real start and end times through `$CFG->task_log_class = '	ool_otel	ask_logger'`.
- OTLP/HTTP export configured by the standard `OTEL_*` environment variables or the settings page; optional start
  before Moodle with `auto_prepend_file`.
- `enrich_span` and `redact_span` hooks and a no-op tracing API for other plugins.
- Known limitations: database work only as aggregates per request and no spans for outgoing HTTP calls (Moodle
  core has no hook for them).
