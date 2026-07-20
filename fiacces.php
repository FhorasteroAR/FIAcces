<?php
/**
 * Plugin Name:       FIAcces
 * Plugin URI:        https://example.com/fiacces
 * Description:       Plugin de accesibilidad WCAG 2.1 nivel AA. Añade una barra de herramientas flotante en el frontend que permite a los usuarios ajustar texto, contraste, animaciones, cursor y más.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            Tu Nombre
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       fiacces
 * Domain Path:       /languages
 *
 * @package FIAcces
 */

// Evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Constantes del plugin
define( 'FIACCES_VERSION', '1.0.0' );
define( 'FIACCES_PLUGIN_FILE', __FILE__ );
define( 'FIACCES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FIACCES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FIACCES_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'FIACCES_OPTION_KEY', 'fiacces_settings' );

/**
 * Clase principal del plugin (patrón singleton).
 */
final class FIAcces {

    /** @var FIAcces|null */
    private static $instance = null;

    /** @return FIAcces */
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
        require_once FIACCES_PLUGIN_DIR . 'includes/class-fiacces-settings.php';
        require_once FIACCES_PLUGIN_DIR . 'includes/class-fiacces-frontend.php';
        require_once FIACCES_PLUGIN_DIR . 'includes/class-fiacces-admin.php';
        require_once FIACCES_PLUGIN_DIR . 'includes/class-fiacces-rest.php';
    }

    /** Inicializa hooks de WordPress. */
    private function setup_hooks() {
        // Traducciones
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

        // Inicializar módulos
        FIAcces_Settings::init();
        FIAcces_Frontend::init();
        FIAcces_Admin::init();
        FIAcces_REST::init();

        // Activación / desactivación
        register_activation_hook( FIACCES_PLUGIN_FILE, array( __CLASS__, 'on_activate' ) );
        register_deactivation_hook( FIACCES_PLUGIN_FILE, array( __CLASS__, 'on_deactivate' ) );
    }

    /** Carga el dominio de texto para traducciones. */
    public function load_textdomain() {
        load_plugin_textdomain(
            'fiacces',
            false,
            dirname( FIACCES_PLUGIN_BASENAME ) . '/languages/'
        );
    }

    /** Se ejecuta al activar el plugin. */
    public static function on_activate() {
        if ( false === get_option( FIACCES_OPTION_KEY ) ) {
            add_option( FIACCES_OPTION_KEY, FIAcces_Settings::get_defaults() );
        }
        flush_rewrite_rules();
    }

    /** Se ejecuta al desactivar el plugin. */
    public static function on_deactivate() {
        flush_rewrite_rules();
    }
}

// Arrancar el plugin
FIAcces::instance();
