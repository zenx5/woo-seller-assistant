<?php 
    $rate = WooSellerAssistant::get_rate_usd();
    // $zb_token = get_option('wsa_zoho_books_token', '');
    $client_id = get_option('wsa_zoho_client_id', '');
    $client_secret = get_option('wsa_zoho_client_secret', '');
    $organization_id = get_option('wsa_zoho_book_organization', '');
    
    // $code = '1000.86fbc2c71cd24d7633c450171cee04c3.d0fa2475a01c5c52fb1072c7f2441511';
    // $response1 = ZohoApi::generate_code($code);
    // echo json_decode($response1, true)['access_token'];
    // $response2 = ZohoApi::refresh_token( "1000.f5afb220b9b0afd60af96baf1730a7cd.6514f3b3945e4372fdb2c2226041fcdf" );

?>
<h1>Configuracion de la Tienda</h1>
<div style="padding:20px 0px">
    <table>
        <tr>
            <th style="width:200px;">Tasa USD: </th>
            <td>
                <input
                    type="number"
                    id="wsa_rate_usd"
                    name="wsa_rate_usd"
                    step="0.01"
                    value="<?=$rate?>"/>
            </td>
        </tr>
        <tr>
            <th style="width:200px;">Organization Id: </th>
            <td>
                <input
                    type="text"
                    id="wsa_zoho_book_organization"
                    name="wsa_zoho_book_organization"
                    value="<?=$organization_id?>"/>
            </td>
        </tr>
        <tr>
            <th style="width:200px;">Zoho Client Id: </th>
            <td>
                <input
                    type="text"
                    id="wsa_zoho_client_id"
                    name="wsa_zoho_client_id"
                    value="<?=$client_id?>"/>
            </td>
        </tr>
        <tr>
            <th style="width:200px;">Zoho Client Secret: </th>
            <td>
                <input
                    type="text"
                    id="wsa_zoho_client_secret"
                    name="wsa_zoho_client_secret"
                    value="<?=$client_secret?>"/>
            </td>
        </tr>
    </table>
    <button type="button" style="margin-top:20px; padding:5px 20px;" id="save-config">Guardar</button>
    <script>
        document
            .querySelector('#save-config')
            .addEventListener('click', async event => {
                const rate = document.querySelector('#wsa_rate_usd').value
                const organizationId = document.querySelector('#wsa_zoho_book_organization').value
                const clientId = document.querySelector('#wsa_zoho_client_id').value
                const clientSecret = document.querySelector('#wsa_zoho_client_secret').value
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
                        `wsa_zoho_client_secret=${clientSecret}`
                    ].join('&')
                })
                const result = await response.text();
                if( result==1 ) document.location.reload();
            })
    </script>
</div>