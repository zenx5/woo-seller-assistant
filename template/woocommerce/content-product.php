<?php
/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product.php.
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
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Ensure visibility.
if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}
?>
	<?php
	// do_action( 'woocommerce_before_shop_loop_item' );
	// do_action( 'woocommerce_before_shop_loop_item_title' );
	// do_action( 'woocommerce_shop_loop_item_title' );
	// do_action( 'woocommerce_after_shop_loop_item_title' );
	// do_action( 'woocommerce_after_shop_loop_item' );
	?>
<tr>
	<td><?=$product->get_id()?></td>
	<td><a href="<?=$product->get_permalink()?>"><?=$product->get_name()?></a></td>
	<td><?=$product->get_sku()?></td>
	<td><?=$product->get_regular_price()?></td>
	<td>
		<a href="?add-to-cart=<?php echo esc_attr( $product->get_id() ); ?>" data-quantity="1" class="button wp-element-button product_type_simple add_to_cart_button ajax_add_to_cart" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>" data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>" aria-label="Añade “<?php echo esc_attr( $product->get_name() ); ?>” a tu carrito" rel="nofollow">Añadir al carrito</a>
	</td>
</tr>
