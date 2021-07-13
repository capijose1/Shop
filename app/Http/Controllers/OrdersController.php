<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Models\UserAddress;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Services\OrderService;
use App\Exceptions\InvalidRequestException;
use Carbon\Carbon;
use App\Http\Requests\SendReviewRequest;
use App\Events\OrderReviewed;
use App\Http\Requests\ApplyRefundRequest;
use App\Exceptions\CouponCodeUnavailableException;
use App\Models\CouponCode;

class OrdersController extends Controller
{
    public function store(OrderRequest $request, OrderService $orderService)
    {
        $user    = $request->user();
        $address = UserAddress::find($request->input('address_id'));
        $coupon  = null;

        // Si el usuario envía un código de cupón 
        if ($code = $request->input('coupon_code')) {
            $coupon = CouponCode::where('code', $code)->first();
            if (!$coupon) {
                throw new CouponCodeUnavailableException('El cupón no existe ');
            }
        }
        // Agregue la variable $ cupón al parámetro 
        return $orderService->store($user, $address, $request->input('remark'), $request->input('items'), $coupon);
    }

    public function index(Request $request)
    {
        $orders = Order::query()
            // Utilice el método with para precargar para evitar el problema N + 1 
            ->with(['items.product', 'items.productSku'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate();

        return view('orders.index', ['orders' => $orders]);
    }

    public function show(Order $order, Request $request)
    {
        $this->authorize('own', $order);
        return view('orders.show', ['order' => $order->load(['items.productSku', 'items.product'])]);
    }

    public function received(Order $order, Request $request)
    {
        // Verificar permisos 
        $this->authorize('own', $order);

        // Determinar si se envía el estado de envío del pedido. 
        if ($order->ship_status !== Order::SHIP_STATUS_DELIVERED) {
            throw new InvalidRequestException('Estado de entrega incorrecto ');
        }

        // Actualice el estado del envío a recibido 
        $order->update(['ship_status' => Order::SHIP_STATUS_RECEIVED]);

        // Información de la orden de devolución 
        return $order;
    }

    public function review(Order $order)
    {
        // Verificar permisos 
        $this->authorize('own', $order);
        // Determinar si se ha pagado 
        if (!$order->paid_at) {
            throw new InvalidRequestException('El pedido no se ha pagado y no se puede evaluar. ');
        }
        // usar  load Método para cargar datos asociados, evitar  N + 1 Problemas de desempeño 
        return view('orders.review', ['order' => $order->load(['items.productSku', 'items.product'])]);
    }

    public function sendReview(Order $order, SendReviewRequest $request)
    {
        // Verificar permisos
        $this->authorize('own', $order);
        if (!$order->paid_at) {
            throw new InvalidRequestException('El pedido no se ha pagado y no se puede evaluar. ');
        }
        // Juzgar si ha sido evaluado 
        if ($order->reviewed) {
            throw new InvalidRequestException('El pedido ha sido evaluado y no se puede enviar repetidamente. ');
        }
        $reviews = $request->input('reviews');
        // Transacción abierta 
        \DB::transaction(function () use ($reviews, $order) {
            // Recorre los datos enviados por el usuario 
            foreach ($reviews as $review) {
                $orderItem = $order->items()->find($review['id']);
                // Guardar calificaciones y reseñas 
                $orderItem->update([
                    'rating'      => $review['rating'],
                    'review'      => $review['review'],
                    'reviewed_at' => Carbon::now(),
                ]);
            }
            // Marcar pedido como revisado 
            $order->update(['reviewed' => true]);
        });
        event(new OrderReviewed($order));

        return redirect()->back();
    }

    public function applyRefund(Order $order, ApplyRefundRequest $request)
    {
        // Verificar si el pedido pertenece al usuario actual 
        $this->authorize('own', $order);
        // Determinar si el pedido ha sido pagado 
        if (!$order->paid_at) {
            throw new InvalidRequestException('El pedido no ha sido pagado y no es reembolsable. ');
        }
        // Determine si el estado del reembolso del pedido es correcto 
        if ($order->refund_status !== Order::REFUND_STATUS_PENDING) {
            throw new InvalidRequestException('Este pedido ya ha solicitado un reembolso, no lo vuelva a solicitar. ');
        }
        // Coloque el motivo del reembolso ingresado por el usuario en el campo adicional del pedido. 
        $extra                  = $order->extra ?: [];
        $extra['refund_reason'] = $request->input('reason');
        // Cambiar el estado del reembolso del pedido a reembolso solicitado 
        $order->update([
            'refund_status' => Order::REFUND_STATUS_APPLIED,
            'extra'         => $extra,
        ]);

        return $order;
    }
}
