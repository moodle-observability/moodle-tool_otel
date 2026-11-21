Configuration
=============

The settings will be in *Site administration > Plugins > Admin tools > tool_otel*.

Planned settings
----------------

The ``tool_sentry`` column shows whether the `moodle-tool_sentry <https://github.com/moodle-observability/moodle-tool_sentry>`_ plugin has an equivalent setting.

.. list-table::
   :header-rows: 1
   :widths: 60 12 28

   * - Setting
     - tool_otel
     - tool_sentry
   * - OTLP endpoint, protocol and headers.
     - ✅
     - ❌ Uses the Sentry DSN.
   * - Sampling rate.
     - ✅
     - ✅ ``traces_sample_rate``.
