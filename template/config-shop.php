<?php 
    $rate = WooSellerAssistant::get_rate_usd();
    
?>
<h1>Configuracion de la Tienda</h1>
<script>
    console.log(<?=json_encode( SC_Rates::get_row_at() )?>)
</script>
<div style="padding:20px 0px">
    <table>
        <tr>
            <th>Rate: </th>
            <td>
                <input
                    type="number"
                    id="wsa_rate_usd"
                    name="wsa_rate_usd"
                    step="0.01"
                    value="<?=$rate?>"/>
            </td>
        </tr>
    </table>
    <button type="button" style="margin-top:20px; padding:5px 20px;" id="save-config">Guardar</button>
    <script>
        document
            .querySelector('#save-config')
            .addEventListener('click', async event => {
                const rate = document.querySelector('#wsa_rate_usd').value
                const response = await fetch(ajaxurl, {
                    method:'post',
                    headers:{
                        'Content-Type':'application/x-www-form-urlencoded'
                    },
                    body:`action=update_config&wsa_rate_usd=${rate}`
                })
                const result = await response.text();
                if( result==1 ) document.location.reload();
            })
    </script>
</div>