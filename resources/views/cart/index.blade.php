@extends('layouts.app')
@section('title', '购物车')

@section('content')
<div class="row">
<div class="col-lg-10 offset-lg-1">
<div class="card">
  <div class="card-header">Mi carrito de compras </div>
  <div class="card-body">
    <table class="table table-striped">
      <thead>
      <tr>
        <th><input type="checkbox" id="select-all"></th>
        <th>Información sobre el producto </th>
        <th>Precio unitario </th>
        <th>Cantidad </th>
        <th>Operando</th>
      </tr>
      </thead>
      <tbody class="product_list">
      @foreach($cartItems as $item)
        <tr data-id="{{ $item->productSku->id }}">
          <td>
            <input type="checkbox" name="select" value="{{ $item->productSku->id }}" {{ $item->productSku->product->on_sale ? 'checked' : 'disabled' }}>
          </td>
          <td class="product_info">
            <div class="preview">
              <a target="_blank" href="{{ route('products.show', [$item->productSku->product_id]) }}">
                <img src="{{ $item->productSku->product->image_url }}">
              </a>
            </div>
            <div @if(!$item->productSku->product->on_sale) class="not_on_sale" @endif>
              <span class="product_title">
                <a target="_blank" href="{{ route('products.show', [$item->productSku->product_id]) }}">{{ $item->productSku->product->title }}</a>
              </span>
              <span class="sku_title">{{ $item->productSku->title }}</span>
              @if(!$item->productSku->product->on_sale)
                <span class="warning">Este prodcuto ha sido eliminado </span>
              @endif
            </div>
          </td>
          <td><span class="price">S./{{ $item->productSku->price }}</span></td>
          <td>
            <input type="text" class="form-control form-control-sm amount" @if(!$item->productSku->product->on_sale) disabled @endif name="amount" value="{{ $item->amount }}">
          </td>
          <td>
            <button class="btn btn-sm btn-danger btn-remove">Eliminar </button>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
    <!-- 开始 -->
    <div>
      <form class="form-horizontal" role="form" id="order-form">
        <div class="form-group row">
          <label class="col-form-label col-sm-3 text-md-right">Elija la dirección de envío </label>
          <div class="col-sm-9 col-md-7">
            <select class="form-control" name="address">
              @foreach($addresses as $address)
                <option value="{{ $address->id }}">{{ $address->full_address }} {{ $address->contact_name }} {{ $address->contact_phone }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="form-group row">
          <label class="col-form-label col-sm-3 text-md-right">Observaciones </label>
          <div class="col-sm-9 col-md-7">
            <textarea name="remark" class="form-control" rows="3"></textarea>
          </div>
        </div>

        <!-- Comienza el código de cupón  -->
        <div class="form-group row">
          <label class="col-form-label col-sm-3 text-md-right">Código promocional </label>
          <div class="col-sm-4">
            <input type="text" class="form-control" name="coupon_code">
            <span class="form-text text-muted" id="coupon_desc"></span>
          </div>
          <div class="col-sm-3">
            <button type="button" class="btn btn-success" id="btn-check-coupon">Buscar </button>
            <button type="button" class="btn btn-danger" style="display: none;" id="btn-cancel-coupon">Cancelar </button>
          </div>
        </div>
        <!-- Fin del código de cupón  -->

        <div class="form-group">
          <div class="offset-sm-3 col-sm-3">
            <button type="button" class="btn btn-primary btn-create-order">Enviar pedidos </button>
          </div>
        </div>
      </form>
    </div>
    <!-- Fin --> 
  </div>
</div>
</div>
</div>
@endsection

@section('scriptsAfterJs')
<script>
  $(document).ready(function () {
   // escucha el evento de clic del botón eliminar 
    $('.btn-remove').click(function () {
     // $ (this) puede obtener el objeto jQuery del botón eliminar en el que se ha hecho clic actualmente 
      // El método más cercano () puede obtener el primer elemento ancestro que coincide con el selector, aquí está la etiqueta <tr> encima del botón Eliminar en el que se ha hecho clic actualmente 
      //El método de datos ('id') puede obtener el valor del atributo de id de datos que establecimos antes, que es el id de SKU correspondiente 
      var id = $(this).closest('tr').data('id');
      swal({
        title: "¿Está seguro de que desea eliminar este producto?",
        icon: "warning",
        buttons: ['Cancelar ', 'Determinar'],
        dangerMode: true,
      })
      .then(function(willDelete) {
        // Si el usuario hace clic en el botón Aceptar, el valor de willDelete será verdadero, de lo contrario será falso 
        if (!willDelete) {
          return;
        }
        axios.delete('/cart/' + id)
          .then(function () {
            location.reload();
          })
      });
    });

    // Escuche el evento de cambio de seleccionar todo / cancelar todos los botones de opción 
    $('#select-all').change(function() {
      // Obtener el estado seleccionado del botón de opción 
      // El método prop () puede saber si la etiqueta contiene un determinado atributo, cuando el botón de radio está marcado, la etiqueta correspondiente agregará un atributo marcado 
      var checked = $(this).prop('checked');
      // Obtener todas las casillas de verificación con nombre = seleccionar y sin el atributo deshabilitado 
      // No queremos que se seleccione la casilla de verificación correspondiente para los productos que se han eliminado de los estantes, por lo que debemos agregar la condición: no ([deshabilitado]) 
      $('input[name=select][type=checkbox]:not([disabled])').each(function() {
        // Establezca su estado de verificación para que sea coherente con el cuadro de radio de destino 
        $(this).prop('checked', checked);
      });
    });

    // escucha el evento de clic del botón de crear orden
    $('.btn-create-order').click(function () {
     // Construya los parámetros de la solicitud, escriba la identificación y los comentarios de la dirección seleccionada por el usuario en los parámetros de la solicitud 
      var req = {
        address_id: $('#order-form').find('select[name=address]').val(),
        items: [],
        remark: $('#order-form').find('textarea[name=remark]').val(),
        coupon_code: $('input[name=coupon_code]').val(),// Obtenga el código de cupón del cuadro de entrada del código de cupón 
      };
      // Atraviesa todas las etiquetas <tr> con el atributo data-id en la etiqueta <table>, es decir, el SKU del producto en cada carrito de compras 
      $('table tr[data-id]').each(function () {
       // Obtener el botón de radio de la fila actual 
        var $checkbox = $(this).find('input[name=select][type=checkbox]');
        // Si el botón de opción está deshabilitado o no seleccionado, omita 
        if ($checkbox.prop('disabled') || !$checkbox.prop('checked')) {
          return;
        }
       // Obtener el cuadro de entrada de cantidad en la fila actual 
        var $input = $(this).find('input[name=amount]');
        // Si el usuario establece la cantidad en 0 o no es un número, omítalo también 
        if ($input.val() == 0 || isNaN($input.val())) {
          return;
        }
       // Almacene el ID de SKU y la cantidad en la matriz de parámetros de solicitud 
        req.items.push({
          sku_id: $(this).data('id'),
          amount: $input.val(),
        })
      });
      axios.post('{{ route('orders.store') }}', req)
        .then(function (response) {
          swal('Pedido enviado correctamente', '', 'success')
          .then(() => {
            location.href = '/orders/' + response.data.id;
          });
        }, function (error) {
          if (error.response.status === 422) {
            // El código de estado http es 422, lo que significa que la verificación de la entrada del usuario falló 
            var html = '<div>';
            _.each(error.response.data.errors, function (errors) {
              _.each(errors, function (error) {
                html += error+'<br>';
              })
            });
            html += '</div>';
            swal({content: $(html)[0], icon: 'error'})
          } else if (error.response.status === 403) { // Juzgando el estado aquí 403 
            swal(error.response.data.msg, '', 'error');
          } else {
            swal('Error del sistema', '', 'error');
          }
        });
    });

    // Verificar el evento de clic en el botón 
    $('#btn-check-coupon').click(function () {
      // Obtén el código de cupón ingresado por el usuario 

      var code = $('input[name=coupon_code]').val();
      // Si no hay entrada, aparecerá un mensaje emergente 
      if(!code) {
        swal('Ingrese el código del cupón', '', 'warning');
        return;
      }
      // Llamar a la interfaz de inspección
      axios.get('/coupon_codes/' + encodeURIComponent(code))
        .then(function (response) {  // El primer parámetro del método then es la devolución de llamada, que se llamará cuando la solicitud sea exitosa 
          $('#coupon_desc').text(response.data.description);// Salida de información preferencial 
          $('input[name=coupon_code]').prop('readonly', true); // Deshabilitar el cuadro de entrada 
          $('#btn-cancel-coupon').show(); // Mostrar botón Cancelar 
          $('#btn-check-coupon').hide(); // Ocultar botón de verificación 
        }, function (error) {
          // Si el código de retorno es 404, el cupón no existe 
          if(error.response.status === 404) {
            swal('El código promocional no existe', '', 'error');
          } else if (error.response.status === 403) {
          // Si el código de retorno es 403, significa que no se cumplen otras condiciones 
            swal(error.response.data.msg, '', 'error');
          } else {
          // otros errores 
            swal('Error interno del sistema ', '', 'error');
          }
        })
    });

    //Evento de clic de botón oculto 
    $('#btn-cancel-coupon').click(function () {
      $('#coupon_desc').text(''); // Ocultar ofertas 
      $('input[name=coupon_code]').prop('readonly', false);  // Activar cuadro de entrada 
      $('#btn-cancel-coupon').hide(); // Ocultar botón cancelar 
      $('#btn-check-coupon').show(); // Mostrar botón de verificación 
    });



  });
</script>
@endsection
