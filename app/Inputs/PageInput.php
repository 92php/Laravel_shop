<?php

namespace App\Inputs;

use Illuminate\Validation\Rule;

class PageInput extends Input
{
    public $page = 1;
    public $limit = 10;
    public $sort = 'add_time';
    public $order = 'desc';

    public function rules()
    {
        return [
            'page' => 'integer',
            'limit' => 'integer',
            'sort' => 'string',
            'order' => Rule::in(['desc', 'asc'])
        ];
    }


}