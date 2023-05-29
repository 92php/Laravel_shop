<?php

namespace App\Models\Goods;

use App\Models\BaseModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Models\Goods\Issue
 *
 * @property int $id
 * @property string|null $question 问题标题
 * @property string|null $answer 问题答案
 * @property Carbon|null $add_time 创建时间
 * @property Carbon|null $update_time 更新时间
 * @property bool|null $deleted 逻辑删除
 * @method static Builder|Issue newModelQuery()
 * @method static Builder|Issue newQuery()
 * @method static Builder|Issue query()
 * @method static Builder|Issue whereAddTime($value)
 * @method static Builder|Issue whereAnswer($value)
 * @method static Builder|Issue whereDeleted($value)
 * @method static Builder|Issue whereId($value)
 * @method static Builder|Issue whereQuestion($value)
 * @method static Builder|Issue whereUpdateTime($value)
 * @mixin Eloquent
 */
class Issue extends BaseModel
{
}
