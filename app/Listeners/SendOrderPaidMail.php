<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Notifications\OrderPaidNotification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

//implementa ShouldQueue significa oyente asincrónico 
class SendOrderPaidMail implements ShouldQueue
{
    public function handle(OrderPaid $event)
    {
        // Sacar el orden correspondiente del objeto de evento. 
        $order = $event->getOrder();
        // Llame al método de notificación para enviar una notificación 
        $order->user->notify(new OrderPaidNotification($order));
    }
}
