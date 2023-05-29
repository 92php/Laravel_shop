<?php

namespace Tests\Feature;

use App\Models\User\Address;
use App\Services\User\AddressServices;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use DatabaseTransactions;

    public function testList()
    {
        $this->assertLitemallApiGet('wx/address/list');
    }

    public function testDelete()
    {
        $address = Address::factory()->create(['user_id' => $this->user->id]);
        $this->assertNotEmpty($address->toArray());
        $response = $this->post('wx/address/delete', ['id' => $address->id]);
        $response->assertJson(['errno' => 0]);
        $address = Address::query()->find($address->id);
        $this->assertEmpty($address);
    }

    public function testSave()
    {
        $data = [
            "name" => "1",
            "tel" => "15158040000",
            "province" => "北京市",
            "city" => "市辖区",
            "county" => "东城区",
            "areaCode" => "110101",
            "postalCode" => "",
            "addressDetail" => "1",
            "isDefault" => false
        ];
        $response = $this->post('wx/address/save', $data);
        $response->assertJson(['errno' => 0]);

        $id = $response->getOriginalContent()['data'] ?? 0;

        $data = [
            "id" => $id,
            "name" => "2",
            "tel" => "15158040001",
            "province" => "北京市",
            "city" => "市辖区",
            "county" => "东城区",
            "areaCode" => "110102",
            "postalCode" => "",
            "addressDetail" => "3",
            "isDefault" => true
        ];
        $response = $this->post('wx/address/save', $data);
        $response->assertJson(['errno' => 0]);
        $address = AddressServices::getInstance()->getAddress($this->user->id, $id);
        $this->assertEquals($data, Arr::only($address->toArray(), array_keys($data)));
    }


}
