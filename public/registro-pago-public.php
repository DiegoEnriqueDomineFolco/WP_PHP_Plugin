<?php

/**
 * Gestion de visualización del formulario de solicitud de producto a traves de shortcode y manejo de solicitud via AJAX.
 */

class Registro_Pago_Public
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
    const PLUGIN_SLUG = 'registro-pago';
    const POST_TYPE = 'registro_pago';

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
            self::POST_TYPE . '_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                self::POST_TYPE . '_nonce' => wp_create_nonce(self::POST_TYPE . '_nonce'),
            )
        );

        // wp_enqueue_style(
        //     'solicitar-producto-css',
        //     plugins_url('public/css/solicitar-producto.css', dirname(__FILE__)),
        //     array(),
        //     $this->version
        // );
    }

    // // Formulario shortcode a mostrar en el front-end
    public function registro_pago_form_shortcode()
    {

        require_once plugin_dir_path(__FILE__) . '../public/views/' . self::PLUGIN_SLUG . '-form.php';
        return $html ?? '';
    }

    // Handler AJAX para guardar el registro desde AJAX
    public function registro_pago_submit_handler()
    {
        // Solo aceptar peticiones POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_send_json_error(['message' => 'Método de petición no permitido.']);
        }

        check_ajax_referer(self::POST_TYPE . '_nonce', self::POST_TYPE . '_nonce');

        // Validar y sanitizar datos
        $info = [
            'post_id'  => intval($_POST['post_id'] ?? 0),
            'dni'      => sanitize_text_field($_POST['registro-dni'] ?? ''),
            'nombre'   => sanitize_text_field($_POST['registro-nombre'] ?? ''),
            'email'    => sanitize_email($_POST['registro-email'] ?? ''),
            'telefono' => sanitize_text_field($_POST['registro-telefono'] ?? ''),
            'fecha'   => sanitize_text_field($_POST['registro-fecha'] ?? ''),
            // 'fin'      => sanitize_text_field($_POST['registro-fin'] ?? ''),
            'detalles' => sanitize_textarea_field($_POST['registro-detalles'] ?? ''),
        ];

        // Validar solo los campos obligatorios (fin es opcional)
        if (!$info['nombre'] || !$info['email'] || !$info['telefono'] || !$info['fecha']) {
            wp_send_json_error(['message' => 'Por favor completa todos los campos obligatorios.']);
        }

        // Crear el post tipo personalizado
        $post_id = wp_insert_post([
            'post_type'    => self::POST_TYPE,
            'post_title'   => $info['nombre'] . ' - ' . $info['email'],
            'post_status'  => 'private',
            'post_content' => $info['detalles'],
        ]);

        if (is_wp_error($post_id) || !$post_id) {
            wp_send_json_error(['message' => 'No se pudo guardar el registro.']);
        }

        $info['post_id'] = $post_id;

        // Guardar metadatos
        update_post_meta($post_id, 'registro_pago_post_id', $info['post_id']);
        update_post_meta($post_id, 'registro_pago_nombre', $info['nombre']);
        update_post_meta($post_id, 'registro_pago_dni', $info['dni']);
        update_post_meta($post_id, 'registro_pago_email', $info['email']);
        update_post_meta($post_id, 'registro_pago_telefono', $info['telefono']);
        update_post_meta($post_id, 'registro_pago_fecha', $info['fecha']);
        // update_post_meta($post_id, 'registro_pago_fin', $info['fin']);
        update_post_meta($post_id, 'registro_pago_detalles', $info['detalles']);
        update_post_meta($post_id, 'registro_pago_estado', 'Inicial');
        update_post_meta($post_id, 'registro_pago_leida', 0);

        $this->enviar_email_insert($info);
        // if (!$email_enviado) {
        //     wp_send_json_error(['message' => 'La solicitud se guardó pero el email no pudo enviarse.']);
        // }

        wp_send_json_success(['message' => 'Registro enviado correctamente!']);
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
        $subject = 'Nuevo registro de pago recibido';
        $message = "Se ha recibido un nuevo registro de pago:\n\n";
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
        $registro_admin_a = '<a href="' . esc_url($registro_admin_href) . '" target="_blank">' . esc_html__('Click aquí', 'solicitar-producto') . '</a>';
        $message .= "\nPuedes revisar el registro en el siguiente enlace:\n" . $registro_admin_a . "\n";

        $dashboard_admin_href = admin_url('edit.php?post_type=registro_pago');
        $dashboard_admin_a = '<a href="' . esc_url($dashboard_admin_href) . '" target="_blank">' . esc_html__('Click aquí', 'solicitar-producto') . '</a>';
        // error_log('Link admin solicitud: ' . print_r($dashboard_admin_href, true));
        $message .= "\nPuedes revisar todos los registros en el siguiente enlace:\n" . $dashboard_admin_a . "\n";

        // error_log('Enviando email de nueva solicitud a ' . $to . ' con asunto: ' . $subject);
        // error_log('mssg html: ' . print_r($message, true));
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        // return wp_mail($to, $subject, $message, $headers);
    }
}
