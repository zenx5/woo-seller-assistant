<?php

defined( 'ABSPATH' ) || exit;

require_once 'class-zoho-books.php';
require_once 'class-second-currency-rates.php';
require_once 'class-wc-cart-2.php';


class WooSellerAssistant {

    public static function activation() {
        SC_Rates::create_table();
        $user_roles = get_option('wp_user_roles');
        $user_roles['shop_manager']['capabilities']['create_users'] = true;
        update_option('wp_user_roles',  $user_roles );
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
        add_action( 'woocommerce_checkout_create_order_line_item', [__CLASS__, 'update_item_order'], 1, 4);
        add_action( 'woocommerce_checkout_order_created', [__CLASS__, 'order_created'] );
        add_action( 'wp_head', [__CLASS__, 'js_head']);
        add_action( 'wp_footer', [__CLASS__, 'js_footer']);
        add_action( 'save_post', [__CLASS__, 'update_custom_field']);
        add_filter( 'manage_edit-shop_order_columns', [__CLASS__, 'add_column_list_shop_order']);
        add_action( 'manage_posts_custom_column',  [__CLASS__, 'column_shop_order_content']);
    }

    public static function column_shop_order_content($column) {
        global $post;
        $product_id = $post->ID;
        switch ($column)
        {
            case 'order_rate_usd':
                echo get_post_meta($product_id, 'order_rate_usd') ? get_post_meta($product_id, 'order_rate_usd')[0] : '-';
            break;
        }
    }

    public static function add_column_list_shop_order($columns) {
        $newcolumns = [];
        foreach( $columns as $key => $label ) {
            if( $key === "order_total" ) {
                $newcolumns['order_rate_usd'] = 'Tasa';
            }
            $newcolumns[$key] = $label;
        }
        return $newcolumns;
    }

    public static function update_custom_field($post_id) {
        if (isset($_POST['order_rate_usd'])) {
            update_post_meta($post_id, 'order_rate_usd', sanitize_text_field($_POST['order_rate_usd']));
        }
    }

    public static function order_created( $order ){
        //setear la tasa del dia a la orden
        update_post_meta(
            $order->get_id(),
            'order_rate_usd',
            WooSellerAssistant::get_rate_usd()
        );
        // crear factura en Books
        //self::order_to_invoice( $order );
    }

    public static function order_to_data_invoice($order) {
        $data = [

        ];

        ZohoBooks::create_invoice($data);
    }

    public static function js_head() {
        if( true ){
            ?>
                <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
            <?php
        }
    }

    public static function js_footer() {
        if( true ){
            ?>
                <script>
                    const { createApp } = Vue

                    createApp({
                        data(){
                            return {
                                client:-1,
                                message:'Hello'
                            }
                        },
                        methods: {
                            createUser(){
                                const public_client = 'ck_ef8fa4043a81838fc7d923b0a0e1dd08a82d2d41';
                                const private_client = 'cs_0c61d7175a180e0fa12fa53ca2edcc142115d99c';
                                const headers = new Headers()
                                headers.set('Authorization', 'Basic ' +btoa(`${public_client}:${private_client}`))
                                headers.set('Content-Type', 'application/json')
                                const username = document.querySelector('#username')?.value
                                if( username === '' ) {
                                    alert('Defina el username');
                                    return
                                }
                                fetch('https://wp.test/wp-json/wc/v3/customers', {
                                    method:'post',
                                    headers: headers,
                                    body:JSON.stringify({
                                        "email": document.querySelector('#billing_email')?.value,
                                        "first_name": document.querySelector('#billing_first_name')?.value,
                                        "last_name": document.querySelector('#billing_last_name')?.value,
                                        "username": username,
                                        "billing": {
                                            "first_name": document.querySelector('#billing_first_name')?.value,
                                            "last_name": document.querySelector('#billing_last_name')?.value,
                                            "company": document.querySelector('#billing_company')?.value,
                                            "address_1": document.querySelector('#billing_address_1')?.value,
                                            "address_2": document.querySelector('#billing_address_2')?.value,
                                            "city": document.querySelector('#billing_city')?.value,
                                            "state": document.querySelector('#billing_state')?.value,
                                            "postcode": document.querySelector('#billing_postcode')?.value,
                                            "country": document.querySelector('#billing_country')?.value,
                                            "email": document.querySelector('#billing_email')?.value,
                                            "phone": document.querySelector('#billing_phone')?.value
                                        },
                                        "shipping": {
                                            "first_name": document.querySelector('#shipping_first_name')?.value ?? document.querySelector('#billing_first_name')?.value,
                                            "last_name": document.querySelector('#shipping_last_name')?.value ?? document.querySelector('#billing_last_name')?.value,
                                            "company": document.querySelector('#shipping_company')?.value ?? document.querySelector('#billing_company')?.value,
                                            "address_1": document.querySelector('#shipping_address_1')?.value ?? document.querySelector('#billing_address_1')?.value,
                                            "address_2": document.querySelector('#shipping_address_2')?.value ?? document.querySelector('#billing_address_2')?.value,
                                            "city": document.querySelector('#shipping_city')?.value ?? document.querySelector('#billing_city')?.value,
                                            "state": document.querySelector('#shipping_state')?.value ?? document.querySelector('#billing_state')?.value,
                                            "postcode": document.querySelector('#shipping_postcode')?.value ?? document.querySelector('#billing_postcode')?.value,
                                            "country": document.querySelector('#shipping_country')?.value ?? document.querySelector('#billing_country')?.value,
                                        }
                                    })
                                })
                                .then( response => response.json() )
                                .then( result => console.log( result ) )

                            },
                            selectUser(event) {
                                if( event.target?.value !== -1 ) {
                                    const customer = wp_customers.find( user => user.ID==event.target?.value )
                                    Object.keys( customer.billing ).forEach( key => {
                                        const field = document.querySelector(`#${key}`)
                                        if( field ) {
                                            field.value = customer.billing[key]
                                        }
                                    })
                                    Object.keys( customer.shipping ).forEach( key => {
                                        const field = document.querySelector(`#${key}`)
                                        if( field ) {
                                            field.value = customer.shipping[key]
                                        }
                                    })
                                }
                            }
                        }
                    }).mount('#app-checkout')
                </script>

            <?php
        }
    }

    public static function wc_price($raw, $price, $options=null) {
        return $raw ? $price : wc_price($price, $options);
    }

    public static function update_item_order($item, $cart_item_key, $values, $order ) {
        $product_id = $item->get_product_id();
        $product = $item->get_product();
        $product_price = $product->get_price();
        $quantity = $values['quantity'];
        $price = self::get_price_in_cart( $product_price, $product_id, $cart_item_key );
        $item->set_props([
            "subtotal" => $price * $quantity,
            "total" => $price * $quantity
        ]);
        $order->set_total( self::get_total_in_cart(false,true) );
        delete_option( 'price_'.$cart_item_key.'_'.$product_id );
        return $item;
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
        if( isset($_POST['wsa_zoho_books_token']) ) {
            update_option('wsa_zoho_books_token', $_POST['wsa_zoho_books_token']);
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

    public static function get_product_subtotal($_product, $cart_item_key, $quantity, $include_change = false, $raw = false) {
        $_price = apply_filters( 'wsa_price_in_cart', $_product->get_price(), $_product->get_ID(), $cart_item_key );
        $subtotal = $_price * $quantity;
        
        return $include_change ? 
            self::wc_price($raw, $subtotal ) . " | ".self::wc_price($raw, $subtotal*WooSellerAssistant::get_rate_usd(), ["currency" => "VES"] ) :
            self::wc_price($raw, $subtotal );
    }

    public static function get_subtotal_in_cart( $include_change = false, $raw =false ) {
        return self::get_total_in_cart( $include_change, $raw );
    }
    
    public static function get_total_in_cart($include_change = false, $raw =false) {
        $total = 0;
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) { 
            $_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
            $_price = apply_filters( 'wsa_price_in_cart', $_product->get_price(), $_product->get_ID(), $cart_item_key );
            $total += $_price * $cart_item['quantity'];
        }
        return $include_change ? 
            self::wc_price($raw, $total ) . " | ".self::wc_price($raw, $total*WooSellerAssistant::get_rate_usd(), ["currency" => "VES"] ) :
            self::wc_price($raw, $total );
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