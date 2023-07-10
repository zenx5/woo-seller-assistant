<?php 
    $rate = WooSellerAssistant::get_rate_usd();
    $zb_token = get_option('wsa_zoho_books_token', '')
    // $user_roles = get_option('wp_user_roles');
    // $user_roles['shop_manager']['capabilities']['create_users'] = true;
    // $user_roles['shop_manager']['capabilities']['level_10'] = true;
    // update_option('wp_user_roles',  $user_roles );
?>
<h1>Configuracion de la Tienda</h1>
<script>
    console.log(<?=json_encode( $user_roles )?>)
</script>
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
            <th style="width:200px;">Token Zoho Books: </th>
            <td>
                <input
                    type="text"
                    id="wsa_zoho_books_token"
                    name="wsa_zoho_books_token"
                    value="<?=$zb_token?>"/>
            </td>
        </tr>
    </table>
    <button type="button" style="margin-top:20px; padding:5px 20px;" id="save-config">Guardar</button>
    <script>
        document
            .querySelector('#save-config')
            .addEventListener('click', async event => {
                const rate = document.querySelector('#wsa_rate_usd').value
                const token = document.querySelector('#wsa_zoho_books_token').value
                const response = await fetch(ajaxurl, {
                    method:'post',
                    headers:{
                        'Content-Type':'application/x-www-form-urlencoded'
                    },
                    body:`action=update_config&wsa_rate_usd=${rate}&wsa_zoho_books_token=${token}`
                })
                const result = await response.text();
                if( result==1 ) document.location.reload();
            })
    </script>
</div>