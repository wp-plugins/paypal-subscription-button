<?php
/**
 * This class handles the db query
 */

if (!class_exists('psb_Query'))
{
    class psb_Query
    {
        var $post_vars;
        var $current_user_id;
        var $wpdb;
        var $payment_type;
        var $membership_type;
        var $psb_options;
		
        function __construct($post_vars = '', $admin_options = '', $psb_mp_types = NULL)
        {
            global $wpdb;
            $this->current_user_id = $post_vars['custom'];
            $this->wpdb = $wpdb;
            $this->admin_options = $admin_options;

            if ($psb_mp_types != NULL AND $post_vars != '')
            {
                $this->payment_type = $psb_mp_types->get_payment_type();
                $this->membership_type = $psb_mp_types->get_membership_type();

                //Sanitizes post_vars
                $this->sanitize_postvars($post_vars);
            }
		
            //Tell wordpress about the custom tables.
            $wpdb->psb_members = $wpdb->prefix . 'psb_members';
            $wpdb->psb_transactions = $wpdb->prefix . 'psb_transactions';
            $wpdb->psb_cancelled = $wpdb->prefix . 'psb_cancelled';
	}
		
	function sanitize_postvars(&$post_vars)
        {
            foreach($post_vars AS $key => $var)
            {
                $this->post_vars[$key] = $this->wpdb->escape($var);
            }
        }
		
        function transact_id_exists()
        {
            $transact_id = $this->wpdb->get_row("SELECT * FROM ".$this->wpdb->psb_transactions." WHERE txn_id = '".$this->post_vars['txn_id']."' ");
                        
            if ($transact_id)
            {
                return true;
            }
            else
            {
                return false;
            }
        }
		
	function wp_user_id_exists()
        {
            $subscruser_id = $this->wpdb->get_row("SELECT * FROM ".$this->wpdb->psb_members." WHERE wp_user_id = '".$this->current_user_id."' ");

            if ($subscruser_id)
            {
                return true;
            }
            else
            {
                return false;
            }
        }

        function register_member()
        {
            /**
             *  If it's upfront, we need to manually check if a user is due. Therefore upon registration,
             *  we need to set the date when the user will be due. Otherwise, don't set anything.
             */

            $num_days = $this->admin_options['payment_types'][$this->payment_type][$this->payment_type.'_'.$this->membership_type];
            $current_time = date('Y-m-d H:i:s');

            if ($this->payment_type == 'x-days')
            {
               $due_time = date('Y-m-d H:i:s', strtotime('+'.$num_days.' day', strtotime($current_time)));
            }
            else if ($this->payment_type == 'one-month')
            {
               $due_time = date('Y-m-d H:i:s', strtotime('+1 month', strtotime($current_time)));
            }
            else if ($this->payment_type == 'one-year')
            {
               $due_time = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($current_time)));
            }
            else
            {
               $due_time = 0;
            }

            $affected_rows = $this->wpdb->insert($this->wpdb->psb_members, array(
                                                                              'wp_user_id' => $this->current_user_id,
                                          				      'membership_type' => $this->membership_type,
                                                                              'payment_type' => $this->payment_type,
                                                                              'status' => 'active',
                                                                              'due' => $due_time
                                                                           ));
            if ($affected_rows > 0)
            {
                return true;
            }
            else
            {
                return false;
            }
        }
		
        function register_transaction()
        {
            $current_time = date('Y-m-d H:i:s');
            $affected_rows = $this->wpdb->insert($this->wpdb->psb_transactions, array(
                                                                                    'wp_user_id' => $this->current_user_id,
                                                                                    'date' => $current_time,
                                                                                    'amount' => $this->post_vars['mc_gross'],
                                                                                    'txn_id' => $this->post_vars['txn_id'],
                                                                                    'first_name' => $this->post_vars['first_name'],
                                                                                    'last_name' => $this->post_vars['last_name'],
                                                                                    'payer_email' => $this->post_vars['payer_email']
                                                                                ));
            if ($affected_rows > 0)
            {
                return true;
            }
            else
            {
                return false;
            }
        }
	
        function update_status($status, $user_id = '')
        {
            if ($user_id != '')
            {
                $id = $user_id;
            }
            else
            {
                $id = $this->current_user_id;
            }
            
            $affected_rows = $this->wpdb->update($this->wpdb->psb_members, array('status' => $status), array('wp_user_id' => $id));
			
            if ($affected_rows > 0)
            {
                return true;
            }
            else
            {
                return false;
            }
        }
		
        function log_end($user_id = '')
        {
            if ($user_id != '')
            {
                $id = $user_id;
            }
            else
            {
                $id = $this->current_user_id;
            }
            
            $current_time = date('Y-m-d H:i:s');
            $affected_rows = $this->wpdb->insert($this->wpdb->psb_cancelled, array('wp_user_id' => $id, 'date' => $current_time));

            if ($affected_rows > 0)
            {
                return true;
            }
            else
            {
                return false;
            }
        }

        function get_due_users()
        {
            $current_time = date('Y-m-d H:i:s');
            
            $query_result = $this->wpdb->get_results("SELECT * FROM ".$this->wpdb->psb_members." WHERE TIMESTAMPDIFF(MINUTE, due, '".$current_time."') >= 0 AND status = 'active' AND due != 0");

            if ($query_result)
            {
                $due_users = Array();
                
                foreach ($query_result as $row_obj)
                {
                    $due_users[] = $row_obj->wp_user_id;
                }
                return $due_users;
            }
            else
            {
                return false;
            }
        }
    }
}