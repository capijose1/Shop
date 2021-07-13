<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class AdminTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // base tables
        \Encore\Admin\Auth\Database\Menu::truncate();
        \Encore\Admin\Auth\Database\Menu::insert(
            [
                [
                    "icon" => "fa-bar-chart",
                    "order" => 1,
                    "parent_id" => 0,
                    "permission" => NULL,
                    "title" => "Casa",
                    "uri" => "/"
                ],
                [
                    "icon" => "fa-tasks",
                    "order" => 6,
                    "parent_id" => 0,
                    "permission" => NULL,
                    "title" => "Gestión del sistema",
                    "uri" => NULL
                ],
                [
                    "icon" => "fa-users",
                    "order" => 7,
                    "parent_id" => 2,
                    "permission" => NULL,
                    "title" => "Administrador",
                    "uri" => "auth/users"
                ],
                [
                    "icon" => "fa-user",
                    "order" => 8,
                    "parent_id" => 2,
                    "permission" => NULL,
                    "title" => "Personaje",
                    "uri" => "auth/roles"
                ],
                [
                    "icon" => "fa-ban",
                    "order" => 9,
                    "parent_id" => 2,
                    "permission" => NULL,
                    "title" => "Autoridad",
                    "uri" => "auth/permissions"
                ],
                [
                    "icon" => "fa-bars",
                    "order" => 10,
                    "parent_id" => 2,
                    "permission" => NULL,
                    "title" => "Menu",
                    "uri" => "auth/menu"
                ],
                [
                    "icon" => "fa-history",
                    "order" => 11,
                    "parent_id" => 2,
                    "permission" => NULL,
                    "title" => "Registro de operaciones",
                    "uri" => "auth/logs"
                ],
                [
                    "icon" => "fa-user",
                    "order" => 3,
                    "parent_id" => 0,
                    "permission" => NULL,
                    "title" => "Gestión de usuarios",
                    "uri" => "/users"
                ],
                [
                    "icon" => "fa-cubes",
                    "order" => 4,
                    "parent_id" => 0,
                    "permission" => NULL,
                    "title" => "Gestión de productos basicos",
                    "uri" => "/products"
                ],
                [
                    "icon" => "fa-rmb",
                    "order" => 2,
                    "parent_id" => 0,
                    "permission" => NULL,
                    "title" => "Gestión de pedidos",
                    "uri" => "/orders"
                ],
                [
                    "icon" => "fa-tags",
                    "order" => 5,
                    "parent_id" => 0,
                    "permission" => NULL,
                    "title" => "Gestión de cupones",
                    "uri" => "/coupon_codes"
                ]
            ]
        );

        \Encore\Admin\Auth\Database\Permission::truncate();
        \Encore\Admin\Auth\Database\Permission::insert(
            [
                [
                    "http_method" => "",
                    "http_path" => "*",
                    "name" => "All permission",
                    "slug" => "*"
                ],
                [
                    "http_method" => "GET",
                    "http_path" => "/",
                    "name" => "Dashboard",
                    "slug" => "dashboard"
                ],
                [
                    "http_method" => "",
                    "http_path" => "/auth/login\r\n/auth/logout",
                    "name" => "Login",
                    "slug" => "auth.login"
                ],
                [
                    "http_method" => "GET,PUT",
                    "http_path" => "/auth/setting",
                    "name" => "User setting",
                    "slug" => "auth.setting"
                ],
                [
                    "http_method" => "",
                    "http_path" => "/auth/roles\r\n/auth/permissions\r\n/auth/menu\r\n/auth/logs",
                    "name" => "Auth management",
                    "slug" => "auth.management"
                ],
                [
                    "http_method" => "",
                    "http_path" => "/users*",
                    "name" => "Gestión de usuarios",
                    "slug" => "users"
                ],
                [
                    "http_method" => "",
                    "http_path" => "/products*",
                    "name" => "Gestión de productos básicos",
                    "slug" => "products"
                ],
                [
                    "http_method" => "",
                    "http_path" => "/coupon_codes*",
                    "name" => "Gestión de cupones",
                    "slug" => "coupon_codes"
                ],
                [
                    "http_method" => "",
                    "http_path" => "/orders*",
                    "name" => "Gestión de pedidos",
                    "slug" => "orders"
                ]
            ]
        );

        \Encore\Admin\Auth\Database\Role::truncate();
        \Encore\Admin\Auth\Database\Role::insert(
            [
                [
                    "name" => "Administrator",
                    "slug" => "administrator"
                ],
                [
                    "name" => "Operación",
                    "slug" => "operation"
                ]
            ]
        );

        // pivot tables
        DB::table('admin_role_menu')->truncate();
        DB::table('admin_role_menu')->insert(
            [
                [
                    "menu_id" => 2,
                    "role_id" => 1
                ]
            ]
        );

        DB::table('admin_role_permissions')->truncate();
        DB::table('admin_role_permissions')->insert(
            [
                [
                    "permission_id" => 1,
                    "role_id" => 1
                ],
                [
                    "permission_id" => 2,
                    "role_id" => 2
                ],
                [
                    "permission_id" => 3,
                    "role_id" => 2
                ],
                [
                    "permission_id" => 4,
                    "role_id" => 2
                ],
                [
                    "permission_id" => 6,
                    "role_id" => 2
                ],
                [
                    "permission_id" => 7,
                    "role_id" => 2
                ],
                [
                    "permission_id" => 8,
                    "role_id" => 2
                ],
                [
                    "permission_id" => 9,
                    "role_id" => 2
                ]
            ]
        );

        // finish
    }
}
