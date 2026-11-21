Extending
=========

Tracing API
-----------

Other plugins can create spans without depending on the OpenTelemetry SDK. The API is a no-op when tracing is off:

.. code-block:: php

   $span = \tool_otel\local\tracer::start_span('local_example.sync', ['local_example.items' => count($items)]);
   try {
       // Work.
       \tool_otel\local\tracer::end_span($span);
   } catch (\Throwable $e) {
       \tool_otel\local\tracer::end_span($span, [], $e);
       throw $e;
   }

Check ``class_exists(\tool_otel\local\tracer::class)`` first if ``tool_otel`` is optional for your plugin.

Hooks
-----

* ``\tool_otel\hook\enrich_span``: add attributes before a span ends (``set_attribute()``, ``is_root()``). It runs on the request path: no I/O.
* ``\tool_otel\hook\redact_span``: remove (``remove_attribute()``) or mask (``mask_attribute()``) attributes before export.

Each callback runs on its own: an exception is reported with ``debugging()`` and never breaks tracing or the request. The built-in redaction runs after the hooks.
