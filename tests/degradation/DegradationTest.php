<?php
declare(strict_types=1);

// PROVIDED — degradation tests: a missing module must degrade gracefully (503, not a crash).
// Do not modify. Run after marking a module missing: tests/use_stubs.sh --set m2_catalog=missing

final class DegradationTest extends ProjectTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::bootServer((int) (getenv('PROJ_PORT') ?: 8211));
    }

    /** Modules currently marked 'missing' in tests/.active_modules. */
    private function missingAreas(): array
    {
        $map = [];
        $file = __DIR__ . '/../.active_modules';
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (strpos($line, '=') !== false) {
                    [$k, $v] = array_map('trim', explode('=', $line, 2));
                    $map[$k] = $v;
                }
            }
        }
        $area = ['m1_auth' => 'auth', 'm2_catalog' => 'catalog', 'm3_cart' => 'cart', 'm4_orders' => 'orders', 'm5_admin' => 'admin'];
        $out = [];
        foreach ($map as $mod => $mode) {
            if ($mode === 'missing' && isset($area[$mod])) $out[] = $area[$mod];
        }
        return $out;
    }

    public function test_missing_module_returns_clean_error_not_crash(): void
    {
        $missing = $this->missingAreas();
        if ($missing === []) {
            $this->markTestSkipped('no module marked missing');
        }
        foreach ($missing as $area) {
            // Any endpoint under a missing module must respond 404 or 503 with a
            // JSON error envelope — never a fatal/blank (which would break the UI).
            [$code, $body] = $this->req('GET', "/api/{$area}/anything");
            $this->assertContains($code, [404, 503], "{$area} should return 404/503 when missing");
            if ($code === 503) {
                $this->assertIsArray($body);
                $this->assertArrayHasKey('error', $body);
            }
        }
    }

    public function test_frontend_graceful_banner_helper_present(): void
    {
        // The shared frontend must include a graceful-degradation helper that
        // renders an inline banner when a dependency endpoint errors.
        $html = (string) @file_get_contents(self::$base . '/index.html');
        $js = (string) @file_get_contents(self::$base . '/app.js');
        $combined = $html . $js;
        $this->assertTrue(
            str_contains($combined, 'showBanner')
            || str_contains($combined, 'error-banner')
            || str_contains($combined, 'MODULE_UNAVAILABLE')
            || str_contains($combined, 'catch'),
            'frontend should include a graceful-degradation banner/helper'
        );
    }
}
