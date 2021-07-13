<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Exception;

class CouponCodeUnavailableException extends Exception
{
    public function __construct($message, int $code = 403)
    {
        parent::__construct($message, $code);
    }

    // Cuando se activa esta excepción, se llamará al método de renderizado para mostrar al usuario
    public function render(Request $request)
    {
        //Si el usuario solicita a través de Api, se devolverá un mensaje de error en formato JSON
        if ($request->expectsJson()) {
            return response()->json(['msg' => $this->message], $this->code);
        }
        // De lo contrario, vuelva a la página anterior con un mensaje de error.
        return redirect()->back()->withErrors(['coupon_code' => $this->message]);
    }
}
