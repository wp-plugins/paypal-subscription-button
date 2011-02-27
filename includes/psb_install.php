<?php
//Installs custom database tables.
function psb_install () {
   global $wpdb;
   
   $table_name1 = $wpdb->prefix . "psb_members";
   $table_name2 = $wpdb->prefix . "psb_transactions";
   $table_name3 = $wpdb->prefix . "psb_cancelled";
   if( $wpdb->get_var("show tables like '$table_name1'") != $table_name1 
   && $wpdb->get_var("show tables like '$table_name2'") != $table_name2
   && $wpdb->get_var("show tables like '$table_name3'") != $table_name3 ) {
      
      $sql = "CREATE TABLE " . $table_name1 . " (
	  member_id int(20) unsigned NOT NULL auto_increment,
	  wp_user_id int(20) unsigned NOT NULL,
	  subscr_id varchar(50) NOT NULL,
	  membership_type varchar(32) NOT NULL,
	  payment_type varchar(32) NOT NULL,
	  status varchar(16) NOT NULL,
	  PRIMARY KEY  (member_id),
	  UNIQUE KEY members (subscr_id)
	) $charset_collate;";  
	
	  $sql .= "CREATE TABLE " . $table_name2 . " (
	  trans_id int(20) unsigned NOT NULL auto_increment,
	  wp_user_id int(20) unsigned NOT NULL,
	  subscr_id varchar(50) default NULL,
	  date varchar(50) NOT NULL,
	  amount DECIMAL(5,2) NOT NULL,
	  txn_id varchar(50) NOT NULL,
	  first_name varchar(50) NOT NULL,
	  last_name varchar(50) NOT NULL,
	  payer_email varchar(75) NOT NULL,
	  PRIMARY KEY  (trans_id),
	  UNIQUE KEY transact (txn_id, subscr_id)
	) $charset_collate;"; 	
	
	  $sql .= "CREATE TABLE " . $table_name3 . " (
	  cancel_id int(20) unsigned NOT NULL auto_increment,
	  wp_user_id int(20) unsigned NOT NULL,
	  subscr_id varchar(50) NOT NULL,
	  date varchar(50) NOT NULL,
	  PRIMARY KEY  (cancel_id),
	  UNIQUE KEY cancel (subscr_id)
	) $charset_collate;"; 

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
   }
   
   psb_add_options();
}

function psb_add_options() {
	//Add options for admin settings.
    add_option("psb_db_version", 0.1);
}