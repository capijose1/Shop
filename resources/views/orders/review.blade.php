@extends('layouts.app')
@section('title', 'Revision de producto')

@section('content')
<div class="row">
<div class="col-lg-10 offset-lg-1">
<div class="card">
  <div class="card-header">
    Revision de producto
    <a class="float-right" href="{{ route('orders.index') }}">Volver a la lista de pedidos</a>
  </div>
  <div class="card-body">
    <form action="{{ route('orders.review.store', [$order->id]) }}" method="post">
      <input type="hidden" name="_token" value="{{ csrf_token() }}">
      <table class="table">
        <tbody>
        <tr>
          <td>Nombre del producto</td>
          <td>Puntuación</td>
          <td>Evaluación</td>
        </tr>
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
              <input type="hidden" name="reviews[{{$index}}][id]" value="{{ $item->id }}">
            </td>
            <td class="vertical-middle">
              <!-- Si el pedido ha sido evaluado, se mostrará la puntuación, lo mismo a continuación  -->
              @if($order->reviewed)
                <span class="rating-star-yes">{{ str_repeat('★', $item->rating) }}</span><span class="rating-star-no">{{ str_repeat('★', 5 - $item->rating) }}</span>
              @else
                <ul class="rate-area">
                  <input type="radio" id="5-star-{{$index}}" name="reviews[{{$index}}][rating]" value="5" checked /><label for="5-star-{{$index}}"></label>
                  <input type="radio" id="4-star-{{$index}}" name="reviews[{{$index}}][rating]" value="4" /><label for="4-star-{{$index}}"></label>
                  <input type="radio" id="3-star-{{$index}}" name="reviews[{{$index}}][rating]" value="3" /><label for="3-star-{{$index}}"></label>
                  <input type="radio" id="2-star-{{$index}}" name="reviews[{{$index}}][rating]" value="2" /><label for="2-star-{{$index}}"></label>
                  <input type="radio" id="1-star-{{$index}}" name="reviews[{{$index}}][rating]" value="1" /><label for="1-star-{{$index}}"></label>
                </ul>
              @endif
            </td>
            <td>
              @if($order->reviewed)
                {{ $item->review }}
              @else
                <textarea class="form-control {{ $errors->has('reviews.'.$index.'.review') ? 'is-invalid' : '' }}" name="reviews[{{$index}}][review]"></textarea>
                @if($errors->has('reviews.'.$index.'.review'))
                  @foreach($errors->get('reviews.'.$index.'.review') as $msg)
                    <span class="invalid-feedback" role="alert"><strong>{{ $msg }}</strong></span>
                  @endforeach
                @endif
              @endif
            </td>
          </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
          <td colspan="3" class="text-center">
            @if(!$order->reviewed)
              <button type="submit" class="btn btn-primary center-block">Enviar </button>
            @else
              <a href="{{ route('orders.show', [$order->id]) }}" class="btn btn-primary">Revisar orden</a>
            @endif
          </td>
        </tr>
        </tfoot>
      </table>
    </form>
  </div>
</div>
</div>
</div>
@endsection
