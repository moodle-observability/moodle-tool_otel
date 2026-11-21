moodle-tool_otel
================

**OpenTelemetry tracing for Moodle.**

Adds distributed tracing to Moodle with OpenTelemetry and exports the traces through OTLP to Jaeger, Grafana Tempo, Sentry or any compatible backend.

.. note::

   Version 0.1.0 (alpha). The spike that evaluated the OpenTelemetry PHP SDK with the Moodle bootstrap is in :doc:`spike`.
   Follow the progress in the `issues <https://github.com/moodle-observability/moodle-tool_otel/issues>`_.

Features
--------

The ``tool_sentry`` column shows whether the `moodle-tool_sentry <https://github.com/moodle-observability/moodle-tool_sentry>`_ plugin also offers the feature. See the full comparison in :doc:`otel-vs-sentry`.

.. list-table::
   :header-rows: 1
   :widths: 60 12 28

   * - Feature
     - tool_otel
     - tool_sentry
   * - Root span per HTTP request with route and user context, without personal data; continues an incoming W3C ``traceparent``.
     - ✅
     - ❌ Only enables SDK tracing; creates no Moodle spans.
   * - Database work of each request.
     - ⚠️ Aggregates on the request span (reads, writes, query time); per-query spans need a core hook.
     - ❌
   * - Spans for outgoing HTTP calls made with the ``curl`` class.
     - ❌ Moodle has no hook for outgoing calls.
     - ❌
   * - Spans for web services and scheduled and adhoc tasks.
     - ✅ Tasks with ``$CFG->task_log_class``.
     - ❌ Tags task errors with context, without spans.
   * - OTLP exporter for any compatible backend.
     - ✅
     - ❌ Sends to Sentry only.
   * - Configurable trace sampling.
     - ✅
     - ✅ ``traces_sample_rate``.
   * - Starts before Moodle (``auto_prepend_file``).
     - ✅
     - ✅
   * - Extension points: enrich and redact spans; tracing API for other plugins.
     - ✅
     - ❌

Legend: ✅ available; ⚠️ partial or only under the stated condition; ❌ not offered.

Requirements
------------

* Moodle 5.3 or later.
* PHP version supported by your Moodle release (8.3 or later).
* PostgreSQL or MariaDB.
* An OTLP/HTTP receiver: an OpenTelemetry Collector, Grafana Tempo, Jaeger or Sentry.

The OpenTelemetry PHP SDK is included in the plugin (``vendor/``); the ``opentelemetry`` PHP extension is not needed.

Links
-----

* `Source code <https://github.com/moodle-observability/moodle-tool_otel>`_
* `Issues <https://github.com/moodle-observability/moodle-tool_otel/issues>`_
* `Moodle Observability <https://moodle-observability.github.io/>`_

.. toctree::
   :hidden:
   :maxdepth: 1

   installation
   configuration
   usage
   extending
   spike
   otel-vs-sentry
