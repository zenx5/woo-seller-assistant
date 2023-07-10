<?php

class ZohoApi {

    public function generate_code( $code ) {
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
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function refresh_token( $last_token ) {
        $url = 'https://accounts.zoho.com/oauth/v2/token';
        $client_id = get_option('wsa_zoho_client_id', '');
        $client_secret = get_option('wsa_zoho_client_secret', '');
        $data = [
            "refresh_token" => $last_token,
            "client_id" => $client_id,
            "client_secret" => $client_secret,
            "code" => "refresh_token"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}