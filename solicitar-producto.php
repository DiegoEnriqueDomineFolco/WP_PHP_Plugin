<?php

/**
 * Plugin Name: Solicitar Producto
 * Description: Plugin para solicitar productos sin necesidad de de tienda. Shortcode: [solicitar_producto_form], (public form para solicitar producto).
 * despliega un formulario para solicitar productos. CPT 'solicitud_producto' para gestionar las solicitudes.
 * Añade una página en el admin para gestionar las solicitudes recibidas, todas y por estado (Inicial, Vista, Pagada, Cancelada); 
 * cuenta las solicitudes "leídas" y despliega un contador en el menú.
 * Ordenamiento y filtros customizados en la lista de solicitudes en el admin.
 * AJAX para enviar solicitudes sin recargar la página.
 * Version History:
 * 1.0.0 - Versión beta final (con todo lo descripto arriba).
 * 2.0.0 - Gestión comprobantes de pago. Shortcode: [registro_pago_form], (public form para registrar pago). Menu en admin para ver comprobantes recibidos. CPT 'registro_pago'.
 * Version: 2.0.0
 * Author: Diego & Chaty & Copi & Dios
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 * Rename this for your plugin and update it as you release new versions.
 */
define('SOLICITAR_PRODUCTO_VERSION', '2.0.0');

/*
* PLUGIN BASE DIR constant.
*/
define('SOLICITAR_PRODUCTO_DIR', plugin_dir_path(__FILE__));

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/Main.php';

// /**
// * The code that runs during plugin activation.
// * This action is documented in includes/Activator.php
// */

// function activate_solicitar_producto(){
//     require_once plugin_dir_path( __FILE__ ) . 'includes/Activator.php';
//     Solicitar_Producto_Activator::activate();
// }

// /**
// * The code that runs during plugin deactivation.
// * This action is documented in includes/Deactivator.php
// */

// function deactivate_solicitar_producto() {
//     require_once plugin_dir_path( __FILE__ ) . 'includes/Deactivator.php';
//     Solicitar_Producto_Deactivator::deactivate();
// }

// register_activation_hook( __FILE__, 'activate_solicitar_producto' );
// register_deactivation_hook( __FILE__, 'deactivate_solicitar_producto' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_solicitar_producto()
{

    $plugin = new Solicitar_Producto_Plugin();
    $plugin->run();
}

// Kick off the plugin.
run_solicitar_producto();
