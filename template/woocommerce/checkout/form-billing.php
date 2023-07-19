<?php
/**
 * Checkout billing information form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-billing.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 * @global WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();
$is_seller = in_array('administrator', $current_user->roles) || in_array('shop_manager', $current_user->roles);

$users = [];
$_users = $is_seller ? get_users([
	"role" => "customer"
]) : [];

foreach( $_users as $_user ) {
	$customer = new WC_Customer( $_user->ID );
	$data = json_decode( json_encode( $_user->data ), true );
	$dni = get_user_meta($_user->ID, '_book_cf_dni');
	$data["dni"] = count($dni) ? $dni[0] : "";
	unset( $data["user_pass"] );
	$users[] = [
		"ID" => $_user->ID,
		"data" => $data,
		"roles" => $_user->roles,
		"billing" => [
			"billing_first_name" => $customer->get_billing_first_name(),
			"billing_last_name" => $customer->get_billing_last_name(),
			"billing_company" => $customer->get_billing_company(),
			"billing_country" => $customer->get_billing_country(),
			"billing_address_1" => $customer->get_billing_address_1(),
			"billing_address_2" => $customer->get_billing_address_2(),
			"billing_city" => $customer->get_billing_city(),
			"billing_state" => $customer->get_billing_state(),
			"billing_postcode" => $customer->get_billing_postcode(),
			"billing_phone" => $customer->get_billing_phone(),
			"billing_email" => $customer->get_billing_email()
		],
		"shipping" => [
			"shipping_first_name" => $customer->get_billing_first_name(),
			"shipping_last_name" => $customer->get_billing_last_name(),
			"shipping_company" => $customer->get_billing_company(),
			"shipping_country" => $customer->get_billing_country(),
			"shipping_address_1" => $customer->get_billing_address_1(),
			"shipping_address_2" => $customer->get_billing_address_2(),
			"shipping_city" => $customer->get_billing_city(),
			"shipping_state" => $customer->get_billing_state(),
			"shipping_postcode" => $customer->get_billing_postcode(),
			"shipping_phone" => $customer->get_billing_phone(),
			"shipping_email" => $customer->get_billing_email()
		]
	];
}


//create_users
?>
<?php if( $is_seller ): ?>
	<script>
		const wp_customers = <?=json_encode( $users )?>;
		console.log(wp_customers)
	</script>
<?php endif;?>
<div <?= $is_seller ? 'id="app-checkout"' : '' ?> >
	<?php if( $is_seller ): ?>
		<div style="display:flex; flex-direction:row; gap:5px;">
			<label class="woocommerce-form__label" style="width:50%;">
				<b>Cliente</b>
				<select style="width:100%" v-model="client" name="customer" v-on:change="selectUser">
					<option value="-1">Nuevo Usuario</option>
					<option v-for="customer in customersFiltered " :value="customer.data.ID">{{customer.data.display_name}}</option>
				</select>
			</label>
			<label class="woocommerce-form__label" style="width:50%;">
				<b>Buscar</b>
				<input type="text" placeholder="Buscar..." style="width:100%" v-model="search" v-on:keyup="searchUser" />
			</label>
		</div>
		
		<div v-if="client==-1" style="margin-top:10px; display:flex; flex-direction:row; justify-content:space-between;">
			<button type="button" v-on:click="createUser">Crear Usuario</button>
			<input type="text" id="username" name="username" placeholder="Username" />
		</div>
	<?php endif; ?>
	<div class="woocommerce-billing-fields">
		<?php if ( wc_ship_to_billing_address_only() && WC()->cart->needs_shipping() ) : ?>

			<h3><?php esc_html_e( 'Billing &amp; Shipping', 'woocommerce' ); ?></h3>

		<?php else : ?>

			<h3><?php esc_html_e( 'Billing details', 'woocommerce' ); ?></h3>

		<?php endif; ?>

		<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

		<div class="woocommerce-billing-fields__field-wrapper">
			<?php
			$fields = $checkout->get_checkout_fields( 'billing' );

			foreach ( $fields as $key => $field ) {
				
				if( $key!="billing_company" ) {
					
					// if( $key=="billing_country" ) $value = "VE";
					// else if( $key=="billing_state" ) $value = "VE-F";
					// else $value = $checkout->get_value( $key );

					// if( $key=="billing_country" || $key=="billing_state" ) $field = array_merge($field, [ "required"=>false, "custom_attributes" => ["disabled" => true] ]);
					
					woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
				}
				if( $key=="billing_last_name" ) {
					woocommerce_form_field( "dni", [
						"type" => "text",
						"label" => "DNI",
						"class" => "form-row form-row-wide"
					], "" );
				}
			}
			?>
		</div>

		<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
	</div>
</div>


<?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) : ?>
	<div class="woocommerce-account-fields">
		<?php if ( ! $checkout->is_registration_required() ) : ?>

			<p class="form-row form-row-wide create-account">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" <?php checked( ( true === $checkout->get_value( 'createaccount' ) || ( true === apply_filters( 'woocommerce_create_account_default_checked', false ) ) ), true ); ?> type="checkbox" name="createaccount" value="1" /> <span><?php esc_html_e( 'Create an account?', 'woocommerce' ); ?></span>
				</label>
			</p>

		<?php endif; ?>

		<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

		<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?>

			<div class="create-account">
				<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $key => $field ) : ?>
					<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
				<?php endforeach; ?>
				<div class="clear"></div>
			</div>

		<?php endif; ?>

		<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>
	</div>
<?php endif; ?>
