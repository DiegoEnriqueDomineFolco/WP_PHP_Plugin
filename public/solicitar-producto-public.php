<?php

/**
 * Gestion de visualización del formulario de solicitud de producto a traves de shortcode y manejo de solicitud via AJAX.
 */

class Solicitar_Producto_Public
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_title    The ID of this plugin.
     */
    private $plugin_title;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**CONSTANTES INTERNAS */
    const PLUGIN_SLUG = 'solicitar-producto';
    const POST_TYPE = 'solicitud_producto';

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_title       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_title, $version)
    {

        $this->plugin_title = $plugin_title;
        $this->version = $version;
    }

    /**
     * Encola todos los assets (JS y CSS) necesarios para el formulario de solicitud de producto.
     * Unificado para DRY.
     */
    public function enqueue_assets()
    {
        wp_enqueue_script(
            self::PLUGIN_SLUG . '-display',
            plugins_url('public/js/' . self::PLUGIN_SLUG . '-display.js', dirname(__FILE__)),
            array('jquery'),
            $this->version,
            true
        );

        // Localizar datos para el script JS (AJAX URL y nonce)
        wp_localize_script(
            self::PLUGIN_SLUG . '-display',
            'solicitar_producto_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'solicitar_producto_nonce' => wp_create_nonce('solicitar_producto_nonce'),
            )
        );

        wp_enqueue_style(
            self::PLUGIN_SLUG . '-css',
            plugins_url('public/css/' . self::PLUGIN_SLUG . '.css', dirname(__FILE__)),
            array(),
            $this->version
        );
    }

    // // Formulario shortcode a mostrar en el front-end
    public function solicitar_producto_form_shortcode()
    {

        require_once plugin_dir_path(__FILE__) . '../public/views/' . self::PLUGIN_SLUG . '-form.php';
        return $html ?? '';
    }

    // Handler AJAX para guardar el registro desde AJAX
    public function solicitar_producto_submit_handler()
    {
        // Solo aceptar peticiones POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_send_json_error(['message' => 'Método de petición no permitido.']);
        }

        check_ajax_referer('solicitar_producto_nonce', 'solicitar_producto_nonce');

        // Validar y sanitizar datos
        $info = [
            'post_id'  => intval($_POST['post_id'] ?? 0),
            'dni'      => sanitize_text_field($_POST['registro-dni'] ?? ''),
            'nombre'   => sanitize_text_field($_POST['registro-nombre'] ?? ''),
            'email'    => sanitize_email($_POST['registro-email'] ?? ''),
            'telefono' => sanitize_text_field($_POST['registro-telefono'] ?? ''),
            'inicio'   => sanitize_text_field($_POST['registro-inicio'] ?? ''),
            'fin'      => sanitize_text_field($_POST['registro-fin'] ?? ''),
            'detalles' => sanitize_textarea_field($_POST['registro-detalles'] ?? ''),
        ];

        $result = $this->crear_solicitud($info);
        if (!$result['success']) {
            wp_send_json_error(['message' => $result['message']]);
        }
        wp_send_json_success(['message' => '¡Registro enviado correctamente!']);
    }

    /**
     * Crea una solicitud de producto y guarda los metadatos. Retorna ['success'=>bool, 'post_id'=>int, 'message'=>string]
     */
    public function crear_solicitud($info) {
        // Validar solo los campos obligatorios
        if (!$info['nombre'] || !$info['email'] || !$info['telefono'] || !$info['inicio'] || !$info['fin'] || !$info['dni']) {
            return ['success' => false, 'message' => 'Por favor completa todos los campos obligatorios.'];
        }

        // Crear el post tipo personalizado
        $post_id = wp_insert_post([
            'post_type'    => 'solicitud_producto',
            'post_title'   => $info['nombre'] . ' - ' . $info['email'],
            'post_status'  => 'private',
            'post_content' => $info['detalles'],
        ]);

        if (is_wp_error($post_id) || !$post_id) {
            return ['success' => false, 'message' => 'No se pudo guardar el registro.'];
        }

        // Guardar metadatos
        update_post_meta($post_id, 'solicitud_post_id', $info['post_id']);
        update_post_meta($post_id, 'solicitud_nombre', $info['nombre']);
        update_post_meta($post_id, 'solicitud_dni', $info['dni']);
        update_post_meta($post_id, 'solicitud_email', $info['email']);
        update_post_meta($post_id, 'solicitud_telefono', $info['telefono']);
        update_post_meta($post_id, 'solicitud_inicio', $info['inicio']);
        update_post_meta($post_id, 'solicitud_fin', $info['fin']);
        update_post_meta($post_id, 'solicitud_detalles', $info['detalles']);
        update_post_meta($post_id, 'solicitud_estado', 'Inicial');
        update_post_meta($post_id, 'solicitud_leida', 0);

        $this->enviar_email_insert($info);

        return ['success' => true, 'creada_post_id' => $post_id, 'message' => 'Solicitud creada correctamente.'];
    }

    /**
     * Envía un email con los datos de la solicitud recibida.
     * @param array $data Datos de la solicitud
     * @return bool True si el email se envió correctamente, false si falló
     */
    private function enviar_email_insert($data)
    {
        $registro_post_id = $data['post_id'];

        $to = get_option('admin_email');
        $subject = 'Nueva solicitud de producto recibida';
        $message = "Se ha recibido una nueva solicitud de producto:\n\n";
        $message .= "Nombre: " . $data['nombre'] . "\n";
        $message .= "Documento: " . $data['dni'] . "\n";
        $message .= "Email: " . $data['email'] . "\n";
        $message .= "Teléfono: " . $data['telefono'] . "\n";
        $message .= "Inicio de reserva: " . $data['inicio'] . "\n";
        $message .= "Fin de reserva: " . ($data['fin'] ? $data['fin'] : 'No especificado') . "\n";
        $message .= "Detalles: " . $data['detalles'] . "\n";

        // Enlaces útiles <a ... >click aquí</a>
        $registro_admin_href = admin_url('post.php?post=' . $registro_post_id . '&action=edit');
        // error_log('Link admin a: ' . print_r($registro_admin_href, true));
        $registro_admin_a = '<a href="' . esc_url($registro_admin_href) . '" target="_blank">' . esc_html__('Click aquí', self::PLUGIN_SLUG) . '</a>';
        $message .= "\nPuedes revisar la solicitud en el siguiente enlace:\n" . $registro_admin_a . "\n";

        $dashboard_admin_href = admin_url('edit.php?post_type=' . self::POST_TYPE);
        $dashboard_admin_a = '<a href="' . esc_url($dashboard_admin_href) . '" target="_blank">' . esc_html__('Click aquí', self::PLUGIN_SLUG) . '</a>';
        // error_log('Link admin solicitud: ' . print_r($dashboard_admin_href, true));
        $message .= "\nPuedes revisar todas las solicitudes en el siguiente enlace:\n" . $dashboard_admin_a . "\n";

        // error_log('Enviando email de nueva solicitud a ' . $to . ' con asunto: ' . $subject);
        // error_log('mssg html: ' . print_r($message, true));
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        // return wp_mail($to, $subject, $message, $headers);
    }
}
