<?php

namespace Database\Factories\Goods;

use App\Models\Goods\Goods;
use App\Models\Goods\GoodsProduct;
use App\Models\Goods\GoodsSpecification;
use App\Models\Promotion\GrouponRules;
use App\Services\Goods\GoodsServices;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoodsProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GoodsProduct::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $goods = Goods::factory()->create();
        $spec = GoodsSpecification::factory()->create([
            "goods_id" => $goods->id
        ]);
        return [
            "goods_id" => $goods->id,
            "specifications" => [$spec->value],
            "price" => 999,
            "number" => 100,
            "url" => $this->faker->imageUrl(),
        ];
    }


    public function groupon()
    {
        return $this->state(function () {
            return [];
        })->afterCreating(function (GoodsProduct $product) {
            $goods = GoodsServices::getInstance()->getGoods($product->goods_id);
            GrouponRules::factory()->create([
                'goods_id' => $product->goods_id,
                'goods_name' => $goods->name,
                'pic_url' => $goods->pic_url,
                'discount' => 1,
            ]);
        });
    }
}
