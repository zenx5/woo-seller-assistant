<?php

include 'class-zoho-api.php';

class ZohoBooks extends ZohoApi {



    public static function create_invoice($data = []) {
        return [];
        $token = self::refresh_token( get_option('wsa_zoho_refresh_token', '') );
        $url = "https://www.zohoapis.com/books/v3/invoices?organization_id=$organization_id";
        $headers = [
            'Authorization:  Zoho-oauthtoken '.$token,
            'content-type: application/json'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public static function list_all_invoices() {
        $organization_id = get_option('wsa_zoho_book_organization', '');
        $refresh_token = get_option('wsa_zoho_refresh_token', '');
        $access_token = get_option('wsa_zoho_access_token', '');
        $client_id = get_option('wsa_zoho_client_id', '');
        $client_secret = get_option('wsa_zoho_client_secret', '');

        $response = self::get_token();
        if( $response["error"]==1 ) return [];

        $url = "https://www.zohoapis.com/books/v3/invoices?organization_id=$organization_id";
        $headers = [
            'Authorization:  Zoho-oauthtoken '.$response["access_token"]
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = json_decode( curl_exec($ch), true );
        curl_close($ch);

        return ( $response['code']==0 ) ? $response['invoices'] : [];
        
    }

}