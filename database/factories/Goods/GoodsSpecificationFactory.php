<?php

namespace Database\Factories\Goods;

use App\Models\Goods\GoodsSpecification;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoodsSpecificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GoodsSpecification::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            "goods_id" => 0,
            "specification" => '规格',
            "value" => '标准'
        ];
    }
}
