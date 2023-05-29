<?php

namespace App\Inputs;

use App\Exceptions\BusinessException;
use App\VerifyRequestInput;

class Input
{
    use VerifyRequestInput;

    /**
     * @param  null|array  $data
     * @return Input
     * @throws BusinessException
     */
    public function fill($data = null)
    {
        if (is_null($data)) {
            $data = request()->input();
        }

        $this->check($data, $this->rules());

        $map = get_object_vars($this); //获取类定义的属性（参数）
        $keys = array_keys($map);
        collect($data)->map(function ($v, $k) use ($keys) {
            if (in_array($k, $keys)) {
                $this->$k = $v;
            }
        });
        return $this;
    }

    public function rules()
    {
        return [];
    }

    /**
     * @param  null|array  $data
     * @return Input|static
     * @throws BusinessException
     */
    public static function new($data = null)
    {
        //实例化并调用fill方法
        return (new static())->fill($data);
    }

}
