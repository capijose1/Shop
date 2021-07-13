<?php

return [

    /*
     * Título del sitio 
     */
    'name' => 'Laravel Shop',

    /*
     * Logotipo en la parte superior de la página. 
     */
    'logo' => '<b>Laravel</b> Shop',

    /*
     * Pequeño logo en la parte superior de la página. 
     */
    'logo-mini' => '<b>LS</b>',

    /*
     * Ruta del archivo de inicio de Laravel-Admin
     */
    'bootstrap' => app_path('Admin/bootstrap.php'),

    /*
     *Configuración de enrutamiento 
     */
    'route' => [
        // prefijo de enrutamiento
        'prefix' => env('ADMIN_ROUTE_PREFIX', 'admin'),
        // Prefijo del espacio de nombres del controlador 
        'namespace' => 'App\\Admin\\Controllers',
        // Lista de middleware predeterminada 
        'middleware' => ['web', 'admin'],
    ],

    /*
     * Laravel-Admin Directorio de instalación 
     */
    'directory' => app_path('Admin'),

    /*
     * Título de la página de Laravel-Admin 
     */
    'title' => 'Laravel Shop Experiencia en gestión ',

    /*
     * usar o no  https
     */
    'secure' => env('ADMIN_HTTPS', false),

    /*
     * Laravel-Admin Configuración de autenticación de usuario 
     */
    'auth' => [

        'controller' => App\Admin\Controllers\AuthController::class,

        'guards' => [
            'admin' => [
                'driver'   => 'session',
                'provider' => 'admin',
            ],
        ],

        'providers' => [
            'admin' => [
                'driver' => 'eloquent',
                'model'  => Encore\Admin\Auth\Database\Administrator::class,
            ],
        ],

        // Ya sea para mostrar la opción de mantener la sesión iniciada 
        'remember' => true,

        // URL de la página de inicio de sesión 
        'redirect_to' => 'auth/login',

        //Dirección a la que se puede acceder sin autenticación de usuario 
        'excepts' => [
            'auth/login',
            'auth/logout',
            '_handle_action_',
        ]
    ],

    /*
     * Laravel-Admin Configuración de carga de archivos 
     */
    'upload' => [
        //Correspondiente a discos en filesystem.php 
        'disk' => 'public',

        'directory' => [
            'video'=>'video',
            'image' => 'images',
            'music' => 'music',
            'file'  => 'files',
        ],
    ],

    /*
     * Laravel-Admin Configuración de la base de datos 
     */
    'database' => [

     // Nombre de la conexión a la base de datos, déjelo en blanco 
        'connection' => '',

       // Tabla y modelo de usuario administrador
        'users_table' => 'admin_users',
        'users_model' => Encore\Admin\Auth\Database\Administrator::class,

        // Tabla de roles y modelo 
        'roles_table' => 'admin_roles',
        'roles_model' => Encore\Admin\Auth\Database\Role::class,

        // Tabla de permisos y modelo 
        'permissions_table' => 'admin_permissions',
        'permissions_model' => Encore\Admin\Auth\Database\Permission::class,

        // Tabla de menú y modelo 
        'menu_table' => 'admin_menu',
        'menu_model' => Encore\Admin\Auth\Database\Menu::class,

        // Tabla intermedia asociativa de varios a varios 
        'operation_log_table'    => 'admin_operation_log',
        'user_permissions_table' => 'admin_user_permissions',
        'role_users_table'       => 'admin_role_users',
        'role_permissions_table' => 'admin_role_permissions',
        'role_menu_table'        => 'admin_role_menu',
    ],

    /*
     * Laravel-Admin Configuración del registro de operaciones 
     */
    'operation_log' => [
        /*
         * Solo registre los siguientes tipos de solicitudes 
         */
        'allowed_methods' => ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'TRACE', 'PATCH'],

        'enable' => true,

        /*
         *Ruta sin registro de operaciones 
         */
        'except' => [
           'admin/auth/logs*',
        ],
    ],

    /*
    * Si los permisos de verificación de enrutamiento 
    */
    'check_route_permission' => true,

    /*
     * ¿El menú verifica los permisos? 
    */
    'check_menu_roles'       => true,

    /*
    * Avatar predeterminado del administrador 
    */
    'default_avatar' => '/vendor/laravel-admin/AdminLTE/dist/img/user2-160x160.jpg',

    /*
     * Proveedor de componentes de mapa 
     */
    'map_provider' => 'google',

    /*
     * Estilo de página 
     * @see https://adminlte.io/docs/2.4/layout
     */
    'skin' => 'skin-blue-light',

    /*
     *Diseño de fondo 
     */
    'layout' => ['sidebar-mini', 'sidebar-collapse'],

    /*
     * Imagen de fondo de la página de inicio de sesión 
     */
    'login_background_image' => '',

    /*
     * Mostrar versión 
     */
    'show_version' => true,

    /*
     * Entorno de visualización 
     */
    'show_environment' => true,

    /*
     * Permisos de enlace de menú 
     */
    'menu_bind_permission' => true,

    /*
     * Breadcrumbs habilitado de forma predeterminada 

     */
    'enable_default_breadcrumb' => true,

    /*
    * Comprimir archivos de recursos 

    */
    'minify_assets' => [
        // Recursos que no necesitan comprimirse 
        'excepts' => [

        ],
    ],
    /*
    * Habilitar búsqueda de menú 
    */
    'enable_menu_search' => true,
    /*
    * Mensaje de advertencia superior 
    */
    'top_alert' => '',
    /*
    * Estilo de visualización de funcionamiento de la mesa 
    */
    'grid_action_class' => \Encore\Admin\Grid\Displayers\DropdownActions::class,
    /*
     * El directorio donde se encuentra la extensión .
     */
    'extension_dir' => app_path('Admin/Extensions'),

    /*
     * Configuraciones extendidas .
     */
    'extensions' => [
        // Agregar inicio de configuración del editor 
        'quill' => [
            // If the value is set to false, this extension will be disabled
            'enable' => true,
            'config' => [
                'modules' => [
                    'syntax' => true,
                    'toolbar' =>
                        [
                            ['size' => []],
                            ['header' => []],
                            'bold',
                            'italic',
                            'underline',
                            'strike',
                            ['script' => 'super'],
                            ['script' => 'sub'],
                            ['color' => []],
                            ['background' => []],
                            'blockquote',
                            'code-block',
                            ['list' => 'ordered'],
                            ['list' => 'bullet'],
                            ['indent' => '-1'],
                            ['indent' => '+1'],
                            'direction',
                            ['align' => []],
                            'link',
                            'image',
                            'video',
                            'formula',
                            'clean'
                        ],
                ],
                'theme' => 'snow',
                'height' => '200px',
            ]
        ]
        // El final de la nueva configuración del editor 
    ]
];
