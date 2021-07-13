<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

// Significa que esta clase debe ejecutarse en la cola en lugar de ejecutarse inmediatamente cuando se activa 
class CloseOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    public function __construct(Order $order, $delay)
    {
        $this->order = $order;
        // Establece el tiempo de retardo, el parámetro del método delay () representa cuántos segundos ejecutar 
        $this->delay($delay);
    }

    // Definir la lógica de ejecución específica de esta clase de tarea 

    // Cuando el procesador de cola saca una tarea de la cola, llamará al método handle ()
    public function handle()
    {
        // Determinar si se ha pagado el pedido correspondiente
        // Si ya pagó, no es necesario que cierre el pedido, solo salga
        if ($this->order->paid_at) {
            return;
        }
        // Ejecutar sql a través de la transacción
        \DB::transaction(function() {
            // Marque el campo cerrado del pedido como verdadero para cerrar el pedido.
            $this->order->update(['closed' => true]);
            // Recorra el SKU del producto en el pedido y agregue la cantidad en el pedido al inventario de SKU
            foreach ($this->order->items as $item) {
                $item->productSku->addStock($item->amount);
            }
            if ($this->order->couponCode) {
                $this->order->couponCode->changeUsed(false);
            }
        });
    }
}
