<?php


class WooSellerAssistant {

    public static function activation() {
        update_option("wsa_rate_usd", 1);
    }

    public static function deactivation() {
        
    }

    public static function init() {
        add_filter( 'woocommerce_locate_template', [__CLASS__, 'template_replace'],1,3);
        add_action( 'admin_menu', [__CLASS__, 'admin_menu']);
        add_action( 'wp_ajax_update_config', [__CLASS__, 'update_config']);
        add_action( 'wp_loaded', array( __CLASS__, 'update_price_action' ), 20 );
    }

    public static function update_price_action() {
        if( !isset($_POST['update_cart']) ) return;
        // DEBO HACER UN HANDLER PARA ACTUALIZAR EL PRECIO DEL PRODUCTO EN LA ORDEN Y TAMBIEN GUARDAR EL RATE DE LA ORDEN
    }

    public static function update_config() {
        update_option(
            "wsa_rate_usd", 
            isset($_POST['wsa_rate_usd']) ? 
                $_POST['wsa_rate_usd'] : 
                get_option("wsa_rate_usd", 1)
        );
        echo 1;
        die();
    }

    public static function admin_menu() {
        add_menu_page(
            'Config Shop',
            'Config Shop',
            'manage_options',
            'config-shop',
            'config_shop_html',
            "",
            10
        );

        function config_shop_html() {
            include_once WP_PLUGIN_DIR.'/woo-seller-assistant/template/config-shop.php';
        }
    }

    public static function template_replace( $template, $template_name, $template_path) {
        global $woocommerce;
        $_template = $template;
        if ( ! $template_path ) 
            $template_path = $woocommerce->template_url;
    
        // $plugin_path  = untrailingslashit( plugin_dir_path( __FILE__ ) )  . '/template/woocommerce/';
        $plugin_path = '/app/wp-content/plugins/woo-seller-assistant/template/woocommerce/';
        
        // Look within passed path within the theme - this is priority
        $template = locate_template(
            array(
                $template_path . $template_name,
                $template_name
            )
        );
        
        if( ! $template && file_exists( $plugin_path . $template_name ) )
            $template = $plugin_path . $template_name;
        
        if ( ! $template )
            $template = $_template;

        return $template;
    }
    
}