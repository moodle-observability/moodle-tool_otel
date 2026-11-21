<img src="docs/logo/logo.svg" alt="moodle-tool_otel logo" width="96" align="right" />

# moodle-tool_otel

[![Documentation](https://github.com/moodle-observability/moodle-tool_otel/actions/workflows/docs.yml/badge.svg)](https://github.com/moodle-observability/moodle-tool_otel/actions/workflows/docs.yml)

OpenTelemetry tracing for Moodle (`tool_otel`). Adds distributed tracing to Moodle with OpenTelemetry and exports the traces through OTLP to Jaeger, Grafana Tempo, Sentry or any compatible backend.

**Status:** 0.1.0 (alpha). Download it from the [releases](https://github.com/moodle-observability/moodle-tool_otel/releases).

Documentation (English and Brazilian Portuguese): https://moodle-observability.github.io/moodle-tool_otel/

Requirements: Moodle 5.3 or later.

Quick start: install the plugin in `public/admin/tool/otel`, upgrade Moodle and set
`OTEL_EXPORTER_OTLP_ENDPOINT=http://<collector>:4318` in the environment of PHP (or enable it in
*Site administration > Plugins > Admin tools > OpenTelemetry tracing*). For task spans, add
`$CFG->task_log_class = '\tool_otel\task_logger';` to `config.php`.

Part of [Moodle Observability](https://github.com/moodle-observability). Contributions: see the [contributing guide](https://github.com/moodle-observability/.github/blob/main/CONTRIBUTING.md).

License: [GPL-3.0](LICENSE).
