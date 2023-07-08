<?php

defined( 'ABSPATH' ) || exit;

class WC_Cart_Two {

	
    public static function update_price( $product_price, $product_id, $cart_item_key ) {
        update_option('price_'.$cart_item_key.'_'.$product_id, $product_price );
    }

    public static function update_cart_action() {
        if ( ! ( isset( $_REQUEST['apply_coupon'] ) || isset( $_REQUEST['remove_coupon'] ) || isset( $_REQUEST['remove_item'] ) || isset( $_REQUEST['undo_item'] ) || isset( $_REQUEST['update_cart'] ) || isset( $_REQUEST['proceed'] ) ) ) {
			return;
		}

		wc_nocache_headers();

		$nonce_value = wc_get_var( $_REQUEST['woocommerce-cart-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

		if ( ! empty( $_POST['apply_coupon'] ) && ! empty( $_POST['coupon_code'] ) ) {
			WC()->cart->add_discount( wc_format_coupon_code( wp_unslash( $_POST['coupon_code'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		} elseif ( isset( $_GET['remove_coupon'] ) ) {
			WC()->cart->remove_coupon( wc_format_coupon_code( urldecode( wp_unslash( $_GET['remove_coupon'] ) ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		} elseif ( ! empty( $_GET['remove_item'] ) && wp_verify_nonce( $nonce_value, 'woocommerce-cart' ) ) {
			$cart_item_key = sanitize_text_field( wp_unslash( $_GET['remove_item'] ) );
			$cart_item     = WC()->cart->get_cart_item( $cart_item_key );

			if ( $cart_item ) {
				WC()->cart->remove_cart_item( $cart_item_key );

				$product = wc_get_product( $cart_item['product_id'] );

				/* translators: %s: Item name. */
				$item_removed_title = apply_filters( 'woocommerce_cart_item_removed_title', $product ? sprintf( _x( '&ldquo;%s&rdquo;', 'Item name in quotes', 'woocommerce' ), $product->get_name() ) : __( 'Item', 'woocommerce' ), $cart_item );

				// Don't show undo link if removed item is out of stock.
				if ( $product && $product->is_in_stock() && $product->has_enough_stock( $cart_item['quantity'] ) ) {
					/* Translators: %s Product title. */
					$removed_notice  = sprintf( __( '%s removed.', 'woocommerce' ), $item_removed_title );
					$removed_notice .= ' <a href="' . esc_url( wc_get_cart_undo_url( $cart_item_key ) ) . '" class="restore-item">' . __( 'Undo?', 'woocommerce' ) . '</a>';
				} else {
					/* Translators: %s Product title. */
					$removed_notice = sprintf( __( '%s removed.', 'woocommerce' ), $item_removed_title );
				}

				wc_add_notice( $removed_notice, apply_filters( 'woocommerce_cart_item_removed_notice_type', 'success' ) );
			}

			$referer = wp_get_referer() ? remove_query_arg( array( 'remove_item', 'add-to-cart', 'added-to-cart', 'order_again', '_wpnonce' ), add_query_arg( 'removed_item', '1', wp_get_referer() ) ) : wc_get_cart_url();
			wp_safe_redirect( $referer );
			exit;

		} elseif ( ! empty( $_GET['undo_item'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $nonce_value, 'woocommerce-cart' ) ) {

			// Undo Cart Item.
			$cart_item_key = sanitize_text_field( wp_unslash( $_GET['undo_item'] ) );

			WC()->cart->restore_cart_item( $cart_item_key );

			$referer = wp_get_referer() ? remove_query_arg( array( 'undo_item', '_wpnonce' ), wp_get_referer() ) : wc_get_cart_url();
			wp_safe_redirect( $referer );
			exit;

		}

		// Update Cart - checks apply_coupon too because they are in the same form.
		if ( ( ! empty( $_POST['apply_coupon'] ) || ! empty( $_POST['update_cart'] ) || ! empty( $_POST['proceed'] ) ) && wp_verify_nonce( $nonce_value, 'woocommerce-cart' ) ) {
            
			$cart_updated = false;
			$cart_totals  = isset( $_POST['cart'] ) ? wp_unslash( $_POST['cart'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( ! WC()->cart->is_empty() && is_array( $cart_totals ) ) {
				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {

					$_product = $values['data'];
                    
					// Skip product if no updated quantity was posted.
					if ( ! isset( $cart_totals[ $cart_item_key ] ) || ! isset( $cart_totals[ $cart_item_key ]['qty'] ) || ! isset( $cart_totals[ $cart_item_key ]['price'] ) ) {
						continue;
					}

					// Sanitize.
					$quantity = apply_filters( 'woocommerce_stock_amount_cart_item', wc_stock_amount( preg_replace( '/[^0-9\.]/', '', $cart_totals[ $cart_item_key ]['qty'] ) ), $cart_item_key );
                    $price = preg_replace( '/[^0-9\.]/', '', $cart_totals[ $cart_item_key ]['price'] );

                    // die(json_encode($values));
                    // die(json_encode([
                    //     "quantity" => $quantity,
                    //     "price" => $price,
                    //     "values" => $values['quantity']
                    // ]));
                    

					if ( '' === $quantity || '' === $price  ) { //|| ($quantity === $values['quantity'])
						continue;
					}

                    // public function set_totals
                    // DEBO GUARDAR EL PRECIO

                    self::update_price( $price, $values['product_id'], $cart_item_key );

					// Update cart validation.
					$passed_validation = apply_filters( 'woocommerce_update_cart_validation', true, $cart_item_key, $values, $quantity );

					// is_sold_individually.
					if ( $_product->is_sold_individually() && $quantity > 1 ) {
						/* Translators: %s Product title. */
						wc_add_notice( sprintf( __( 'You can only have 1 %s in your cart.', 'woocommerce' ), $_product->get_name() ), 'error' );
						$passed_validation = false;
					}

					if ( $passed_validation ) {
						WC()->cart->set_quantity( $cart_item_key, $quantity, false );
						$cart_updated = true;
					}
				}
			}

			// Trigger action - let 3rd parties update the cart if they need to and update the $cart_updated variable.
			$cart_updated = apply_filters( 'woocommerce_update_cart_action_cart_updated', $cart_updated );

			if ( $cart_updated ) {
				WC()->cart->calculate_totals();
			}

			if ( ! empty( $_POST['proceed'] ) ) {
				wp_safe_redirect( wc_get_checkout_url() );
				exit;
			} elseif ( $cart_updated ) {
				wc_add_notice( __( 'Cart updated.', 'woocommerce' ), apply_filters( 'woocommerce_cart_updated_notice_type', 'success' ) );
				$referer = remove_query_arg( array( 'remove_coupon', 'add-to-cart' ), ( wp_get_referer() ? wp_get_referer() : wc_get_cart_url() ) );
				wp_safe_redirect( $referer );
				exit;
			}
		}
    }
}