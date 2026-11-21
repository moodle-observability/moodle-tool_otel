<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace tool_otel\local;

use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\Sampler\TraceIdRatioBasedSampler;
use OpenTelemetry\SDK\Trace\SpanExporterInterface;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

/**
 * Tracing facade. Every public method is a no-op when tracing is not active, so other plugins can call it freely.
 *
 * @package    tool_otel
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tracer {
    /** Instrumentation scope name. */
    public const SCOPE = 'moodle-observability/tool_otel';

    /** @var TracerProvider|null Provider. */
    private static ?TracerProvider $provider = null;

    /** @var \OpenTelemetry\API\Trace\SpanInterface|null Root span of the request. */
    private static $root = null;

    /** @var \OpenTelemetry\Context\ScopeInterface|null Scope of the root span. */
    private static $rootscope = null;

    /** @var string Name of the root span. */
    private static string $rootname = '';

    /** @var array Attributes of the root span, set when it ends (after enrichment and redaction). */
    private static array $rootattributes = [];

    /** @var bool Whether tracing was started by prepend.php. */
    private static bool $prepend = false;

    /**
     * Loads the Composer dependencies of the plugin.
     *
     * @return bool
     */
    public static function load_vendor(): bool {
        $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (!is_readable($autoload)) {
            return false;
        }
        require_once($autoload);
        return true;
    }

    /**
     * Whether tracing is active in this process.
     *
     * @return bool
     */
    public static function is_active(): bool {
        return self::$provider !== null;
    }

    /**
     * Whether tracing was started by prepend.php in this request.
     *
     * @return bool
     */
    public static function started_by_prepend(): bool {
        return self::$prepend;
    }

    /**
     * Starts the tracer provider with an OTLP/HTTP exporter (or the given exporter, for tests).
     *
     * @param config $config Configuration.
     * @param SpanExporterInterface|null $exporter Exporter used instead of OTLP (spans exported synchronously).
     * @return bool True when tracing is active.
     */
    public static function start(config $config, ?SpanExporterInterface $exporter = null): bool {
        if (self::$provider !== null) {
            return true;
        }
        if (!$config->enabled || !self::load_vendor()) {
            return false;
        }
        try {
            if ($exporter === null) {
                $transport = (new \OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory())->create(
                    $config->traces_url(),
                    'application/x-protobuf',
                    $config->headers,
                    null,
                    2.0,
                    100,
                    1
                );
                $processor = new BatchSpanProcessor(
                    new \OpenTelemetry\Contrib\Otlp\SpanExporter($transport),
                    \OpenTelemetry\API\Common\Time\Clock::getDefault()
                );
            } else {
                $processor = new SimpleSpanProcessor($exporter);
            }
            $resource = ResourceInfoFactory::emptyResource()->merge(
                ResourceInfo::create(Attributes::create(['service.name' => $config->servicename]))
            );
            self::$provider = new TracerProvider(
                $processor,
                new ParentBased(new TraceIdRatioBasedSampler($config->samplerate)),
                $resource
            );
        } catch (\Throwable $e) {
            self::$provider = null;
            return false;
        }
        if ($exporter === null) {
            register_shutdown_function([self::class, 'shutdown']);
        }
        return true;
    }

    /**
     * Called by prepend.php: starts tracing from the environment and opens the root span of the request.
     *
     * @return void
     */
    public static function start_from_prepend(): void {
        if (self::start(config::from_environment())) {
            self::$prepend = true;
            self::start_request($_SERVER);
        }
    }

    /**
     * Opens the root span of a web request, continuing an incoming W3C traceparent.
     *
     * @param array $server The $_SERVER array.
     * @return void
     */
    public static function start_request(array $server): void {
        if (self::$provider === null || self::$root !== null) {
            return;
        }
        $headers = [];
        foreach (['traceparent' => 'HTTP_TRACEPARENT', 'tracestate' => 'HTTP_TRACESTATE'] as $name => $key) {
            if (!empty($server[$key])) {
                $headers[$name] = (string) $server[$key];
            }
        }
        $parent = TraceContextPropagator::getInstance()->extract($headers);
        $method = strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET'));
        $path = redactor::path((string) ($server['REQUEST_URI'] ?? '/'));
        self::$rootname = $method . ' ' . $path;
        self::$rootattributes = [
            'http.request.method' => $method,
            'url.path' => $path,
            'url.scheme' => (!empty($server['HTTPS']) && $server['HTTPS'] !== 'off') ? 'https' : 'http',
        ];
        self::$root = self::get_tracer()->spanBuilder(self::$rootname)
            ->setParent($parent)
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->startSpan();
        self::$rootscope = self::$root->activate();
    }

    /**
     * Renames the root span (for example "webservice core_course_get_courses").
     *
     * @param string $name Name.
     * @return void
     */
    public static function rename_root(string $name): void {
        if (self::$root !== null) {
            self::$rootname = $name;
        }
    }

    /**
     * Sets an attribute of the root span.
     *
     * @param string $name Name.
     * @param string|int|float|bool $value Value.
     * @return void
     */
    public static function set_root_attribute(string $name, string|int|float|bool $value): void {
        if (self::$root !== null) {
            self::$rootattributes[$name] = $value;
        }
    }

    /**
     * Ends the root span: status, database aggregates, enrichment and redaction by other plugins.
     *
     * @return void
     */
    public static function end_request(): void {
        global $DB, $SCRIPT;
        if (self::$root === null) {
            return;
        }
        $status = (int) http_response_code();
        if ($status > 0) {
            self::$rootattributes['http.response.status_code'] = $status;
        }
        if (!empty($SCRIPT)) {
            self::$rootattributes['http.route'] = (string) $SCRIPT;
        }
        if (isset($DB) && is_object($DB) && method_exists($DB, 'perf_get_reads')) {
            self::$rootattributes['moodle.db.reads'] = (int) $DB->perf_get_reads();
            self::$rootattributes['moodle.db.writes'] = (int) $DB->perf_get_writes();
            self::$rootattributes['moodle.db.queries_time_ms'] = round((float) $DB->perf_get_queries_time() * 1000, 2);
        }
        if (function_exists('isloggedin')) {
            self::$rootattributes['moodle.user.authenticated'] = isloggedin() && !isguestuser();
        }
        self::$rootattributes['process.memory.peak_bytes'] = memory_get_peak_usage(true);
        $last = error_get_last();
        $fatal = $last !== null && ($last['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR));
        $attributes = self::finish_attributes(self::$rootname, self::$rootattributes, true);
        self::$root->updateName(self::$rootname);
        self::$root->setAttributes($attributes);
        if ($status >= 500 || $fatal) {
            self::$root->setStatus(StatusCode::STATUS_ERROR);
        }
        if (self::$rootscope !== null) {
            self::$rootscope->detach();
        }
        self::$root->end();
        self::$root = null;
        self::$rootscope = null;
        self::$rootattributes = [];
    }

    /**
     * Starts a child span of the current span. Returns null (and does nothing) when tracing is not active.
     *
     * @param string $name Span name.
     * @param array $attributes Attributes.
     * @return array|null Handle for end_span().
     */
    public static function start_span(string $name, array $attributes = []): ?array {
        if (self::$provider === null) {
            return null;
        }
        $span = self::get_tracer()->spanBuilder($name)->setSpanKind(SpanKind::KIND_INTERNAL)->startSpan();
        return ['span' => $span, 'scope' => $span->activate(), 'name' => $name, 'attributes' => $attributes];
    }

    /**
     * Ends a span started with start_span(). Accepts null, so callers need no check.
     *
     * @param array|null $handle Handle returned by start_span().
     * @param array $attributes More attributes.
     * @param \Throwable|null $error Error to record (marks the span as failed).
     * @return void
     */
    public static function end_span(?array $handle, array $attributes = [], ?\Throwable $error = null): void {
        if ($handle === null || self::$provider === null) {
            return;
        }
        $span = $handle['span'];
        $span->setAttributes(self::finish_attributes($handle['name'], $attributes + $handle['attributes'], false));
        if ($error !== null) {
            $span->setStatus(StatusCode::STATUS_ERROR, get_class($error));
            $span->setAttribute('exception.type', get_class($error));
        }
        $handle['scope']->detach();
        $span->end();
    }

    /**
     * Records a finished task as a span with its real start and end times.
     *
     * @param string $classname Task class.
     * @param string $type scheduled or adhoc.
     * @param bool $failed Whether it failed.
     * @param int $dbreads Database reads.
     * @param int $dbwrites Database writes.
     * @param float $timestart Start (Unix time with microseconds).
     * @param float $timeend End (Unix time with microseconds).
     * @return void
     */
    public static function record_task(
        string $classname,
        string $type,
        bool $failed,
        int $dbreads,
        int $dbwrites,
        float $timestart,
        float $timeend
    ): void {
        if (self::$provider === null && !self::start(config::load())) {
            return;
        }
        $name = 'task ' . ltrim($classname, '\\');
        $attributes = self::finish_attributes($name, [
            'moodle.task.classname' => $classname,
            'moodle.task.type' => $type,
            'moodle.task.failed' => $failed,
            'moodle.db.reads' => $dbreads,
            'moodle.db.writes' => $dbwrites,
        ], false);
        $span = self::get_tracer()->spanBuilder($name)
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->setStartTimestamp(self::nanos($timestart))
            ->setAttributes($attributes)
            ->startSpan();
        if ($failed) {
            $span->setStatus(StatusCode::STATUS_ERROR);
        }
        $span->end(self::nanos($timeend));
    }

    /**
     * Unix time with microseconds as integer nanoseconds, without the precision loss of a float multiplication.
     *
     * @param float $time Unix time.
     * @return int
     */
    public static function nanos(float $time): int {
        $seconds = (int) floor($time);
        return $seconds * 1000000000 + (int) round(($time - $seconds) * 1e6) * 1000;
    }

    /**
     * Ends the request span and flushes the exporter. Registered as a shutdown function.
     *
     * @return void
     */
    public static function shutdown(): void {
        if (self::$provider === null) {
            return;
        }
        try {
            self::end_request();
            self::$provider->shutdown();
        } catch (\Throwable $e) {
            // Tracing must never break the response.
            if (function_exists('debugging')) {
                debugging('tool_otel: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        self::$provider = null;
    }

    /**
     * Forgets the provider and the root span. Only for PHPUnit tests.
     *
     * @return void
     */
    public static function reset_for_testing(): void {
        if (self::$rootscope !== null) {
            self::$rootscope->detach();
        }
        self::$provider = null;
        self::$root = null;
        self::$rootscope = null;
        self::$rootattributes = [];
        self::$rootname = '';
        self::$prepend = false;
    }

    /**
     * The tracer of the plugin.
     *
     * @return \OpenTelemetry\API\Trace\TracerInterface
     */
    private static function get_tracer(): \OpenTelemetry\API\Trace\TracerInterface {
        return self::$provider->getTracer(self::SCOPE);
    }

    /**
     * Applies the enrich_span and redact_span hooks (when Moodle is ready) and the built-in redaction.
     *
     * @param string $name Span name.
     * @param array $attributes Attributes.
     * @param bool $root Whether it is the root span of the request.
     * @return array
     */
    private static function finish_attributes(string $name, array $attributes, bool $root): array {
        if (function_exists('setup_DB') && class_exists(\core\di::class) && !during_initial_install()) {
            $enrich = new \tool_otel\hook\enrich_span($name, $attributes, $root);
            self::dispatch($enrich);
            $redact = new \tool_otel\hook\redact_span($name, $enrich->get_attributes());
            self::dispatch($redact);
            $attributes = $redact->get_attributes();
        }
        $clean = [];
        foreach (redactor::clean($attributes) as $key => $value) {
            if (is_scalar($value)) {
                $clean[$key] = $value;
            }
        }
        return $clean;
    }

    /**
     * Calls each callback of a hook on its own: a failing callback never breaks tracing or the request.
     *
     * @param object $hook Hook.
     * @return void
     */
    private static function dispatch(object $hook): void {
        try {
            $callbacks = \core\di::get(\core\hook\manager::class)->getListenersForEvent($hook);
        } catch (\Throwable $e) {
            return;
        }
        foreach ($callbacks as $callback) {
            try {
                $callback($hook);
            } catch (\Throwable $e) {
                debugging('tool_otel: a ' . get_class($hook) . ' callback failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
    }
}
