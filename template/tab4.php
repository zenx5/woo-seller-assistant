<?php 
    $invoices = ZohoBooks::list_all_invoices();
    $items = ZohoBooks::list_all_items();

    echo json_encode($items);
?>
<div>
    <ul>
    <?php foreach ($invoices as $invoice): ?>
        <li>
            <h4>Factura <?=$invoice['invoice_number']?></h4>
            <p><b>Cliente:</b> <?=$invoice['customer_name']?>(<?=$invoice['email']?>) </p>
            <p><b>Total:</b><?=$invoice['currency_symbol']?> <?=$invoice['total']?></p>
        </li>
    <?php endforeach; ?>
    </ul>
</div>