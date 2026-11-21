Spike: SDK PHP do OpenTelemetry e o Moodle
==========================================

Resultado da issue #6: o SDK PHP do OpenTelemetry funciona com o bootstrap do Moodle 5.3, e o que dá para instrumentar?

Decisão
-------

Instrumentação manual com o SDK PHP do OpenTelemetry, distribuído em ``vendor/``, sem a extensão PHP ``opentelemetry`` (indisponível na maioria das hospedagens e que só instrumenta automaticamente o PDO, que o Moodle não usa).

Achados
-------

.. list-table::
   :header-rows: 1

   * - Tema
     - Achado
   * - Dependências
     - ``open-telemetry/sdk`` 1.17 e ``exporter-otlp`` 1.6 ocupam cerca de 8 MB. O core do Moodle já traz Guzzle, as interfaces PSR, ``symfony/http-client`` e ``symfony/polyfill-mbstring``; o plugin os declara como ``replace`` no ``composer.json`` e os reaproveita, então nenhuma classe é carregada duas vezes.
   * - Início
     - ``auto_prepend_file`` (``prepend.php``, só variáveis de ambiente) cobre a requisição inteira, como no ``tool_sentry``. Sem ele, o hook ``after_config`` inicia o tracing depois do setup do Moodle.
   * - Exportação
     - Batch span processor esvaziado numa função de shutdown, OTLP/HTTP protobuf, timeout de 2 s e uma nova tentativa: um collector lento acrescenta no máximo alguns segundos ao fim de uma requisição.
   * - Requisições
     - Span raiz ``SERVER`` com propagação de contexto W3C. Rota, status, logado (sim ou não) e memória; nenhum dado pessoal.
   * - Banco de dados
     - O ``moodle_database`` não tem hook por consulta (os drivers nativos não são substituíveis). Por requisição, ``perf_get_reads()``, ``perf_get_writes()`` e ``perf_get_queries_time()`` dão agregados. Spans por consulta exigem um hook no core (issue #8).
   * - HTTP de saída
     - A classe legada ``curl`` não tem hook e o ``\core\http_client`` é criado com ``new``, não por injeção de dependência. Spans exigem mudança no core (issue #9).
   * - Tarefas
     - ``$CFG->task_log_class`` aceita um ``\core\task\task_logger`` próprio: o plugin mantém o log no banco e registra cada execução como span com os horários reais.
   * - Web services
     - O evento ``webservice_function_called`` dá ao span da requisição o nome da função.
   * - Custo
     - Ainda não medido formalmente. Os spans são montados em memória durante a requisição e exportados uma vez, no shutdown.
