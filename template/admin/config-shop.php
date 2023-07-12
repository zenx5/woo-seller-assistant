<?php 
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 1;
    $code = isset($_GET['code']) ? $_GET['code'] : '';
    $rate = WooSellerAssistant::get_rate_usd();
    $client_id = get_option('wsa_zoho_client_id', '');
    $client_secret = get_option('wsa_zoho_client_secret', '');
    $organization_id = get_option('wsa_zoho_book_organization', '');
    $access_token = get_option( 'wsa_zoho_access_token', '' );
    $refresh_token = get_option( 'wsa_zoho_refresh_token', '' );
    $woo_public_client = get_option('wsa_woo_public_client', '');
    $woo_private_client = get_option('wsa_woo_private_client', '');
    if($access_token!='') {
        // echo "Token: $refresh_token <br/>";
        // echo json_encode( ZohoBooks::list_all_invoices() );
        echo "<div style='display:inline-block; font-weight:bold;top:10px; padding:4px; color:white; background-color:green; margin:5px; border-radius:10px;'>active</div>";
    } else {
        echo "<div style='display:inline-block; font-weight:bold;top:10px; padding:4px; color:white; background-color:red; margin:5px; border-radius:10px;'>inactive</div>";
    }
    
?>
<style>
    .nav-container{
        display: flex;
    }
    .nav-item{
        cursor: pointer;
        padding: 10px;
        border: 1px solid black;
        border-radius: 10px 10px 0 0;
        background-color:#e0e0e1;
    }
    .nav-item.active {
        border-bottom: 0px;
        background-color:#f0f0f1;
        font-weight: bold;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

</style>
<h1>Configuracion</h1>
<ul class="nav-container">
    <li class="nav-item <?=$tab==1?'active':''?>" data-tab="1">Bodega</li>
    <li class="nav-item <?=$tab==2?'active':''?>" data-tab="2">Zoho</li>
    <li class="nav-item <?=$tab==3?'active':''?>" data-tab="3">WooCommerce</li>
    <li class="nav-item <?=$tab==4?'active':''?>" data-tab="4">Facturas</li>
</ul>
<?php for($i=1; $i<=4; $i++): ?>
    <div id="tab-<?=$i?>" class="tab-content <?=$tab==$i?'active':''?>">
        <?php include 'tab'.$i.'.php'; ?>
    </div>
<?php endfor; ?>
<script>
    jQuery('.nav-item').click(function(){
        const item = jQuery(this);
        const tab = item.data('tab')
        item.parent().children().removeClass('active')
        item.addClass('active')
        jQuery('.tab-content').removeClass('active')
        jQuery('#tab-'+tab).addClass('active')
    })
</script>
<script>
    jQuery('.btn-import-from-zoho').click(async function(){
        const action = jQuery(this).data('action')
        const response = await fetch(ajaxurl, {
            method:'post',
            headers:{
                'Content-Type':'application/x-www-form-urlencoded'
            },
            body:`action=${action}`,
        })
        console.log( await response.json() );
    })
    jQuery('.save-config')
        .click(async event => {
            const rate = document.querySelector('#wsa_rate_usd').value
            const organizationId = document.querySelector('#wsa_zoho_book_organization').value
            const clientId = document.querySelector('#wsa_zoho_client_id').value
            const clientSecret = document.querySelector('#wsa_zoho_client_secret').value
            const wooPublicClient = document.querySelector('#wsa_woo_public_client').value
            const wooPrivateClient = document.querySelector('#wsa_woo_private_client').value
            const response = await fetch(ajaxurl, {
                method:'post',
                headers:{
                    'Content-Type':'application/x-www-form-urlencoded'
                },
                body:[
                    `action=update_config`,
                    `wsa_rate_usd=${rate}`,
                    `wsa_zoho_book_organization=${organizationId}`,
                    `wsa_zoho_client_id=${clientId}`,
                    `wsa_zoho_client_secret=${clientSecret}`,
                    `wsa_woo_public_client=${wooPublicClient}`,
                    `wsa_woo_private_client=${wooPrivateClient}`
                ].join('&')
            })
            const result = await response.text();
            if( result==1 ) document.location.reload();
        })
</script>
<?php if( true || $refresh_token=='' ): ?>
    <script>
        jQuery('#action-save-code').click(async function(){
            const code = jQuery('#code-access').val();
            const response = await fetch(ajaxurl, {
                method:'post',
                headers:{
                    'Content-Type':'application/x-www-form-urlencoded'
                },
                body:[
                    `action=generate_code`,
                    `code=${code}`
                ].join('&')
            })
            const result = await response.text();
            // if( result==1 ) document.location.reload();
            console.log( result )
        })
    </script>
<?php endif; ?>