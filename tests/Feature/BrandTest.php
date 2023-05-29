<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use DatabaseTransactions;

    public function testDetail()
    {
        $this->assertLitemallApiGet('wx/brand/detail', ['errmsg']);
        $this->assertLitemallApiGet('wx/brand/detail?id=1001000');
    }

    public function testList()
    {
        $this->assertLitemallApiGet('wx/brand/list');
    }

}
