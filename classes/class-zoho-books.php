<?php

include 'class-zoho-api.php';

class ZohoBooks extends ZohoApi {



    public static function create_invoice($data = []) {
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
        $token = self::refresh_token( get_option('wsa_zoho_refresh_token', '') );
        $organization_id = get_option('wsa_zoho_book_organization', '');
        $url = "https://www.zohoapis.com/books/v3/invoices?organization_id=$organization_id";
        $headers = [
            'Authorization:  Zoho-oauthtoken '.$token,
            'content-type: application/json'
        ];
        return $token;
        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, $url);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        // $response = curl_exec($ch);
        // curl_close($ch);

        // return $response;
    }

    
}