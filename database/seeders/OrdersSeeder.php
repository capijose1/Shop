<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class OrdersSeeder extends Seeder
{
    public function run()
    {
       // Obtener una instancia de Faker 
        $faker = app(\Faker\Generator::class);
        // Crea 100 pedidos 
        $orders = Order::factory()->count(100)->create();
        // El producto comprado se utiliza para actualizar las ventas del producto y puntuarlo más tarde. 
        $products = collect([]);
        foreach ($orders as $order) {
            //1-3 artículos al azar por pedido 
            $items = OrderItem::factory()->count(random_int(1, 3))->create([
                'order_id'    => $order->id,
                'rating'      => $order->reviewed ? random_int(1, 5) : null,  //Puntuación aleatoria 1-5 
                'review'      => $order->reviewed ? $faker->sentence : null,
                'reviewed_at' => $order->reviewed ? $faker->dateTimeBetween($order->paid_at) : null, // El tiempo de evaluación no puede ser anterior al tiempo de pago. 
            ]);

            // Calcule el precio total 
            $total = $items->sum(function (OrderItem $item) {
                return $item->price * $item->amount;
            });

            //Si hay un cupón, calcule el precio con descuento. 
            if ($order->couponCode) {
                $total = $order->couponCode->getAdjustedPrice($total);
            }

            // Actualizar el precio total del pedido 
            $order->update([
                'total_amount' => $total,
            ]);

            // Combinar los productos de este pedido en la colección de productos 
            $products = $products->merge($items->pluck('product'));
        }

        // Filtrar productos duplicados según el ID de producto
        $products->unique('id')->each(function (Product $product) {
            // Descubra las ventas, calificaciones y evaluaciones del producto 
            $result = OrderItem::query()
                ->where('product_id', $product->id)
                ->whereHas('order', function ($query) {
                    $query->whereNotNull('paid_at');
                })
                ->first([
                    \DB::raw('count(*) as review_count'),
                    \DB::raw('avg(rating) as rating'),
                    \DB::raw('sum(amount) as sold_count'),
                ]);

            $product->update([
                'rating'       => $result->rating ?: 5, //Si un producto no está calificado, el valor predeterminado es de 5 puntos. 
                'review_count' => $result->review_count,
                'sold_count'   => $result->sold_count,
            ]);
        });
    }
}
