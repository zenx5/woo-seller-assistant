<?php

defined( 'ABSPATH' ) || exit;

include 'class-zoho-api.php';

class ZohoBooks extends ZohoApi {

    private static $baseurl = "https://www.zohoapis.com/books/v3/";

    public static function create_invoice($data = []) {
        $organization_id = get_option('wsa_zoho_book_organization', '');
        $response = self::get_token();
        if( $response["error"]==1 ) return ["error"=>"not token"];
        $access_token = $response["access_token"];

        $url = "https://www.zohoapis.com/books/v3/invoices?organization_id=$organization_id";
        $headers = [
            'Authorization:  Zoho-oauthtoken '.$access_token,
            'content-type: application/json'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = json_decode( curl_exec($ch), true );
        curl_close($ch);

        return ( $response['code']==0 ) ? $response['invoice'] : $response;
    }

    public static function create_customer ( $data ) {
        $organization_id = get_option('wsa_zoho_book_organization', '');
        $response = self::get_token();
        if( $response["error"]==1 ) return ["error"=>"not token"];
        $access_token = $response["access_token"];

        $url = "https://www.zohoapis.com/books/v3/contacts?organization_id=$organization_id";
        $headers = [
            'Authorization:  Zoho-oauthtoken '.$access_token,
            'content-type: application/json'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = json_decode( curl_exec($ch), true );
        curl_close($ch);

        return ( $response['code']==0 ) ? $response['contact'] : $response;
    }

    public static function create_payment ( $data ) {
        $organization_id = get_option('wsa_zoho_book_organization', '');
        $response = self::get_token();
        if( $response["error"]==1 ) return ["error"=>"not token"];
        $access_token = $response["access_token"];

        $url = "https://www.zohoapis.com/books/v3/customerpayments?organization_id=$organization_id";
        $headers = [
            'Authorization:  Zoho-oauthtoken '.$access_token,
            'content-type: application/json'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = json_decode( curl_exec($ch), true );
        curl_close($ch);

        return ( $response['code']==0 ) ? $response['payment'] : [];
    }

    public static function mark_as ( $mark, $invoice_id ) {
        $organization_id = get_option('wsa_zoho_book_organization', '');
        $response = self::get_token();
        if( $response["error"]==1 ) return ["error"=>"not token"];
        $access_token = $response["access_token"];

        $url = "https://www.zohoapis.com/books/v3/invoices/$invoice_id/$mark?organization_id=$organization_id";
        $headers = [
            'Authorization:  Zoho-oauthtoken '.$access_token,
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = json_decode( curl_exec($ch), true );
        curl_close($ch);
        echo "<h2>".json_encode( $response )."</h2>";

        return $response['code']==0;
    }

    public static function list_all_contacts($id = null) {
        return self::get_single_resource('contacts', $id, 'contact');
    }

    public static function list_all_items($id = null, $page = 1) {
        return self::get_single_resource('items', $id, 'item', $page);
    }

    public static function list_all_invoices($id = null) {
        return self::get_single_resource('invoices', $id);
    }

    public static function get_single_resource($resource, $id = null, $singular_resource = '', $page = 1 ) {
        $organization_id = get_option('wsa_zoho_book_organization', '');
        $response = self::get_token();
        if( $response["error"]==1 ) return [];
        $access_token = $response["access_token"];

        $url = $id ? self::$baseurl."$resource/$id?organization_id=$organization_id" : self::$baseurl."$resource?organization_id=$organization_id&page=$page";
        $key = $id ? ( $singular_resource=='' ? $resource : $singular_resource ) : $resource;
        $headers = [
            'Authorization:  Zoho-oauthtoken '.$access_token
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = json_decode( curl_exec($ch), true );
        curl_close($ch);

        return ( $response['code']==0 ) ? $response[$key] : $response;
    }

}