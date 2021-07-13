<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Order;

class OrderPaidNotification extends Notification
{
    use Queueable;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    // Solo necesitamos notificar por correo electrónico, por lo que solo se necesita un correo aquí
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('El pago del pedido se ha realizado correctamente ')  // título del correo 
            ->greeting($this->order->user->name.'Hola: ') // bienvenidos 
            ->line('Usted en  '.$this->order->created_at->format('m-d H:i').' El pedido creado se ha pagado correctamente. ') // contenido del correo electrónico 
            ->action('Revisar orden', route('orders.show', [$this->order->id])) // Botones y enlaces correspondientes en el correo electrónico 
            ->success(); // El tono del botón
    }
}
