<?php
/**
 * Installs custom database tables.
 * The tables are designed to allow a user to have multiple transaction. Not enabled (as of now) in psb_PSB class.
 */

function psb_install ()
{
   global $wpdb;
   
   $table_name1 = $wpdb->prefix . "psb_members";
   $table_name2 = $wpdb->prefix . "psb_transactions";
   $table_name3 = $wpdb->prefix . "psb_cancelled";
   if ( $wpdb->get_var("show tables like '$table_name1'") != $table_name1
        AND $wpdb->get_var("show tables like '$table_name2'") != $table_name2
        AND $wpdb->get_var("show tables like '$table_name3'") != $table_name3 )
   {
      $sql = "CREATE TABLE " . $table_name1 . " (
	  member_id int(20) unsigned NOT NULL auto_increment,
	  wp_user_id int(20) unsigned NOT NULL,
	  membership_type varchar(32) NOT NULL,
	  payment_type varchar(32) NOT NULL,
	  status varchar(16) NOT NULL,
          due datetime NULL,
	  PRIMARY KEY  (member_id)
	) $charset_collate;";  
	
	  $sql .= "CREATE TABLE " . $table_name2 . " (
	  trans_id int(20) unsigned NOT NULL auto_increment,
	  wp_user_id int(20) unsigned NOT NULL,
	  date datetime NOT NULL,
	  amount DECIMAL(5,2) NOT NULL,
	  txn_id varchar(50) NOT NULL,
	  first_name varchar(50) NOT NULL,
	  last_name varchar(50) NOT NULL,
	  payer_email varchar(75) NOT NULL,
	  PRIMARY KEY  (trans_id),
	  UNIQUE KEY transact (txn_id)
	) $charset_collate;"; 	
	
	  $sql .= "CREATE TABLE " . $table_name3 . " (
	  cancel_id int(20) unsigned NOT NULL auto_increment,
	  wp_user_id int(20) unsigned NOT NULL,
	  date datetime NOT NULL,
	  PRIMARY KEY  (cancel_id)
	) $charset_collate;";

       if (get_option('psb_db_version') != '1.2.0')
       {
          /*
           * If the db is old which means the plugin is an older version, delete subscr_id columns since they're not needed anymore.
           */
          $sql .= "ALTER TABLE". $table_name1 ."DROP COLUMN subscr_id;";
          $sql .= "ALTER TABLE". $table_name2 ."DROP COLUMN subscr_id;";
          $sql .= "ALTER TABLE". $table_name3 ."DROP COLUMN subscr_id;";
       }

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
   }

   psb_add_options();
}

function psb_add_options()
{
    //Add options for admin settings.
    add_option('psb_db_version', '1.2.0');
}