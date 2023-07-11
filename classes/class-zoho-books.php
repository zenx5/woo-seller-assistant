<?php

include 'class-zoho-api.php';

class ZohoBooks extends ZohoApi {

    private static $baseurl = "https://www.zohoapis.com/books/v3/";

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

    public static function list_all_items() {
        return self::get_single_resource('items');        
    }

    public static function list_all_invoices() {
        return self::get_single_resource('invoices');        
    }

    public static function get_single_resource($resource) {
        $organization_id = get_option('wsa_zoho_book_organization', '');
        $response = self::get_token();
        if( $response["error"]==1 ) return [];
        $access_token = $response["access_token"];

        $url = self::$baseurl."$resource?organization_id=$organization_id";
        $headers = [
            'Authorization:  Zoho-oauthtoken '.$access_token
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = json_decode( curl_exec($ch), true );
        curl_close($ch);

        return ( $response['code']==0 ) ? $response[$resource] : [];
    }

}