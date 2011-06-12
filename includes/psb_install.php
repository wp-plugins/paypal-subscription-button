<?php
/**
 * Installs custom database tables.
 * The tables are designed to allow a user to have multiple transaction. Not enabled (as of now) in psb_PSB class.
 */

function psb_install()
{
   global $wpdb;
   
   $new_version = '1.2.3';
   $current_version = get_option('psb_version');
   $old_version_name = get_option('psb_db_version');
   
   $table_name1 = $wpdb->prefix . "psb_members";
   $table_name2 = $wpdb->prefix . "psb_transactions";
   $table_name3 = $wpdb->prefix . "psb_ended";
   $table_old_name3 = $wpdb->prefix . "psb_cancelled";
   
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
          ipn longtext NULL,
	  PRIMARY KEY  (trans_id),
	  UNIQUE KEY transact (txn_id)
	) $charset_collate;"; 	
	
   $sql .= "CREATE TABLE " . $table_name3 . " (
	  cancel_id int(20) unsigned NOT NULL auto_increment,
	  wp_user_id int(20) unsigned NOT NULL,
	  date datetime NOT NULL,
	  PRIMARY KEY  (cancel_id)
	) $charset_collate;";
   
   if ( $wpdb->get_var("show tables like '$table_name1'") != $table_name1
        AND $wpdb->get_var("show tables like '$table_name2'") != $table_name2
        AND ($wpdb->get_var("show tables like '$table_name3'") != $table_name3 OR $wpdb->get_var("show tables like '$table_old_name3'") != $table_old_name3))
   {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
   }
   else
   {
       if ($current_version !== $new_version OR empty($current_version))
       {
          if ($current_version === '1.2.2')
          {
              // rename table_name3 in all previous versions           
              $sql .= "RENAME TABLE" . $table_old_name3 . "to" . $table_name3 . "";
          }
          
          if(!empty($old_version_name))
          {
              // add ipn column for 1.2.1
              if ($old_version_name === '1.2.1') 
              {
                  // rename table_name3 in all previous versions           
                  $sql .= "RENAME TABLE" . $table_old_name3 . "to" . $table_name3 . "";
                  // add ipn column to table_name2
                  $sql .= "ALTER TABLE". $table_name2 ."ADD ipn LONGTEXT NULL";
              }
              
              // drop subscr_id column for 1.2.0
              if ($old_version_name === '1.2.0')
              {
                  // rename table_name3 in all previous versions           
                  $sql .= "RENAME TABLE" . $table_old_name3 . "to" . $table_name3 . "";
                  // add ipn column to table_name2
                  $sql .= "ALTER TABLE". $table_name2 ."ADD ipn LONGTEXT NULL";
                  // drop subscr_id column from all tables
                  $sql .= "ALTER TABLE". $table_name1 ."DROP COLUMN subscr_id;";
                  $sql .= "ALTER TABLE". $table_name2 ."DROP COLUMN subscr_id;";
                  $sql .= "ALTER TABLE". $table_name3 ."DROP COLUMN subscr_id;";
              }
              // delete option psb_db_version because it's not needed anymore
              delete_option('psb_db_version');
          }
          
          // update or set to new version
          update_option('psb_version', $new_version);
          
          require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
          dbDelta($sql);
       }
   }
}