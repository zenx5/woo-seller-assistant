<div style="padding:20px 0px">
    <table>
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
        <?php if( true || $refresh_token=='' ): ?>
            <tr>
                <th style="width:200px;">Zoho Code: </th>
                <td>
                    <input
                        type="text"
                        id="code-access"
                        name="code-access"/>
                </td>
                <td>
                    <button class="button" id="action-save-code">Generar Token</button>
                </td>
            </tr>
        <?php endif; ?>
    </table>
    <button type="button" style="margin-top:20px; padding:5px 20px;" class="save-config button">Guardar</button>
</div>