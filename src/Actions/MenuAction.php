<?php

namespace App\Actions;

class MenuAction
{
    /**
     * Devuelve todas las categorías para llenar widget de categorías dentro del home.
     */
    public function getCategoriasMenu()
    {
        $categorias = Categoria::all()->load('imagenes')->toArray();

        return $categorias;
    }

    /**
     * Devuelve los datos para llenar el carrito del menu.
     */
    public function getInformacionPedido()
    {
        $pedido = '';

        if ($_SESSION['user']) {
            // TODO: if first time should change from pedido_id to user id
            /*if ($_SESSION['pedido_id']) {
                $pedido = Pedido::find($_SESSION['pedido_id']);
            }*/
            $pedido = Pedido::where([['user_id', '=', $_SESSION['user']], ['estado', '=', null]])->get();
            if (!$pedido->isEmpty()) {
                $pedido->load('productos', 'productos.imagenes');
                $pedido = $pedido->first()->toArray();
            } else {
                $pedido = Pedido::create(['user_id' => $_SESSION['user']]);
                $pedido->load('productos', 'productos.imagenes');
                $pedido = $pedido->toArray();
            }
        } elseif ($_SESSION['pedido_id']) {
            $pedido = Pedido::find($_SESSION['pedido_id']);
            $pedido->load('productos', 'productos.imagenes');
            $pedido = $pedido->toArray();
            //print_r($pedido);die();
        } else {
            $pedido = Pedido::create();
            $_SESSION['pedido_id'] = $pedido->id;
            $pedido->load('productos', 'productos.imagenes');
            $pedido = $pedido->toArray();
        }

        return $pedido;
    }
}
