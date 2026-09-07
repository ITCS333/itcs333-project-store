<?php
declare(strict_types=1);

// PROVIDED — shared test bootstrap for all project suites. Do not modify.
use PHPUnit\Framework\TestCase;

abstract class ProjectTestCase extends TestCase
{
    protected static string $base;
    protected static $server;
    protected static string $dbPath;
    protected static int $port = 0;
    protected array $cookies = [];

    /** Boot a fresh dev server over public/ with a fresh seeded sqlite DB. */
    protected static function bootServer(int $port): void
    {
        self::$base = "http://127.0.0.1:{$port}";
        self::$dbPath = sys_get_temp_dir() . '/itcs333_project_test_' . uniqid() . '.sqlite';
        // __DIR__ = <project>/tests  →  project root is dirname(__DIR__).
        $projectRoot = dirname(__DIR__);
        $docroot = $projectRoot . '/public';
        $router = $docroot . '/api.php';

        // Start from a pristine DB file; db() bootstraps it on first use.
        @unlink(self::$dbPath);
        @unlink(self::$dbPath . '-wal');
        @unlink(self::$dbPath . '-shm');

        $env = getenv(); // full environment array
        $env['DB_DRIVER'] = 'sqlite';
        $env['DB_PATH'] = self::$dbPath;
        $env['DB_RESET'] = ''; // reset handled by deleting the file above

        // setsid detaches the server into a new session so it survives the
        // parent shell. DB_RESET is NOT passed (reset already done by unlink above).
        $log = self::$dbPath . '.server.log';
        $cmd = sprintf(
            'DB_DRIVER=sqlite DB_PATH=%s setsid php -S %s -t %s %s >%s 2>&1 < /dev/null &',
            escapeshellarg(self::$dbPath),
            escapeshellarg("127.0.0.1:{$port}"),
            escapeshellarg($docroot),
            escapeshellarg($router),
            escapeshellarg($log)
        );
        shell_exec($cmd);
        self::$port = $port; // server detached in new session; killed via pkill on port
        // Probe a non-module endpoint (the router's 404 for an unknown area) so
        // the readiness check is independent of which module is marked missing.
        for ($i = 0; $i < 100; $i++) {
            $c = @file_get_contents(self::$base . '/index.html');
            if ($c !== false) return;
            usleep(100000);
        }
        $err = @file_get_contents(self::$dbPath . '.server.log');
        throw new \RuntimeException('dev server did not start on port ' . $port . "\n--- server log ---\n" . $err);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$port > 0) { @exec('pkill -f "php -S 127.0.0.1:' . self::$port . '" 2>/dev/null'); self::$port = 0; }
        @unlink(self::$dbPath);
        @unlink(self::$dbPath . '-wal');
        @unlink(self::$dbPath . '-shm');
    }

    protected function setUp(): void
    {
        $this->cookies = [];
    }

    /** HTTP request with a cookie jar. Returns [status, array-json, raw-body, location]. */
    protected function req(string $method, string $path, ?array $json = null): array
    {
        $header = "User-Agent: project-test\r\nContent-Type: application/json\r\n";
        foreach ($this->cookies as $c) $header .= "Cookie: {$c}\r\n";
        $opt = ['http' => ['method' => $method, 'header' => $header, 'ignore_errors' => true, 'follow_location' => 0]];
        if ($json !== null) $opt['http']['content'] = json_encode($json);
        $ctx = stream_context_create($opt);
        $body = @file_get_contents(self::$base . $path, false, $ctx);
        $code = 0; $location = '';
        foreach ($http_response_header ?? [] as $h) {
            if ($code === 0 && preg_match('#\s(\d{3})\s#', $h, $m)) $code = (int) $m[1];
            if (stripos($h, 'Set-Cookie:') === 0) {
                $pair = trim(explode(';', substr($h, 11))[0]);
                $name = explode('=', $pair)[0];
                $this->cookies = array_filter($this->cookies, fn($c) => !str_starts_with($c, $name . '='));
                if (strpos($pair, 'delete') === false) $this->cookies[] = $pair;
            }
            if (stripos($h, 'Location:') === 0) $location = trim(substr($h, 9));
        }
        return [$code, json_decode((string) $body, true), (string) $body, $location];
    }

    /** Register + login a normal user; returns their id. Username is made unique per call. */
    protected function loginUser(string $username, string $password = 'password123'): int
    {
        $u = $username . '_' . substr(uniqid(), -6);
        [$rc] = $this->req('POST', '/api/auth/register', ['username' => $u, 'password' => $password]);
        $this->assertSame(201, $rc, 'register should succeed');
        [$lc, $lb] = $this->req('POST', '/api/auth/login', ['username' => $u, 'password' => $password]);
        $this->assertSame(200, $lc, 'login should succeed');
        return (int) $lb['id'];
    }

    /** Login as the seeded admin (password 'password'). */
    protected function loginAdmin(): void
    {
        [$lc, $lb] = $this->req('POST', '/api/auth/login', ['username' => 'admin', 'password' => 'password']);
        $this->assertSame(200, $lc, 'admin login should succeed');
        $this->assertSame(1, (int) $lb['is_admin']);
    }
}
