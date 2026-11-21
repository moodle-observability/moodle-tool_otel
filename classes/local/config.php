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

/**
 * Effective configuration: environment variables (OTEL_*) first, then the plugin settings.
 *
 * Self-contained: the prepend file loads it before Moodle, when only the environment is available.
 *
 * @package    tool_otel
 * @copyright  2026 Moodle Observability contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class config {
    /** @var bool Whether tracing is enabled. */
    public bool $enabled = false;

    /** @var string OTLP/HTTP base URL. */
    public string $endpoint = '';

    /** @var array<string, string> Headers of the exports. */
    public array $headers = [];

    /** @var float Sampling ratio, from 0 to 1. */
    public float $samplerate = 1.0;

    /** @var string service.name. */
    public string $servicename = 'moodle';

    /** @var string environment, settings or disabled. */
    public string $source = 'disabled';

    /**
     * Configuration from the environment only (no Moodle needed).
     *
     * @param array|null $env Variables (name => value); null reads getenv().
     * @return self
     */
    public static function from_environment(?array $env = null): self {
        $get = function (string $name) use ($env): string {
            $value = $env === null ? getenv($name) : ($env[$name] ?? '');
            return trim((string) ($value === false ? '' : $value));
        };
        $config = new self();
        if ($get('OTEL_EXPORTER_OTLP_ENDPOINT') === '' || strtolower($get('OTEL_SDK_DISABLED')) === 'true') {
            return $config;
        }
        $config->enabled = true;
        $config->source = 'environment';
        $config->endpoint = $get('OTEL_EXPORTER_OTLP_ENDPOINT');
        $config->headers = self::parse_headers($get('OTEL_EXPORTER_OTLP_HEADERS'));
        $config->samplerate = self::ratio($get('OTEL_TRACES_SAMPLER_ARG'));
        $config->servicename = $get('OTEL_SERVICE_NAME') ?: 'moodle';
        return $config;
    }

    /**
     * Configuration from the environment or, when it has none, from the plugin settings.
     *
     * @param array|null $env Variables (name => value); null reads getenv().
     * @return self
     */
    public static function load(?array $env = null): self {
        $config = self::from_environment($env);
        if ($config->enabled || !function_exists('get_config')) {
            return $config;
        }
        $settings = get_config('tool_otel');
        if (empty($settings->enabled) || trim((string) ($settings->endpoint ?? '')) === '') {
            return $config;
        }
        $config->enabled = true;
        $config->source = 'settings';
        $config->endpoint = trim((string) $settings->endpoint);
        $config->headers = self::parse_headers((string) ($settings->headers ?? ''));
        $config->samplerate = self::ratio((string) ($settings->samplerate ?? ''));
        $config->servicename = trim((string) ($settings->servicename ?? '')) ?: 'moodle';
        return $config;
    }

    /**
     * Parses "name=value,name2=value2" (values may be URL encoded, as in OTEL_EXPORTER_OTLP_HEADERS).
     *
     * @param string $headers Header list.
     * @return array<string, string>
     */
    public static function parse_headers(string $headers): array {
        $parsed = [];
        foreach (explode(',', $headers) as $pair) {
            if (!str_contains($pair, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $pair, 2);
            $name = trim($name);
            if (preg_match('/^[A-Za-z0-9-]+$/', $name)) {
                $parsed[$name] = rawurldecode(trim($value));
            }
        }
        return $parsed;
    }

    /**
     * Sampling ratio between 0 and 1 (1 when empty or invalid).
     *
     * @param string $value Value.
     * @return float
     */
    public static function ratio(string $value): float {
        if (trim($value) === '' || !is_numeric($value)) {
            return 1.0;
        }
        return max(0.0, min(1.0, (float) $value));
    }

    /**
     * URL of the traces endpoint.
     *
     * @return string
     */
    public function traces_url(): string {
        $endpoint = rtrim($this->endpoint, '/');
        return str_ends_with($endpoint, '/v1/traces') ? $endpoint : $endpoint . '/v1/traces';
    }
}
