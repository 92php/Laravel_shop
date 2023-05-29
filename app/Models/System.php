<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Models\System
 *
 * @property int $id
 * @property string $key_name 系统配置名
 * @property string $key_value 系统配置值
 * @property Carbon|null $add_time 创建时间
 * @property Carbon|null $update_time 更新时间
 * @property bool|null $deleted 逻辑删除
 * @method static Builder|System newModelQuery()
 * @method static Builder|System newQuery()
 * @method static Builder|System query()
 * @method static Builder|System whereAddTime($value)
 * @method static Builder|System whereDeleted($value)
 * @method static Builder|System whereId($value)
 * @method static Builder|System whereKeyName($value)
 * @method static Builder|System whereKeyValue($value)
 * @method static Builder|System whereUpdateTime($value)
 * @mixin Eloquent
 */
class System extends BaseModel
{
}
