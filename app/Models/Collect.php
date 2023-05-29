<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Models\Collect
 *
 * @property int $id
 * @property int $user_id 用户表的用户ID
 * @property int $value_id 如果type=0，则是商品ID；如果type=1，则是专题ID
 * @property int $type 收藏类型，如果type=0，则是商品ID；如果type=1，则是专题ID
 * @property Carbon|null $add_time 创建时间
 * @property Carbon|null $update_time 更新时间
 * @property bool|null $deleted 逻辑删除
 * @method static Builder|Collect newModelQuery()
 * @method static Builder|Collect newQuery()
 * @method static Builder|Collect query()
 * @method static Builder|Collect whereAddTime($value)
 * @method static Builder|Collect whereDeleted($value)
 * @method static Builder|Collect whereId($value)
 * @method static Builder|Collect whereType($value)
 * @method static Builder|Collect whereUpdateTime($value)
 * @method static Builder|Collect whereUserId($value)
 * @method static Builder|Collect whereValueId($value)
 * @mixin Eloquent
 */
class Collect extends BaseModel
{
}
