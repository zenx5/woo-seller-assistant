<?php 

defined( 'ABSPATH' ) || exit;

class SC_Rates {

    public static function create_table() {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $collate = $wpdb->collate;
        $name_table = $prefix."second_currency_rates";
        $sql = "CREATE TABLE IF NOT EXISTS {$name_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            currency varchar(30),
            value varchar(255),
            target varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id) )
            COLLATE {$collate}";
        require_once(ABSPATH.'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function drop_table() { 
        $prefix = $wpdb->prefix;
        $sql = "drop table {$prefix}second_currency_rates";
        require_once(ABSPATH.'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function get_row_at( $currency="USD", $current_date = null ) {
        global $wpdb;
        $current_date = $current_date ? $current_date : date("Y-m-d");
        $query_results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}second_currency_rates WHERE currency='{$currency}' ORDER BY created_at DESC", OBJECT );
        foreach($query_results as $row ) {
            $created_at = explode(" ", $row->created_at)[0];
            if( $current_date == $created_at ) {
                return $row;
            }
        }
        return $query_results[0];
    }

    public static function new_rate( $data ) {
        global $wpdb;
        $wpdb->insert(
            "{$wpdb->prefix}second_currency_rates",
            $data
        );
    }
    
}