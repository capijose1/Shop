<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\OrderItem;

// implementa ShouldQueue representa que este oyente se ejecuta de forma asincrónica
class UpdateProductSoldCount implements ShouldQueue
{
    // Laravel ejecutará el método handle del oyente de forma predeterminada, y el evento desencadenado se utilizará como parámetro del método handle
    public function handle(OrderPaid $event)
    {
        // Sacar el orden correspondiente del objeto de evento.
        $order = $event->getOrder();
        // Precargar datos del producto
        $order->load('items.product');
        // Recorre los artículos pedidos
        foreach ($order->items as $item) {
            $product   = $item->product;
            // Calcule las ventas de los productos correspondientes
            $soldCount = OrderItem::query()
                ->where('product_id', $product->id)
                ->whereHas('order', function ($query) {
                    $query->whereNotNull('paid_at');  // Se paga el estado del pedido asociado
                })->sum('amount');
            // Actualizar las ventas de productos
            $product->update([
                'sold_count' => $soldCount,
            ]);
        }
    }
}
