<div>
    <input type="text" id="code-access" name="code-access" />
    <button class="button" id="action-save-code">Generar Token</button>
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
            if( result==1 ) document.location.reload();
        })
    </script>
</div>