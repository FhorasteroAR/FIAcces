<?php
/**
 * Admin: página de configuración en wp-admin.
 *
 * @package WP_Accesibilidad_A11y
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPA11Y_Admin {

    public static function init() {
        add_action( 'admin_menu',          array( __CLASS__, 'add_menu' ) );
        add_action( 'admin_init',          array( __CLASS__, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
        add_filter( 'plugin_action_links_' . WPA11Y_PLUGIN_BASENAME, array( __CLASS__, 'add_settings_link' ) );
    }

    /** Agrega enlace "Configuración" en la lista de plugins. */
    public static function add_settings_link( $links ) {
        $url           = admin_url( 'options-general.php?page=wpa11y-settings' );
        $settings_link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Configuración', 'wp-accesibilidad-a11y' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /** Añade la página de ajustes bajo Ajustes > Accesibilidad A11y. */
    public static function add_menu() {
        add_options_page(
            __( 'Accesibilidad A11y', 'wp-accesibilidad-a11y' ),
            __( 'Accesibilidad A11y', 'wp-accesibilidad-a11y' ),
            'manage_options',
            'wpa11y-settings',
            array( __CLASS__, 'render_page' )
        );
    }

    /** Registra la opción del plugin con su sanitización. */
    public static function register_settings() {
        register_setting(
            'wpa11y_settings_group',
            WPA11Y_OPTION_KEY,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( 'WPA11Y_Settings', 'sanitize' ),
                'default'           => WPA11Y_Settings::get_defaults(),
            )
        );
    }

    /** Encola assets en la página del plugin. */
    public static function enqueue_admin_assets( $hook ) {
        if ( 'settings_page_wpa11y-settings' !== $hook ) {
            return;
        }
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        wp_enqueue_style(
            'wpa11y-admin',
            WPA11Y_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WPA11Y_VERSION
        );

        wp_enqueue_script(
            'wpa11y-admin',
            WPA11Y_PLUGIN_URL . 'assets/js/admin.js',
            array( 'wp-color-picker', 'jquery' ),
            WPA11Y_VERSION,
            true
        );
    }

    /** Renderiza la página de ajustes. */
    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $opts = WPA11Y_Settings::get_options();
        ?>
        <div class="wrap wpa11y-admin-wrap">
            <h1><?php esc_html_e( 'WP Accesibilidad A11y — Configuración', 'wp-accesibilidad-a11y' ); ?></h1>
            <p class="wpa11y-admin-intro">
                <?php esc_html_e( 'Configura cómo aparece la barra de accesibilidad WCAG 2.1 en tu sitio.', 'wp-accesibilidad-a11y' ); ?>
            </p>

            <form action="options.php" method="post" class="wpa11y-admin-form">
                <?php settings_fields( 'wpa11y_settings_group' ); ?>

                <div class="wpa11y-admin-grid">

                    <div class="wpa11y-admin-card">
                        <h2><?php esc_html_e( 'Apariencia', 'wp-accesibilidad-a11y' ); ?></h2>

                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row">
                                    <label for="wpa11y_button_position"><?php esc_html_e( 'Posición del botón flotante', 'wp-accesibilidad-a11y' ); ?></label>
                                </th>
                                <td>
                                    <select name="<?php echo esc_attr( WPA11Y_OPTION_KEY ); ?>[button_position]" id="wpa11y_button_position">
                                        <option value="bottom-right" <?php selected( $opts['button_position'], 'bottom-right' ); ?>><?php esc_html_e( 'Abajo a la derecha', 'wp-accesibilidad-a11y' ); ?></option>
                                        <option value="bottom-left"  <?php selected( $opts['button_position'], 'bottom-left' ); ?>><?php esc_html_e( 'Abajo a la izquierda', 'wp-accesibilidad-a11y' ); ?></option>
                                        <option value="top-right"    <?php selected( $opts['button_position'], 'top-right' ); ?>><?php esc_html_e( 'Arriba a la derecha', 'wp-accesibilidad-a11y' ); ?></option>
                                        <option value="top-left"     <?php selected( $opts['button_position'], 'top-left' ); ?>><?php esc_html_e( 'Arriba a la izquierda', 'wp-accesibilidad-a11y' ); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="wpa11y_primary_color"><?php esc_html_e( 'Color primario', 'wp-accesibilidad-a11y' ); ?></label>
                                </th>
                                <td>
                                    <input type="text"
                                           name="<?php echo esc_attr( WPA11Y_OPTION_KEY ); ?>[primary_color]"
                                           id="wpa11y_primary_color"
                                           value="<?php echo esc_attr( $opts['primary_color'] ); ?>"
                                           class="wpa11y-color-picker"
                                           data-default-color="#2563EB">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="wpa11y_shortcut_key"><?php esc_html_e( 'Atajo de teclado', 'wp-accesibilidad-a11y' ); ?></label>
                                </th>
                                <td>
                                    <code>Alt +</code>
                                    <input type="text"
                                           name="<?php echo esc_attr( WPA11Y_OPTION_KEY ); ?>[shortcut_key]"
                                           id="wpa11y_shortcut_key"
                                           value="<?php echo esc_attr( $opts['shortcut_key'] ); ?>"
                                           maxlength="1"
                                           style="width: 50px; text-align: center; text-transform: uppercase;">
                                    <p class="description"><?php esc_html_e( 'Una sola letra (A-Z). Combina con la tecla Alt para abrir/cerrar el panel.', 'wp-accesibilidad-a11y' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Dispositivos móviles', 'wp-accesibilidad-a11y' ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox"
                                               name="<?php echo esc_attr( WPA11Y_OPTION_KEY ); ?>[show_on_mobile]"
                                               value="1"
                                               <?php checked( $opts['show_on_mobile'] ); ?>>
                                        <?php esc_html_e( 'Mostrar también en móviles', 'wp-accesibilidad-a11y' ); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="wpa11y-admin-card">
                        <h2><?php esc_html_e( 'Funcionalidades', 'wp-accesibilidad-a11y' ); ?></h2>
                        <p class="description"><?php esc_html_e( 'Activa o desactiva qué tarjetas verán los usuarios en el panel de accesibilidad.', 'wp-accesibilidad-a11y' ); ?></p>

                        <table class="form-table" role="presentation">
                            <?php
                            $feature_labels = array(
                                'text_size'    => __( 'Ajuste de tamaño de texto', 'wp-accesibilidad-a11y' ),
                                'contrast'     => __( 'Modos de contraste', 'wp-accesibilidad-a11y' ),
                                'readability'  => __( 'Legibilidad (dislexia, subrayado)', 'wp-accesibilidad-a11y' ),
                                'animations'   => __( 'Pausar animaciones', 'wp-accesibilidad-a11y' ),
                                'cursor'       => __( 'Cursor grande', 'wp-accesibilidad-a11y' ),
                                'keyboard_nav' => __( 'Navegación con teclado mejorada', 'wp-accesibilidad-a11y' ),
                            );
                            foreach ( $feature_labels as $key => $label ) :
                                ?>
                                <tr>
                                    <th scope="row"><label for="wpa11y_feat_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
                                    <td>
                                        <label class="wpa11y-admin-switch">
                                            <input type="checkbox"
                                                   id="wpa11y_feat_<?php echo esc_attr( $key ); ?>"
                                                   name="<?php echo esc_attr( WPA11Y_OPTION_KEY ); ?>[features][<?php echo esc_attr( $key ); ?>]"
                                                   value="1"
                                                   <?php checked( ! empty( $opts['features'][ $key ] ) ); ?>>
                                            <?php esc_html_e( 'Activada', 'wp-accesibilidad-a11y' ); ?>
                                        </label>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>

                </div>

                <div class="wpa11y-admin-actions">
                    <?php submit_button( __( 'Guardar cambios', 'wp-accesibilidad-a11y' ), 'primary large' ); ?>
                    <button type="button" class="button button-secondary" id="wpa11y-export-btn">
                        <?php esc_html_e( 'Exportar configuración', 'wp-accesibilidad-a11y' ); ?>
                    </button>
                </div>
            </form>

            <div class="wpa11y-admin-info">
                <h2><?php esc_html_e( '¿Cómo se ve?', 'wp-accesibilidad-a11y' ); ?></h2>
                <p>
                    <?php
                    printf(
                        wp_kses(
                            /* translators: %s: URL del sitio */
                            __( 'Visita la <a href="%s" target="_blank" rel="noopener">página principal</a> de tu sitio para ver el botón de accesibilidad en acción.', 'wp-accesibilidad-a11y' ),
                            array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) )
                        ),
                        esc_url( home_url() )
                    );
                    ?>
                </p>
                <p class="description">
                    <?php esc_html_e( 'Cumplimiento: WCAG 2.1 nivel AA. Versión:', 'wp-accesibilidad-a11y' ); ?>
                    <code><?php echo esc_html( WPA11Y_VERSION ); ?></code>
                </p>
            </div>
        </div>
        <?php
    }
}
