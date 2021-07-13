<?php

namespace App\Http\Requests;

use App\Models\ProductSku;

class AddCartRequest extends Request
{
    public function rules()
    {
        return [
            'sku_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!$sku = ProductSku::find($value)) {
                        return $fail('El producto no existe ');
                    }
                    if (!$sku->product->on_sale) {
                        return $fail('Este producto no está en los estantes.');
                    }
                    if ($sku->stock === 0) {
                        return $fail('Este articulo esta agotado');
                    }
                    if ($this->input('amount') > 0 && $sku->stock < $this->input('amount')) {
                        return $fail('El producto esta agotado');
                    }
                },
            ],
            'amount' => ['required', 'integer', 'min:1'],
        ];
    }

    public function attributes()
    {
        return [
            'amount' => 'Cantidad'
        ];
    }

    public function messages()
    {
        return [
            'sku_id.required' => 'Por favor seleccione un producto '
        ];
    }
}
