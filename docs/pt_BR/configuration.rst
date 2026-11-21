Configuração
============

As configurações ficarão em *Administração do site > Plugins > Ferramentas de administração > tool_otel*.

Configurações previstas
-----------------------

A coluna ``tool_sentry`` indica se o plugin `moodle-tool_sentry <https://github.com/moodle-observability/moodle-tool_sentry>`_ tem configuração equivalente.

.. list-table::
   :header-rows: 1
   :widths: 60 12 28

   * - Configuração
     - tool_otel
     - tool_sentry
   * - Endpoint, protocolo e headers do OTLP.
     - ✅
     - ❌ Usa o DSN do Sentry.
   * - Taxa de amostragem.
     - ✅
     - ✅ ``traces_sample_rate``.
