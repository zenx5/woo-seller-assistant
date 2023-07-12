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
        update_option('wsa_zoho_access_token', '');
        update_option('wsa_zoho_refresh_token', '');
        update_option('wsa_zoho_refresh_token_time', '');
        update_option('wsa_zoho_token_error', '0');
        update_option('wsa_woo_public_client', '');
        update_option('wsa_woo_private_client', '');
    }

    public static function deactivation() {
        
    }

    public static function init() {
        add_filter( 'woocommerce_locate_template', [__CLASS__, 'template_replace'],1,3);
        add_action( 'admin_menu', [__CLASS__, 'admin_menu']);
        add_action( 'wp_ajax_update_config', [__CLASS__, 'update_config']);
        add_action( 'wp_ajax_generate_code', [__CLASS__, 'generate_code']);
        add_action( 'wp_ajax_import_products', [__CLASS__, 'import_products']);
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

    public static function import_products() {
        $response = [
            "new" => [],
            "update" => [],
            "log" => []
        ];
        $items = ZohoBooks::list_all_items();
        foreach( $items as $item ) {
            if( $item['product_type']=='goods' && $item['status']=='active' ) {
                $sku = ($item['sku']!='') ? $item['sku'] : 'sku-'.$item['account_id'].'-'.$item['item_id'];
                $product_id = wc_get_product_id_by_sku($sku);
                $response["log"][] = "Product ID: $product_id";
                if( $product_id ) {
                    $image_document_id = $item['image_document_id'];
                    $organization_id = get_option('wsa_zoho_book_organization', '');
                    $url_image = "https://books.zoho.com/api/v3/documents/$image_document_id?organization_id=$organization_id&inline=true";
                    $product = new WC_Product( $product_id );
                    $response["log"][] = "Price compare: ".$product->get_regular_price()."!=".$item['rate']."?";
                    $response["log"][] = "Name compare: ".$product->get_name()."!=".$item['name']."?";
                    if( $product->get_regular_price()!=$item['rate'] || $product->get_name()!=$item['name'] ) {
                        $product->set_regular_price( $item['rate'] );
                        $product->set_name( $item['name'] );
                        $product->set_image_id( $url_image );
                        $id = $product->save();
                        $response["log"][] = "Result save: $id";
                        $response["update"][] = $item;
                    }
                } else {
                    $image_document_id = $item['image_document_id'];
                    $organization_id = get_option('wsa_zoho_book_organization', '');
                    $url_image = "https://books.zoho.com/api/v3/documents/$image_document_id?organization_id=$organization_id&inline=true";
                    $product = new WC_Product();
                    $product->set_regular_price( $item['rate'] );
                    $product->set_name( $item['name'] );
                    $product->set_sku( $sku );
                    $product->set_image_id( $url_image );
                    $id = $product->save();
                    $response["log"][] = "Result save: $id";
                    $response["new"][] = $item;
                }
            }
        }
        echo json_encode( $response );
        die();
    }

    public static function generate_code() {
        if( isset($_POST['code']) ) {
            $code = $_POST['code'];
            $client_id = get_option('wsa_zoho_client_id', '');
            $client_secret = get_option('wsa_zoho_client_secret', '');
            $refresh_token = ZohoBooks::generate_code( $code, $client_id, $client_secret );
            if( !$refresh_token ) {
                update_option('wsa_zoho_token_error', '1');
                die('0');
            }
            update_option( 'wsa_zoho_refresh_token', $refresh_token );
            $access_token = ZohoBooks::refresh_token( $refresh_token, $client_id, $client_secret );
            if( !$access_token ) {
                update_option('wsa_zoho_token_error', '1');
                die('0');
            }
            update_option( 'wsa_zoho_access_token', $access_token );
            update_option( 'wsa_zoho_access_token_time', date('Y-m-d H:i:s') );
            update_option('wsa_zoho_token_error', '0');
            echo "Aprobado este codigo $code <br/> generado refresh token $access_token";
            die();
        }
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
            "customer_id" => 982000000567001,            
            "invoice_number" => "INV-00003",
            "reference_number" => " ",
            "date" => "2013-11-17",
                "payment_terms" => 15,
                "payment_terms_label" => "Net 15",
            "due_date" => "2013-12-03",
            "discount" => 0,
            "line_items" => [
                [
                    "item_id" => 982000000030049,
                    "product_type" => "goods",
                    "name"  => "Hard Drive",
                    "description"   => "500GB, USB 2.0 interface 1400 rpm, protective hard case.",
                    // "item_order"    => 1,
                    // "bcy_rate"  => 120,
                    // "rate"  => 120,
                    "quantity"  => 1,
                    "unit"  => "Kgs",
                    // "discount_amount"   => 0,
                    "discount"  => 0,
                    // "tags" => [
                    //     [
                    //         "tag_id" => 982000000009070,
                    //         "tag_option_id" => 982000000002670
                    //     ]
                    // ],
                    // "tax_id" => 982000000557028,
                    // "tds_tax_id" => "982000000557012",
                    // "tax_name" => "VAT",
                    // "tax_type" => "tax",
                    // "tax_percentage" => 12.5,
                    // "tax_treatment_code" => "uae_others",
                    // "header_name" => "Electronic devices"
                ]
            ],
            // "payment_options" => [
            //     "payment_gateways" => [
            //         [
            //             "configured" => true,
            //             "additional_field1" => "standard",
            //             "gateway_name" => "paypal"
            //         ]
            //     ]
            // ],
            // "allow_partial_payments" => true,
            // "custom_body" => " ",
            // "custom_subject" => " ",
            // "notes" => "Looking forward for your business.",
            // "terms" => "Terms & Conditions apply",
            // "shipping_charge" => 0,
            // "adjustment" => 0,
            // "adjustment_description" => " ",
            // "reason" => " ",
            // "tax_authority_id" => 11149000000061052,
            // "tax_exemption_id" => 11149000000061054,
            // "avatax_use_code" => "string",
            // "avatax_exempt_no" => "string",
            // "tax_id" => 982000000557028,
            // "expense_id" => " ",
            // "salesorder_item_id" => " ",
            // "avatax_tax_code" => "string",
            // "time_entry_ids" => []
        ];

        ZohoBooks::create_invoice($data);
    }

    public static function js_head() {
        if( is_checkout() ){
            ?>
                <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
            <?php
        }
    }

    public static function js_footer() {
        if( is_checkout() ){
            include WP_PLUGIN_DIR.'/woo-seller-assistant/template/assets/js_checkout_app.php';
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
        if( isset($_POST['wsa_zoho_book_organization']) ) {
            update_option('wsa_zoho_book_organization', $_POST['wsa_zoho_book_organization']);
        }
        if( isset($_POST['wsa_zoho_client_id']) ) {
            update_option('wsa_zoho_client_id', $_POST['wsa_zoho_client_id']);
        }
        if( isset($_POST['wsa_zoho_client_secret']) ) {
            update_option('wsa_zoho_client_secret', $_POST['wsa_zoho_client_secret']);
        }
        if( isset($_POST['wsa_woo_public_client']) ) {
            update_option('wsa_woo_public_client', $_POST['wsa_woo_public_client']);
        }
        if( isset($_POST['wsa_woo_private_client']) ) {
            update_option('wsa_woo_private_client', $_POST['wsa_woo_private_client']);
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
            include_once WP_PLUGIN_DIR.'/woo-seller-assistant/template/admin/config-shop.php';
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