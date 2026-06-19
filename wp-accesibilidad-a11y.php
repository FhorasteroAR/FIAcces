<?php
/**
 * Plugin Name:       WP Accesibilidad A11y
 * Plugin URI:        https://example.com/wp-accesibilidad-a11y
 * Description:       Plugin de accesibilidad WCAG 2.1 nivel AA. Añade una barra de herramientas flotante en el frontend que permite a los usuarios ajustar texto, contraste, animaciones, cursor y más.
 * Version:           1.0.0
 * Requires at least: 7.0
 * Requires PHP:      7.4
 * Author:            Tu Nombre
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-accesibilidad-a11y
 * Domain Path:       /languages
 *
 * @package WP_Accesibilidad_A11y
 */

// Evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Constantes del plugin
define( 'WPA11Y_VERSION', '1.0.0' );
define( 'WPA11Y_PLUGIN_FILE', __FILE__ );
define( 'WPA11Y_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPA11Y_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPA11Y_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPA11Y_OPTION_KEY', 'wpa11y_settings' );

/**
 * Clase principal del plugin (patrón singleton).
 */
final class WP_Accesibilidad_A11y {

    /** @var WP_Accesibilidad_A11y|null */
    private static $instance = null;

    /** @return WP_Accesibilidad_A11y */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->load_dependencies();
            self::$instance->setup_hooks();
        }
        return self::$instance;
    }

    /** Constructor privado (singleton). */
    private function __construct() {}

    /** Carga los archivos de clases necesarios. */
    private function load_dependencies() {
        require_once WPA11Y_PLUGIN_DIR . 'includes/class-wpa11y-settings.php';
        require_once WPA11Y_PLUGIN_DIR . 'includes/class-wpa11y-frontend.php';
        require_once WPA11Y_PLUGIN_DIR . 'includes/class-wpa11y-admin.php';
        require_once WPA11Y_PLUGIN_DIR . 'includes/class-wpa11y-rest.php';
    }

    /** Inicializa hooks de WordPress. */
    private function setup_hooks() {
        // Traducciones
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

        // Inicializar módulos
        WPA11Y_Settings::init();
        WPA11Y_Frontend::init();
        WPA11Y_Admin::init();
        WPA11Y_REST::init();

        // Activación / desactivación
        register_activation_hook( WPA11Y_PLUGIN_FILE, array( __CLASS__, 'on_activate' ) );
        register_deactivation_hook( WPA11Y_PLUGIN_FILE, array( __CLASS__, 'on_deactivate' ) );
    }

    /** Carga el dominio de texto para traducciones. */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wp-accesibilidad-a11y',
            false,
            dirname( WPA11Y_PLUGIN_BASENAME ) . '/languages/'
        );
    }

    /** Se ejecuta al activar el plugin. */
    public static function on_activate() {
        if ( false === get_option( WPA11Y_OPTION_KEY ) ) {
            add_option( WPA11Y_OPTION_KEY, WPA11Y_Settings::get_defaults() );
        }
        flush_rewrite_rules();
    }

    /** Se ejecuta al desactivar el plugin. */
    public static function on_deactivate() {
        flush_rewrite_rules();
    }
}

// Arrancar el plugin
WP_Accesibilidad_A11y::instance();
