<?php
/**
 * This class processes and manages the settings set from the admin interface.
 */

if(!class_exists('psb_Options'))
{
	
    class psb_Options
    {
        var $wp_settings_handle = 'psb_admin_options';
        var $wp_customroles_handle = 'psb_custom_roles';
        var $wp_lastpostvars_handle = 'psb_last_postvars';
	var $default_roles = array('Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber');
	var $post_vars = array();
        var $settings = array();
		
	function __construct($post_vars = '')
        {
            $this->post_vars = $post_vars;
	}
		
	function get_psb_options()
        {
             //Default options
             $this->settings = array(
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
              $existing = get_option($this->wp_settings_handle);
			 
              if (is_array($existing) AND !$this->post_vars['update'])
              {
                    //Adds new array elements to the old psb_admin_options. This is to retain the old options during upgrades.
                    $this->settings = array_merge($this->settings, $existing);
              }
              else
              {
                    if ($this->post_vars['updatepsboptions'])
                    {
                        // save the last sent $_POST for later use
                        update_option($this->wp_lastpostvars_handle, $this->post_vars);
                    }
                  
                    if ($this->post_vars['addcustomrole'])
                    {
                        $this->add_customrole();
                        $this->post_vars = get_option($this->wp_lastpostvars_handle);
                    }
                    
                    //Gets and updates the custom roles
                    $this->get_custom_roles();
                  
                    //Assigns the post_vars values to array elements in $this->settings
                    //This generates new elements for arrays in $this->settings in the process
                    $this->assign_new_values();
                    $this->generate_type_role_option_name();
                    $this->assign_new_values();
                    $this->create_notify_page();
                    
                    //Inserts the final set of options into the wp db
                    update_option($this->wp_settings_handle, $this->settings);
              }
              
              return $this->settings;
	}
		
	function assign_new_values()
        {
            if (is_array($this->post_vars))
            {
                foreach ($this->post_vars AS $name => $value)
                {
                    //Checks if the $name exists as a key in $this->settings
                    //Assigns the return value to $match_key
                    $match_key = $this->multi_array_key_exists($name, $this->settings);
                    $multi_keys = explode(':', $match_key);
                    $multi_keys_length = count($multi_keys);

                    if  ($multi_keys_length > 0)
                    {
                        //If the key belongs to a multi dimensional array, the parser should enter this block
                        if ($multi_keys_length == 2)
                        {
                            //If the key belongs to a two dimensional array
                            $this->settings[$multi_keys[0]][$multi_keys[1]] = $value;
                        }
                        else if ($multi_keys_length == 3)
                        {
                            //If the key belongs to a three dimensional array
                            $this->settings[$multi_keys[0]][$multi_keys[1]][$multi_keys[2]] = $value;
                        }
                    }

                    if ($name == $match_key)
                    {
                        //If the key is a base key
                        $this->settings[$match_key] = $value;
                    }
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
		
	function get_custom_roles()
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
                    $this->settings['custom_roles'][strtolower($value)] = 0;
		}

                return $custom_roles;
            }
            else
            {
                return false;
            }
	}
		
        function generate_type_role_option_name()
        {
            $custom_roles = $this->settings['custom_roles'];
            $payment_types = $this->settings['payment_types'];

            if (is_array($custom_roles))
            {
                foreach ($custom_roles AS $role => $role_status)
                {
                    $f_role = strtolower($role);

                    if ($role_status >= 1) // If greater than 1, it means the value comes from x-days fieds which contain number of days.
                    {
                        //If role status is >= 1, it means it is selected
			//Assigns selected custom roles to selected_custom_roles array for later use
			$this->settings['selected_custom_roles'][] = $role;
			foreach ($payment_types AS $key => $type_role)
                        {
                            //Creates flag vars that determines which payment type is selected for a particular membership/role type
                            //Example: monthly_silver
                            $this->settings['payment_types'][$key][$key.'_'.$f_role] = 0;
                            //Creates vars that hold the amount value for a particular payment type associated with a membership/role type
                            //Example: a_monthly_silver, where "a_" is just a prefix to differentiate the var to those of payment_types array
                            $this->settings['payment_amounts']['a_'.$key.'_'.$f_role] = '';
			}
                    }
		}
            }
            else
            {
                return false;
            }
	}
		
	function create_notify_page()
        {
            //Only creates page if it doesn't exist
            if (empty($this->settings['autoset_ipn_page_ID']) AND $this->settings['autoset_ipn_page'] == 1)
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
                $this->settings['autoset_ipn_page_ID'] = wp_insert_post($auto_post);
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
        
        function add_customrole()
        {
            /**
             * This function handles the addition of custom roles done by the admin user fromt he 
             * PSB admin interface.
             */
            
            $role_name = $this->post_vars['role_name'];
            $role_desc = $this->post_vars['role_desc'];
            $canread = ($this->post_vars['canread'] == 1) ? true : false;
            $canedit = ($this->post_vars['canedit'] == 1) ? true : false;
            $candelete = ($this->post_vars['candelete'] == 1) ? true : false;
            
            add_role($role_name, 
                     ucfirst($role_name), 
                     array('read' => $canread,
                           'edit_posts' => $canedit, 
                           'delete_posts' => $candelete 
                     )
            );
            
            // Custom roles that are added by the admin from the PSB admin iterface
            $custom_roles = get_option($this->wp_customroles_handle);
            
            $custom_roles[$role_name] = array('desc' => $role_desc,
                                     'capabilities' => array('read' => $canread, 
                                                             'edit' => $canedit, 
                                                             'delete' => $candelete)
                               );
            
            update_option($this->wp_customroles_handle, $custom_roles);
            
            return;
        }
    }
}