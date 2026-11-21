moodle-tool_otel
================

**OpenTelemetry tracing for Moodle.**

Adds distributed tracing to Moodle with OpenTelemetry and exports the traces through OTLP to Jaeger, Grafana Tempo, Sentry or any compatible backend.

.. note::

   Research phase: a spike will evaluate the OpenTelemetry PHP SDK with the Moodle bootstrap before an MVP is planned. There is no release yet.
   Follow the progress in the `issues <https://github.com/moodle-observability/moodle-tool_otel/issues>`_.

Planned features
----------------

* Root span per HTTP request with route and user context, without personal data.
* Spans for database queries (without parameter values).
* Spans for outgoing HTTP calls made with the ``curl`` class.
* Spans for web services and scheduled tasks.
* Configurable OTLP exporter and sampling.

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
