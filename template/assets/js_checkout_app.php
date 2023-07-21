<?php
    $user_id = get_current_user_id();
    $public_client = get_option('wsa_woo_public_client_'.$user_id,'');
    $private_client = get_option('wsa_woo_private_client_'.$user_id,'');
    $token = "Basic ".base64_encode("$public_client:$private_client");
?>
    <script>
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
                }
            },
            mounted() {
                this.cleanFields()
            },
            methods: {
                isValidEmail(){
                    const index = wp_customers.findIndex( customer => customer.data.user_email===this.email )
                    console.log(index)
                    return index===-1
                },
                createUser(){
                    if( !this.isValidEmail() ) {
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
                    .then( customer => {
                        if( customer.id ) {
                            const data = {
                                email: customer.email,
                                first_name: customer.first_name,
                                dni: document.querySelector('#dni')?.value,
                                last_name: customer.last_name,
                                billing_first_name: customer.billing.first_name,
                                billing_last_name: customer.billing.last_name,
                                billing_company: customer.billing.company,
                                billing_address_1: customer.billing.address_1,
                                billing_address_2: customer.billing.address_2,
                                billing_city: customer.billing.city,
                                billing_state: customer.billing.state,
                                billing_postcode: customer.billing.postcode,
                                billing_country: customer.billing.country,
                                billing_email: customer.billing.email,
                                billing_phone: customer.billing.phone,
                                shipping_first_name: customer.shipping.first_name,
                                shipping_last_name: customer.shipping.last_name,
                                shipping_company: customer.shipping.company,
                                shipping_address_1: customer.shipping.address_1,
                                shipping_address_2: customer.shipping.address_2,
                                shipping_city: customer.shipping.city,
                                shipping_state: customer.shipping.state,
                                shipping_postcode: customer.shipping.postcode,
                                shipping_country: customer.shipping.country
                            }
                            const queryData = Object.keys(data).map( key => `${key}=${data[key]}`)
                            fetch('https://wp.test/wp-admin/admin-ajax.php', {
                                method:'post',
                                headers:{
                                    'Content-Type':'application/x-www-form-urlencoded'
                                },
                                body:[
                                    `action=create_customer`,
                                    ...queryData
                                ].join('&')
                            })
                                .then( response2 => response2.json() )
                                .then( result => {
                                    if( result ) {
                                        if( sessionStorage.getItem('eu_debug') ) {
                                            console.log( customer )
                                            console.log( result )
                                        } else {
                                            document.location.reload();
                                        }
                                    }
                                } )
                        }
                    } )

                },
                cleanFields() {
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
                },
                selectUser(event) {
                    if( event.target?.value != -1 ) {
                        jQuery('#place_order').attr('disabled', false)
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
                        jQuery('#place_order').attr('disabled', true)
                        this.cleanFields()
                    }
                }
            }
        }).mount('#app-checkout')
    </script>