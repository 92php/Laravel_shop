<?php

namespace App\Services\Goods;

use App\Inputs\GoodsListInput;
use App\Models\Goods\Footprint;
use App\Models\Goods\Goods;
use App\Models\Goods\GoodsAttribute;
use App\Models\Goods\GoodsProduct;
use App\Models\Goods\GoodsSpecification;
use App\Models\Goods\Issue;
use App\Services\BaseServices;
use Illuminate\Database\Eloquent\Builder;

class GoodsServices extends BaseServices
{
    public function getGoodsListByIds(array $ids)
    {
        if (empty($ids)) {
            return collect(); //空集合
        }
        return Goods::query()->whereIn('id', $ids)->get();
    }

    public function getGoods(int $id)
    {
        return Goods::query()->find($id);
    }

    public function getGoodsAttribute(int $goodsId)
    {
        return GoodsAttribute::query()->where('goods_id', $goodsId)->get();
    }

    public function getGoodsSpecification(int $goodsId)
    {
        $spec = GoodsSpecification::query()->where('goods_id', $goodsId)->get()->groupBy('specification');
        return $spec->map(function ($v, $k) {
            return ['name' => $k, 'valueList' => $v];
        })->values();
    }

    public function getGoodsProduct(int $goodsId)
    {
        return GoodsProduct::query()->where('goods_id', $goodsId)->get();
    }

    public function getGoodsProductById(int $id)
    {
        return GoodsProduct::query()->find($id);
    }

    public function getGoodsProductsByIds(array $ids)
    {
        if (empty($ids)) {
            return collect();
        }
        return GoodsProduct::query()->whereIn('id', $ids)->get();
    }

    public function getGoodsIssue(int $page = 1, int $limit = 4)
    {
        return Issue::query()->forPage($page, $limit)->get();
    }

    public function saveFootprint($userId, $goodsId)
    {
        $footprint = new Footprint();
        $footprint->fill(['user_id' => $userId, 'goods_id' => $goodsId]);
        return $footprint->save();
    }

    /**
     * 获取在售商品的数量
     * @return int
     */
    public function countGoodsOnSale()
    {
        return Goods::query()->where('is_on_sale', 1)->count('id');
    }

    public function listGoods(GoodsListInput $input, $columns)
    {
        $query = $this->getQueryByGoodsFilter($input);
        if (!empty($input->categoryId)) {
            $query = $query->where('category_id', $input->categoryId);
        }

        return $query->orderBy($input->sort, $input->order)
            ->paginate($input->limit, $columns, 'page', $input->page);
    }

    public function listL2Category(GoodsListInput $input)
    {
        $query = $this->getQueryByGoodsFilter($input);
        $categoryIds = $query->select(['category_id'])->pluck('category_id')->unique()->toArray();
        return CatalogServices::getInstance()->getL2ListByIds($categoryIds);
    }

    private function getQueryByGoodsFilter(GoodsListInput $input)
    {
        $query = Goods::query()->where('is_on_sale', 1);

        if (!empty($input->brandId)) {
            $query = $query->where('brand_id', $input->brandId);
        }
        if (!is_null($input->isNew)) {
            $query = $query->where('is_new', $input->isNew);
        }
        if (!is_null($input->isHot)) {
            $query = $query->where('is_hot', $input->isHot);
        }
        if (!empty($input->keyword)) {
            $query = $query->where(function (Builder $query) use ($input) {
                $query->where('keywords', 'like', "%$input->keyword%")
                    ->orWhere('name', 'like', "%$input->keyword%");
            });
        }
        return $query;
    }

    public function reduceStock($productId, $num)
    {
        //先比较，再更新，乐观锁（需要解决重复请求问题：加分布式锁）
        return GoodsProduct::query()->where('id', $productId)->where('number', '>=', $num)
            ->decrement('number', $num);
    }

    public function addStock($productId, $num)
    {
        $product = $this->getGoodsProductById($productId);
        $product->number = $product->number + $num;
        return $product->cas();
    }

}
