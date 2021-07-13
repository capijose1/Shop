<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Models\ProductSku;

class OrderRequest extends Request
{
    public function rules()
    {
        return [
            //Determine si el ID de dirección enviado por el usuario existe en la base de datos y pertenece al usuario actual 
            // La última condición es muy importante; de ​​lo contrario, los usuarios malintencionados pueden continuar enviando pedidos con diferentes ID de dirección para atravesar las direcciones de entrega de todos los usuarios en la plataforma. 
            'address_id' => [
                'required',
                Rule::exists('user_addresses', 'id')->where('user_id', $this->user()->id),
            ],
            'items' => ['required', 'array'],
            'items.*.sku_id' => [ // Verifique el parámetro sku_id de cada submatriz en la matriz de elementos 
                'required',
                function ($attribute, $value, $fail) {
                    if (!$sku = ProductSku::find($value)) {
                        return $fail('El producto no existe ');
                    }
                    if (!$sku->product->on_sale) {
                        return $fail('Este producto no está en los estantes. ');
                    }
                    if ($sku->stock === 0) {
                        return $fail('Este articulo esta agotado ');
                    }
                    // Obtener índice actual 
                    preg_match('/items\.(\d+)\.sku_id/', $attribute, $m);
                    $index = $m[1];
                    // Encuentra el número de compras enviadas por el usuario según el índice 
                    $amount = $this->input('items')[$index]['amount'];
                    if ($amount > 0 && $amount > $sku->stock) {
                        return $fail('El producto está agotado ');
                    }
                },
            ],
            'items.*.amount' => ['required', 'integer', 'min:1'],
        ];
    }
}
