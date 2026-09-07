<?php
declare(strict_types=1);

// PROVIDED — m3_cart contract tests. Do not modify.

final class M3CartTest extends ProjectTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::bootServer((int) (getenv('PROJ_PORT') ?: 8203));
    }

    public function test_requires_auth(): void
    {
        [$code] = $this->req('GET', '/api/cart');
        $this->assertSame(401, $code);
    }

    public function test_add_and_list_cart(): void
    {
        $this->loginUser('shopper1');
        [$c1, $b1] = $this->req('POST', '/api/cart', ['product_id' => 1, 'qty' => 2]);
        $this->assertSame(201, $c1);
        $this->assertSame(7.0, (float) $b1['subtotal']);
        [$c2, $b2] = $this->req('GET', '/api/cart');
        $this->assertSame(200, $c2);
        $this->assertCount(1, $b2['data']);
        $this->assertSame(7.0, (float) $b2['total']);
    }

    public function test_add_invalid_and_missing_product(): void
    {
        $this->loginUser('shopper2');
        [$c1] = $this->req('POST', '/api/cart', ['product_id' => 1, 'qty' => 0]);
        $this->assertSame(422, $c1);
        [$c2] = $this->req('POST', '/api/cart', ['product_id' => 9999, 'qty' => 1]);
        $this->assertSame(404, $c2);
    }

    public function test_update_and_delete_line(): void
    {
        $this->loginUser('shopper3');
        [, $added] = $this->req('POST', '/api/cart', ['product_id' => 2, 'qty' => 1]);
        $lineId = (int) $added['id'];
        [$c1, $b1] = $this->req('PATCH', "/api/cart/{$lineId}", ['qty' => 4]);
        $this->assertSame(200, $c1);
        $this->assertSame(4, (int) $b1['qty']);
        [$c2] = $this->req('DELETE', "/api/cart/{$lineId}");
        $this->assertSame(200, $c2);
        [$c3] = $this->req('DELETE', "/api/cart/{$lineId}");
        $this->assertSame(404, $c3);
    }
}
