<?php

namespace App\Models;

use Carbon\Carbon;
use Closure;
use DateTimeInterface;
use Eloquent;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

/**
 * Class BaseModel
 *
 * @package App\Models
 * @method static Builder|BaseModel newModelQuery()
 * @method static Builder|BaseModel newQuery()
 * @method static Builder|BaseModel query()
 * @mixin Eloquent
 */
class BaseModel extends Model
{
    use BooleanSoftDeletes;
    use HasFactory;

    public const CREATED_AT = 'add_time';

    public const UPDATED_AT = 'update_time';

    public $defaultCasts = ['deleted' => 'boolean'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        parent::mergeCasts($this->defaultCasts);
    }

    //静态new方法
    public static function new()
    {
        //return new self(); //这样是new BaseModel
        return new static();
    }

    //类名转化成表名
    public function getTable()
    {
        return $this->table ?? Str::snake(class_basename($this));
    }

    public function toArray()
    {
        $items = parent::toArray();
        //去除字段值为null的字段
        $items = array_filter($items, function ($item) {
            return !is_null($item);
        });
        $keys = array_keys($items);
        $keys = array_map(function ($key) {
            return lcfirst(Str::studly($key));
        }, $keys);
        $values = array_values($items);
        return array_combine($keys, $values);
    }

    public function serializeDate(DateTimeInterface $date)
    {
        return Carbon::instance($date)->toDateTimeString();
    }


    /**
     * 乐观锁更新 compare and save  先比较后更新
     * @return int
     * @throws Throwable
     */
    public function cas()
    {
        //如果更新的字段不存在
        throw_if(!$this->exists, Exception::class, 'model not exists when cas!');

        $dirty = $this->getDirty(); //获取对像中那些值被修改过

        if (empty($dirty)) {
            return 0;
        }

        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
            $dirty = $this->getDirty();
        }

        $diff = array_diff(array_keys($dirty), array_keys($this->original));
        //未获取到的字段，是不能更新的
        throw_if(!empty($diff), Exception::class, 'key ['.implode(',', $diff).'] not exists when cas!');

        //casing事件  拦截作用
        if ($this->fireModelEvent('casing') === false) {
            return 0;
        }

        //去除自动带上的deleted=0条件
        $query = $this->newModelQuery()->where($this->getKeyName(), $this->getKey());
        foreach ($dirty as $key => $value) {
            $query = $query->where($key, $this->getOriginal($key));
        }

        $row = $query->update($dirty);
        if ($row > 0) {
            $this->syncChanges(); //更新Changes中的数据
            //cased事件
            $this->fireModelEvent('cased', false);
            $this->syncOriginal(); //更新Original中的数据
        }
        return $row;
    }

    /**
     * Register a casing model event with the dispatcher.
     *
     * @param  Closure|string  $callback
     * @return void
     */
    public static function casing($callback)
    {
        static::registerModelEvent('casing', $callback);
    }

    /**
     * Register a cased model event with the dispatcher.
     *
     * @param  Closure|string  $callback
     * @return void
     */
    public static function cased($callback)
    {
        static::registerModelEvent('cased', $callback);
    }
}
