moodle-tool_otel
================

**OpenTelemetry tracing for Moodle.**

Adds distributed tracing to Moodle with OpenTelemetry and exports the traces through OTLP to Jaeger, Grafana Tempo, Sentry or any compatible backend.

.. note::

   Research phase: a spike will evaluate the OpenTelemetry PHP SDK with the Moodle bootstrap before an MVP is planned. There is no release yet.
   Follow the progress in the `issues <https://github.com/moodle-observability/moodle-tool_otel/issues>`_.

Planned features
----------------

The ``tool_sentry`` column shows whether the `moodle-tool_sentry <https://github.com/moodle-observability/moodle-tool_sentry>`_ plugin also offers the feature. See the full comparison in :doc:`otel-vs-sentry`.

.. list-table::
   :header-rows: 1
   :widths: 60 12 28

   * - Feature
     - tool_otel
     - tool_sentry
   * - Root span per HTTP request with route and user context, without personal data.
     - ✅
     - ❌ Only enables SDK tracing; creates no Moodle spans.
   * - Spans for database queries (without parameter values).
     - ✅
     - ❌
   * - Spans for outgoing HTTP calls made with the ``curl`` class.
     - ✅
     - ❌
   * - Spans for web services and scheduled tasks.
     - ✅
     - ❌ Tags task errors with context, without spans.
   * - OTLP exporter for any compatible backend.
     - ✅
     - ❌ Sends to Sentry only.
   * - Configurable trace sampling.
     - ✅
     - ✅ ``traces_sample_rate``.

Requirements
------------

* Moodle 5.3 or later.
* PHP version supported by your Moodle release.
* PostgreSQL or MariaDB.

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
   otel-vs-sentry
