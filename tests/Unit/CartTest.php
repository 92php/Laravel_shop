<?php

namespace Tests\Unit;

use App\Models\Goods\GoodsProduct;
use App\Models\Promotion\GrouponRules;
use App\Services\Order\CartServices;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CartTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetCartPriceCutGrouponSimple()
    {
        /** @var GoodsProduct $product1 */
        $product1 = GoodsProduct::factory()->create(['price' => 11.3]);
        /** @var GoodsProduct $product2 */
        $product2 = GoodsProduct::factory()->create(['price' => 20.56]);
        /** @var GoodsProduct $product3 */
        $product3 = GoodsProduct::factory()->create(['price' => 10.6]);
        CartServices::getInstance()->add($this->user->id, $product1->goods_id, $product1->id, 2);
        CartServices::getInstance()->add($this->user->id, $product2->goods_id, $product2->id, 1);
        CartServices::getInstance()->add($this->user->id, $product3->goods_id, $product3->id, 3);
        CartServices::getInstance()->updateChecked($this->user->id, [$product3->id], false);

        $checkedGoodsList = CartServices::getInstance()->getCheckedCartList($this->user->id);
        $grouponPrice = 0;
        $checkedGoodsPrice = CartServices::getInstance()->getCartPriceCutGroupon($checkedGoodsList, null,
            $grouponPrice);
        $this->assertEquals(43.16, $checkedGoodsPrice);
    }

    public function testGetCartPriceCutGrouponGroupon()
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
        $grouponPrice = 0;
        $rulesId = GrouponRules::whereGoodsId($product2->goods_id)->first()->id ?? null;
        $checkedGoodsPrice = CartServices::getInstance()->getCartPriceCutGroupon($checkedGoodsList, $rulesId,
            $grouponPrice);
        $this->assertEquals(129.6, $checkedGoodsPrice);
    }

}
