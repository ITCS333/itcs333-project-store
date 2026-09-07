<?php
declare(strict_types=1);

// PROVIDED — m1_auth contract tests. Do not modify.

final class M1AuthTest extends ProjectTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::bootServer((int) (getenv('PROJ_PORT') ?: 8201));
    }

    public function test_register_creates_user(): void
    {
        [$code, $body] = $this->req('POST', '/api/auth/register', ['username' => 'alice', 'password' => 'pw123456']);
        $this->assertSame(201, $code);
        $this->assertArrayHasKey('id', $body);
        $this->assertSame('alice', $body['username']);
        // password must be hashed in DB
        $pdo = new PDO('sqlite:' . self::$dbPath);
        $row = $pdo->query("SELECT password_hash FROM users WHERE username='alice'")->fetch(PDO::FETCH_ASSOC);
        $this->assertStringStartsWith('$2y$', $row['password_hash']);
    }

    public function test_register_duplicate_and_validation(): void
    {
        $u = 'bob_' . uniqid();
        $this->req('POST', '/api/auth/register', ['username' => $u, 'password' => 'pw123456']);
        [$c1] = $this->req('POST', '/api/auth/register', ['username' => $u, 'password' => 'x']);
        $this->assertSame(409, $c1);
        [$c2, $b2] = $this->req('POST', '/api/auth/register', ['username' => '', 'password' => '']);
        $this->assertSame(422, $c2);
        $this->assertArrayHasKey('fields', $b2);
    }

    public function test_login_logout_me(): void
    {
        $u = 'carol_' . uniqid();
        $this->req('POST', '/api/auth/register', ['username' => $u, 'password' => 'pw123456']);
        [$lc, $lb] = $this->req('POST', '/api/auth/login', ['username' => $u, 'password' => 'pw123456']);
        $this->assertSame(200, $lc);
        $this->assertSame($u, $lb['username']);
        [$mc, $mb] = $this->req('GET', '/api/auth/me');
        $this->assertSame(200, $mc);
        $this->assertSame($u, $mb['username']);
        $this->req('POST', '/api/auth/logout');
        [$mc2] = $this->req('GET', '/api/auth/me');
        $this->assertSame(401, $mc2);
    }

    public function test_login_wrong_credentials(): void
    {
        $this->req('POST', '/api/auth/register', ['username' => 'dave', 'password' => 'pw123456']);
        [$c] = $this->req('POST', '/api/auth/login', ['username' => 'dave', 'password' => 'wrong']);
        $this->assertSame(401, $c);
    }
}
