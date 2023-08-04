<div style="padding:20px 0px">
    <table>
        <tr>
            <th style="width:200px;">Public Client: </th>
            <td>
                <input
                    type="text"
                    id="wsa_woo_public_client"
                    name="wsa_woo_public_client"
                    value="<?=$woo_public_client?>"/>
            </td>
        </tr>
        <tr>
            <th style="width:200px;">Private Client: </th>
            <td>
                <input
                    type="text"
                    id="wsa_woo_private_client"
                    name="wsa_woo_private_client"
                    value="<?=$woo_private_client?>"/>
            </td>
        </tr>
    </table>
    <div style="display:flex; flex-direction:row; align-items:center; gap:5px;">
        <button type="button" style="margin-top:20px; padding:5px 20px;" class="save-config button">Guardar</button>
        <button style="margin-top:20px; padding:5px 20px;" class="button btn-import-from-zoho" data-action="import_products">Importar Productos</button>
        <button style="margin-top:20px; padding:5px 20px;" class="button btn-import-from-zoho" data-action="import_customers">Importar Clientes</button>
        <span style="padding:5px; display:flex; flex-direction:row; margin-top:20px; gap:5px">
            <label style="display:flex; align-items:center;">Page:</label>
            <input type="number" value="1" name="import_page" style="padding: 5px 20px; margin: 5px;"/>
        </span>
    </div>
</div>