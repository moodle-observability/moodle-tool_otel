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

use PHPUnit\Framework\Attributes\CoversClass;
use tool_otel\local\config;
use tool_otel\local\redactor;

/**
 * Tests of the configuration and of the built-in redaction.
 *
 * @package    tool_otel
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(config::class)]
#[CoversClass(redactor::class)]
final class config_test extends \advanced_testcase {
    /**
     * The environment wins over the settings.
     */
    public function test_environment(): void {
        $config = config::from_environment([
            'OTEL_EXPORTER_OTLP_ENDPOINT' => 'http://collector:4318/',
            'OTEL_EXPORTER_OTLP_HEADERS' => 'Authorization=Basic%20abc, x-team = obs ,bad header=1',
            'OTEL_TRACES_SAMPLER_ARG' => '0.25',
            'OTEL_SERVICE_NAME' => 'moodle-prod',
        ]);
        $this->assertTrue($config->enabled);
        $this->assertSame('environment', $config->source);
        $this->assertSame('http://collector:4318/v1/traces', $config->traces_url());
        $this->assertSame(['Authorization' => 'Basic abc', 'x-team' => 'obs'], $config->headers);
        $this->assertSame(0.25, $config->samplerate);
        $this->assertSame('moodle-prod', $config->servicename);

        $this->assertFalse(config::from_environment([])->enabled);
        $disabled = ['OTEL_EXPORTER_OTLP_ENDPOINT' => 'x', 'OTEL_SDK_DISABLED' => 'true'];
        $this->assertFalse(config::from_environment($disabled)->enabled);
    }

    /**
     * Without the environment, the plugin settings are used when enabled.
     */
    public function test_settings(): void {
        $this->resetAfterTest();
        $this->assertSame('disabled', config::load([])->source);
        set_config('enabled', 1, 'tool_otel');
        set_config('endpoint', 'https://otlp.example.com/v1/traces', 'tool_otel');
        set_config('samplerate', '7', 'tool_otel');
        set_config('servicename', '', 'tool_otel');
        $config = config::load([]);
        $this->assertSame('settings', $config->source);
        $this->assertSame('https://otlp.example.com/v1/traces', $config->traces_url());
        $this->assertSame(1.0, $config->samplerate);
        $this->assertSame('moodle', $config->servicename);
        $this->assertSame('environment', config::load(['OTEL_EXPORTER_OTLP_ENDPOINT' => 'http://c:4318'])->source);
    }

    /**
     * Personal and secret attributes are removed.
     */
    public function test_redactor(): void {
        $clean = redactor::clean([
            'http.route' => '/course/view.php',
            'user.email' => 'a@b.c',
            'enduser.id' => 5,
            'moodle.user.authenticated' => true,
            'http.request.header.cookie' => 'x',
            'client.address' => '10.0.0.1',
            'moodle.sesskey' => 'x',
        ]);
        // The end user id is personal data too (enduser.id contains "user.id").
        $this->assertSame(['http.route' => '/course/view.php', 'moodle.user.authenticated' => true], $clean);
        $this->assertSame('/login/index.php', redactor::path('/login/index.php?token=abc'));
        $this->assertSame('/', redactor::path(''));
    }
}
