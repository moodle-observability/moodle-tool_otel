moodle-tool_otel
================

**Tracing com OpenTelemetry para o Moodle.**

Adiciona tracing distribuído ao Moodle com OpenTelemetry e exporta os traces via OTLP para Jaeger, Grafana Tempo, Sentry ou qualquer backend compatível.

.. note::

   Fase de pesquisa: um spike vai avaliar o SDK PHP do OpenTelemetry com o bootstrap do Moodle antes de planejar o MVP. Ainda não há versão publicada.
   Acompanhe o andamento nas `issues <https://github.com/moodle-observability/moodle-tool_otel/issues>`_.

Funcionalidades previstas
-------------------------

A coluna ``tool_sentry`` indica se o plugin `moodle-tool_sentry <https://github.com/moodle-observability/moodle-tool_sentry>`_ também oferece a funcionalidade. Veja o comparativo completo em :doc:`otel-vs-sentry`.

.. list-table::
   :header-rows: 1
   :widths: 60 12 28

   * - Funcionalidade
     - tool_otel
     - tool_sentry
   * - Span raiz por requisição HTTP com rota e contexto do usuário, sem dados pessoais.
     - ✅
     - ❌ Só ativa o tracing do SDK; não cria spans do Moodle.
   * - Spans de consultas ao banco (sem os valores dos parâmetros).
     - ✅
     - ❌
   * - Spans das chamadas HTTP de saída feitas com a classe ``curl``.
     - ✅
     - ❌
   * - Spans de web services e tarefas agendadas.
     - ✅
     - ❌ Marca os erros das tarefas com contexto (tags), sem spans.
   * - Exportador OTLP para qualquer backend compatível.
     - ✅
     - ❌ Envia apenas para o Sentry.
   * - Amostragem de traces configurável.
     - ✅
     - ✅ ``traces_sample_rate``.

Requisitos
----------

* Moodle 5.3 ou posterior.
* Versão do PHP suportada pela sua versão do Moodle.
* PostgreSQL ou MariaDB.

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
   otel-vs-sentry
