<?php

namespace Database\Factories\Promotion;

use App\Models\Promotion\GrouponRules;
use Illuminate\Database\Eloquent\Factories\Factory;

class GrouponRulesFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GrouponRules::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'goods_id' => 0,
            'goods_name' => '',
            'pic_url' => '',
            'discount' => 0,
            'discount_member' => 2,
            'expire_time' => now()->addDays(10)->toDateTimeString(),
            'status' => 0,
        ];
    }
}
