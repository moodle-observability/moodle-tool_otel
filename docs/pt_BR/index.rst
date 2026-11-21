moodle-tool_otel
================

**Tracing com OpenTelemetry para o Moodle.**

Adiciona tracing distribuído ao Moodle com OpenTelemetry e exporta os traces via OTLP para Jaeger, Grafana Tempo, Sentry ou qualquer backend compatível.

.. note::

   Versão 0.1.0 (alfa). O spike que avaliou o SDK PHP do OpenTelemetry com o bootstrap do Moodle está em :doc:`spike`.
   Acompanhe o andamento nas `issues <https://github.com/moodle-observability/moodle-tool_otel/issues>`_.

Funcionalidades
---------------

A coluna ``tool_sentry`` indica se o plugin `moodle-tool_sentry <https://github.com/moodle-observability/moodle-tool_sentry>`_ também oferece a funcionalidade. Veja o comparativo completo em :doc:`otel-vs-sentry`.

.. list-table::
   :header-rows: 1
   :widths: 60 12 28

   * - Funcionalidade
     - tool_otel
     - tool_sentry
   * - Span raiz por requisição HTTP com rota e contexto do usuário, sem dados pessoais; continua um ``traceparent`` W3C recebido.
     - ✅
     - ❌ Só ativa o tracing do SDK; não cria spans do Moodle.
   * - Trabalho de banco de dados de cada requisição.
     - ⚠️ Agregados no span da requisição (leituras, escritas, tempo de consulta); spans por consulta exigem um hook no core.
     - ❌
   * - Spans das chamadas HTTP de saída feitas com a classe ``curl``.
     - ❌ O Moodle não tem hook para chamadas de saída.
     - ❌
   * - Spans de web services e de tarefas agendadas e adhoc.
     - ✅ Tarefas com ``$CFG->task_log_class``.
     - ❌ Marca os erros das tarefas com contexto (tags), sem spans.
   * - Exportador OTLP para qualquer backend compatível.
     - ✅
     - ❌ Envia apenas para o Sentry.
   * - Amostragem de traces configurável.
     - ✅
     - ✅ ``traces_sample_rate``.
   * - Inicia antes do Moodle (``auto_prepend_file``).
     - ✅
     - ✅
   * - Pontos de extensão: enriquecer e redigir spans; API de tracing para outros plugins.
     - ✅
     - ❌

Legenda: ✅ disponível; ⚠️ parcial ou só na condição indicada; ❌ não oferecido.

Requisitos
----------

* Moodle 5.3 ou posterior.
* Versão do PHP suportada pela sua versão do Moodle (8.3 ou posterior).
* PostgreSQL ou MariaDB.
* Um receptor OTLP/HTTP: OpenTelemetry Collector, Grafana Tempo, Jaeger ou Sentry.

O SDK PHP do OpenTelemetry vem no plugin (``vendor/``); a extensão PHP ``opentelemetry`` não é necessária.

Links
-----

* `Código-fonte <https://github.com/moodle-observability/moodle-tool_otel>`_
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
