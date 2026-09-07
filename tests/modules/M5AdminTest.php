<?php
declare(strict_types=1);

// PROVIDED — m5_admin contract tests. Do not modify.

final class M5AdminTest extends ProjectTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::bootServer((int) (getenv('PROJ_PORT') ?: 8205));
    }

    public function test_non_admin_forbidden(): void
    {
        $this->loginUser('notadmin');
        [$code] = $this->req('POST', '/api/admin/products', ['category_id' => 1, 'name' => 'X', 'price' => 1, 'stock' => 1]);
        $this->assertSame(403, $code);
    }

    public function test_admin_product_crud(): void
    {
        $this->loginAdmin();
        [$c1, $created] = $this->req('POST', '/api/admin/products', ['category_id' => 1, 'name' => 'Marker', 'price' => 1.5, 'stock' => 60]);
        $this->assertSame(201, $c1);
        $id = (int) $created['id'];
        [$c2, $upd] = $this->req('PUT', "/api/admin/products/{$id}", ['category_id' => 1, 'name' => 'Marker Pro', 'price' => 2.5, 'stock' => 55]);
        $this->assertSame(200, $c2);
        $this->assertSame('Marker Pro', $upd['name']);
        [$c3] = $this->req('DELETE', "/api/admin/products/{$id}");
        $this->assertSame(200, $c3);
        [$c4] = $this->req('DELETE', "/api/admin/products/{$id}");
        $this->assertSame(404, $c4);
    }

    public function test_admin_validation(): void
    {
        $this->loginAdmin();
        [$code, $body] = $this->req('POST', '/api/admin/products', ['category_id' => 1, 'name' => '', 'price' => -1]);
        $this->assertSame(422, $code);
        $this->assertArrayHasKey('fields', $body);
    }

    public function test_sales_report(): void
    {
        // place an order as a normal user first
        $this->loginUser('reportbuyer');
        $this->req('POST', '/api/cart', ['product_id' => 5, 'qty' => 2]);
        $this->req('POST', '/api/orders');
        // fresh session as admin
        $this->cookies = [];
        $this->loginAdmin();
        [$code, $report] = $this->req('GET', '/api/admin/reports/sales');
        $this->assertSame(200, $code);
        $this->assertArrayHasKey('revenue', $report);
        $this->assertGreaterThan(0, (float) $report['revenue']);
    }
}
