<?php 
defined( 'ABSPATH' ) || exit;

class LogControl {

    public static function create_table() {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $collate = $wpdb->collate;
        $name_table = $prefix."log_control";
        $sql = "CREATE TABLE IF NOT EXISTS {$name_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            file varchar(255),
            line varchar(255),
            message varchar(255),
            user_id bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id) )
            COLLATE {$collate}";
        require_once(ABSPATH.'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function drop_table() { 
        $sql = "drop table log_control";
        require_once(ABSPATH.'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function get_all() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}log_control WHERE 1 ORDER BY created_at DESC", OBJECT );
    }

    public static function insert($file, $line, $message) {
        global $wpdb;
        $id = get_current_user_id();
        $wpdb->insert(
            "{$wpdb->prefix}log_control",
            [
                "file" => $file,
                "line" => $line,
                "message" => $message,
                "user_id" => $id
            ]
        );
    }

}