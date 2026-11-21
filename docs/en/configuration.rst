Configuration
=============

Environment variables (recommended)
-----------------------------------

When ``OTEL_EXPORTER_OTLP_ENDPOINT`` is set, the plugin uses the standard OpenTelemetry variables and ignores its settings page:

.. list-table::
   :header-rows: 1

   * - Variable
     - Default
     - Description
   * - ``OTEL_EXPORTER_OTLP_ENDPOINT``
     -
     - Base URL of the OTLP/HTTP receiver, for example ``http://otel-collector:4318`` (``/v1/traces`` is added).
   * - ``OTEL_EXPORTER_OTLP_HEADERS``
     -
     - Comma separated ``name=value`` pairs (URL encoded values), for example an API key.
   * - ``OTEL_SERVICE_NAME``
     - ``moodle``
     - ``service.name`` of the traces.
   * - ``OTEL_TRACES_SAMPLER_ARG``
     - ``1``
     - Sampling ratio, from 0 to 1. Upstream decisions (``traceparent``) are respected.
   * - ``OTEL_SDK_DISABLED``
     -
     - ``true`` disables tracing.

The variables must reach PHP (``environment:`` of the container, ``clear_env = no`` or ``env[...]`` in PHP-FPM, ``SetEnv`` in Apache).

Settings page
-------------

Without the environment variables, *Site administration > Plugins > Admin tools > OpenTelemetry tracing* has the same options (enable, endpoint, headers, sampling rate, service name). The page shows which configuration is active.

Start before Moodle (optional)
------------------------------

By default tracing starts from the ``after_config`` hook, so the request span misses the Moodle setup. To include it, start the plugin before Moodle (requires the environment variables):

.. code-block:: ini

   auto_prepend_file=/var/www/html/public/admin/tool/otel/prepend.php

If ``tool_sentry`` is also in prepend mode, only one file can be prepended: create a small file that includes both.

Task spans (optional)
---------------------

Each scheduled and adhoc task run becomes a span (with its real start and end times) when the task logger of the plugin is set in ``config.php``. It keeps the core task log too:

.. code-block:: php

   $CFG->task_log_class = '\tool_otel\task_logger';

Moodle calls the task logger according to *Site administration > Server > Tasks > Task log configuration*: with *Log mode* set to failures only (``$CFG->task_logmode = 1``), only failed runs become spans. Log all task output to trace every run.
