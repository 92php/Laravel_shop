<?php

namespace App\Http\Controllers\Wx;

use App\Exceptions\BusinessException;
use Illuminate\Http\RedirectResponse;

class HomeController extends WxController
{
    protected $only = [];

    /**
     * 分享链接调整
     * @return RedirectResponse
     * @throws BusinessException
     */
    public function redirectShareUrl()
    {
        $type = $this->verifyString('type', 'groupon');
        $id = $this->verifyId('id');

        if ($type == 'groupon') {
            return redirect()->to(env('H5_URL').'/#/items/detail/'.$id);
        }
        if ($type == 'goods') {
            return redirect()->to(env('H5_URL').'/#/items/detail/'.$id);
        }
        return redirect()->to(env('H5_URL').'/#/items/detail/'.$id);
    }
}