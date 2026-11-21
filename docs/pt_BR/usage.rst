Uso
===

O que é rastreado
-----------------

* **Requisições**: um span ``SERVER`` por requisição web, com nome ``<método> <caminho>`` (``webservice <função>`` nas chamadas de web service), com ``http.request.method``, ``url.path`` (sem a query string), ``http.route``, ``http.response.status_code``, ``moodle.user.authenticated`` (nunca quem), ``moodle.db.reads``, ``moodle.db.writes``, ``moodle.db.queries_time_ms`` e ``process.memory.peak_bytes``. Status ``ERROR`` em HTTP 5xx e erros fatais.
* **Tarefas**: um span por execução de tarefa, com nome ``task <classe>``, ``moodle.task.type``, ``moodle.task.failed`` e os contadores do banco (exige ``$CFG->task_log_class``).
* **Contexto**: um header ``traceparent`` recebido continua o trace de quem chamou (balanceador, frontend, outro serviço).

Nenhum dado pessoal é exportado: atributos cujos nomes se referem a e-mail, nomes de usuário, ids, endereços, tokens, senhas ou cookies são removidos antes da exportação.

Backends
--------

* **OpenTelemetry Collector** (recomendado): ``OTEL_EXPORTER_OTLP_ENDPOINT=http://otel-collector:4318`` e, de lá, qualquer backend.
* **Grafana Tempo** / **Grafana Cloud**: aponte para o endpoint OTLP/HTTP e defina a autorização em ``OTEL_EXPORTER_OTLP_HEADERS``.
* **Jaeger**: o Jaeger aceita OTLP/HTTP na porta 4318.
* **Sentry**: o Sentry aceita traces OTLP; use o endpoint e o header de autenticação mostrados nas configurações do projeto no Sentry. Veja :doc:`otel-vs-sentry`.

Limitações
----------

Veja :doc:`spike`: o trabalho de banco é informado como agregados da requisição, e as chamadas HTTP de saída não têm spans, porque o core do Moodle não tem hook para elas.
