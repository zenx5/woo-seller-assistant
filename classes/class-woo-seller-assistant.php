<?php

defined( 'ABSPATH' ) || exit;

require_once 'class-second-currency-rates.php';
require_once 'class-wc-cart-2.php';


class WooSellerAssistant {

    public static function activation() {
        update_option("wsa_rate_usd", 1);
        SC_Rates::create_table();
    }

    public static function deactivation() {
        
    }

    public static function init() {
        add_filter( 'woocommerce_locate_template', [__CLASS__, 'template_replace'],1,3);
        add_action( 'admin_menu', [__CLASS__, 'admin_menu']);
        add_action( 'wp_ajax_update_config', [__CLASS__, 'update_config']);
        remove_action( 'wp_loaded', array( 'WC_Form_Handler', 'update_cart_action' ), 20 );
        add_action( 'wp_loaded', array( 'WC_Cart_Two', 'update_cart_action' ), 20 );
        add_filter( 'wsa_price_in_cart', [__CLASS__, 'get_price_in_cart'],1,3);
    }

    public static function set_rate_usd( $value = 1 ) {
        SC_Rates::new_rate([
            "currency" => "USD",
            "value" => $value,
            "target" => "[]",
            "created_at" => date("Y-m-d H:i:s")
        ]);
    }

    public static function get_rate_usd($currency = "USD", $date = null) {
        try {
            $row_rate = SC_Rates::get_row_at($currency, $date);
            return floatval( $row_rate->value );
        } catch(Exception $error) {
            return 1;
        }
    }

    public static function get_price_in_cart( $product_price, $product_id, $cart_item_key ) {
        return get_option('price_'.$cart_item_key.'_'.$product_id, $product_price );
    }

    public static function get_tag_cart() {
        $term_cart = get_terms( array(
            'taxonomy'   => 'product_tag',
            'name' => '_into_cart',
            'hide_empty' => false,
        ) );
        return $term_cart[0]->term_id;
    }

    public static function update_config() {
        if( isset($_POST['wsa_rate_usd']) ) {
            WooSellerAssistant::set_rate_usd( $_POST['wsa_rate_usd'] );
        }
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