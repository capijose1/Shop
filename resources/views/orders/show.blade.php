@extends('layouts.app')
@section('title', 'Revisar orden ')

@section('content')
<div class="row">
<div class="col-lg-10 offset-lg-1">
<div class="card">
  <div class="card-header">
    <h4>Detalles de pedido</h4>
  </div>
  <div class="card-body">
    <table class="table">
      <thead>
      <tr>
        <th>Información sobre el producto</th>
        <th class="text-center">Precio unitario</th>
        <th class="text-center">Cantidad</th>
        <th class="text-right item-amount">Total parcial</th>
      </tr>
      </thead>
      @foreach($order->items as $index => $item)
        <tr>
          <td class="product-info">
            <div class="preview">
              <a target="_blank" href="{{ route('products.show', [$item->product_id]) }}">
                <img src="{{ $item->product->image_url }}">
              </a>
            </div>
            <div>
              <span class="product-title">
                 <a target="_blank" href="{{ route('products.show', [$item->product_id]) }}">{{ $item->product->title }}</a>
              </span>
              <span class="sku-title">{{ $item->productSku->title }}</span>
            </div>
          </td>
          <td class="sku-price text-center vertical-middle">S./{{ $item->price }}</td>
          <td class="sku-amount text-center vertical-middle">{{ $item->amount }}</td>
          <td class="item-amount text-right vertical-middle">S./{{ number_format($item->price * $item->amount, 2, '.', '') }}</td>
        </tr>
      @endforeach
      <tr><td colspan="4"></td></tr>
    </table>
    <div class="order-bottom">
      <div class="order-info">
        <div class="line"><div class="line-label">Dirección de Envío: </div><div class="line-value">{{ join(' ', $order->address) }}</div></div>
        <div class="line"><div class="line-label">Pedidos: </div><div class="line-value">{{ $order->remark ?: '-' }}</div></div>
        <div class="line"><div class="line-label">Número de orden: </div><div class="line-value">{{ $order->no }}</div></div>
        <!-- Estado de la logística de salida  -->
        <div class="line">
          <div class="line-label">Estado logístico: </div>
          <div class="line-value">{{ \App\Models\Order::$shipStatusMap[$order->ship_status] }}</div>
        </div>
        <!-- Mostrar si hay información logística  -->
        @if($order->ship_data)
        <div class="line">
          <div class="line-label">Información logística: </div>
          <div class="line-value">{{ $order->ship_data['express_company'] }} {{ $order->ship_data['express_no'] }}</div>
        </div>
        @endif
        <!-- Muestre la información del reembolso cuando se haya pagado el pedido y el estado del reembolso no sea no reembolsado  -->
        @if($order->paid_at && $order->refund_status !== \App\Models\Order::REFUND_STATUS_PENDING)
        <div class="line">
          <div class="line-label">Estado de reembolso: </div>
          <div class="line-value">{{ \App\Models\Order::$refundStatusMap[$order->refund_status] }}</div>
        </div>
        <div class="line">
          <div class="line-label">Motivo de reembolso: </div>
          <div class="line-value">{{ $order->extra['refund_reason'] }}</div>
        </div>
        @endif
      </div>
      <div class="order-summary text-right">
        <!-- Mostrar información de descuento comenzar  -->
        @if($order->couponCode)
        <div class="text-primary">
          <span>Información de descuento: </span>
          <div class="value">{{ $order->couponCode->description }}</div>
        </div>
        @endif
        <!-- Oferta de fin de exhibición -->
        <div class="total-amount">
          <span>Precio total del pedido: </span>
          <div class="value">S./{{ $order->total_amount }}</div>
        </div>
        <div>
          <span>Estado de la orden: </span>
          <div class="value">
            @if($order->paid_at)
              @if($order->refund_status === \App\Models\Order::REFUND_STATUS_PENDING)
                Pagado
              @else
                {{ \App\Models\Order::$refundStatusMap[$order->refund_status] }}
              @endif
            @elseif($order->closed)
              Cerrado
            @else
              No pagado
            @endif
          </div>

          @if(isset($order->extra['refund_disagree_reason']))
          <div>
            <span>Motivo de la denegación del reembolso: </span>
            <div class="value">{{ $order->extra['refund_disagree_reason'] }}</div>
          </div>
          @endif

          <!-- Botón de pago para empezar  -->
          @if(!$order->paid_at && !$order->closed)
          <div class="payment-buttons">
            <a class="btn btn-primary btn-sm" href="{{ route('payment.alipay', ['order' => $order->id]) }}">Paga con Ali-Pay </a>
            <button class="btn btn-sm btn-success" id='btn-wechat'>WeChat Pay </button>
          </div>
          @endif
          <!-- Si se envía el estado de entrega del pedido, se mostrará el botón de confirmación de recepción  -->
          @if($order->ship_status === \App\Models\Order::SHIP_STATUS_DELIVERED)
          <div class="receive-button">
            <button type="button" id="btn-receive" class="btn btn-sm btn-success">Confirmar recibo </button>
          </div>
          @endif
          <!-- Cuando se haya pagado el pedido y no se reembolse el estado del reembolso, se mostrará el botón de solicitud de reembolso  -->
          @if($order->paid_at && $order->refund_status === \App\Models\Order::REFUND_STATUS_PENDING)
          <div class="refund-button">
            <button class="btn btn-sm btn-danger" id="btn-apply-refund">Solicita un reembolso </button>
          </div>
          @endif
        </div>


      </div>
    </div>
  </div>
</div>
</div>
</div>
@endsection

@section('scriptsAfterJs')
<script>
  $(document).ready(function() {
    // Evento del botón de pago de WeChat 
    $('#btn-wechat').click(function() {
      swal({
        // El parámetro de contenido puede ser un elemento DOM, aquí usamos jQuery para generar dinámicamente una etiqueta img y obtener el elemento DOM a través de [0] 
        content: $('<img src="{{ route('payment.wechat', ['order' => $order->id]) }}" />')[0],
        // El parámetro de botones puede establecer el texto mostrado por el botón 
        buttons: ['Cerrar', 'Pago completado'],
      })
      .then(function(result) {
      // Si el usuario hace clic en el botón de pago completado, la página se vuelve a cargar. 
        if (result) {
          location.reload();
        }
      })
    });

    // Confirmar evento de clic de botón de recibo 
    $('#btn-receive').click(function() {
      // Cuadro de confirmación emergente 
      swal({
        title: "¿Confirma que se han recibido las mercancías? ",
        icon: "warning",
        dangerMode: true,
        buttons: ['Cancelar', 'Recibo'],
      })
      .then(function(ret) {
        // Si hace clic en el botón cancelar, no haga nada 
        if (!ret) {
          return;
        }
        // ajax Enviar operación de confirmación 
        axios.post('{{ route('orders.received', [$order->id]) }}')
          .then(function () {
            // actualizar página
            location.reload();
          })
      });
    });

    // Evento de clic de botón de reembolso 
    $('#btn-apply-refund').click(function () {
      swal({
        text: 'Ingrese el motivo del reembolso',
        content: "input",
      }).then(function (input) {
        // Esta función se activa cuando el usuario hace clic en el botón en el cuadro emergente de tragar 
        if(!input) {
          swal('El motivo del reembolso no puede estar vacío ', '', 'error');
          return;
        }
        // Solicitar interfaz de reembolso 
        axios.post('{{ route('orders.apply_refund', [$order->id]) }}', {reason: input})
          .then(function () {
            swal('Solicitud de reembolso exitosa ', '', 'success').then(function () {
              // Vuelva a cargar la página cuando el usuario haga clic en el botón en la ventana emergente 
              location.reload();
            });
          });
      });
    });


  });
</script>
@endsection
