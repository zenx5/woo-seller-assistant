<?php
    $public_client = get_option('wsa_woo_public_client','');
    $private_client = get_option('wsa_woo_private_client','');
    $token = "Basic ".base64_encode("$public_client:$private_client");
?>
    <script>
        const { createApp } = Vue
        console.log('js_footer')
        createApp({
            data(){
                return {
                    customers: wp_customers,
                    client:-1,
                    search:'',
                }
            },
            computed: {
                customersFiltered: function() {
                    return this.customers.filter( customer => {
                        if( this.search==="" ) return true;
                        return customer.data.display_name.includes( this.search )
                    })
                }
            },
            methods: {
                searchUser(){
                    console.log( this.search )
                },
                createUser(){
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
                            "email": document.querySelector('#billing_email')?.value,
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
                    .then( result => console.log( result ) )

                },
                selectUser(event) {
                    console.log( 'select user' )
                    if( event.target?.value !== -1 ) {
                        const customer = wp_customers.find( user => user.ID==event.target?.value )
                        console.log( customer )
                        Object.keys( customer.billing ).forEach( key => {
                            const field = document.querySelector(`#${key}`)
                            if( field ) {
                                const item = jQuery(`#${key}`)
                                item.val( customer.billing[key] )
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
                    }
                }
            }
        }).mount('#app-checkout')
    </script>