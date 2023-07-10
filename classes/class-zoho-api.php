<?php

class ZohoApi {

    public static function generate_code( $code, $field = null ) {
        $url = 'https://accounts.zoho.com/oauth/v2/token';
        $client_id = get_option('wsa_zoho_client_id', '');
        $client_secret = get_option('wsa_zoho_client_secret', '');
        $data = [
            "grant_type" => "authorization_code",
            "client_id" => $client_id,
            "client_secret" => $client_secret,
            "code" => $code
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
        curl_close($ch);

        return $field ? json_decode($response, true)[$field] : $response;
    }

    public static function refresh_token( $last_token, $field = null ) {
        $url = 'https://accounts.zoho.com/oauth/v2/token';
        $client_id = get_option('wsa_zoho_client_id', '');
        $client_secret = get_option('wsa_zoho_client_secret', '');
        $data = [
            "grant_type" => "refresh_token",
            "refresh_token" => $last_token,
            "client_id" => $client_id,
            "client_secret" => $client_secret
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
        curl_close($ch);

        return $field ? json_decode($response, true)[$field] : $response;
    }
}