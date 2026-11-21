Installation
============

1. Download ``tool_otel-<version>.zip`` from the `releases <https://github.com/moodle-observability/moodle-tool_otel/releases>`_, or clone the repository into ``public/admin/tool/otel``:

   .. code-block:: bash

      git clone https://github.com/moodle-observability/moodle-tool_otel.git public/admin/tool/otel

2. Run ``php admin/cli/upgrade.php`` (or open *Site administration > Notifications*).
3. Configure the OTLP endpoint (:doc:`configuration`).

The SDK and its dependencies are in ``vendor/``. They reuse the Guzzle, PSR and Symfony packages of Moodle core instead of shipping their own copies.

Uninstall: remove ``auto_prepend_file`` and ``$CFG->task_log_class`` if you set them, then *Site administration > Plugins > Plugins overview*, ``tool_otel``, *Uninstall*.
