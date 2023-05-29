<?php

namespace App\Http\Controllers\Wx;

use App\Exceptions\BusinessException;
use App\Inputs\AddressInput;
use App\Services\User\AddressServices;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends WxController
{
    /**
     * 获取用户地址列表
     * @return JsonResponse
     */
    public function list()
    {
        $list = AddressServices::getInstance()->getAddressListByUserId($this->user()->id);
        return $this->successPaginate($list);
    }

    public function detail()
    {
        $id = $this->verifyId('id', 0);
        $address = AddressServices::getInstance()->getAddress($this->userId(), $id);
        if (empty($address)) {
            return $this->badArgumentValue();
        }
        return $this->success($address);
    }

    /**
     * 保存地址
     * @return JsonResponse
     * @throws BusinessException
     */
    public function save()
    {
        $input = AddressInput::new();
        $address = AddressServices::getInstance()->saveAddress($this->userId(), $input);
        return $this->success($address->id);
    }

    /**
     * 地址删除
     * @param  Request  $request
     * @return JsonResponse
     * @throws Exception
     */
    public function delete()
    {
        $id = $this->verifyId('id', 0);
        AddressServices::getInstance()->delete($this->user()->id, $id);
        return $this->success();
    }
}
