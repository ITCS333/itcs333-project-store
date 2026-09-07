<?php
declare(strict_types=1);

// PROVIDED — m2_catalog contract tests. Do not modify.

final class M2CatalogTest extends ProjectTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::bootServer((int) (getenv('PROJ_PORT') ?: 8202));
    }

    public function test_list_products(): void
    {
        [$code, $body] = $this->req('GET', '/api/catalog/products');
        $this->assertSame(200, $code);
        $this->assertArrayHasKey('data', $body);
        $this->assertGreaterThanOrEqual(5, count($body['data']));
        $this->assertArrayHasKey('name', $body['data'][0]);
        $this->assertArrayHasKey('price', $body['data'][0]);
        $this->assertArrayHasKey('stock', $body['data'][0]);
    }

    public function test_get_product_and_404(): void
    {
        [$code, $body] = $this->req('GET', '/api/catalog/products/1');
        $this->assertSame(200, $code);
        $this->assertSame('Notebook', $body['name']);
        [$c2] = $this->req('GET', '/api/catalog/products/9999');
        $this->assertSame(404, $c2);
    }

    public function test_categories(): void
    {
        [$code, $body] = $this->req('GET', '/api/catalog/categories');
        $this->assertSame(200, $code);
        $this->assertGreaterThanOrEqual(3, count($body['data']));
    }

    public function test_filter_by_category_and_search(): void
    {
        [$c1, $b1] = $this->req('GET', '/api/catalog/products?category_id=2');
        $this->assertSame(200, $c1);
        foreach ($b1['data'] as $p) $this->assertSame(2, (int) $p['category_id']);
        [$c2, $b2] = $this->req('GET', '/api/catalog/products?q=notebook');
        $this->assertSame(200, $c2);
        $this->assertGreaterThanOrEqual(1, count($b2['data']));
    }
}
