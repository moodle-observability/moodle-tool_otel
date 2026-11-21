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

namespace tool_otel;

use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use PHPUnit\Framework\Attributes\CoversClass;
use tool_otel\local\config;
use tool_otel\local\tracer;

/**
 * Tests of the tracer, the hooks, the task logger and the web service observer.
 *
 * @package    tool_otel
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(tracer::class)]
#[CoversClass(hook\enrich_span::class)]
#[CoversClass(hook\redact_span::class)]
#[CoversClass(task_logger::class)]
#[CoversClass(observer::class)]
final class tracer_test extends \advanced_testcase {
    /** @var InMemoryExporter Exporter of the test. */
    private $exporter;

    #[\Override]
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        require_once(__DIR__ . '/fixtures/span_callbacks.php');
    }

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        tracer::reset_for_testing();
        $this->assertTrue(tracer::load_vendor());
        $this->exporter = new InMemoryExporter();
        $this->use_callbacks([]);
    }

    #[\Override]
    protected function tearDown(): void {
        tracer::reset_for_testing();
        parent::tearDown();
    }

    /**
     * Uses a hook manager with only the given callbacks.
     *
     * @param array $callbacks Callback definitions (as in db/hooks.php).
     */
    private function use_callbacks(array $callbacks): void {
        $file = make_request_directory() . '/hooks.php';
        file_put_contents($file, '<?php $callbacks = ' . var_export($callbacks, true) . ';');
        \core\di::set(\core\hook\manager::class, \core\hook\manager::phpunit_get_instance(['local_campus' => $file]));
    }

    /**
     * Starts the tracer with the in-memory exporter.
     */
    private function start(): void {
        $config = config::from_environment(['OTEL_EXPORTER_OTLP_ENDPOINT' => 'http://unused:4318']);
        $this->assertTrue(tracer::start($config, $this->exporter));
    }

    /**
     * Exported spans keyed by name.
     *
     * @return array
     */
    private function spans(): array {
        $spans = [];
        foreach ($this->exporter->getSpans() as $span) {
            $spans[$span->getName()] = $span;
        }
        return $spans;
    }

    /**
     * Without configuration nothing starts and the API is a no-op.
     */
    public function test_disabled(): void {
        $this->assertFalse(tracer::start(config::from_environment([])));
        $this->assertFalse(tracer::is_active());
        $this->assertNull(tracer::start_span('x'));
        tracer::end_span(null);
        tracer::start_request(['REQUEST_METHOD' => 'GET']);
        tracer::end_request();
        $this->assertSame([], $this->exporter->getSpans());
    }

    /**
     * Root span of a request: continues the incoming trace, child spans and attributes without personal data.
     */
    public function test_request_span(): void {
        $this->resetAfterTest();
        $this->start();
        $traceid = '4bf92f3577b34da6a3ce929d0e0e4736';
        tracer::start_request([
            'REQUEST_METHOD' => 'post',
            'REQUEST_URI' => '/course/view.php?id=2&sesskey=abc',
            'HTTP_TRACEPARENT' => "00-{$traceid}-00f067aa0ba902b7-01",
        ]);
        $child = tracer::start_span('local_x.work', ['local_x.items' => 3]);
        tracer::end_span($child, ['user.email' => 'a@b.c'], new \moodle_exception('error'));
        tracer::set_root_attribute('user.email', 'someone@example.com');
        tracer::rename_root('POST /course/view.php');
        tracer::end_request();

        $spans = $this->spans();
        $root = $spans['POST /course/view.php'];
        $this->assertSame($traceid, $root->getTraceId());
        $this->assertSame('00f067aa0ba902b7', $root->getParentSpanId());
        $attributes = $root->getAttributes()->toArray();
        $this->assertSame('POST', $attributes['http.request.method']);
        $this->assertSame('/course/view.php', $attributes['url.path']);
        $this->assertArrayHasKey('moodle.db.reads', $attributes);
        $this->assertFalse($attributes['moodle.user.authenticated']);
        $this->assertArrayNotHasKey('user.email', $attributes);

        $work = $spans['local_x.work'];
        $this->assertSame($root->getSpanId(), $work->getParentSpanId());
        $this->assertSame(3, $work->getAttributes()->get('local_x.items'));
        $this->assertNull($work->getAttributes()->get('user.email'));
        $this->assertSame('Error', $work->getStatus()->getCode());
    }

    /**
     * Other plugins enrich and redact the spans; a failing callback is isolated.
     */
    public function test_hooks(): void {
        $this->resetAfterTest();
        $this->use_callbacks([
            ['hook' => hook\enrich_span::class, 'callback' => [fixtures\span_callbacks::class, 'fail'], 'priority' => 200],
            ['hook' => hook\enrich_span::class, 'callback' => [fixtures\span_callbacks::class, 'enrich']],
            ['hook' => hook\redact_span::class, 'callback' => [fixtures\span_callbacks::class, 'redact']],
        ]);
        $this->start();
        tracer::start_request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/my/']);
        tracer::end_request();
        $this->assertDebuggingCalled();

        $attributes = $this->spans()['GET /my/']->getAttributes()->toArray();
        $this->assertSame('north', $attributes['local_campus.name']);
        $this->assertTrue($attributes['local_campus.root']);
        $this->assertArrayNotHasKey('local_campus.secretcode', $attributes);
        $this->assertSame('[Filtered]', $attributes['url.path']);
    }

    /**
     * The task logger keeps the core log and records a span with the real times.
     */
    public function test_task_logger(): void {
        global $DB;
        $this->resetAfterTest();
        $this->start();
        $task = new \core\task\session_cleanup_task();
        $logpath = make_request_directory() . '/task.log';
        file_put_contents($logpath, 'Task output');
        task_logger::store_log_for_task($task, $logpath, true, 10, 2, 1700000000.5, 1700000002.25);

        $this->assertTrue($DB->record_exists_select(
            'task_log',
            $DB->sql_like('classname', ':classname'),
            ['classname' => '%session_cleanup_task']
        ));
        $span = $this->spans()['task core\task\session_cleanup_task'];
        $this->assertSame(1700000000500000000, $span->getStartEpochNanos());
        $this->assertSame(1700000002250000000, $span->getEndEpochNanos());
        $attributes = $span->getAttributes()->toArray();
        $this->assertSame('scheduled', $attributes['moodle.task.type']);
        $this->assertTrue($attributes['moodle.task.failed']);
        $this->assertSame(10, $attributes['moodle.db.reads']);
        $this->assertSame('Error', $span->getStatus()->getCode());
        $this->assertTrue(task_logger::has_log_report());
    }

    /**
     * Web service calls name the root span.
     */
    public function test_webservice_observer(): void {
        $this->resetAfterTest();
        $this->start();
        tracer::start_request(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/webservice/rest/server.php']);
        \core\event\webservice_function_called::create([
            'other' => ['function' => 'core_course_get_courses'],
        ])->trigger();
        tracer::end_request();
        $span = $this->spans()['webservice core_course_get_courses'];
        $this->assertSame('core_course_get_courses', $span->getAttributes()->get('moodle.webservice.function'));
    }
}
