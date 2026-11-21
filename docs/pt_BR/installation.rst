Instalação
==========

1. Baixe ``tool_otel-<versão>.zip`` em `releases <https://github.com/moodle-observability/moodle-tool_otel/releases>`_, ou clone o repositório em ``public/admin/tool/otel``:

   .. code-block:: bash

      git clone https://github.com/moodle-observability/moodle-tool_otel.git public/admin/tool/otel

2. Rode ``php admin/cli/upgrade.php`` (ou abra *Administração do site > Notificações*).
3. Configure o endpoint OTLP (:doc:`configuration`).

O SDK e suas dependências ficam em ``vendor/``. Eles reaproveitam os pacotes Guzzle, PSR e Symfony do core do Moodle em vez de trazer cópias próprias.

Desinstalação: remova o ``auto_prepend_file`` e o ``$CFG->task_log_class``, se os definiu, e depois *Administração do site > Plugins > Visão geral dos plugins*, ``tool_otel``, *Desinstalar*.
