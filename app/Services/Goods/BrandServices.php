<?php

namespace App\Services\Goods;

use App\Models\Goods\Brand;
use App\Services\BaseServices;

class BrandServices extends BaseServices
{

    public function getBrand(int $id)
    {
        return Brand::query()->find($id);
    }

    public function getBrandList(int $page, int $limit, $sort, $order, $columns = ['*'])
    {
        $query = Brand::query();
        if (!empty($sort) && !empty($order)) {
            $query = $query->orderBy($sort, $order);
        }
        return $query->paginate($limit, $columns, 'page', $page);
    }
}