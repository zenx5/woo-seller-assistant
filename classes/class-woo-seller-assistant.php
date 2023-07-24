<?php

defined( 'ABSPATH' ) || exit;

require_once 'class-zoho-books.php';
require_once 'class-second-currency-rates.php';
require_once 'class-wc-cart-2.php';
require_once 'class-data-format.php';
require_once 'class-log-control.php';


class WooSellerAssistant {

    public static function activation() {
        SC_Rates::create_table();
        LogControl::create_table();
        $user_roles = get_option('wp_user_roles');
        $user_roles['shop_manager']['capabilities']['create_users'] = true;
        update_option('wp_user_roles',  $user_roles );
        update_option('wsa_zoho_access_token', '');
        update_option('wsa_zoho_refresh_token', '');
        update_option('wsa_zoho_refresh_token_time', '');
        update_option('wsa_zoho_token_error', '0');
        LogControl::insert(__FILE__, __LINE__, 'activado plugin');
        // update_option('wsa_woo_public_client', '');
        // update_option('wsa_woo_private_client', '');
    }

    public static function deactivation() {
        $user_roles = get_option('wp_user_roles');
        $user_roles['shop_manager']['capabilities']['create_users'] = false;
        update_option('wp_user_roles',  $user_roles );
        LogControl::insert(__FILE__, __LINE__, 'desactivado plugin');
    }

    public static function uninstall() {
        SC_Rates::drop_table();
        LogControl::drop_table();
    }

    public static function init() {
        add_filter( 'woocommerce_locate_template', [__CLASS__, 'template_replace'],1,3);
        add_action( 'admin_menu', [__CLASS__, 'admin_menu']);
        add_action( 'wp_ajax_update_config', [__CLASS__, 'action_update_config']);
        add_action( 'wp_ajax_generate_code', [__CLASS__, 'action_generate_code']);
        add_action( 'wp_ajax_import_products', [__CLASS__, 'action_import_products']);
        add_action( 'wp_ajax_import_customers', [__CLASS__, 'action_import_customers']);
        add_action( 'wp_ajax_create_invoice', [__CLASS__, 'action_create_invoice']);
        add_action( 'wp_ajax_create_customer', [__CLASS__, 'action_create_customer']);
        add_action( 'wp_ajax_update_default_quantity', [__CLASS__, 'action_get_list_quantities_html']);
        remove_action( 'wp_loaded', array( 'WC_Form_Handler', 'update_cart_action' ), 20 );
        add_action( 'wp_loaded', array( 'WC_Cart_Two', 'update_cart_action' ), 20 );
        add_filter( 'wsa_price_in_cart', [__CLASS__, 'get_price_in_cart'],1,3);
        add_filter( 'woocommerce_checkout_customer_id', [__CLASS__, 'set_order_customer_id']);
        add_filter( 'woocommerce_add_to_cart_product_id', [__CLASS__, 'add_to_cart']);
        add_action( 'woocommerce_checkout_create_order_line_item', [__CLASS__, 'update_item_order'], 1, 4);
        add_action( 'woocommerce_checkout_order_created', [__CLASS__, 'order_created'] );
        add_action( 'woocommerce_checkout_order_created', [__CLASS__, 'update_user_data'] );
        add_action( 'woocommerce_order_status_completed', [__CLASS__, 'pay_invoice']);
        add_action( 'woocommerce_admin_order_data_after_order_details', [__CLASS__, 'book_details']);
        add_action( 'woocommerce_product_options_related', [__CLASS__, 'related_product_default_quantity']);
        add_action( 'wp_head', [__CLASS__, 'js_head']);
        add_action( 'wp_footer', [__CLASS__, 'js_footer']);
        add_action( 'save_post', [__CLASS__, 'update_custom_field']);
        add_filter( 'manage_edit-shop_order_columns', [__CLASS__, 'add_column_list_shop_order']);
        add_action( 'manage_posts_custom_column',  [__CLASS__, 'column_shop_order_content']);
        add_filter( 'posts_clauses', [__CLASS__, 'woocommerce_get_catalog_ordering_args']);
        add_action( 'add_meta_boxes', [__CLASS__, 'product_grouped_linked']);
    }

    public static function product_grouped_linked() {
        if( isset( $_GET['post'] ) ) {
            $product = new WC_Product_Grouped( $_GET['post'] );
            if( count( $product->get_children() ) )  {
                function meta_product_combo() {
                    $query = new WP_Query([
                        'post_type' => 'product',
                        'product_type' => 'simple',
                        'posts_per_page' => -1
                    ]);

                    $product_combo_id = count(get_post_meta( $_GET['post'], 'product_combo_id')) ? get_post_meta( $_GET['post'], 'product_combo_id')[0] : $_GET['post'];

                    ?>
                        <select name="product_combo_id">
                            <?php foreach( $query->posts as $post ):
                                $product = new WC_Product( $post->ID );
                            ?>
                                <option value="<?=$post->ID?>" <?=$product_combo_id==$post->ID?'selected':''?>  ><?=$product->get_name()?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php
                }
        
                add_meta_box( 'woo-product-combo', 'Configurar Combo', 'meta_product_combo', 'product', 'normal', 'high' );
            }
        }
    }

    public static function add_to_cart($product_id) {
        if(isset($_REQUEST['add_to_cart_combo'])) {
            return $_REQUEST['add-to-cart-combo-id'];
        }
        return $product_id;
    }

    public static function get_grouped_childrens( $product_id ) {
        $product = new WC_Product_Grouped( $product_id );
        $childrens = [];
        foreach ( $product->get_children() as $id ) {
            $product_children = new WC_Product($id);
            $childrens[] = [
                "id" => $id,
                "name" => $product_children->get_name(),
                "value" => count(get_post_meta( $product_id, 'default_quantity_'.$id )) ? get_post_meta( $product_id, 'default_quantity_'.$id )[0] : 0
            ];
        }
        return $childrens;
    }

    public static function related_product_default_quantity() {
        if( isset( $_GET['post'] ) && isset( $_GET['action'] ) ) {
            $style = "width:25%; border-radius:4px; font-size:1rem; margin:4px; padding:4px;";
            self::get_grouped_childrens($_GET['post'])
        ?>
            <div style="position:relative; width:80%; left:20%">
                <p class="form-field">
                    <label>Default Quantities</label>
                </p>
                <input type="hidden" name="ids_grouped" value=""/>
                <ul id="list-quantities"></ul>
                <script>
                    const loadQuantities = () => {
                        const params = new URLSearchParams( document.location.search )
                        fetch(ajaxurl, {
                            method:'post',
                            headers:{
                                'Content-Type':'application/x-www-form-urlencoded'
                            },
                            body: 'action=update_default_quantity&post='+params.get('post')
                        })
                            .then( response => response.json() )
                            .then( data => {
                                console.log( data )
                                jQuery('#list-quantities').html( data.html )
                                jQuery('input[name="ids_grouped"]').val( data.ids.join(',') )
                            })
                    }
                    jQuery('#grouped_products').change(loadQuantities)
                    loadQuantities()
                </script>
            </div>
        <?php
        }
    }

    public static function action_get_list_quantities_html() {
        $ids = [];
        $childrens = self::get_grouped_childrens($_POST['post']);
        $html = '';
            foreach( $childrens as $children ){
                $ids[] = $children['id'];
                $html .= '<li style="display:flex; flex-direction:row; justify-content:center; align-items:center:">';
                $html .= '<label>'.$children['name'].'</label>';
                $html .= '<input type="number" name="default_quantity_'.$children['id'].'" style="width:25%; border-radius:4px; font-size:1rem; margin:4px; padding:4px;" value="'.$children['value'].'"/></li>';
            }
        echo json_encode([
            "html" => $html,
            "ids" => $ids
        ]);
        die();
    }

    public static function woocommerce_get_catalog_ordering_args($args) {
        if( isset($_GET['searchbysku']) ) {
            $sku = isset($_GET['searchbysku']) ? $_GET['searchbysku'] : '';
            $args['join'] .= " LEFT JOIN wp_wc_product_meta_lookup ON (wp_posts.ID=wp_wc_product_meta_lookup.product_id) ";
            $args['where'] .= " AND wp_wc_product_meta_lookup.sku LIKE '%$sku%' ";
        }
        return $args;
    }


    public static function book_details( $order ) {
        $invoice_id = count( get_post_meta($order->get_id(), '_book_invoice_id') )>0 ? get_post_meta($order->get_id(), '_book_invoice_id')[0] : null; 
        $error = count( get_post_meta($order->get_id(), '_book_error') )>0 ? get_post_meta($order->get_id(), '_book_error')[0] : null; 
        
        ?>
            <div class="order_data_column" style="width:100%;">
                <h3>Zoho Books:</h3>
                <p class="form-field form-field-wide" >
                    <label>Factura Id:</label>
                    <?php if( $invoice_id ): ?>
                        <input disabled value="<?=$invoice_id?>" style="width:100% !important; padding:4px; box-sizing: border-box;display: inline-block;margin: 0;position: relative;vertical-align: middle;"/>
                    <?php else: ?>
                        <button type="button" id="create-invoice"class="button">Generar Factura</button>
                        <script>
                            jQuery('#create-invoice')
                                .click(function(){
                                    console.log(this)
                                    fetch(ajaxurl, {
                                        method:'post',
                                        headers:{
                                            'Content-Type':'application/x-www-form-urlencoded'
                                        },
                                        body:[
                                            `action=create_invoice`,
                                            `order_id=<?=$order->get_id()?>`,
                                        ].join('&')
                                    })
                                        .then( response => response.json() )
                                        .then( json => document.location.reload() )
                                })
                        </script>
                    <?php endif; ?>
                </p>
                <?php if( $error && !$invoice_id ){
                    echo "<p>$error</p>";
                }?>
            </div>
        <?php
    }

    public static function action_create_customer() {
        if( !isset($_POST['first_name']) || !isset($_POST['last_name']) ) {
            return;
        }
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];

        $data = [
            "contact_name" => "$first_name $last_name",
            "contact_type" => "customer",
            "customer_sub_type" => "individual",
            "billing_address" => [
                "attention" => "",
                "address" => isset($_POST["billing_address_1"]) ? $_POST["billing_address_1"] : "",
                "street2" => isset($_POST["billing_address_2"]) ? $_POST["billing_address_2"] : "",
                "state_code" => "",
                "city" => isset($_POST["billing_city"]) ? $_POST["billing_city"] : "",
                "state" => isset($_POST["billing_state"]) ? $_POST["billing_state"] : "",
                "zip" => isset($_POST["billing_postcode"]) ? $_POST["billing_postcode"] : "",
                "country" => isset($_POST["billing_country"]) ? $_POST["billing_country"] : "",
                "fax" => "",
                "phone" => isset($_POST["billing_phone"]) ? $_POST["billing_phone"] : "",
            ],
            "shipping_address" => [
                "attention" => "",
                "address" => isset($_POST["shipping_address_1"]) ? $_POST["shipping_address_1"] : "",
                "street2" => isset($_POST["shipping_address_2"]) ? $_POST["shipping_address_2"] : "",
                "state_code" => "",
                "city" => isset($_POST["shipping_city"]) ? $_POST["shipping_city"] : "",
                "state" => isset($_POST["shipping_state"]) ? $_POST["shipping_state"] : "",
                "zip" => isset($_POST["shipping_postcode"]) ? $_POST["shipping_postcode"] : "",
                "country" => isset($_POST["shipping_country"]) ? $_POST["shipping_country"] : "",
                "fax" => "",
                "phone" => isset($_POST["shipping_phone"]) ? $_POST["shipping_phone"] : "",
            ],
            "custom_fields" => [
                [
                    "label" => "DNI",// intval( CF_DNI ),
                    "value" => $_POST["dni"]
                ],
                // [
                //     "label" => "cf_tipo_de_servicio", //intval( CF_TIPO_SERVICIO ),
                //     "value" => ["Víveres"]
                // ],
            ]
        ];
        LogControl::insert(__FILE__, __LINE__, "create customer, input data: ".$data["contact_name"]);
        $customer = ZohoBooks::create_customer($data);
        $result = json_encode( $customer );
        if( isset($customer['contact_id']) ) {
            update_user_meta(
                $_POST['customer_id'],
                '_book_contact_id',
                $customer['contact_id']
            );
            LogControl::insert(__FILE__, __LINE__, "creado customer con id: ".$customer['contact_id'] );
        } else {
            LogControl::insert(__FILE__, __LINE__, "Algo fue mal al crear un customer para ".$data["contact_name"] );
        }
        echo $result;
        die();
    }

    public static function action_import_customers() {
        $response = [
            "new" => [],
            "update" => [],
        ];
        $log = "";
        $contacts = ZohoBooks::list_all_contacts();
        foreach( $contacts as $contact ) {
            if( $contact['contact_type']=='customer' ) {
                $user = get_user_by( 'email', $contact['email'] );
                if ( !$user ) {
                    $username = explode('@', $contact['email'])[0];
                    $user_id = wp_insert_user([
                        "user_pass" => $username,
                        "user_login" => $username,
                        "user_email" => $contact['email'],
                        "first_name" => $contact['first_name'],
                        "last_name" => $contact['last_name'],
                        "user_nicename" => $contact['first_name']."-".$contact['last_name'],
                        "display_name" => $contact['first_name']." ".$contact['last_name'],
                        "show_admin_bar_front" => false,
                        "role" => "customer"
                    ]);
                    update_user_meta(
                        $user_id,
                        '_book_cf_dni',
                        $contact['cf_dni']
                    );
                    update_user_meta(
                        $user_id,
                        '_book_referido_por',
                        $contact['referido_por']
                    );
                    update_user_meta(
                        $user_id,
                        '_book_contact_id',
                        $contact['contact_id']
                    );
                    $response["new"][] = $contact;
                    $log .= "User create with ID $user_id, ";
                } else {
                    $username = explode('@', $contact['email'])[0];
                    $user_id = wp_insert_user([
                        "ID" => $user->ID,
                        "user_pass" => $username,
                        "user_login" => $username,
                        "user_email" => $contact['email'],
                        "first_name" => $contact['first_name'],
                        "last_name" => $contact['last_name'],
                        "user_nicename" => $contact['first_name']."-".$contact['last_name'],
                        "display_name" => $contact['first_name']." ".$contact['last_name'],
                        "show_admin_bar_front" => false,
                        "role" => "customer"
                    ]);
                    update_user_meta(
                        $user_id,
                        '_book_cf_dni',
                        $contact['cf_dni']
                    );
                    update_user_meta(
                        $user_id,
                        '_book_referido_por',
                        $contact['referido_por']
                    );
                    update_user_meta(
                        $user_id,
                        '_book_contact_id',
                        $contact['contact_id']
                    );
                    $response["update"][] = $contact;
                    $log .= "User update with ID $user_id, ";
                }
            }
        }
        LogControl::insert(__FILE__, __LINE__, $log);
        echo json_encode( $response );
        die();
    }

    public static function action_import_products() {
        $response = [
            "new" => [],
            "update" => [],
        ];
        $log = "";
        $items = ZohoBooks::list_all_items();
        foreach( $items as $item ) {
            if( $item['product_type']=='goods' && $item['status']=='active' ) {
                $sku = ($item['sku']!='') ? $item['sku'] : 'sku-'.$item['account_id'].'-'.$item['item_id'];
                $product_id = wc_get_product_id_by_sku($sku);
                if( $product_id ) {
                    $image_document_id = $item['image_document_id'];
                    $organization_id = get_option('wsa_zoho_book_organization', '');
                    $url_image = "";
                    if( count( $image_document_id )>4 )  {
                        $url_image = "https://books.zoho.com/api/v3/documents/$image_document_id?organization_id=$organization_id&inline=true";    
                    }
                    $product = new WC_Product( $product_id );
                    if( $product->get_regular_price()!=$item['rate'] || $product->get_name()!=$item['name'] ) {
                        $product->set_regular_price( $item['rate'] );
                        $product->set_name( $item['name'] );
                        $product->set_image_id( $url_image );
                        $id = $product->save();
                        update_post_meta(
                            $id,
                            '_book_item_id',
                            $item['item_id']
                        );
                        update_post_meta(
                            $id,
                            '_book_account_id',
                            $item['account_id']
                        );
                        update_post_meta(
                            $id,
                            '_book_account_name',
                            $item['account_name']
                        );
                        update_post_meta(
                            $id,
                            '_book_unit',
                            $item['unit']
                        );
                        
                        $log .= "Result save: $id, ";
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
                    update_post_meta(
                        $id,
                        '_book_item_id',
                        $item['item_id']
                    );
                    update_post_meta(
                        $id,
                        '_book_account_id',
                        $item['account_id']
                    );
                    update_post_meta(
                        $id,
                        '_book_account_name',
                        $item['account_name']
                    );
                    update_post_meta(
                        $id,
                        '_book_unit',
                        $item['unit']
                    );
                    $log .= "Result save: $id, ";
                    $response["new"][] = $item;
                }
            }
        }
        LogControl::insert(__FILE__, __LINE__, $log);
        echo json_encode( $response );
        die();
    }

    public static function action_generate_code() {
        if( isset($_POST['code']) ) {
            $code = $_POST['code'];
            $client_id = get_option('wsa_zoho_client_id', '');
            $client_secret = get_option('wsa_zoho_client_secret', '');
            $refresh_token = ZohoBooks::generate_code( $code, $client_id, $client_secret );
            if( !$refresh_token ) {
                update_option('wsa_zoho_token_error', '1');
                LogControl::insert(__FILE__, __LINE__, "Error al crear Refresh Token");
                die('0');
            }
            update_option( 'wsa_zoho_refresh_token', $refresh_token );
            $access_token = ZohoBooks::refresh_token( $refresh_token, $client_id, $client_secret );
            if( !$access_token ) {
                update_option('wsa_zoho_token_error', '1');
                LogControl::insert(__FILE__, __LINE__, "Error al crear Access Token");
                die('0');
            }
            update_option( 'wsa_zoho_access_token', $access_token );
            update_option( 'wsa_zoho_access_token_time', date('Y-m-d H:i:s') );
            update_option('wsa_zoho_token_error', '0');
            LogControl::insert(__FILE__, __LINE__, "Aprobado este codigo $code => generado refresh token $access_token");
            echo "Aprobado este codigo $code <br/> generado refresh token $access_token";
            die();
        }
    }

    public static function column_shop_order_content($column) {
        global $order;
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
        if (isset($_POST['ids_grouped'])) {
            $ids = explode(',', $_POST['ids_grouped']);
            foreach( $ids as $id ) {
                update_post_meta($post_id, "default_quantity_$id", sanitize_text_field($_POST["default_quantity_$id"]));
            }
        }
        if( isset($_POST['product_combo_id']) ) {
            update_post_meta($post_id, 'product_combo_id', sanitize_text_field($_POST['product_combo_id']));
        }
    }

    public static function pay_invoice( $order_id ) {
        $order = new WC_Order( $order_id );
        $response = ZohoBooks::create_payment( DataFormat::order_data_to_payment_data( $order ) );
        if( count($response)!=0 ) {
            update_post_meta( $order->get_id(), '_book_paid', 1 );
            LogControl::insert(__FILE__, __LINE__, "Pagada orden $order_id, respuesta ".substr(json_encode( $response ),0,30)."...");
        }
    }

    public static function update_user_data( $order ){
        $billing_address_1 = $order->get_billing_address_1();
        $billing_address_2 = $order->get_billing_address_2();
        $billing_city = $order->get_billing_city();
        $billing_state = $order->get_billing_state();
        $billing_postcode = $order->get_billing_postcode();
        $billing_country = $order->get_billing_country();
        $billing_phone = $order->get_billing_phone();

        $user = get_user_by( 'email', $email );
        if( $user ) {
            $user_id = wp_insert_user([
                "ID" => $user->ID,
                "billing_address_1" => $billing_address_1,
                "billing_address_2" => $billing_address_2,
                "billing_city" => $billing_city,
                "billing_state" => $billing_state,
                "billing_postcode" => $billing_postcode,
                "billing_country" => $billing_country,
                "billing_phone" => $billing_phone
            ]);
        }
    }

    public static function order_created( $order ){
        update_post_meta(
            $order->get_id(),
            'order_rate_usd',
            WooSellerAssistant::get_rate_usd()
        );
        
        $invoice = ZohoBooks::create_invoice( DataFormat::order_to_data_invoice( $order ) );
        if( isset($invoice["invoice_id"]) ) {
            LogControl::insert(__FILE__, __LINE__, "Creada factura ".$invoice['invoice_id']." para la orden ".$order->get_id());
            update_post_meta(
                $order->get_id(),
                '_book_invoice_id',
                $invoice["invoice_id"]
            );
            update_post_meta(
                $order->get_id(),
                '_book_paid',
                0
            );
        } else {
            LogControl::insert(__FILE__, __LINE__, "No se pudo crear factura para la orden ".$order->get_id() );
            update_post_meta(
                $order->get_id(),
                '_book_error',
                json_encode( $invoice )
            );
            update_post_meta(
                $order->get_id(),
                '_book_paid',
                0
            );
        }
    }

    public static function action_create_invoice() {
        if( !isset($_POST['order_id']) ) {
            echo "[]";
        } else {
            $order = new WC_Order( $_POST['order_id'] );
            $invoice = ZohoBooks::create_invoice( DataFormat::order_to_data_invoice( $order ) );
            if( isset($invoice["invoice_id"]) ) {
                update_post_meta(
                    $order->get_id(),
                    '_book_invoice_id',
                    $invoice["invoice_id"]
                );
            } else {
                update_post_meta(
                    $order->get_id(),
                    '_book_error',
                    json_encode( $invoice )
                );
            }
            echo json_encode($invoice);
        }
        die();
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

    public static function set_order_customer_id($id) {
        if( isset($_POST['customer']) ) {
            return $_POST['customer']==-1 ? $id : $_POST['customer'];
        }
        return $id;
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
        $id = get_current_user_id();
        SC_Rates::new_rate([
            "currency" => "USD",
            "value" => $value,
            "target" => "[]",
            "created_at" => date("Y-m-d H:i:s")
        ]);
        LogControl::insert(__FILE__, __LINE__, "Acutalizada tasa por usuario al nuevo valor: $value");
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

    public static function action_update_config() {
        $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : get_current_user_id();
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
            update_option('wsa_woo_public_client_'.$user_id, $_POST['wsa_woo_public_client']);
        }
        if( isset($_POST['wsa_woo_private_client']) ) {
            update_option('wsa_woo_private_client_'.$user_id, $_POST['wsa_woo_private_client']);
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
    
        $plugin_path = plugin_dir_path(__DIR__).'template/woocommerce/';
        
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