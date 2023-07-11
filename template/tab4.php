<?php 
    $invoices = ZohoBooks::list_all_invoices();

?>
<div>
    <ul>
    <?php foreach ($invoices as $invoice): ?>
        <li>
            <h4>Factura <?=$invoice['invoice_number']?></h4>
            <p>Cliente: <?=$invoice['customer_name']?>(<?=$invoice['email']?>) </p>
            <p><?=$invoice['currency_symbol']?> <?=$invoice['total']?></p>
        </li>
    <?php endforeach; ?>
    </ul>
</div>