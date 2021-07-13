@extends('layouts.app')
@section('title', $product->title)

@section('content')
<div class="row">
<div class="col-lg-10 offset-lg-1">
<div class="card">
  <div class="card-body product-info">
    <div class="row">
      <div class="col-5">
        <img class="cover" src="{{ $product->image_url }}" alt="">
      </div>
      <div class="col-7">
        <div class="title">{{ $product->title }}</div>
        <div class="price"><label>Precio</label><em>S./</em><span>{{ $product->price }}</span></div>
        <div class="sales_and_reviews">
          <div class="sold_count">Ventas acumuladas  <span class="count">{{ $product->sold_count }}</span></div>
          <div class="review_count">Calificación <span class="count">{{ $product->review_count }}</span></div>
          <div class="rating" title="Estrellas {{ $product->rating }}">Estrellas <span class="count">{{ str_repeat('★', floor($product->rating)) }}{{ str_repeat('☆', 5 - floor($product->rating)) }}</span></div>
        </div>
        <div class="skus">
          <label>Seleccione</label>
          <div class="btn-group btn-group-toggle" data-toggle="buttons">
            @foreach($product->skus as $sku)
              <label
                  class="btn sku-btn"
                  data-price="{{ $sku->price }}"
                  data-stock="{{ $sku->stock }}"
                  data-toggle="tooltip"
                  title="{{ $sku->description }}"
                  data-placement="bottom">
                <input type="radio" name="skus" autocomplete="off" value="{{ $sku->id }}"> {{ $sku->title }}
              </label>
            @endforeach
          </div>
        </div>
        <div class="cart_amount"><label>Cantidad</label><input type="text" class="form-control form-control-sm" value="1"><span>Unidad</span><span class="stock"></span></div>
        <div class="buttons">
          @if($favored)
            <button class="btn btn-danger btn-disfavor">No favorito</button>
          @else
            <button class="btn btn-success btn-favor">❤ Favorito</button>
          @endif
          <button class="btn btn-primary btn-add-to-cart">Añadir al carrito de compra</button>
        </div>
      </div>
    </div>
    <div class="product-detail">
      <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
          <a class="nav-link active" href="#product-detail-tab" aria-controls="product-detail-tab" role="tab" data-toggle="tab" aria-selected="true">Detalle de producto</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#product-reviews-tab" aria-controls="product-reviews-tab" role="tab" data-toggle="tab" aria-selected="false">Evaluación de usuario</a>
        </li>
      </ul>
      <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" id="product-detail-tab">
          {!! $product->description !!}
        </div>
        <div role="tabpanel" class="tab-pane" id="product-reviews-tab">
          <! - Inicio de la lista de comentarios -> 
          <table class="table table-bordered table-striped">
            <thead>
            <tr>
              <td>Usuario</td>
              <td>Producto</td>
              <td>Puntaje</td>
              <td>Evaluación</td>
              <td>Hora</td>
            </tr>
            </thead>
            <tbody>
              @foreach($reviews as $review)
              <tr>
                <td>{{ $review->order->user->name }}</td>
                <td>{{ $review->productSku->title }}</td>
                <td>{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</td>
                <td>{{ $review->review }}</td>
                <td>{{ $review->reviewed_at->format('Y-m-d H:i') }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
          <!-- Fin de la lista de comentarios -->
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
  $(document).ready(function () {
    $('[data-toggle="tooltip"]').tooltip({trigger: 'hover'});
    $('.sku-btn').click(function () {
      $('.product-info .price span').text($(this).data('price'));
      $('.product-info .stock').text('en stock: ' + $(this).data('stock') + 'Unidad');
    });

    // escucha el evento de clic del botón favorito 
    $('.btn-favor').click(function () {
      axios.post('{{ route('products.favor', ['product' => $product->id]) }}')
        .then(function () {
          swal('Operación exitosa', '', 'success')
          .then(function () { // Se agregó un método then () aquí 
              location.reload();
            });
        }, function(error) {
          if (error.response && error.response.status === 401) {
            swal('Por favor ingrese primero ', '', 'error');
          }  else if (error.response && error.response.data.msg) {
            swal(error.response.data.msg, '', 'error');
          }  else {
            swal('Error del sistema ', '', 'error');
          }
        });
    });

    $('.btn-disfavor').click(function () {
      axios.delete('{{ route('products.disfavor', ['product' => $product->id]) }}')
        .then(function () {
          swal('Operación exitosa ', '', 'success')
            .then(function () {
              location.reload();
            });
        });
    });

    // Evento de clic en el botón Agregar al carrito
    $('.btn-add-to-cart').click(function () {

      // Solicitud para unirse a la interfaz del carrito de compras 
      axios.post('{{ route('cart.add') }}', {
        sku_id: $('label.active input[name=skus]').val(),
        amount: $('.cart_amount input').val(),
      })
        .then(function () {
          swal('Se añadio al carrito correctamente', '', 'success')
            .then(function() {
              location.href = '{{ route('cart.index') }}';
            });
        }, function (error) { // La solicitud no puede ejecutar esta devolución de llamada 
          if (error.response.status === 401) {

            //El código de estado http es 401, lo que significa que el usuario no ha iniciado sesión. 
            swal('Solicitar registro', '', 'error');

          } else if (error.response.status === 422) {

           // el código de estado http es 422, lo que significa que la verificación de la entrada del usuario falló 
            var html = '<div>';
            _.each(error.response.data.errors, function (errors) {
              _.each(errors, function (error) {
                html += error+'<br>';
              })
            });
            html += '</div>';
            swal({content: $(html)[0], icon: 'error'})
          } else {

            // De lo contrario, el sistema debe colgarse 
            swal('error del sistema ', '', 'error');
          }
        })
    });


  });
</script>
@endsection
