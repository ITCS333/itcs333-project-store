<?php
declare(strict_types=1);

// PROVIDED — integration tests: all REAL modules assembled (no stubs). Do not modify.

final class IntegrationTest extends ProjectTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::bootServer((int) (getenv('PROJ_PORT') ?: 8210));
    }

    public function test_full_shopping_flow(): void
    {
        // register → browse → add to cart → checkout → order history → admin sees sale
        $this->loginUser('flowuser');

        // browse catalog
        [$cc, $catalog] = $this->req('GET', '/api/catalog/products');
        $this->assertSame(200, $cc);
        $this->assertGreaterThanOrEqual(5, count($catalog['data']));

        // add two items
        $this->req('POST', '/api/cart', ['product_id' => 1, 'qty' => 2]); // 2 * 3.5 = 7.0
        $this->req('POST', '/api/cart', ['product_id' => 3, 'qty' => 1]); // 1 * 7.0 = 7.0

        // checkout
        [$oc, $order] = $this->req('POST', '/api/orders');
        $this->assertSame(201, $oc);
        $this->assertSame(14.0, round((float) $order['total'], 2));

        // cart cleared
        [, $cart] = $this->req('GET', '/api/cart');
        $this->assertCount(0, $cart['data']);

        // order in history
        [, $history] = $this->req('GET', '/api/orders');
        $this->assertGreaterThanOrEqual(1, count($history['data']));

        // stock decremented
        [, $prod] = $this->req('GET', '/api/catalog/products/1');
        $this->assertSame(48, (int) $prod['stock']);

        // admin sees the sale
        $this->cookies = [];
        $this->loginAdmin();
        [$sc, $report] = $this->req('GET', '/api/admin/reports/sales');
        $this->assertSame(200, $sc);
        $this->assertGreaterThanOrEqual(14.0, (float) $report['revenue']);
    }

    public function test_admin_create_product_appears_in_catalog(): void
    {
        $this->loginAdmin();
        [$pc, $created] = $this->req('POST', '/api/admin/products', ['category_id' => 2, 'name' => 'Webcam', 'price' => 15.0, 'stock' => 20]);
        $this->assertSame(201, $pc);
        $id = (int) $created['id'];
        [, $prod] = $this->req('GET', "/api/catalog/products/{$id}");
        $this->assertSame('Webcam', $prod['name']);
    }
}
