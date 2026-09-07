<?php
declare(strict_types=1);

// PROVIDED — m4_orders contract tests. Do not modify.

final class M4OrdersTest extends ProjectTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::bootServer((int) (getenv('PROJ_PORT') ?: 8204));
    }

    public function test_checkout_empty_cart(): void
    {
        $this->loginUser('buyer1');
        [$code] = $this->req('POST', '/api/orders');
        $this->assertSame(400, $code);
    }

    public function test_checkout_creates_order_and_clears_cart(): void
    {
        $this->loginUser('buyer2');
        $this->req('POST', '/api/cart', ['product_id' => 1, 'qty' => 2]);
        $this->req('POST', '/api/cart', ['product_id' => 2, 'qty' => 1]);
        [$code, $order] = $this->req('POST', '/api/orders');
        $this->assertSame(201, $code);
        $this->assertSame(8.25, round((float) $order['total'], 2));
        $this->assertCount(2, $order['items']);
        // cart cleared
        [, $cart] = $this->req('GET', '/api/cart');
        $this->assertCount(0, $cart['data']);
        // stock decremented (Notebook 50 - 2 = 48)
        [, $prod] = $this->req('GET', '/api/catalog/products/1');
        $this->assertSame(48, (int) $prod['stock']);
    }

    public function test_order_history_and_detail(): void
    {
        $this->loginUser('buyer3');
        $this->req('POST', '/api/cart', ['product_id' => 3, 'qty' => 1]);
        [, $order] = $this->req('POST', '/api/orders');
        $oid = (int) $order['id'];
        [$c1, $list] = $this->req('GET', '/api/orders');
        $this->assertSame(200, $c1);
        $this->assertGreaterThanOrEqual(1, count($list['data']));
        [$c2, $detail] = $this->req('GET', "/api/orders/{$oid}");
        $this->assertSame(200, $c2);
        $this->assertSame($oid, (int) $detail['id']);
        $this->assertArrayHasKey('items', $detail);
    }

    public function test_requires_auth(): void
    {
        [$code] = $this->req('GET', '/api/orders');
        $this->assertSame(401, $code);
    }
}
