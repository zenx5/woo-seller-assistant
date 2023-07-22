<?php

defined( 'ABSPATH' ) || exit;

class ZohoApi {

    public static function generate_code( $code, $client_id, $client_secret ) {
        $url = 'https://accounts.zoho.com/oauth/v2/token';
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
        $response = json_decode( curl_exec($ch), true );
        curl_close($ch);

        return isset($response['refresh_token']) ? $response['refresh_token'] : null;
    }

    public static function refresh_token( $refresh_token, $client_id, $client_secret ) {
        $url = 'https://accounts.zoho.com/oauth/v2/token';
        $data = [
            "grant_type" => "refresh_token",
            "refresh_token" => $refresh_token,
            "client_id" => $client_id,
            "client_secret" => $client_secret
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = json_decode( curl_exec($ch), true );
        curl_close($ch);
        
        return isset($response['access_token']) ? $response['access_token'] : null;
    }

    public static function get_token() {
        $error = 0;
        $refresh_token = get_option('wsa_zoho_refresh_token', '');
        $access_token = get_option('wsa_zoho_access_token', '');
        $client_id = get_option('wsa_zoho_client_id', '');
        $client_secret = get_option('wsa_zoho_client_secret', '');
        if ( ! is_string( $access_token ) ) {
            $error = 0;
            $access_token = '';
            update_option('wsa_zoho_access_token', '');
            update_option('wsa_zoho_token_error', '1');
        } else {
            $present = date('Y-m-d H:i:s');
            $time_limit = date('Y-m-d H:i:s', strtotime( get_option('wsa_zoho_access_token_time', '').' + 1 minute' ));
            if( $present > $time_limit ) {
                $access_token = self::refresh_token( $refresh_token, $client_id, $client_secret );
                if( $access_token ) {
                    update_option('wsa_zoho_access_token', $access_token);
                    update_option( 'wsa_zoho_access_token_time', date('Y-m-d H:i:s') );
                } else {
                    $error = 1;
                    $access_token = '';
                    update_option('wsa_zoho_access_token', '');
                    update_option('wsa_zoho_token_error', '1');
                }
            }
        }
        return [
            "error" => $error,
            "access_token" => $access_token
        ];

    }
}