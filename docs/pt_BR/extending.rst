Extensão
========

API de tracing
--------------

Outros plugins podem criar spans sem depender do SDK do OpenTelemetry. A API não faz nada quando o tracing está desligado:

.. code-block:: php

   $span = \tool_otel\local\tracer::start_span('local_example.sync', ['local_example.items' => count($items)]);
   try {
       // Trabalho.
       \tool_otel\local\tracer::end_span($span);
   } catch (\Throwable $e) {
       \tool_otel\local\tracer::end_span($span, [], $e);
       throw $e;
   }

Confira ``class_exists(\tool_otel\local\tracer::class)`` antes, se o ``tool_otel`` for opcional para o seu plugin.

Hooks
-----

* ``\tool_otel\hook\enrich_span``: acrescenta atributos antes de um span terminar (``set_attribute()``, ``is_root()``). Roda no caminho da requisição: sem I/O.
* ``\tool_otel\hook\redact_span``: remove (``remove_attribute()``) ou mascara (``mask_attribute()``) atributos antes da exportação.

Cada callback roda isolado: uma exceção é informada com ``debugging()`` e nunca quebra o tracing nem a requisição. A redação embutida roda depois dos hooks.
