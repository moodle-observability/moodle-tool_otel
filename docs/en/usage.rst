Usage
=====

What is traced
--------------

* **Requests**: one ``SERVER`` span per web request, named ``<method> <path>`` (``webservice <function>`` for web service calls), with ``http.request.method``, ``url.path`` (without the query string), ``http.route``, ``http.response.status_code``, ``moodle.user.authenticated`` (never who), ``moodle.db.reads``, ``moodle.db.writes``, ``moodle.db.queries_time_ms`` and ``process.memory.peak_bytes``. Status ``ERROR`` for HTTP 5xx and fatal errors.
* **Tasks**: one span per task run, named ``task <class>``, with ``moodle.task.type``, ``moodle.task.failed`` and the database counters (needs ``$CFG->task_log_class``).
* **Context**: an incoming ``traceparent`` header continues the trace of the caller (load balancer, frontend, another service).

No personal data is exported: attributes whose names refer to e-mail, user names, ids, addresses, tokens, passwords or cookies are removed before export.

Backends
--------

* **OpenTelemetry Collector** (recommended): ``OTEL_EXPORTER_OTLP_ENDPOINT=http://otel-collector:4318``, then route to any backend.
* **Grafana Tempo** / **Grafana Cloud**: point to the OTLP/HTTP endpoint and set the authorization in ``OTEL_EXPORTER_OTLP_HEADERS``.
* **Jaeger**: Jaeger accepts OTLP/HTTP on port 4318.
* **Sentry**: Sentry accepts OTLP traces; use the endpoint and the authentication header shown in the project settings of Sentry. See :doc:`otel-vs-sentry`.

Limitations
-----------

See :doc:`spike`: database work is reported as aggregates of the request, and outgoing HTTP calls have no spans, because Moodle core has no hook for them.
