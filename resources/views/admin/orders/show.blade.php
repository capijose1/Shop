<div class="box box-info">
  <div class="box-header with-border">
    <h3 class="box-title">Número de serie del pedido: {{ $order->no }}</h3>
    <div class="box-tools">
      <div class="btn-group float-right" style="margin-right: 10px">
        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-default"><i class="fa fa-list"></i> 列表</a>
      </div>
    </div>
  </div>
  <div class="box-body">
    <table class="table table-bordered">
      <tbody>
      <tr>
        <td>Comprador ：</td>
        <td>{{ $order->user->name }}</td>
        <td>Tiempo de pago: </td>
        <td>{{ $order->paid_at->format('Y-m-d H:i:s') }}</td>
      </tr>
      <tr>
        <td>método de pago: </td>
        <td>{{ $order->payment_method }}</td>
        <td>Número de canal de pago: </td>
        <td>{{ $order->payment_no }}</td>
      </tr>
      <tr>
        <td>Dirección de Envío:</td>
        <td colspan="3">{{ $order->address['address'] }} {{ $order->address['zip'] }} {{ $order->address['contact_name'] }} {{ $order->address['contact_phone'] }}</td>
      </tr>
      <tr>
        <td rowspan="{{ $order->items->count() + 1 }}">Lista de productos </td>
        <td>nombre del producto </td>
        <td>precio unitario </td>
        <td>Cantidad</td>
      </tr>
      @foreach($order->items as $item)
      <tr>
        <td>{{ $item->product->title }} {{ $item->productSku->title }}</td>
        <td>S./{{ $item->price }}</td>
        <td>{{ $item->amount }}</td>
      </tr>
      @endforeach
      <tr>
        <td>Total de la orden ：</td>
        <td>S./{{ $order->total_amount }}</td>
        <!-- Aquí también hay un nuevo estado de entrega -->
        <td>Estado de entrega: </td>
        <td>{{ \App\Models\Order::$shipStatusMap[$order->ship_status] }}</td>
      </tr>
      <!-- Comienza el envío del pedido  -->
      <!-- Si el pedido no se envía, muestre el formulario de envío  -->
      @if($order->ship_status === \App\Models\Order::SHIP_STATUS_PENDING)
        @if($order->refund_status !== \App\Models\Order::REFUND_STATUS_SUCCESS)
        <tr>
          <td colspan="4">
            <form action="{{ route('admin.orders.ship', [$order->id]) }}" method="post" class="form-inline">
              <!-- No olvide el campo token csrf  -->
              {{ csrf_field() }}
              <div class="form-group {{ $errors->has('express_company') ? 'has-error' : '' }}">
                <label for="express_company" class="control-label">Compañía de logísitca: </label>
                <input type="text" id="express_company" name="express_company" value="" class="form-control" placeholder="Entrar empresa de logística :">
                @if($errors->has('express_company'))
                  @foreach($errors->get('express_company') as $msg)
                    <span class="help-block">{{ $msg }}</span>
                  @endforeach
                @endif
              </div>
              <div class="form-group {{ $errors->has('express_no') ? 'has-error' : '' }}">
                <label for="express_no" class="control-label">Numero de envío </label>
                <input type="text" id="express_no" name="express_no" value="" class="form-control" placeholder="输入物流单号">
                @if($errors->has('express_no'))
                  @foreach($errors->get('express_no') as $msg)
                    <span class="help-block">{{ $msg }}</span>
                  @endforeach
                @endif
              </div>
              <button type="submit" class="btn btn-success" id="ship-btn">Barco </button>
            </form>
          </td>
        </tr>
        @endif
      @else
      <!-- De lo contrario, muestre la empresa de logística y el número de pedido de logística  -->
      <tr>
        <td>Compañía de logísitca: </td>
        <td>{{ $order->ship_data['express_company'] }}</td>
        <td>Numero de envío ：</td>
        <td>{{ $order->ship_data['express_no'] }}</td>
      </tr>
      @endif
      <!-- Fin de la entrega del pedido  -->

      @if($order->refund_status !== \App\Models\Order::REFUND_STATUS_PENDING)
      <tr>
        <td>Estado de reembolso: </td>
        <td colspan="2">{{ \App\Models\Order::$refundStatusMap[$order->refund_status] }}，Razón: {{ $order->extra['refund_reason'] }}</td>
        <td>
          <!-- Si se aplica el estado de reembolso del pedido, muestre el botón de procesamiento -->
          @if($order->refund_status === \App\Models\Order::REFUND_STATUS_APPLIED)
          <button class="btn btn-sm btn-success" id="btn-refund-agree">estar de acuerdo </button>
          <button class="btn btn-sm btn-danger" id="btn-refund-disagree">discrepar </button>
          @endif
        </td>
      </tr>
      @endif

      </tbody>
    </table>
  </div>
</div>

<script>
$(document).ready(function() {
  //Evento de clic de botón en desacuerdo 
  $('#btn-refund-disagree').click(function() {
    // La versión de SweetAlert utilizada por Laravel-Admin es diferente de la versión que usamos en primer plano, por lo que los parámetros también son diferentes. 
    swal({
      title: 'Ingrese el motivo del rechazo del reembolso ',
      input: 'text',
      showCancelButton: true,
      confirmButtonText: "confirmar ",
      cancelButtonText: "cancelar",
      showLoaderOnConfirm: true,
      preConfirm: function(inputValue) {
        if (!inputValue) {
          swal('La razón no puede estar vacía ', '', 'error')
          return false;
        }
        // Laravel-Admin no tiene axios, use el método ajax de jQuery para solicitar 
        return $.ajax({
          url: '{{ route('admin.orders.handle_refund', [$order->id]) }}',
          type: 'POST',
          data: JSON.stringify({   // Convierte la solicitud en una cadena JSON 
            agree: false, // rechazar la solicitud 
            reason: inputValue,
            //Traer token CSRF 
            // El token CSRF se puede obtener a través de LA.token en la página de Laravel-Admin 
            _token: LA.token,
          }),
          contentType: 'application/json',  // El formato de datos solicitado es JSON 
        });
      },
      allowOutsideClick: false
    }).then(function (ret) {
      // Si el usuario hace clic en el botón "Cancelar", no se hará nada. 
      if (ret.dismiss === 'cancel') {
        return;
      }
      swal({
        title: 'Operación exitosa ',
        type: 'success'
      }).then(function() {
        // Actualiza la página cuando el usuario hace clic en el botón del trago. 
        location.reload();
      });
    });
  });

  // Aceptar evento de clic de botón 
  $('#btn-refund-agree').click(function() {
    swal({
      title: '¿Estás seguro de que deseas devolver el dinero al usuario? ',
      type: 'warning',
      showCancelButton: true,
      confirmButtonText: "confirmar",
      cancelButtonText: "cancelar",
      showLoaderOnConfirm: true,
      preConfirm: function() {
        return $.ajax({
          url: '{{ route('admin.orders.handle_refund', [$order->id]) }}',
          type: 'POST',
          data: JSON.stringify({
            agree: true, // El representante acepta reembolsar 
            _token: LA.token,
          }),
          contentType: 'application/json',
        });
      },
      allowOutsideClick: false
    }).then(function (ret) {
      //Si el usuario hace clic en el botón "Cancelar", no se hará nada. 
      if (ret.dismiss === 'cancel') {
        return;
      }
      swal({
        title: 'Operación exitosa ',
        type: 'success'
      }).then(function() {
        // Actualizar la página cuando el usuario hace clic en el botón de la gol 
        location.reload();
      });
    });
  });


});
</script>
