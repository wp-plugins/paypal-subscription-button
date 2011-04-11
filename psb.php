<?php
/*
 * Plugin Name: Paypal Subscription Button
 * Plugin URI: http://adred.tumblr.com/psb
 * Description: Integrates Paypal Subscription Button into wordpress.
 * Author: Red Adaya
 * Version: 1.2.0
 * Author URI: http://adred.tumblr.com/psb
 */
 
/*
 * Copyright (C) 2010-2011 Red Joba Adaya, adred.tumblr.com
 *	
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *	
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
	
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * This class the main controller.
 * It passes information to and from different classes.
 */

include_once(dirname (__FILE__) . '/includes/psb_install.php');
include_once(dirname (__FILE__) . '/includes/psb_uninstall.php');
include_once(dirname (__FILE__) . '/php/psb_query.php');
include_once(dirname (__FILE__) . '/php/psb_ipn.php');	
include_once(dirname (__FILE__) . '/php/psb_options.php');
include_once(dirname (__FILE__) . '/php/psb_settings.php');
include_once(dirname (__FILE__) . '/php/psb_mp_types.php');
include_once(dirname (__FILE__) . '/php/psb_cron.php');

if (!class_exists('psb_PSB'))
{
	
    class psb_PSB
    {
	var $post_vars;
	var $admin_options;
	var $wp_user_obj;
	var $psb_mp_types;
	var $psb_query;
	var $psb_ipn;
	var $paypal_email;
	var $currency;
		
	function __construct()
        {
            //Options from wp db
            $this->admin_options = get_option( 'psb_admin_options' );
            $this->paypal_email = $this->admin_options['merchant_email'];
            $this->currency = strtoupper( $this->admin_options['currency'] );
			
            // initialize backend settings options
            if (class_exists('psb_Options'))
            {
                $psb_options = new psb_Options();

	    	if (isset($psb_options))
                {
                    $psb_options->get_psb_options();
		}
            }
	}
		
	function listener()
        {
            // initialize variables and classes and pass $_POST so other classes can use it
            $this->init_classes($_POST);
			
            if ($this->admin_options['autoset_ipn_page'] == 1)
            {
                $page_id = $this->admin_options['autoset_ipn_page_ID'];
            }
            else
            {
                $page_id = $this->admin_options['manual_ipn_page_ID'];
            }
			
            if (is_page($page_id))
            {
                //post back to Payapl to vefiy post_vars
                $this->psb_ipn->postback();
				
                // check if post_vars are verified
                if ($this->psb_ipn->is_verified())
                {
                    $payment_status = $this->post_vars['payment_status'];
                    $txn_type = $this->post_vars['txn_type'];
					
                    if ($this->is_empty_string($payment_status) AND $this->is_empty_string($txn_type))
                    {
                        $this->psb_ipn->notify("Unknown Payment Status and Transaction Type.");
			return;
                    }
					
                    if ($payment_status == 'Completed')
                    {
                        $this->psb_ipn->notify($this->process_completed());
                    }
                    else if ($payment_status == 'Pending')
                    {
                        if ($this->post_vars['pending_reason'] != "intl")
                        {
                            $this->psb_ipn->notify("Pending Payment - " .$this->post_vars['pending_reason']. "");
			}	
                    }
                    else if ($payment_status == 'Denied')
                    {
                        $this->psb_ipn->notify( "Denied Payment.");
                    }
                    else if ($payment_status == 'Refunded')
                    {
                        $this->psb_ipn->notify("Refunded Payment.");
                    }
						
                    if ($txn_type == "subscr_cancel")
                    {
                        $this->psb_ipn->notify($this->process_end('cancelled'));
                    }
                    else if ($txn_type == "subscr_eot")
                    {
                        $this->psb_ipn->notify($this->process_end('expired'));
                    }
                    else if ($txn_type == "subscr_signup")
                    {
                        $this->psb_ipn->notify("Someone has signed up.");
                    }
                }
                else
                {
                    $this->psb_ipn->notify("Bad order (PayPal says it's invalid).");
                    return;
		} 
            }
	}
		
	function init_classes($post_vars)
        {
            //initialize variables and classes
			
            // assign post_vars to class variable post_vars so member functions can access it
	    $this->post_vars = $post_vars;
			
	    // create an instance of WP_User class
	    $this->wp_user_obj = new WP_User($this->post_vars['custom']);
				
	    if (class_exists("psb_MP_Types"))
            {
                // create an instance of psb_MP_Types class.
                $this->psb_mp_types = new psb_MP_Types($this->post_vars['mc_gross']);
            }
				
            if (class_exists("psb_Query"))
            {
                // create an instance of psb_Query class.
		$this->psb_query = new psb_Query($this->post_vars, $this->admin_options, $this->psb_mp_types);
            }
				
            if(class_exists("psb_IPN"))
            {
                // create an instance of psb_IPN class.
		$this->psb_ipn = new psb_IPN($this->post_vars, $this->admin_options);
            }

            return;
	}
		
	function process_completed()
        {
            $receiver_email = strtolower(trim($this->post_vars['receiver_email']));
            $mc_currency = trim($this->post_vars['mc_currency']);
			
            if ($receiver_email != $this->paypal_email OR $mc_currency != $this->currency)
            {
            	return "Someone attempted a sale using a manipulated URL";
            }
			
            // get final payment type. e.g. monthly
            $payment_type = $this->psb_mp_types->get_payment_type();
            // get final role. e.g. silver
            $membership_type = $this->psb_mp_types->get_membership_type();
			
            if ($this->is_empty_string($payment_type) OR $this->is_empty_string($membership_type))
            {
		return "NULL payment_type & membership_type";
            }
													
            if ($this->psb_query->transact_id_exists() OR $this->psb_query->wp_user_id_exists())
            {
		return "This was NOT a successful transaction due to duplicate txn_id/user_id";
            }
										
            // register current transaction
            $trans_reg_result = $this->psb_query->register_transaction();
            // register current member
            $subscr_reg_result = $this->psb_query->register_member();
											
            if (!$trans_reg_result OR !$subscr_reg_result)
            {
                return "This was NOT a successful transaction due to database query issues.";
            }
			
            //set the user's role to $membership_type. e.g. gold
            $this->wp_user_obj->set_role($membership_type);
            return "This was a successful transaction";
	}
		
	function process_end($subscr_status)
        {
            $this->wp_user_obj->set_role( 'subscriber' );

            if ($this->psb_query->update_status($subscr_status) AND $this->psb_query->log_end())
            {
                return "Subscription has (been) $subscr_status.";
            }
            else
            {
                return "Subscription has (been) $subscr_status but failed to update and log.";
            }
	}
		
	function is_empty_string($s)
        {
            // check if string empty
            if (!isset($s) OR trim($s) === '')
            {
                return true;
            }
	}
    }
}

if (class_exists('psb_PSB'))
{
    $psb = new psb_PSB();

    if (isset($psb))
    {
        /**
         * Initiate plugin activation
         */
        
        //Installs custom database tables.
	register_activation_hook(__FILE__,'psb_install');
	//Uninstalls custom database tables.
	register_deactivation_hook(__FILE__, 'psb_uninstall');
	//load listener into wordpress
	add_action('wp_footer', array(&$psb, 'listener'));
    }
}

if (class_exists('psb_Cron'))
{
    $psb_cron = new psb_Cron();

    if (isset($psb_cron))
    {
        add_action('psb_cron_event', array(&$psb_cron, 'update_member_status'));
        add_action('wp', array(&$psb_cron, 'reg_cron_event'));
    }
}