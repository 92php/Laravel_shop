<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Models\SearchHistory
 *
 * @property int $id
 * @property int $user_id 用户表的用户ID
 * @property string $keyword 搜索关键字
 * @property string $from 搜索来源，如pc、wx、app
 * @property Carbon|null $add_time 创建时间
 * @property Carbon|null $update_time 更新时间
 * @property bool|null $deleted 逻辑删除
 * @method static Builder|SearchHistory newModelQuery()
 * @method static Builder|SearchHistory newQuery()
 * @method static Builder|SearchHistory query()
 * @method static Builder|SearchHistory whereAddTime($value)
 * @method static Builder|SearchHistory whereDeleted($value)
 * @method static Builder|SearchHistory whereFrom($value)
 * @method static Builder|SearchHistory whereId($value)
 * @method static Builder|SearchHistory whereKeyword($value)
 * @method static Builder|SearchHistory whereUpdateTime($value)
 * @method static Builder|SearchHistory whereUserId($value)
 * @mixin Eloquent
 */
class SearchHistory extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'keyword',
        'from'
    ];
}
