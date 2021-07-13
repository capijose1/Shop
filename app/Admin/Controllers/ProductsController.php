<?php

namespace App\Admin\Controllers;

use App\Models\Product;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ProductsController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Mercancía';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Product);

        $grid->id('ID')->sortable();
        $grid->title('Nombre del producto');
        $grid->on_sale('Ha sido agregado')->display(function ($value) {
            return $value ? 'Si' : 'No';
        });
        $grid->price('Precio');
        $grid->rating('Puntaje');
        $grid->sold_count('Ventas');
        $grid->review_count('Número de comentarios');

        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableDelete();
        });
        $grid->tools(function ($tools) {
            // Desactivar el botón de eliminación de lotes 
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Product);

        //Cree un cuadro de entrada, el título del primer parámetro es el nombre del campo del modelo y el segundo parámetro es la descripción del campo 
        $form->text('title', 'Nombre del producto')->rules('required');

       // Crea un cuadro para seleccionar imágenes 
        $form->image('image', 'Imagen de portada')->rules('required|image');

        //Crea un editor de texto enriquecido 
        $form->quill('description', 'Descripción del Producto')->rules('required');

        // Crear un conjunto de botones de opción 
        $form->radio('on_sale', 'Poner en la cola')->options(['1' => 'Si', '0'=> 'No'])->default('0');

        //Agregar directamente el modelo de asociación de uno a varios 
        $form->hasMany('skus', 'SKU Lista', function (Form\NestedForm $form) {
            $form->text('title', 'SKU Nombre')->rules('required');
            $form->text('description', 'SKU Descripción')->rules('required');
            $form->text('price', 'Precio unitario')->rules('required|numeric|min:0.01');
            $form->text('stock', 'Inventario restante')->rules('required|integer|min:0');
        });

        // Defina la devolución de llamada del evento, esta devolución de llamada se activará cuando el modelo esté a punto de guardarse 
        $form->saving(function (Form $form) {
            $form->model()->price = collect($form->input('skus'))->where(Form::REMOVE_FLAG_NAME, 0)->min('price') ?: 0;
        });

        return $form;
    }
}
