<?php

if( !class_exists( psb_Query ) ) {
	class psb_Query {
		var $post_vars;
		var $current_user_id;
		var $wpdb;
		var $payment_type;
		var $membership_type;
		
		function __construct( $post_vars, $psb_mp_types ) {
			global $wpdb;
			$this->current_user_id = $post_vars[ 'custom' ];
			$this->wpdb = $wpdb;
			$this->payment_type = $psb_mp_types->get_payment_type();
			$this->membership_type = $psb_mp_types->get_membership_type();
			
			//Sanitizes post_vars
			$this->sanitize_postvars( $post_vars );
			
			//Tell wordpress about the custom tables.
			$wpdb->psb_members = $wpdb->prefix . 'psb_members';
			$wpdb->psb_transactions = $wpdb->prefix . 'psb_transactions';
			$wpdb->psb_cancelled = $wpdb->prefix . 'psb_cancelled';
		}
		
		function sanitize_postvars( &$post_vars ) {
			foreach( $post_vars as $key => $var ) {
				$this->post_vars[ $key ] = $this->wpdb->escape( $var );	
			}
		}
		
		function transact_id_exists() {
			$transact_id = $this->wpdb->get_row( "SELECT * FROM ".$this->wpdb->psb_transactions.
												  " WHERE txn_id = '".$this->post_vars['txn_id']."' " );
			if( $transact_id ) {
				return true;	
			} 
		}
		
		function wp_user_id_exists() {
			$subscruser_id = $this->wpdb->get_row( "SELECT * FROM ".$this->wpdb->psb_members.
													" WHERE wp_user_id = '".$this->current_user_id."' " );
			if( $subscruser_id ) {
				return true;	
			} 
		}
		
		function register_member() {
			$affected_rows = $this->wpdb->insert( $this->wpdb->psb_members, 
												   array( 'wp_user_id' => $this->current_user_id, 
												   		  'subscr_id' => $this->post_vars['subscr_id'],
														  'membership_type' => $this->membership_type,
														  'payment_type' => $this->payment_type,
														  'status' => 'active' ) );
			if( $affected_rows > 0 ) {
				return true;	
			}
		}
		
		function register_transaction() {
			$affected_rows = $this->wpdb->insert( $this->wpdb->psb_transactions, 
												   array( 'wp_user_id' => $this->current_user_id,
												   		  'subscr_id' => $this->post_vars['subscr_id'],
													  	  'date' => $this->post_vars['payment_date'],
														  'amount' => $this->post_vars['mc_gross'],
														  'txn_id' => $this->post_vars['txn_id'],
														  'first_name' => $this->post_vars['first_name'],
														  'last_name' => $this->post_vars['last_name'],
														  'payer_email' => $this->post_vars['payer_email'] ) );
			if( $affected_rows > 0 ) {
				return true;	
			}
		}
		
		function update_status( $status ) {
			$affected_rows = $this->wpdb->update( $this->wpdb->psb_members, array( 'status' => $status ), 
															 array( 'subscr_id' => $this->post_vars['subscr_id'] ) );
			
			if( $affected_rows > 0 ) {
				return true;	
			}
		}
		
		function log_end() {
			
			$current_time = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) );
			
			$query_result = $this->wpdb->get_row( "SELECT * FROM ".$this->wpdb->psb_transactions.
												   " WHERE subscr_id = '".$this->post_vars['subscr_id']."' " );
			if( $query_result ) {
				$affected_rows = $this->wpdb->insert( $this->wpdb->psb_cancelled, 
													   array( 'wp_user_id' => $query_result->wp_user_id, 
															  'subscr_id' => $this->post_vars['subscr_id'],
															  'date' => $current_time ) );
				if( $affected_rows > 0 ) {
					return true;	
				}
			} 
		}
	}
}