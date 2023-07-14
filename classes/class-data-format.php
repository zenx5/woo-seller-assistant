<?php

class DataFormat {

    public static function order_to_data_invoice($order, $id = null ) {
        $user_id = $order->get_customer_id();
        [$customer_id] = get_user_meta($user_id, '_book_contact_id');
        $customer = new WC_Customer( $customer_id );
        $line_items = [];
        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $_product = new WC_Product( $product_id );
            [$item_id] = get_post_meta( $product_id, '_book_item_id' );
            [$unit] = get_post_meta( $product_id, '_book_unit' );

            $line_items[] = [
                "item_id" => $item_id,
                "name" => $item->get_name(),
                "quantity" => floatval( $item->get_quantity() ),
                "description" => $_product->get_description(),
                "rate" => floatval( $item->get_total() ) / floatval( $item->get_quantity() ),
                "unit" => $unit,
                "discount" => 0
            ];

        }
        $data = [
            "customer_id" => $customer_id,
            "date" => $order->get_date_created()->date('Y-m-d'),
            "due_date" => $order->get_date_created()->date('Y-m-d'),
            "discount" => 0,
            "line_items" => $line_items
        ];

        return $data;
    }

    public static function order_data_to_payment_data( $order ) {
        $customer_id = $order->get_customer_id();

        $contact_id = get_user_meta( $customer_id, '_book_contact_id' );
        $contact_id = count($contact_id)? $contact_id[0] : null;

        $invoice_id = get_post_meta($order->get_id(), '_book_invoice_id');
        $invoice_id = count($invoice_id)? $invoice_id[0] : null;

        $paid = get_post_meta($order->get_id(), '_book_paid');
        $paid = count($paid)? $paid[0] : null;

        if( !$invoice_id || !$contact_id || $paid===null ) return [];

        return [
            "customer_id" => $contact_id,
            "payment_mode" => "cash",
            "amount" => $order->get_total(),
            "date" => date('Y-m-d'),
            "description" => "Payment has been added",
            "invoices" => [
                [
                    "invoice_id" => $invoice_id,
                    "amount_applied" => $order->get_total()
                ]
            ]
            // "exchange_rate": 1,
            // "payment_form": "cash",
            // "bank_charges": 10,
            // "custom_fields": [
            //     {
            //         "label": "label",
            //         "value": 129890
            //     }
            // ],
            // "invoice_id" => $invoice_id,
            // "amount_applied" => $order->get_total()
        ];
    }
}