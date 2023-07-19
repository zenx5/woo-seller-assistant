<?php
    $public_client = get_option('wsa_woo_public_client','');
    $private_client = get_option('wsa_woo_private_client','');
    $token = "Basic ".base64_encode("$public_client:$private_client");
?>
    <script>
        jQuery('#billing_email').attr('v-model','email')
        const { createApp } = Vue
        createApp({
            data(){
                return {
                    customers: wp_customers,
                    client:-1,
                    search:'',
                    email:''
                }
            },
            computed: {
                customersFiltered: function() {
                    return this.customers.filter( customer => {
                        if( this.search==="" ) return true;
                        return (
                            String(customer.data.display_name).toLowerCase().includes( this.search.toLowerCase() ) ||
                            String(customer.data.dni).toLowerCase().includes( this.search.toLowerCase() )
                        )
                    })
                },
                isValidEmail(){
                    return wp_customers.findIndex( customer => customer.data.user_email===email )===-1
                }
            },
            methods: {
                createUser(){
                    if( this.isValidEmail() ) {
                        alert('El email pertenece a otro usuario')
                        return
                    }
                    const headers = new Headers()
                    headers.set('Authorization', "<?=$token?>")
                    headers.set('Content-Type', 'application/json')
                    const username = document.querySelector('#username')?.value
                    if( username === '' ) {
                        alert('Defina el username');
                        return
                    }
                    fetch('https://wp.test/wp-json/wc/v3/customers', {
                        method:'post',
                        headers: headers,
                        body:JSON.stringify({
                            "email": this.email,
                            "first_name": document.querySelector('#billing_first_name')?.value,
                            "last_name": document.querySelector('#billing_last_name')?.value,
                            "username": username,
                            "billing": {
                                "first_name": document.querySelector('#billing_first_name')?.value,
                                "last_name": document.querySelector('#billing_last_name')?.value,
                                "company": document.querySelector('#billing_company')?.value,
                                "address_1": document.querySelector('#billing_address_1')?.value,
                                "address_2": document.querySelector('#billing_address_2')?.value,
                                "city": document.querySelector('#billing_city')?.value,
                                "state": document.querySelector('#billing_state')?.value,
                                "postcode": document.querySelector('#billing_postcode')?.value,
                                "country": document.querySelector('#billing_country')?.value,
                                "email": document.querySelector('#billing_email')?.value,
                                "phone": document.querySelector('#billing_phone')?.value
                            },
                            "shipping": {
                                "first_name": document.querySelector('#shipping_first_name')?.value ?? document.querySelector('#billing_first_name')?.value,
                                "last_name": document.querySelector('#shipping_last_name')?.value ?? document.querySelector('#billing_last_name')?.value,
                                "company": document.querySelector('#shipping_company')?.value ?? document.querySelector('#billing_company')?.value,
                                "address_1": document.querySelector('#shipping_address_1')?.value ?? document.querySelector('#billing_address_1')?.value,
                                "address_2": document.querySelector('#shipping_address_2')?.value ?? document.querySelector('#billing_address_2')?.value,
                                "city": document.querySelector('#shipping_city')?.value ?? document.querySelector('#billing_city')?.value,
                                "state": document.querySelector('#shipping_state')?.value ?? document.querySelector('#billing_state')?.value,
                                "postcode": document.querySelector('#shipping_postcode')?.value ?? document.querySelector('#billing_postcode')?.value,
                                "country": document.querySelector('#shipping_country')?.value ?? document.querySelector('#billing_country')?.value,
                            }
                        })
                    })
                    .then( response => response.json() )
                    .then( result => {
                        console.log( result )
                        document.location.reload()
                    } )

                },
                selectUser(event) {
                    if( event.target?.value != -1 ) {
                        const customer = wp_customers.find( user => user.ID==event.target?.value )
                        jQuery('#dni').val( customer.data.dni )
                        Object.keys( customer.billing ).forEach( key => {
                            const field = document.querySelector(`#${key}`)
                            if( field ) {
                                const item = jQuery(`#${key}`)
                                const [first, last] = customer.data.display_name.split(' ')
                                if( 'billing_email' === key ) {
                                    item.val( customer.billing[key] || customer.data.user_email )
                                    item.attr('disabled', true)
                                }
                                else if( 'billing_first_name' === key ) {
                                    item.val( customer.billing[key] || first )
                                }
                                else if( 'billing_last_name' === key ) {
                                    item.val( customer.billing[key] || last )
                                }
                                else if( 'billing_country'===key || 'billing_state'===key ){
                                    item.val( 'billing_country'===key ? 'VE' : 'VE-F' )
                                }
                                else {
                                    item.val( customer.billing[key] )
                                }
                                if( item.is('select') ) {
                                    item.change()
                                }
                            }
                        })
                        Object.keys( customer.shipping ).forEach( key => {
                            const field = document.querySelector(`#${key}`)
                            if( field ) {
                                const item = jQuery(`#${key}`)
                                item.val( customer.shipping[key] )
                                if( item.is('select') ) {
                                    item.change()
                                }
                            }
                        })
                    } else {
                        const billing = wp_customers.length ? wp_customers[0].billing : null;
                        jQuery('#dni').val('')
                        Object.keys( billing ).forEach( key => {
                            const field = document.querySelector(`#${key}`)
                            if( field ) {
                                const item = jQuery(`#${key}`)
                                item.val('')
                                if( 'billing_email' === key ) {
                                    item.attr('disabled', false)
                                }
                                if( item.is('select') ) {
                                    item.change()
                                }
                            }
                        })
                    }
                }
            }
        }).mount('#app-checkout')
    </script>