<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Exceptions\InvalidRequestException;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use App\Events\OrderPaid;

class PaymentController extends Controller
{
    public function payByAlipay(Order $order, Request $request)
    {
        // Determinar si el pedido pertenece al usuario actual 

        $this->authorize('own', $order);
        // El pedido ha sido pagado o cerrado 

        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('Estado de pedido incorrecto');
        }

        // Llame al pago web de Alipay 
        return app('alipay')->web([
            'out_trade_no' => $order->no, // Se debe garantizar que el número de pedido no se repita en el lado del comerciante. 
            'total_amount' => $order->total_amount, // Importe del pedido, unidad de soles, admite dos decimales 
            'subject'      => 'Para pagar los pedidos de Laravel Shop: '.$order->no, // Título de la orden
        ]);
    }

    public function alipayReturn()
    {
        try {
            app('alipay')->verify();
        } catch (\Exception $e) {
            return view('pages.error', ['msg' => 'Datos incorrectos']);
        }

        return view('pages.success', ['msg' => 'Pago exitoso']);
    }

    public function alipayNotify()
    {
        // Verificar los parámetros de entrada 
        $data  = app('alipay')->verify();
        // Si el estado del pedido no es exitoso o está terminado, no se seguirá la lógica de seguimiento. 
        // Todo el estado de la transacción:  https://docs.open.alipay.com/59/103672
        if(!in_array($data->trade_status, ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            return app('alipay')->success();
        }
        // $data->out_trade_no Obtenga el número de serie del pedido y consúltelo en la base de datos 
        $order = Order::where('no', $data->out_trade_no)->first();
        // Normalmente, es poco probable que se pague un pedido que no existe, este juicio es solo para fortalecer la robustez del sistema. 
        if (!$order) {
            return 'fail';
        }
        // Si el estado de este pedido ya está pagado 
        if ($order->paid_at) {
            // Devolver datos a Alipay 
            return app('alipay')->success();
        }

        $order->update([
            'paid_at'        => Carbon::now(), // Tiempo de pago 
            'payment_method' => 'alipay', // Metodo de pago
            'payment_no'     => $data->trade_no, // Número de pedido de Alipay 
        ]);

        $this->afterPaid($order);

        return app('alipay')->success();
    }

    public function payByWechat(Order $order, Request $request)
    {
        $this->authorize('own', $order);
        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }

        // Solía ​​regresar directamente, ahora coloque el valor de retorno en una variable 
        $wechatOrder = app('wechat_pay')->scan([
            'out_trade_no' => $order->no,
            'total_fee'    => $order->total_amount * 100,
            'body'         => 'Para pagar los pedidos de Laravel Shop: '.$order->no,
        ]);
        // Tome la cadena que se convertirá como parámetro del constructor QrCode 
        $qrCode = new QrCode($wechatOrder->code_url);

        // Imprima los datos de imagen de código bidimensional generados en forma de una cadena con el tipo de respuesta correspondiente 
        return response($qrCode->writeString(), 200, ['Content-Type' => $qrCode->getContentType()]);
    }

    public function wechatNotify()
    {
        // Verifique que los parámetros de devolución de llamada sean correctos 
        $data  = app('wechat_pay')->verify();
        // Encuentra el pedido correspondiente 
        $order = Order::where('no', $data->out_trade_no)->first();
        // Si el pedido no existe, notifique a WeChat Pay 
        if (!$order) {
            return 'fail';
        }
        // Pedido pagado 
        if ($order->paid_at) {
            // Informe a WeChat Pay que este pedido ha sido procesado 
            return app('wechat_pay')->success();
        }

        // Marcar pedido como pagado 
        $order->update([
            'paid_at'        => Carbon::now(),
            'payment_method' => 'wechat',
            'payment_no'     => $data->transaction_id,
        ]);

        $this->afterPaid($order);

        return app('wechat_pay')->success();
    }

    public function wechatRefundNotify(Request $request)
    {
        // Respuesta de falla a WeChat 
        $failXml = '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>';
        $data = app('wechat_pay')->verify(null, true);

        // Si no se encuentra un orden correspondiente, no puede suceder en principio, por lo que la robustez del código está garantizada 
        if(!$order = Order::where('no', $data['out_trade_no'])->first()) {
            return $failXml;
        }

        if ($data['refund_status'] === 'SUCCESS') {
            // El reembolso es exitoso, cambie el estado del reembolso del pedido para reembolsarlo correctamente 
            $order->update([
                'refund_status' => Order::REFUND_STATUS_SUCCESS,
            ]);
        } else {
            // Si el reembolso falla, el estado específico se almacena en el campo adicional y el estado del reembolso cambia a fallido 
            $extra = $order->extra;
            $extra['refund_failed_code'] = $data['refund_status'];
            $order->update([
                'refund_status' => Order::REFUND_STATUS_FAILED,
                'extra' => $extra
            ]);
        }

        return app('wechat_pay')->success();
    }
    
    protected function afterPaid(Order $order)
    {
        event(new OrderPaid($order));
    }
}
