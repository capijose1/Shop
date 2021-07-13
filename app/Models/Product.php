<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
                    'title', 'description', 'image', 'on_sale',
                    'rating', 'sold_count', 'review_count', 'price'
    ];
    protected $casts = [
        'on_sale' => 'boolean', // on_sale es un campo booleano 
    ];
    // Asociar con SKU del producto 
    public function skus()
    {
        return $this->hasMany(ProductSku::class);
    }

    public function getImageUrlAttribute()
    {
        error_log('message here.');
        // Si el campo de la imagen en sí ya es una URL completa, regrese directamente 
        if (Str::startsWith($this->attributes['image'], ['http://', 'https://'])) {
            return $this->attributes['image'];
        }


        return Storage::disk('public')->url($this->attributes['image']);
    }
}
