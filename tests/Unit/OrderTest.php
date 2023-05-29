<?php

namespace Tests\Unit;

use App\Enums\OrderEnums;
use App\Inputs\OrderSubmitInput;
use App\Models\Goods\GoodsProduct;
use App\Models\Order\Order;
use App\Models\Order\OrderGoods;
use App\Models\Promotion\GrouponRules;
use App\Models\User\User;
use App\Services\Goods\GoodsServices;
use App\Services\Order\CartServices;
use App\Services\Order\OrderServices;
use App\Services\User\AddressServices;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use DatabaseTransactions;

    public function testReduceStock()
    {
        /** @var GoodsProduct $product1 */
        $product1 = GoodsProduct::factory()->create(['price' => 11.3]);
        /** @var GoodsProduct $product2 */
        $product2 = GoodsProduct::factory()->groupon()->create(['price' => 20.56]);
        /** @var GoodsProduct $product3 */
        $product3 = GoodsProduct::factory()->create(['price' => 10.6]);
        CartServices::getInstance()->add($this->user->id, $product1->goods_id, $product1->id, 2);
        CartServices::getInstance()->add($this->user->id, $product2->goods_id, $product2->id, 5);
        CartServices::getInstance()->add($this->user->id, $product3->goods_id, $product3->id, 3);
        CartServices::getInstance()->updateChecked($this->user->id, [$product1->id], false);
        // 19.56*5+10.6*3=129.6
        $checkedGoodsList = CartServices::getInstance()->getCheckedCartList($this->user->id);

        OrderServices::getInstance()->reduceProductsStock($checkedGoodsList);

        $this->assertEquals($product2->number - 5, $product2->refresh()->number);
        $this->assertEquals($product3->number - 3, $product3->refresh()->number);
    }

    public function testSubmit()
    {
        $this->user = User::factory()->AddressDefault()->create();
        $address = AddressServices::getInstance()->getDefaultAddress($this->user->id);

        /** @var GoodsProduct $product1 */
        $product1 = GoodsProduct::factory()->create(['price' => 11.3]);
        /** @var GoodsProduct $product2 */
        $product2 = GoodsProduct::factory()->groupon()->create(['price' => 20.56]);
        /** @var GoodsProduct $product3 */
        $product3 = GoodsProduct::factory()->create(['price' => 10.6]);
        CartServices::getInstance()->add($this->user->id, $product1->goods_id, $product1->id, 2);
        CartServices::getInstance()->add($this->user->id, $product2->goods_id, $product2->id, 5);
        CartServices::getInstance()->add($this->user->id, $product3->goods_id, $product3->id, 3);
        CartServices::getInstance()->updateChecked($this->user->id, [$product1->id], false);
        // 19.56*5+10.6*3=129.6
        $checkedGoodsList = CartServices::getInstance()->getCheckedCartList($this->user->id);
        $grouponPrice = 0;
        $rulesId = GrouponRules::whereGoodsId($product2->goods_id)->first()->id ?? null;
        $checkedGoodsPrice = CartServices::getInstance()->getCartPriceCutGroupon($checkedGoodsList, $rulesId,
            $grouponPrice);
        $this->assertEquals(129.6, $checkedGoodsPrice);

        $input = OrderSubmitInput::new([
            'addressId' => $address->id,
            'cartId' => 0,
            'couponId' => 0,
            'grouponRulesId' => $rulesId,
            'message' => '备注'
        ]);
        $order = OrderServices::getInstance()->submit($this->user->id, $input);

        $this->assertNotEmpty($order->id);
        $this->assertEquals($checkedGoodsPrice, $order->goods_price);
        $this->assertEquals($checkedGoodsPrice, $order->actual_price);
        $this->assertEquals($checkedGoodsPrice, $order->order_price);
        $this->assertEquals($grouponPrice, $order->groupon_price);
        $this->assertEquals('备注', $order->message);

        $list = OrderGoods::whereOrderId($order->id)->get()->toArray();
        $this->assertEquals(2, count($list));

        $productIds = CartServices::getInstance()->getCartList($this->user->id)->pluck('product_id')->toArray();
        $this->assertEquals([$product1->id], $productIds);
    }

    private function getOrder()
    {
        $this->user = User::factory()->AddressDefault()->create();
        $address = AddressServices::getInstance()->getDefaultAddress($this->user->id);

        /** @var GoodsProduct $product1 */
        $product1 = GoodsProduct::factory()->create(['price' => 11.3]);
        /** @var GoodsProduct $product2 */
        $product2 = GoodsProduct::factory()->groupon()->create(['price' => 20.56]);
        /** @var GoodsProduct $product3 */
        $product3 = GoodsProduct::factory()->create(['price' => 10.6]);
        CartServices::getInstance()->add($this->user->id, $product1->goods_id, $product1->id, 2);
        CartServices::getInstance()->add($this->user->id, $product2->goods_id, $product2->id, 5);
        CartServices::getInstance()->add($this->user->id, $product3->goods_id, $product3->id, 3);
        CartServices::getInstance()->updateChecked($this->user->id, [$product1->id], false);
        $rulesId = GrouponRules::whereGoodsId($product2->goods_id)->first()->id ?? null;
        $input = OrderSubmitInput::new([
            'addressId' => $address->id,
            'cartId' => 0,
            'couponId' => 0,
            'grouponRulesId' => $rulesId,
            'message' => '备注'
        ]);
        return OrderServices::getInstance()->submit($this->user->id, $input);
    }

    public function testCancel()
    {
        $order = $this->getOrder();
        OrderServices::getInstance()->userCancel($this->user->id, $order->id);
        $this->assertEquals(OrderEnums::STATUS_CANCEL, $order->refresh()->order_status);
        $goodsList = OrderServices::getInstance()->getOrderGoodsList($order->id);
        $productIds = $goodsList->pluck('product_id')->toArray();
        $products = GoodsServices::getInstance()->getGoodsProductsByIds($productIds);
        $this->assertEquals([100, 100], $products->pluck('number')->toArray());
    }

    public function testCas()
    {
        $user = $this->user->refresh();
        $user->nickname = 'test1';
        $user->mobile = '15000000000';
        $is = $user->cas();
        $this->assertEquals(1, $is);
        $this->assertEquals('test1', User::find($this->user->id)->nickname);
        User::query()->where('id', $this->user->id)->update(['nickname' => 'test2']);
        $is = $user->cas();
        $this->assertEquals(0, $is);
        $this->assertEquals('test2', User::find($this->user->id)->nickname);
        $user->save();
    }

    public function testBaseProcess()
    {
        $order = $this->getOrder()->refresh();
        OrderServices::getInstance()->payOrder($order, 'payid_test');
        $this->assertEquals(OrderEnums::STATUS_PAY, $order->refresh()->order_status);
        $this->assertEquals('payid_test', $order->pay_id);

        $shipSn = '1234567';
        $shipChannel = 'shunfeng';
        OrderServices::getInstance()->ship($this->user->id, $order->id, $shipSn, $shipChannel);
        $order->refresh();
        $this->assertEquals(OrderEnums::STATUS_SHIP, $order->order_status);
        $this->assertEquals($shipSn, $order->ship_sn);
        $this->assertEquals($shipChannel, $order->ship_channel);

        OrderServices::getInstance()->confirm($this->user->id, $order->id);
        $order->refresh();
        $this->assertEquals(2, $order->comments);
        $this->assertEquals(OrderEnums::STATUS_CONFIRM, $order->order_status);

        OrderServices::getInstance()->delete($this->user->id, $order->id);
        $this->assertNull(Order::find($order->id));
    }

    public function testRefundProcess()
    {
        $order = $this->getOrder()->refresh();
        OrderServices::getInstance()->payOrder($order, 'payid_test');
        $this->assertEquals(OrderEnums::STATUS_PAY, $order->refresh()->order_status);
        $this->assertEquals('payid_test', $order->pay_id);

        OrderServices::getInstance()->refund($this->user->id, $order->id);
        $order->refresh();
        $this->assertEquals(OrderEnums::STATUS_REFUND, $order->order_status);

        OrderServices::getInstance()->agreeRefund($order->refresh(), '微信退款接口', '1234567');
        $order->refresh();
        $this->assertEquals(OrderEnums::STATUS_REFUND_CONFIRM, $order->order_status);
        $this->assertEquals('微信退款接口', $order->refund_type);
        $this->assertEquals('1234567', $order->refund_content);

        OrderServices::getInstance()->delete($this->user->id, $order->id);
        $this->assertNull(Order::find($order->id));
    }

    public function testOrderStatusTrait()
    {
        $order = $this->getOrder();
        $this->assertEquals(true, $order->isCreateStatus());
        $this->assertEquals(false, $order->isCancelStatus());
        $this->assertEquals(false, $order->isPayStatus());

        $this->assertEquals(true, $order->canCancelHandle());
        $this->assertEquals(true, $order->canPayHandle());
        $this->assertEquals(false, $order->canDeleteHandle());
        $this->assertEquals(false, $order->canConfirmHandle());
    }
}
