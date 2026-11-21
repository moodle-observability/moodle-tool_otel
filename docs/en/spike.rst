Spike: OpenTelemetry PHP SDK and Moodle
=======================================

Result of issue #6: is the OpenTelemetry PHP SDK usable with the Moodle 5.3 bootstrap, and what can be instrumented?

Decision
--------

Manual instrumentation with the OpenTelemetry PHP SDK, shipped in ``vendor/``, without the ``opentelemetry`` PHP extension (not available on most hosts and it only auto-instruments PDO, which Moodle does not use).

Findings
--------

.. list-table::
   :header-rows: 1

   * - Topic
     - Finding
   * - Dependencies
     - ``open-telemetry/sdk`` 1.17 and ``exporter-otlp`` 1.6 need about 8 MB. Moodle core already ships Guzzle, the PSR interfaces, ``symfony/http-client`` and ``symfony/polyfill-mbstring``; the plugin declares them as ``replace`` in ``composer.json`` and reuses them, so no class is loaded twice.
   * - Start
     - ``auto_prepend_file`` (``prepend.php``, environment only) covers the whole request, like ``tool_sentry``. Without it, the ``after_config`` hook starts tracing after the Moodle setup.
   * - Export
     - Batch span processor flushed in a shutdown function, OTLP/HTTP protobuf, 2 s timeout and one retry: a slow collector adds at most a few seconds to the end of a request, after the response is sent by the web server buffer.
   * - Requests
     - Root ``SERVER`` span with W3C context propagation. Route, status, logged in (yes or no) and memory; no personal data.
   * - Database
     - ``moodle_database`` has no hook per query (the native drivers are not pluggable). Per request, ``perf_get_reads()``, ``perf_get_writes()`` and ``perf_get_queries_time()`` give aggregates. Per-query spans need a core hook (issue #8).
   * - Outgoing HTTP
     - The legacy ``curl`` class has no hook and ``\core\http_client`` is created with ``new``, not through dependency injection. Spans need a core change (issue #9).
   * - Tasks
     - ``$CFG->task_log_class`` accepts a custom ``\core\task\task_logger``: the plugin keeps the database log and records each run as a span with its real times.
   * - Web services
     - The ``webservice_function_called`` event names the request span after the function.
   * - Overhead
     - Not measured formally yet. Spans are built in memory during the request and exported once, at shutdown.
