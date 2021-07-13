@extends('layouts.app')
@section('title', 'Lista de sitios de ingresos ')

@section('content')
  <div class="row">
    <div class="col-md-10 offset-md-1">
      <div class="card panel-default">
        <div class="card-header">
          Lista de sitios de ingresos
          <a href="{{ route('user_addresses.create') }}" class="float-right">Sitio de direcciones recientemente aumentado</a>
        </div>
        <div class="card-body">
          <table class="table table-bordered table-striped">
            <thead>
            <tr>
              <th>Recipiente</th>
              <th>Ruinas de la tierra</th>
              <th>Código postal</th>
              <th>Teléfono</th>
              <th>Operación</th>
            </tr>
            </thead>
            <tbody>
            @foreach($addresses as $address)
              <tr>
                <td>{{ $address->contact_name }}</td>
                <td>{{ $address->full_address }}</td>
                <td>{{ $address->zip }}</td>
                <td>{{ $address->contact_phone }}</td>
                <td>
                  <a href="{{ route('user_addresses.edit', ['user_address' => $address->id]) }}" class="btn btn-primary">Modificar</a>
                  <button class="btn btn-danger btn-del-address" type="button" data-id="{{ $address->id }}">Eliminar</button>
                </td>
              </tr>
            @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('scriptsAfterJs')
<script>
$(document).ready(function() {
  // Estuche con botones y botones
  $('.btn-del-address').click(function() {
    // Botón basado en atributos de identificación de datos, identificación del sitio de Yajoji
    var id = $(this).data('id');
    // Alerta dulce para el ajuste
    swal({
        title: "Excluido",
        icon: "warning",
        buttons: ['Cancelar', 'Confirmación'],
        dangerMode: true,
      })
    .then(function(willDelete) { // El número de botones a ajustar
      // Punto de usuario que decide si eliminará  verdadero, negación falsa
      // Cancelación de puntos de usuario
      if (!willDelete) {
        return;
      }
      // Para la interfaz de ajuste, para la URL de solicitud de interfaz que viene
      axios.delete('/user_addresses/' + id)
        .then(function () {
          // Solicitud exitosa
          location.reload();
        })
    });
  });
});
</script>
@endsection
