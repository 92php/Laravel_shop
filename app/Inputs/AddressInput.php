<?php

namespace App\Inputs;

class AddressInput extends Input
{
    public $id;
    public $name;
    public $province;
    public $city;
    public $county;
    public $addressDetail;
    public $areaCode;
    public $postalCode = '';
    public $tel;
    public $isDefault;

    public function rules()
    {
        return [
            'id' => 'integer',
            'name' => 'string',
            'province' => 'string',
            'city' => 'string',
            'county' => 'string',
            'addressDetail' => 'string',
            'areaCode' => 'string',
            'postalCode' => 'string',
            'tel' => 'regex:/^1[0-9]{10}$/',
            'isDefault' => 'bool'
        ];
    }


}