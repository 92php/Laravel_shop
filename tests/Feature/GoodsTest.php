<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GoodsTest extends TestCase
{
    use DatabaseTransactions;

    public function testCount()
    {
        $this->assertLitemallApiGet('wx/goods/count');
    }

    public function testCategory()
    {
        $this->assertLitemallApiGet('wx/goods/category?id=1008009');
        $this->assertLitemallApiGet('wx/goods/category?id=1005000');
    }

    public function testList()
    {
        $this->assertLitemallApiGet('wx/goods/list?categoryId=abc', ['errmsg']);
        $this->assertLitemallApiGet('wx/goods/list?isNew=0', ['errmsg']);
        $this->assertLitemallApiGet('wx/goods/list?isNew=a', ['errmsg']);
        $this->assertLitemallApiGet('wx/goods/list?page=a&limit=5', ['errmsg']);
        $this->assertLitemallApiGet('wx/goods/list?page=1&limit=a', ['errmsg']);
        $this->assertLitemallApiGet('wx/goods/list?sort=name&order=asc', ['errmsg']);
        $this->assertLitemallApiGet('wx/goods/list?sort=id&order=asc', ['errmsg']);
        $this->assertLitemallApiGet('wx/goods/list?sort=name&order=abc', ['errmsg']);
        $this->assertLitemallApiGet('wx/goods/list');
        $this->assertLitemallApiGet('wx/goods/list?categoryId=1008009');
        $this->assertLitemallApiGet('wx/goods/list?brandId=1001000');
        $this->assertLitemallApiGet('wx/goods/list?keyword=四件套');
        $this->assertLitemallApiGet('wx/goods/list?isNew=1');
        $this->assertLitemallApiGet('wx/goods/list?isHot=1');
        $this->assertLitemallApiGet('wx/goods/list?page=2&limit=5');
    }

    public function testDetail()
    {
        $this->assertLitemallApiGet('wx/goods/detail?id=1009009');
        $this->assertLitemallApiGet('wx/goods/detail?id=1181000');
        $this->assertLitemallApiGet('wx/goods/detail?id=1036013');
    }
}
