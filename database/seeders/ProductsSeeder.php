<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductsSeeder extends Seeder
{
    public function run()
    {
        // 创建 30 个商品
        $products = \App\Models\Product::factory()->count(30)->create();
        foreach ($products as $product) {
            // Cree 3 SKU, y el campo `product_id` de cada SKU se establece en el ID de producto del ciclo actual 
            $skus = \App\Models\ProductSku::factory()->count(3)->create(['product_id' => $product->id]);
            // Encuentre el precio SKU con el precio más bajo y establezca el precio del producto en ese precio 
            $product->update(['price' => $skus->min('price')]);
        }
    }
}
