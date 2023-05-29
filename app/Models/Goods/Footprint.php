<?php

namespace App\Models\Goods;

use App\Models\BaseModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Models\Goods\Footprint
 *
 * @property int $id
 * @property int $user_id 用户表的用户ID
 * @property int $goods_id 浏览商品ID
 * @property Carbon|null $add_time 创建时间
 * @property Carbon|null $update_time 更新时间
 * @property bool|null $deleted 逻辑删除
 * @method static Builder|Footprint newModelQuery()
 * @method static Builder|Footprint newQuery()
 * @method static Builder|Footprint query()
 * @method static Builder|Footprint whereAddTime($value)
 * @method static Builder|Footprint whereDeleted($value)
 * @method static Builder|Footprint whereGoodsId($value)
 * @method static Builder|Footprint whereId($value)
 * @method static Builder|Footprint whereUpdateTime($value)
 * @method static Builder|Footprint whereUserId($value)
 * @mixin Eloquent
 */
class Footprint extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'goods_id'
    ];

}
