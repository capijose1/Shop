<?php

namespace App\Admin\Controllers;

use App\Models\Order;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use App\Exceptions\InvalidRequestException;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Http\Requests\Admin\HandleRefundRequest;
use App\Exceptions\InternalException;

class OrdersController extends AdminController
{
    use ValidatesRequests;

    protected $title = 'Operación';

    protected function grid()
    {
        $grid = new Grid(new Order);

        // Solo se muestran los pedidos que se han pagado, y el pedido se ordena en orden inverso de forma predeterminada por hora de pago
        $grid->model()->whereNotNull('paid_at')->orderBy('paid_at', 'desc');

        $grid->no('Número de serie del pedido');
        // Utilice el método de columna al mostrar campos relacionados
        $grid->column('user.name', 'Comprador' );
        $grid->total_amount('cantidad total')->sortable();
        $grid->paid_at('Tiempo de pago')->sortable();
        $grid->ship_status('Logística')->display(function($value) {
            return Order::$shipStatusMap[$value];
        });
        $grid->refund_status('Estado de reembolso')->display(function($value) {
            return Order::$refundStatusMap[$value];
        });
        // Desactive el botón crear, no es necesario crear un pedido en segundo plano
        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            // Deshabilitar los botones de eliminar y editar
            $actions->disableDelete();
            $actions->disableEdit();
        });
        $grid->tools(function ($tools) {
            // Desactivar el botón de eliminación masiva 
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        return $grid;
    }

    public function show($id, Content $content)
    {
        return $content
            ->header('revisar orden')
            // El método del cuerpo puede aceptar la vista de Laravel como parámetro
            ->body(view('admin.orders.show', ['order' => Order::find($id)]));
    }

    public function ship(Order $order, Request $request)
    {
        // Determinar si se ha pagado el pedido actual
        if (!$order->paid_at) {
            throw new InvalidRequestException('El pedido no ha sido pagado' );
        }
        // Determine si el estado actual de entrega del pedido no se ha enviado
        if ($order->ship_status !== Order::SHIP_STATUS_PENDING) {
            throw new InvalidRequestException('El pedido ha sido enviado');
        }
        //Después de Laravel 5.5, el método de validación puede devolver el valor validado
        $data = $this->validate($request, [
            'express_company' => ['required'],
            'express_no'      => ['required'],
        ], [], [
            'express_company' => 'Compañía de logísitca',
            'express_no'      => 'Número de envío ',
        ]);
        // Cambiar el estado de entrega del pedido a entregado y almacenar la información logística
        $order->update([
            'ship_status' => Order::SHIP_STATUS_DELIVERED,
            //Especificamos que ship_data es una matriz en la propiedad $ casts del modelo Order
             // Entonces aquí puedes pasar la matriz directamente
            'ship_data'   => $data,
        ]);

        // 返回上一页
        return redirect()->back();
    }

    public function handleRefund(Order $order, HandleRefundRequest $request)
    {
        if ($order->refund_status !== Order::REFUND_STATUS_APPLIED) {
            throw new InvalidRequestException('El estado del pedido es incorrecto');
        }
        if ($request->input('agree')) {
            // Aclare el motivo del rechazo del reembolso
            $extra = $order->extra ?: [];
            unset($extra['refund_disagree_reason']);
            $order->update([
                'extra' => $extra,
            ]);
            // Lógica de reembolso de llamadas
            $this->_refundOrder($order);
        } else {
            // Ponga el motivo del rechazo del reembolso en el campo adicional del pedido.
            $extra = $order->extra ?: [];
            $extra['refund_disagree_reason'] = $request->input('reason');
            //Cambiar el estado del reembolso del pedido a no reembolsado
            $order->update([
                'refund_status' => Order::REFUND_STATUS_PENDING,
                'extra'         => $extra,
            ]);
        }

        return $order;
    }

    protected function _refundOrder(Order $order)
    {
        switch ($order->payment_method) {
            case 'wechat':
                //Generar número de orden de reembolso
                $refundNo = Order::getAvailableRefundNo();
                app('wechat_pay')->refund([
                    'out_trade_no' => $order->no, // Número de serie del pedido anterior
                    'total_fee' => $order->total_amount * 100, //Importe de pedido
                    'refund_fee' => $order->total_amount * 100, // El importe del pedido a reembolsar, en minutos.
                    'out_refund_no' => $refundNo, // Número de orden de reembolso
                    // El resultado del reembolso de WeChat Pay no se devuelve en tiempo real, pero se notifica a través de la devolución de llamada, por lo que aquí debe ir acompañado de la dirección de la interfaz de devolución de llamada.
                    'notify_url' => ngrok_url('payment.alipay.notify'),
                ]);
                // Cambiar el estado del pedido a reembolso
                $order->update([
                    'refund_no' => $refundNo,
                    'refund_status' => Order::REFUND_STATUS_PROCESSING,
                ]);
                break;
            case 'alipay':
                // Utilice el método que acabamos de escribir para generar un número de pedido de reembolso 
                $refundNo = Order::getAvailableRefundNo();
                // Llame al método de reembolso de la instancia de pago de Alipay 
                $ret = app('alipay')->refund([
                    'out_trade_no' => $order->no, // Número de serie del pedido anterior
                    'refund_amount' => $order->total_amount, // Importe del reembolso, unidad de yuanes
                    'out_request_no' => $refundNo, //Número de orden de reembolso
                ]);
                //De acuerdo con la documentación de Alipay, si hay un campo sub_code en el valor de devolución, el reembolso falló.
                if ($ret->sub_code) {
                    // Guarde el reembolso fallido en el campo adicional
                    $extra = $order->extra;
                    $extra['refund_failed_code'] = $ret->sub_code;
                    // Marque el estado del reembolso del pedido como reembolso fallido
                    $order->update([
                        'refund_no' => $refundNo,
                        'refund_status' => Order::REFUND_STATUS_FAILED,
                        'extra' => $extra,
                    ]);
                } else {
                    //Marque el estado del reembolso del pedido como reembolso exitoso y guarde el número de pedido de reembolso
                    $order->update([
                        'refund_no' => $refundNo,
                        'refund_status' => Order::REFUND_STATUS_SUCCESS,
                    ]);
                }
                break;
            default:
                // Imposible en principio, esto es solo por la robustez del código
                throw new InternalException('Método de pago de pedido desconocido: '.$order->payment_method);
                break;
        }
    }
}
