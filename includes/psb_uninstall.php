<?php
/**
 * Uninstall untility
 */

function psb_uninstall()
{
    global $wpdb;
    $table_name1 = $wpdb->prefix . "psb_members";
    $table_name2 = $wpdb->prefix . "psb_transactions";
    $table_name3 = $wpdb->prefix . "psb_cancelled";

    /**
     * Remove // below to enable deleting of tables during uninstall.
     */
        
    //$wpdb->query("DROP TABLE IF EXISTS $table_name1");
    //$wpdb->query("DROP TABLE IF EXISTS $table_name2");
    //$wpdb->query("DROP TABLE IF EXISTS $table_name3");
}