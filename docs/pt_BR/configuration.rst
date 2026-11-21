Configuração
============

Variáveis de ambiente (recomendado)
-----------------------------------

Com ``OTEL_EXPORTER_OTLP_ENDPOINT`` definida, o plugin usa as variáveis padrão do OpenTelemetry e ignora a página de configurações:

.. list-table::
   :header-rows: 1

   * - Variável
     - Padrão
     - Descrição
   * - ``OTEL_EXPORTER_OTLP_ENDPOINT``
     -
     - URL base do receptor OTLP/HTTP, por exemplo ``http://otel-collector:4318`` (``/v1/traces`` é acrescentado).
   * - ``OTEL_EXPORTER_OTLP_HEADERS``
     -
     - Pares ``nome=valor`` separados por vírgula (valores codificados como URL), por exemplo uma chave de API.
   * - ``OTEL_SERVICE_NAME``
     - ``moodle``
     - ``service.name`` dos traces.
   * - ``OTEL_TRACES_SAMPLER_ARG``
     - ``1``
     - Taxa de amostragem, de 0 a 1. Decisões anteriores (``traceparent``) são respeitadas.
   * - ``OTEL_SDK_DISABLED``
     -
     - ``true`` desliga o tracing.

As variáveis precisam chegar ao PHP (``environment:`` do container, ``clear_env = no`` ou ``env[...]`` no PHP-FPM, ``SetEnv`` no Apache).

Página de configurações
-----------------------

Sem as variáveis de ambiente, *Administração do site > Plugins > Ferramentas de administração > OpenTelemetry tracing* tem as mesmas opções (habilitar, endpoint, headers, taxa de amostragem, nome do serviço). A página mostra qual configuração está ativa.

Iniciar antes do Moodle (opcional)
----------------------------------

Por padrão o tracing começa no hook ``after_config``, então o span da requisição não inclui o setup do Moodle. Para incluí-lo, inicie o plugin antes do Moodle (exige as variáveis de ambiente):

.. code-block:: ini

   auto_prepend_file=/var/www/html/public/admin/tool/otel/prepend.php

Se o ``tool_sentry`` também estiver em modo prepend, só um arquivo pode ser incluído: crie um arquivo pequeno que inclua os dois.

Spans de tarefas (opcional)
---------------------------

Cada execução de tarefa agendada e adhoc vira um span (com os horários reais de início e fim) quando o task logger do plugin é definido no ``config.php``. Ele mantém também o log de tarefas do core:

.. code-block:: php

   $CFG->task_log_class = '\tool_otel\task_logger';

O Moodle chama o task logger conforme *Administração do site > Servidor > Tarefas > Configuração do log de tarefas*: com o modo de log só de falhas (``$CFG->task_logmode = 1``), apenas as execuções com falha viram spans. Registre todas as execuções para rastrear todas.
