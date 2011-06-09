<?php
/**
 * This class processes and manages the settings set from the admin interface.
 */

if(!class_exists('psb_Options'))
{
	
    class psb_Options
    {
        var $admin_options_name = "psb_admin_options";
	var $default_roles = array('Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber');
	var $post_vars;
		
	function __construct($post_vars = '')
        {
            $this->post_vars = $post_vars;
	}
		
	function get_psb_options()
        {
             //Default options
             $concat_admin_options_name = array(
                                            'live' => 0,
                  			    'currency' => '',
			                    'merchant_email' => '',
                                            'notify_email' => '',
                                            'autoset_ipn_page' => 1,
                                            'autoset_ipn_page_ID' => '',
                                            'manual_ipn_page_ID' => '',
                                            'custom_roles' => array(),
                                            'selected_custom_roles' => array(),
                                            'payment_amounts' => array(),
                                            'payment_types' => array(
                                                                'weekly' => array(),
                                                                'monthly' => array(),
                                                                'yearly' => array(),
                                                                'one-month' => array(),
                                                                'one-year' => array(),
                                                                'x-days' => array()
                                                               ));
                         
              //Gets psb_admin_options from the db
              $aon = get_option($this->admin_options_name);
              //Gets and updates the custom roles
              $this->get_custom_roles($concat_admin_options_name);
			 
              if (!empty($aon) AND (!isset($this->post_vars['update']) OR !$this->post_vars['update'] ))
              {
                    //Adds new array elements to the old psb_admin_options. This is to retain the old options during upgrades.
                    //Only enter this block if updatepsboptions post_var is set to true
                    $this->assign_new_values($concat_admin_options_name, $aon);
              }
			 
              if (isset($this->post_vars['update']) AND true == $this->post_vars['update'])
              {
                    //Assigns the post_vars values to array elements in $concat_admin_options_name
                    //This generates new elements for arrays in $concat_admin_options_name in the process
                    $this->assign_new_values($concat_admin_options_name);
                    $this->generate_type_role_option_name($concat_admin_options_name);
                    $this->assign_new_values($concat_admin_options_name);
                    $this->create_notify_page($aon, $concat_admin_options_name);
              }

              //Inserts the final set of options into the wp db
              update_option($this->admin_options_name, $concat_admin_options_name);
              return $concat_admin_options_name;
	}
		
	function assign_new_values(&$concat_admin_options_name, $aon = '')
        {
            if (!empty($this->post_vars))
            {
                $array_source = $this->post_vars;
            }
            else
            {
                $array_source = $aon;
            }

            foreach ($array_source AS $name => $value)
            {
                //Checks if the $name exists as a key in $concat_admin_options_name
		//Assigns the return value to $match_key
		$match_key = $this->multi_array_key_exists($name, $concat_admin_options_name);
		$multi_keys = explode(':', $match_key);
		$multi_keys_length = count($multi_keys);

		if  (0 < $multi_keys_length)
                {
                    //If the key belongs to a multi dimensional array, the parser should enter this block
                    if (2 == $multi_keys_length)
                    {
                        //If the key belongs to a two dimensional array
			$concat_admin_options_name[$multi_keys[0]][$multi_keys[1]] = $value;
                    }
                    else if (3 == $multi_keys_length)
                    {
                        //If the key belongs to a three dimensional array
                        $concat_admin_options_name[$multi_keys[0]][$multi_keys[1]][$multi_keys[2]] = $value;
                    }
                }
		
                if ($name == $match_key)
                {
                    //If the key is a base key
                    $concat_admin_options_name[$match_key] = $value;
		}
            }

            return false;
	}
				
	function multi_array_key_exists($needle, $haystack)
        {
            //Checks if a key exists in the $haystack
            //This function supports three(or more) dimensional array

            foreach ($haystack AS $key => $value)
            {
                if ($needle == $key)
                {
                    return $key;
		}
		
                if (is_array($value))
                {
                    if ($this->multi_array_key_exists($needle, $value))
                    {
                        return $key . ":" . $this->multi_array_key_exists($needle, $value);
                    }
                    else
                    {
                        continue;
                    }
		}
            }

            return false;
	} 
		
	function get_custom_roles(&$concat_admin_options_name)
        {
            $wp_roles = new WP_Roles();
            $roles = $wp_roles->get_names();

            if (is_array($roles))
            {
                //Gets the the roles that are not default-- custom roles
		$custom_roles = array_diff($roles, $this->default_roles);
		foreach ($custom_roles AS $value)
                {
                    //Creates flag vars that determines which custom roles are selected
                    //Assigns 0 as a default value which means it is not selected
                    $concat_admin_options_name['custom_roles'][strtolower($value)] = 0;
		}

                return $custom_roles;
            }
            else
            {
                return false;
            }
	}
		
        function generate_type_role_option_name(&$concat_admin_options_name)
        {
            $custom_roles = $concat_admin_options_name['custom_roles'];
            $payment_types = $concat_admin_options_name['payment_types'];

            if (is_array($custom_roles))
            {
                foreach ($custom_roles AS $role => $role_status)
                {
                    $f_role = strtolower($role);

                    if ($role_status >= 1) // If greater than 1, it means the value comes from x-days fieds which contain number of days.
                    {
                        //If role status is >= 1, it means it is selected
			//Assigns selected custom roles to selected_custom_roles array
			$concat_admin_options_name['selected_custom_roles'][] = $role;
			foreach ($payment_types AS $key => $type_role)
                        {
                            //Creates flag vars that determines which payment type is selected for a particular membership/role type
                            //Example: monthly_silver
                            $concat_admin_options_name['payment_types'][$key][$key.'_'.$f_role] = 0;
                            //Creates vars that hold the amount value for a particular payment type associated with a membership/role type
                            //Example: a_monthly_silver, where "a_" is just a prefix to differentiate the var to those of payment_types array
                            $concat_admin_options_name['payment_amounts']['a_'.$key.'_'.$f_role] = '';
			}
                    }
		}
            }
            else
            {
                return false;
            }
	}
		
	function create_notify_page(&$aon, &$concat_admin_options_name)
        {
            //Only creates page if it doesn't exist
            if (empty($aon['autoset_ipn_page_ID']) AND 1 == $concat_admin_options_name['autoset_ipn_page'])
            {
                //Creates page data
		global $current_user;
		$current_user = wp_get_current_user(); 
		$auto_post = array(
                                'ID' => '',
			        'menu_order' => '',
				'comment_status' => 'closed',
				'ping_status' => 'closed',
				'pinged' => '',
				'post_author' => $current_user->ID,
				'post_category' => '',
				'post_content' => 'WARNING: Do not delete this post! PSB plugin uses this!',
				'post_date' => '',
				'post_date_gmt' => '',
				'post_excerpt' => '',
				'post_name' => '',
				'post_parent' => '',
				'post_password' => '',
				'post_status' => 'publish',
				'post_title' => $this->create_notify_page_title(),
				'post_type' => 'page',
				'tags_input' => '',
				'to_ping' => ''
                            );

                //Creates page and assigns the return value(page ID) to autoset_ipn_page_ID
                $concat_admin_options_name['autoset_ipn_page_ID'] = wp_insert_post($auto_post);
            }
            else
            {
                return false;
            }
	}
		
	function create_notify_page_title()
        {
            //Creates page title for the notification page
            //To Pull 7 Unique Random Values Out Of AlphaNumeric
            //removed number 0, capital o, number 1 and small L
            //Total: keys = 42, elements = 43
            
            $characters = array(
                            'A','B','C','D','E','F','G','H','J','K','L','M',
			    'N','P','Q','R','S','T','U','V','W','X','Y','Z',
			    '1','2','3','4','5','6','7','8','9'
                          );

            //make an "empty container" or array for our keys
            $keys = array();
            
            while (count($keys) < 15)
            {
                $x = mt_rand(0, count($characters)-1);
		if (!in_array($x, $keys))
                {
                    $keys[] = $x;
		}
            }
            foreach ($keys AS $key)
            {
                $random_chars .= $characters[$key];
            }

            return $random_chars;
        }
    }
}