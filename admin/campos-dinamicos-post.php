<?php

class Campos_Dinamicos_Post
{
    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_title    The string used to uniquely identify this plugin.
     */
    protected $plugin_title;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    // Constantes reutilizables
    const META_KEY = '_campos_dinamicos';
    const PAGE_SLUG = 'campos-dinamicos-post';
    const NONCE_NAME = 'campos_dinamicos_nonce';
    const TEXT_DOMAIN = 'solicitar-producto';
    const FIELD_KEY = 'key';
    const FIELD_VALUE = 'value';
    // Tipos de campo soportados (extensible)
    const FIELD_TYPES = [
        'text' => 'Texto'
    ];

    /**
     * @var Agenda_Manager $agenda_manager Instancia para gestión de agenda/reservas
     */
    private $agenda_manager;


    /**
     * @var Solicitar_Producto_Public $solicitud_public Instancia para gestión de solicitudes de producto
     */
    private $solicitud_public;

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

        // Incluir la clase Agenda_Manager y asignar instancia
        require_once plugin_dir_path(__FILE__) . '../includes/Agenda_Manager.php';
        $this->agenda_manager = new Agenda_Manager($plugin_title, $version);

        // Incluir la clase Campos_Dinamicos_List_Table solo si estamos en admin
        if (is_admin()) {
            require_once plugin_dir_path(__FILE__) . 'class-campos-dinamicos-list-table.php';
        }

        // Incluir la clase Solicitar_Producto_Public para crear solicitudes
        require_once plugin_dir_path(__FILE__) . '../public/solicitar-producto-public.php';
        $this->solicitud_public = new Solicitar_Producto_Public($this->plugin_title, $this->version);

        // Registrar el shortcode
        add_shortcode('obtener_valor_post', [$this, 'shortcode_obtener_valor_post']);

        // Encolar scripts solo en admin
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // Endpoint AJAX para validar clave duplicada
        add_action('wp_ajax_campos_dinamicos_check_key', [$this, 'ajax_check_key']);

        // Hooks para procesar acciones desde formularios
        add_action('admin_post_guardar_reserva_manual', [$this, 'procesar_alta_reserva_y_solicitud']);
        add_action('admin_post_guardar_campos_dinamicos', [$this, 'procesar_guardado_campos_dinamicos']);
    }

    /**
     * Devuelve una instancia de la tabla de campos dinámicos solo en admin.
     */
    public function get_list_table()
    {
        if (is_admin() && class_exists('Campos_Dinamicos_List_Table')) {
            return new Campos_Dinamicos_List_Table();
        }
        return null;
    }

    /**
     * Endpoint AJAX para validar si una clave ya existe en los campos dinámicos de un post.
     * Recibe: key, post_id, nonce
     * Devuelve: success true/false y mensaje
     */
    public function ajax_check_key()
    {
        check_ajax_referer('campos_dinamicos_nonce', 'nonce');
        $key = isset($_POST['key']) ? sanitize_text_field($_POST['key']) : '';
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$key || !$post_id) {
            wp_send_json_error(['message' => 'Datos insuficientes.']);
        }
        $campos = get_post_meta($post_id, self::META_KEY, true);
        if (!is_array($campos)) {
            $campos = [];
        }
        foreach ($campos as $campo) {
            if (isset($campo['key']) && $campo['key'] === $key) {
                wp_send_json_success(['exists' => true, 'message' => 'La clave ya existe.']);
            }
        }
        wp_send_json_success(['exists' => false, 'message' => 'La clave está disponible.']);
    }



    /**
     * Encola los scripts y estilos necesarios para los campos dinámicos en el admin.
     */
    public function enqueue_assets()
    {
        $screen = get_current_screen();
        // Solo en admin y en la página de edición de campos dinámicos/metabox
        if (is_admin() && (
            $screen->id === 'post' ||
            $screen->id === 'toplevel_page_' . self::PAGE_SLUG ||
            $screen->id === self::PAGE_SLUG
        )) {
            wp_enqueue_script(
                'campos-dinamicos-js',
                plugins_url('admin/js/campos-dinamicos.js', dirname(__FILE__)),
                array('jquery'),
                null,
                true
            );
            // Pasar datos PHP a JS: URL de AJAX y nonce de seguridad
            wp_localize_script('campos-dinamicos-js', 'CamposDinamicosVars', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('campos_dinamicos_nonce')
            ));
        }
    }

    /**
     * Shortcode para obtener el valor de un campo dinámico por clave y post_id
     * Uso: [obtener_valor_post key="mi_clave" post_id="123"]
     */
    public function shortcode_obtener_valor_post($atts)
    {
        $atts = shortcode_atts([
            'key' => '',
            'post_id' => ''
        ], $atts);

        $key = sanitize_text_field($atts['key']);
        $post_id = $atts['post_id'] ? intval($atts['post_id']) : get_the_ID();
        if (!$key || !$post_id) {
            return '';
        }
        $campos = get_post_meta($post_id, self::META_KEY, true);
        if (!is_array($campos)) {
            return '';
        }
        foreach ($campos as $campo) {
            if (isset($campo['key']) && $campo['key'] === $key) {
                return esc_html($campo['value']);
            }
        }
        return '';
    }

    /**
     * Agrega una página personalizada en el dashboard para editar campos dinámicos de un post.
     */
    public function add_admin_page()
    {
        add_menu_page(
            __('Campos dinámicos', self::TEXT_DOMAIN),
            __('Campos dinámicos', self::TEXT_DOMAIN),
            'edit_posts',
            self::PAGE_SLUG,
            [$this, 'render_admin_page'],
            'dashicons-list-view',
            80
        );
    }

    /**
     * Renderiza la página personalizada para editar los campos dinámicos de un post.
     */
    public function render_admin_page()
    {
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        if (!$post_id) {
            $list_table = $this->get_list_table();
            if ($list_table) {
                $list_table->prepare_items();
                include plugin_dir_path(__FILE__) . 'views/campos-dinamicos-list.php';
            } else {
                echo '<div class="notice notice-error"><p>No se pudo cargar la tabla de campos dinámicos.</p></div>';
            }
            return;
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'post') {
            echo '<div class="notice notice-error"><p>' . __('El post no existe o no es una Entrada estándar.', self::TEXT_DOMAIN) . '</p></div>';
            return;
        }


        $error_msg = '';
        // Solo renderizar la vista y mensajes
        $this->render_campos_dinamicos_view($post, $post_id, $error_msg);

        // Renderizar la vista de agenda/reservas asociada
        $this->agenda_manager->render_agenda_admin_view($post_id);
    }

    // Procesa alta de reserva manual y solicitud enlazada (hook admin_post)
    public function procesar_alta_reserva_y_solicitud()
    {
        if (!current_user_can('edit_posts')) {
            wp_die('No tienes permisos suficientes.');
        }

        check_admin_referer('alta_reserva_manual', 'alta_reserva_nonce');

        $post_id = intval($_POST['post_id'] ?? 0);
        $info = [
            'post_id'  => $post_id,
            'dni'      => sanitize_text_field($_POST['solicitud']['dni'] ?? ''),
            'nombre'   => sanitize_text_field($_POST['solicitud']['nombre'] ?? ''),
            'email'    => sanitize_email($_POST['solicitud']['email'] ?? ''),
            'telefono' => sanitize_text_field($_POST['solicitud']['telefono'] ?? ''),
            'inicio'   => sanitize_text_field($_POST['solicitud']['inicio'] ?? ''),
            'fin'      => sanitize_text_field($_POST['solicitud']['fin'] ?? ''),
            'detalles' => sanitize_textarea_field($_POST['solicitud']['detalles'] ?? ''),
        ];

        $solicitud_result = $this->solicitud_public->crear_solicitud($info);

        if (!$solicitud_result['success']) {
            wp_redirect(add_query_arg('msg', urlencode($solicitud_result['message']), wp_get_referer()));
            exit;
        }

        // Crear la reserva manual enlazada a la solicitud
        $reserva = [
            'solicitud_id' => $solicitud_result['creada_post_id'],
            'fecha_inicio' => $info['inicio'],
            'fecha_fin' => $info['fin'],
            'timestamp_db' => current_time('mysql')
        ];

        $resultado = $this->agenda_manager->procesar_alta_reserva_manual($post_id, $reserva);
        $msg = $resultado['success'] ? 'Solicitud y reserva creadas correctamente.' : 'Solicitud creada, pero error al crear la reserva: ' . $resultado['message'];
        wp_redirect(add_query_arg('msg', urlencode($msg), wp_get_referer()));
        exit;
    }

    // Procesa guardado de campos dinámicos (hook admin_post)
    public function procesar_guardado_campos_dinamicos()
    {
        if (!current_user_can('edit_posts')) {
            wp_die('No tienes permisos suficientes.');
        }
        $post_id = intval($_POST['post_id'] ?? 0);
        $result = $this->save_campos_dinamicos($post_id);
        $msg = '';
        if ($result === true) {
            $msg = __('Campos guardados correctamente.', self::TEXT_DOMAIN);
        } elseif (is_string($result) && $result !== '') {
            $msg = $result;
        }
        wp_redirect(add_query_arg('msg', urlencode($msg), wp_get_referer()));
        exit;
    }

    /**
     * Agrega el metabox de campos dinámicos solo en posts estándar.
     */
    public function add_campos_dinamicos_metabox()
    {
        add_meta_box(
            'campos-dinamicos-metabox',
            __('Campos dinámicos', 'solicitar-producto'),
            [$this, 'render_campos_dinamicos_metabox'],
            'post',
            'normal',
            'default'
        );
    }

    /**
     * Renderiza el contenido del metabox de campos dinámicos.
     */
    public function render_campos_dinamicos_metabox($post)
    {
        /**
         * Callback requerido por WordPress para renderizar el contenido del metabox.
         * No imprime HTML directamente, solo prepara los datos y delega al view.
         * Si se elimina este método, el metabox no se mostrará en la edición de posts.
         */
        $campos = get_post_meta($post->ID, self::META_KEY, true);
        if (!is_array($campos)) {
            $campos = [];
        }
        $post_id = $post->ID;
        $error_msg = '';
        $this->render_campos_dinamicos_view($post, $campos, $post_id, $error_msg);
    }

    /**
     * Guarda los campos dinámicos al guardar el post.
     */
    public function save_campos_dinamicos($post_id)
    {
        if (!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce($_POST[self::NONCE_NAME], self::NONCE_NAME)) {
            return '';
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return '';
        }
        if (!current_user_can('edit_post', $post_id)) {
            return '';
        }
        if (isset($_POST['campos_dinamicos']) && is_array($_POST['campos_dinamicos'])) {
            $result = $this->validate_and_sanitize_campos($_POST['campos_dinamicos']);
            $campos = $result['campos'];
            $error = $result['error'];
            if ($error) {
                return $error;
            }
            update_post_meta($post_id, self::META_KEY, $campos);
            return true;
        } else {
            delete_post_meta($post_id, self::META_KEY);
            return true;
        }
    }

    /**
     * Agrega el botón "Campos dinámicos" en las acciones rápidas del listado de Entradas (posts estándar).
     * Solo aparece para el post_type 'post' y usuarios con permisos de edición.
     */
    public function add_campos_dinamicos_action($actions, $post)
    {
        // Solo para posts estándar y usuarios con permisos
        if ($post->post_type === 'post' && current_user_can('edit_post', $post->ID)) {
            $admin_url = admin_url('admin.php?page=' . self::PAGE_SLUG . '&post_id=' . $post->ID);
            $actions['campos_dinamicos'] = '<a href="' . esc_url($admin_url) . '">' . esc_html__('Campos dinámicos', self::TEXT_DOMAIN) . '</a>';
        }
        return $actions;
    }

    /*************************** UTILS *************************/

    /**
     * Renderiza el view del metabox con los datos necesarios.
     */
    private function render_campos_dinamicos_view($post, $post_id, $error_msg = '')
    {
        // Obtener los campos actuales del post
        $campos = get_post_meta($post->ID, self::META_KEY, true);
        if (!is_array($campos)) {
            $campos = [];
        }

        // Renderiza el archivo view con los datos necesarios
        include plugin_dir_path(__FILE__) . 'views/campos-dinamicos-metabox.php';
    }

    /**
     * Valida y sanitiza los campos dinámicos recibidos por POST.
     * Devuelve array limpio y un mensaje de error si hay claves duplicadas.
     */
    private function validate_and_sanitize_campos($campos_raw)
    {
        // Valida y sanitiza los campos dinámicos recibidos por POST
        $campos = [];
        $keys = [];
        $error = '';
        foreach ($campos_raw as $campo) {
            $key = sanitize_text_field($campo[self::FIELD_KEY] ?? '');
            $value = sanitize_text_field($campo[self::FIELD_VALUE] ?? '');
            if ($key !== '') {
                if (in_array($key, $keys)) {
                    $error = __('No se permiten claves duplicadas.', self::TEXT_DOMAIN);
                }
                $keys[] = $key;
                $campos[] = [
                    self::FIELD_KEY => $key,
                    self::FIELD_VALUE => $value
                ];
            }
        }
        return ['campos' => $campos, 'error' => $error];
    }
}
