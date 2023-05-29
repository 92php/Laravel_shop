<?php

namespace Tests\Feature;

use App\Models\Goods\GoodsProduct;
use App\Services\Goods\GoodsServices;
use App\Services\Order\CartServices;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CartTest extends TestCase
{
    use DatabaseTransactions;

    /** @var GoodsProduct $product */
    private $product;

    public function setUp(): void
    {
        parent::setUp();
        $this->product = GoodsProduct::factory()->create([
            'number' => 10
        ]);
    }

    public function testIndex()
    {
        $this->post('wx/cart/add', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 2
        ]);

        $resp = $this->get('wx/cart/index', []);
        $resp->assertJson([
            "errno" => 0, "errmsg" => "成功", "data" => [
                'cartList' => [
                    [
                        'goodsId' => $this->product->goods_id,
                        'productId' => $this->product->id,
                    ]
                ],
                'cartTotal' => [
                    "goodsCount" => 2,
                    "goodsAmount" => 1998.00,
                    "checkedGoodsCount" => 2,
                    "checkedGoodsAmount" => 1998.00,
                ]
            ]
        ]);

        $goods = GoodsServices::getInstance()->getGoods($this->product->goods_id);
        $goods->is_on_sale = false;
        $goods->save();

        $resp = $this->get('wx/cart/index', []);
        $resp->assertJson([
            "errno" => 0, "errmsg" => "成功", "data" => [
                'cartList' => [],
                'cartTotal' => [
                    "goodsCount" => 0,
                    "goodsAmount" => 0,
                    "checkedGoodsCount" => 0,
                    "checkedGoodsAmount" => 0,
                ]
            ]
        ]);

        $cart = CartServices::getInstance()->getCartProduct($this->user->id,
            $this->product->goods_id, $this->product->id);
        $this->assertNull($cart);
    }

    public function testFastadd()
    {
        $resp = $this->post('wx/cart/add', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 2
        ]);
        $resp->assertJson(["errno" => 0, "errmsg" => "成功", "data" => "2"]);

        $resp = $this->post('wx/cart/fastadd', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 5
        ]);

        $cart = CartServices::getInstance()->getCartProduct($this->user->id,
            $this->product->goods_id, $this->product->id);
        $this->assertEquals(5, $cart->number);

        $resp->assertJson(["errno" => 0, "errmsg" => "成功", 'data' => $cart->id]);
    }

    public function testAdd()
    {
        $resp = $this->post('wx/cart/add', [
            'goodsId' => 0,
            'productId' => 0,
            'number' => 1
        ]);
        $resp->assertJson(["errno" => 402]);

        $resp = $this->post('wx/cart/add', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 11
        ]);
        $resp->assertJson(["errno" => 711, "errmsg" => "库存不足"]);

        $resp = $this->post('wx/cart/add', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 2
        ]);
        $resp->assertJson(["errno" => 0, "errmsg" => "成功", "data" => "2"]);

        $resp = $this->post('wx/cart/add', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 3
        ]);
        $resp->assertJson(["errno" => 0, "errmsg" => "成功", "data" => "5"]);

        $cart = CartServices::getInstance()->getCartProduct($this->user->id,
            $this->product->goods_id, $this->product->id);
        $this->assertEquals(5, $cart->number);

        $resp = $this->post('wx/cart/add', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 6
        ]);
        $resp->assertJson(["errno" => 711, "errmsg" => "库存不足"]);
    }


    public function testUpdate()
    {
        $resp = $this->post('wx/cart/add', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 2
        ]);
        $resp->assertJson(["errno" => 0, "errmsg" => "成功", "data" => "2"]);

        $cart = CartServices::getInstance()->getCartProduct($this->user->id,
            $this->product->goods_id, $this->product->id);

        $resp = $this->post('wx/cart/update', [
            'id' => $cart->id,
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 6
        ]);
        $resp->assertJson(["errno" => 0, "errmsg" => "成功"]);

        $resp = $this->post('wx/cart/update', [
            'id' => $cart->id,
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 11
        ]);
        $resp->assertJson(['errno' => 711, 'errmsg' => '库存不足']);

        $resp = $this->post('wx/cart/update', [
            'id' => $cart->id,
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 0
        ]);
        $resp->assertJson(['errno' => 402]);
    }

    public function testDelete()
    {
        $resp = $this->post('wx/cart/add', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 2
        ]);
        $resp->assertJson(["errno" => 0, "errmsg" => "成功", "data" => "2"]);

        $cart = CartServices::getInstance()->getCartProduct($this->user->id,
            $this->product->goods_id, $this->product->id);
        $this->assertNotNull($cart);

        $resp = $this->post('wx/cart/delete', [
            'productIds' => [$this->product->id],
        ]);

        $cart = CartServices::getInstance()->getCartProduct($this->user->id,
            $this->product->goods_id, $this->product->id);
        $this->assertNull($cart);

        $resp = $this->post('wx/cart/delete', [
            'productIds' => [],
        ]);
        $resp->assertJson(["errno" => 402]);
    }

    public function testChecked()
    {
        $resp = $this->post('wx/cart/add', [
            'goodsId' => $this->product->goods_id,
            'productId' => $this->product->id,
            'number' => 2
        ]);
        $resp->assertJson(["errno" => 0, "errmsg" => "成功", "data" => "2"]);

        $cart = CartServices::getInstance()->getCartProduct($this->user->id,
            $this->product->goods_id, $this->product->id);

        $this->assertTrue($cart->checked);

        $resp = $this->post('wx/cart/checked', [
            'productIds' => [$this->product->id],
            'isChecked' => 0
        ]);

        $cart = CartServices::getInstance()->getCartProduct($this->user->id,
            $this->product->goods_id, $this->product->id);
        $this->assertFalse($cart->checked);

        $resp = $this->post('wx/cart/checked', [
            'productIds' => [$this->product->id],
            'isChecked' => 1
        ]);

        $cart = CartServices::getInstance()->getCartProduct($this->user->id,
            $this->product->goods_id, $this->product->id);
        $this->assertTrue($cart->checked);
    }


    public function testCheckout()
    {
        $this->assertLitemallApi('wx/cart/checkout');
        $this->assertLitemallApi('wx/cart/checkout', 'get', [
            "cartId" => 0,
            "addressId" => 0,
            "couponId" => 0,
            "userCouponId" => 0,
            "grouponRulesId" => 0,
        ], ['data.userCouponId']);

        $this->assertLitemallApi('wx/cart/checkout', 'get', [
            "cartId" => 193,
            "addressId" => 2,
            "couponId" => 2,
            "userCouponId" => 9,
            "grouponRulesId" => 0,
        ]);
        $this->assertLitemallApi('wx/cart/checkout', 'get', [
            "cartId" => 0,
            "addressId" => 0,
            "couponId" => 1,
            "userCouponId" => 9,
            "grouponRulesId" => 0,
        ]);
        $this->assertLitemallApi('wx/cart/checkout', 'get', [
            "cartId" => 0,
            "addressId" => 0,
            "couponId" => -1,
            "userCouponId" => 0,
            "grouponRulesId" => 0,
        ]);
    }
}
