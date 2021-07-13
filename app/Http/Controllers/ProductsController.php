<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Exceptions\InvalidRequestException;
use App\Models\OrderItem;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        // Crea un generador de consultas 
        $builder = Product::query()->where('on_sale', true);
        // Determine si se ha enviado un parámetro de búsqueda y, de ser así, asígnelo a la variable $ search 
        // El parámetro de búsqueda se utiliza para los productos de búsqueda aproximada. 
        if ($search = $request->input('search', '')) {
            $like = '%'.$search.'%';
           // Título del producto de búsqueda aproximada, detalles del producto, título del SKU, descripción del SKU 
            $builder->where(function ($query) use ($like) {
                $query->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('skus', function ($query) use ($like) {
                        $query->where('title', 'like', $like)
                            ->orWhere('description', 'like', $like);
                    });
            });
        }

        // Si hay un parámetro de pedido enviado, si es así, asígnelo a la variable $ order 
        // El parámetro de orden se usa para controlar las reglas de clasificación de los productos 
        if ($order = $request->input('order', '')) {
            // Termina con _asc o _desc 
            if (preg_match('/^(.+)_(asc|desc)$/', $order, $m)) {
                // Si el comienzo de la cadena es una de estas 3 cadenas, es un valor de clasificación legal
                if (in_array($m[1], ['price', 'sold_count', 'rating'])) {
                    // Construya los parámetros de clasificación de acuerdo con el valor de clasificación entrante 
                    $builder->orderBy($m[1], $m[2]);
                }
            }
        }

        $products = $builder->paginate(16);

        return view('products.index', [
            'products' => $products,
            'filters'  => [
                'search' => $search,
                'order'  => $order,
            ],
        ]);
    }

    public function show(Product $product, Request $request)
    {
        if (!$product->on_sale) {
            throw new InvalidRequestException('商品未上架');
        }

        $favored = false;
        // Cuando el usuario no está conectado, se devuelve un valor nulo y, cuando está conectado, se devuelve el objeto de usuario correspondiente. 
        if($user = $request->user()) {
            // Busque el producto cuya identificación es la identificación del producto actual de los productos que el usuario actual ha marcado como favorito 
           // La función boolval () se usa para convertir el valor a booleano 
            $favored = boolval($user->favoriteProducts()->find($product->id));
        }

        $reviews = OrderItem::query()
            ->with(['order.user', 'productSku']) //Relaciones precargadas 
            ->where('product_id', $product->id)
            ->whereNotNull('reviewed_at') // Filtrar lo evaluado 
            ->orderBy('reviewed_at', 'desc') // Orden inverso por tiempo de evaluación 
            ->limit(10) // Sacar 10 
            ->get();

        // Por último, no olvide inyectarlo en la plantilla. 
        return view('products.show', [
            'product' => $product,
            'favored' => $favored,
            'reviews' => $reviews
        ]);
    }

    public function favor(Product $product, Request $request)
    {
        $user = $request->user();
        if ($user->favoriteProducts()->find($product->id)) {
            return [];
        }

        $user->favoriteProducts()->attach($product);

        return [];
    }

    public function disfavor(Product $product, Request $request)
    {
        $user = $request->user();
        $user->favoriteProducts()->detach($product);

        return [];
    }

    public function favorites(Request $request)
    {
        $products = $request->user()->favoriteProducts()->paginate(16);

        return view('products.favorites', ['products' => $products]);
    }
}
