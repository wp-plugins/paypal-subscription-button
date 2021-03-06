<?php
/**
 * This class displays the admin interface.
 */

if (!class_exists('psb_Settings'))
{

    class psb_Settings
    {
    	var $post_vars;
	var $option_values;
	var $update;
        var $custom_roles;
	
	function __construct($post_vars)
        {
            $this->post_vars = $post_vars;
	}
		
	function psb_admin_init()
        {
            //Registers options page stylesheet
            wp_register_style('psb_stylesheet', WP_PLUGIN_URL . '/paypal-subscription-button/css/settings.css');
	}
			
	function create_menu()
        {
            //creates new top-level menu
            $page = add_menu_page('PSB', 'PSB', 'administrator', __FILE__, array(&$this, 'print_settings'), plugins_url('/images/icon.png', dirname(__FILE__)));
			
            //Using registered $page handle to hook stylesheet loading
            add_action('admin_print_styles-' . $page, array(&$this, 'psb_admin_styles'));
			
            //Triggers instantiation of psb_Options class
            $this->init_options();
	}
		
	function psb_admin_styles()
        {
            //It will be called only on your plugin admin page, enqueue our stylesheet here
            wp_enqueue_style('psb_stylesheet');
	}
		
	function init_options()
        {
            if (class_exists('psb_Options'))
            {
                //only instantiates psb_Options when form is submitted
		if ($this->post_vars['update'] == true)
                {
                    //instantiates psb_Options class and pass post_vars to it
                    $psb_options = new psb_Options($this->post_vars);
                    $this->update = true;
                }
            }
			
            if (isset($psb_options))
            {
                //updates options and retrieves the psb_admin_options array
		//only do that when form is submitted
		$this->option_values = $psb_options->get_psb_options();
            } 
            else
            {
                //gets psb_admin_options array from the the db directly when coming from other admin menus-- no form is submitted.
		$this->option_values = get_option('psb_admin_options');
            }
            
            $this->custom_roles = get_option('psb_custom_roles');
	}
	
	function print_settings()
        { ?>
            <div class="wrap">
                <div id="icon-options-general" class="icon32"></div>
                <h2>Paypal Subscription Button</h2>
                <?php

                if ($this->update)
                { ?>
                    <div id="notify-update"><span>Settings have been succesfully saved...</span></div> <?php
                } ?>
                
                <form id="custom-roles-table" method="post" action="">
                    
                    <input type="hidden" name="deletecustomroles"  value="true" />
                    <input type="hidden" name="update"  value="true" />
                    
                    <span class="cr-title">Custom Roles</span>
                    <table id="cr-table">
                        <tr id="cr-heading">
                            <td class="cr-blank-col"></td>
                            <td class="cr-name-col">Name</td>
                            <td class="cr-desc-col">Description</td>
                            <td class="cr-status-col">Status</td>
                        </tr>
                        
                        <?php 
                        if (is_array($this->custom_roles))
                        {
                            foreach ($this->custom_roles as $role => $value) 
                            { ?>
                                <tr>
                                <td class="cr-blank-col"><input type="checkbox" name="<?php echo $role; ?>" value="1" /></td>
                                <td><?php echo ucfirst($role); ?></td>
                                <td><?php echo $value['desc']; ?></td>
                                <?php
                                $role = strtolower($role);
                                $selected_custom_roles = $this->option_values['selected_custom_roles'];
                                $match = 0;
                                foreach ($selected_custom_roles as $selected_custom_role)
                                {
                                    if ($role == strtolower($selected_custom_role))
                                    { 
                                        $match = 1; ?>
                                        <td>Active</td> <?php
                                        continue;
                                    }
                                } 
                                if ($match == 0)
                                { ?>
                                    <td>Inctive</td> <?php
                                } ?>
                            </tr> <?php
                            } 
                        }?> 
                    </table>
                    <span><input type="submit" class="button-primary" value="<?php _e('Delete Role(s)') ?>" /></span>                
                </form>
                    
                <form id="add-custom-roles" method="post" action="">
                    <input type="hidden" name="addcustomrole"  value="true" />
                    <input type="hidden" name="update"  value="true" />
                    
                    <span class="cr-title">Add Custom Role</span>
                    <ul class="cr-ul">
                        <li><label>Role name: </label>
                            <input id="role-name" maxlength="250" size="60" name="role_name" value="" />
                        </li>
                        <li><label>Role description: </label>
                            <input id="role-desc" maxlength="250" size="120" name="role_desc" value="" />
                        </li>
                        <li><span>Privilege(s):</span></li>
                        <ul class="cr-ul">
                            <li><span class="subtitle privileges-title">Choose if a user can read, edit, and delete posts.</span></li>
                            <ul class="cr-ul privileges">
                                <li><input type="checkbox" name="canread" value="1" disabled="disabled" checked="checked"/><span>Read</span></li>
                                <li><input type="checkbox" name="canedit" value="1"  /><span>Edit</span></li>
                                <li><input type="checkbox" name="candelete" value="1" /><span>Delete</span></li>
                                <p class="submit">
                                    <input type="submit" class="button-primary" value="<?php _e('Add Role') ?>" />
                                </p>
                            </ul>
                        </ul>
                    </ul>
                </form> 
                    
                <form id="psb-options" method="post" action="">
                    <input type="hidden" name="updatepsboptions"  value="true" />
                    <input type="hidden" name="update"  value="true" />
                    <input type="hidden" name="autoset_ipn_page_ID"  value="<?php echo $this->option_values['autoset_ipn_page_ID']; ?>" />

                    <ul class="input-align subtitle-margin">
                        <li><span class="title">Do you want PSB to go live?</span>
                            <span class="subtitle">Choose either live "paypal.com" or test mode "sandbox.paypal.com".</span>
                            <input name="live" type="radio" value="0" <?php checked('0', $this->option_values['live']); ?> />
                            <label> Paypal sandbox </label>
                            <input name="live" type="radio" value="1" <?php checked('1', $this->option_values['live']); ?> />
                            <label> Paypal live </label>
                        </li>
                    </ul>

                    <ul class="input-align subtitle-margin">
                        <li><span class="title">Paypal merchant email address </span>
                            <span class="subtitle">Enter the merchant email you used for setting up the subscription button.</span>
                            <input id="merchant_email" maxlength="250" size="60" name="merchant_email" value="<?php echo $this->option_values['merchant_email']; ?>" />
                        </li>
                        <li><span class="title">Transaction notification email address </span>
                            <span class="subtitle">Enter the email address that will be sent with transaction notifications from your site.</span>
                            <input id="notify_email" maxlength="250" size="60" name="notify_email" value="<?php echo $this->option_values['notify_email']; ?>" />
                        </li>
                        <li><span class="title">Payment currency</span>
                            <span class="subtitle">Enter your currency of preference for the payment.</span>
                            <input id="currency" maxlength="150" size="40" name="currency" value="<?php echo $this->option_values['currency']; ?>" />
                        </li>
                    </ul>

                    <ul class="input-align subtitle-margin">
                        <li><span class="title">Instant Paypal Notification(IPN) page</span>
                            <span class="subtitle">Choose to either automatically or manually setup the page. </span>
                            <input name="autoset_ipn_page" type="radio" value="1" <?php checked('1', $this->option_values['autoset_ipn_page']); ?> />
                            <label> Automatically setup the page (Recommended) </label>
                            <input name="autoset_ipn_page" type="radio" value="0" <?php checked('0', $this->option_values['autoset_ipn_page']); ?> />
                            <label> Manually set the page </label>
                            <?php if ($this->option_values['autoset_ipn_page'] == 0)
                                  { ?>
                                        <span class="subtitle">Pls enter the ID of the page that will be sent with notifications from Paypal.</span>
                                        <input id="manual_ipn_page_ID" maxlength="150" size="40" name="manual_ipn_page_ID"
                                               value="<?php echo $this->option_values['manual_ipn_page_ID']; ?>" />
                            <?php } ?>
                        </li>
                    </ul>

                    <ul class="m-types input-align subtitle-margin">
                        <li><span class="title">Membership types</span>
                            <span class="subtitle">Below are the custom roles found in your installation. Tick the ones that will be used.</span>
                            <?php $m_types = $this->option_values['custom_roles'];
                                  if (is_array($m_types) AND !empty($m_types))
                                  {
                                     foreach ($m_types AS $key => $value)
                                     { ?>
                                        <input type="checkbox" name="<?php echo $key; ?>" value="1" <?php checked('1', $value); ?> />
                                        <label> <?php echo $key; ?> </label> <?php
                                     }
                                  } 
                                  else
                                  { ?>
                                      <span><i>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;You must create custom roles first...</i></span> <?php
                                  }?>
                        </li>
                    </ul>

                    <ul class="p-options subtitle-margin cell-align"><?php
                        if (in_array(1, $m_types))
                        { ?>
                            <li><span class="title">Payment options</span>
                                <span class="subtitle">Select the payment option(s) for each membership type.</span><?php
                                $selected_custom_roles = $this->option_values['selected_custom_roles'];
                                $p_types = $this->option_values['payment_types'];
                                $p_amounts = $this->option_values['payment_amounts'];
                                if (is_array($selected_custom_roles))
                                { ?>
                                    <table> <?php
                                        foreach ($selected_custom_roles AS $selected_custom_role)
                                        { ?>
                                            <tr>
                                                <td><span class="p-options-title"> <?php echo ucfirst($selected_custom_role); ?> </span></td><?php
                                                foreach ($p_types AS $p_name => $p_value)
                                                {
                                                    switch ($p_name)
                                                    {
                                                        case 'weekly';
                                                            $name = $p_name . "_" . $selected_custom_role; ?>
                                                                <td><input type="checkbox"
                                                                           name="<?php echo $name; ?>"
                                                                           value="1" <?php checked('1', $p_types[$p_name][$name]); ?> />
                                                                    <label> Weekly </label>
                                                                </td><?php
                                                                break;
                                                            case 'monthly';
                                                                $name = $p_name . "_" . $selected_custom_role; ?>
                                                                <td><input type="checkbox"
                                                                           name="<?php echo $name; ?>"
                                                                           value="1" <?php checked('1', $p_types[$p_name][$name]); ?> />
                                                                     <label> Monthly </label>
                                                                </td><?php
                                                                break;
                                                            case 'yearly';
                                                                $name = $p_name . "_" . $selected_custom_role; ?>
                                                                <td><input type="checkbox"
                                                                           name="<?php echo $name; ?>"
                                                                           value="1" <?php checked('1', $p_types[$p_name][$name]); ?> />
                                                                     <label> Yearly </label>
                                                                </td><?php
                                                                break;
                                                            case 'one-month';
                                                                $name = $p_name . "_" . $selected_custom_role; ?>
                                                                <td><input type="checkbox"
                                                                           name="<?php echo $name; ?>"
                                                                           value="1" <?php checked('1', $p_types[$p_name][$name]); ?> />
                                                                     <label> 1 Month </label>
                                                                </td><?php
                                                                break;
                                                            case 'one-year';
                                                                $name = $p_name . "_" . $selected_custom_role; ?>
                                                                <td><input type="checkbox"
                                                                           name="<?php echo $name; ?>"
                                                                           value="1" <?php checked('1', $p_types[$p_name][$name]); ?> />
                                                                     <label> 1 Year </label>
                                                                </td><?php
                                                                break;
                                                            case 'x-days';
                                                                $name = $p_name . "_" . $selected_custom_role; ?>
                                                                <td><input class="x-days"
                                                                        maxlength="10"
                                                                        size="6"
                                                                        name="<?php echo $name; ?>"
                                                                        value="<?php echo $p_types[$p_name][$name]; ?>" />
                                                                    <label> days </label>
                                                                <!--
                                                                <td><input type="checkbox"
                                                                           name="<?php echo $name; ?>"
                                                                           value="1" <?php checked('1', $p_types[$p_name][$name]); ?> />
                                                                     <label> X Days </label>
                                                                -->
                                                                </td><?php
                                                                break;
                                                    }
                                                } ?>
                                            </tr> <?php
                                        } ?>
                                    </table> <?php
                                } ?>
                            </li> <?php
                        } ?>
                    </ul>

                    <ul class="p-amounts subtitle-margin cell-align"><?php
                        if (in_array(1, $m_types))
                        { ?>
                            <li><span class="title">Payment amounts</span>
                                <span class="subtitle">Enter payment amount(s) for each membership type.</span><?php
                                if (is_array($selected_custom_roles) AND is_array($p_amounts))
                                { ?>
                                    <table>
                                        <tr id="tr-heading">
                                            <td></td>
                                            <td>Weekly</td>
                                            <td>Monthly</td>
                                            <td>Yearly</td>
                                            <td>1 Month</td>
                                            <td>1 Year</td>
                                            <td>Days</td>
                                        </tr><?php
                                        foreach ($selected_custom_roles AS $selected_custom_role)
                                        { ?>
                                            <tr id="tr-fields">
                                                <td><span class="p-options-title"> <?php echo ucfirst($selected_custom_role); ?></span></td> <?php
                                                    foreach ($p_types AS $p_name => $p_value)
                                                    {
                                                        switch ($p_name)
                                                        {
                                                            case 'weekly';
                                                                $name = $p_name . "_" . $selected_custom_role; ?>
                                                                <td><input id="<?php echo 'a_'.$name; ?>" maxlength="45" size="15"
                                                                           name="<?php echo 'a_'.$name; ?>"
                                                                           <?php echo ('0' == $p_types[$p_name][$name]) ? 'disabled ': ''; ?>
                                                                           value="<?php echo $p_amounts['a_'.$name]; ?>" />
                                                                </td> <?php
                                                                break;
                                                            case 'monthly';
                                                                $name = $p_name . "_" . $selected_custom_role; ?>
                                                                <td><input id="<?php echo 'a_'.$name; ?>" maxlength="45" size="15"
                                                                           name="<?php echo 'a_'.$name; ?>"
                                                                           <?php echo ('0' == $p_types[$p_name][$name]) ? 'disabled ': ''; ?>
                                                                           value="<?php echo $p_amounts['a_'.$name]; ?>" />
                                                                </td> <?php
                                                                break;
                                                            case 'yearly';
                                                                $name = $p_name . "_" . $selected_custom_role; ?>
                                                                <td><input id="<?php echo 'a_'.$name; ?>" maxlength="45" size="15"
                                                                           name="<?php echo 'a_'.$name; ?>"
                                                                           <?php echo ('0' == $p_types[$p_name][$name]) ? 'disabled ': ''; ?>
                                                                           value="<?php echo $p_amounts['a_'.$name]; ?>" />
                                                                </td> <?php
                                                                break;
                                                            case 'x-days';
                                                                $name = $p_name . "_" . $selected_custom_role; ?>
                                                                <td><input id="<?php echo 'a_'.$name; ?>" maxlength="45" size="15"
                                                                           name="<?php echo 'a_'.$name; ?>"
                                                                           <?php echo ('0' == $p_types[$p_name][$name]) ? 'disabled ': ''; ?>
                                                                           value="<?php echo $p_amounts['a_'.$name]; ?>" />
                                                                </td> <?php
                                                                break;
                                                            case 'one-month';
                                                                $name = $p_name . "_" . $selected_custom_role; ?>
                                                                <td><input id="<?php echo 'a_'.$name; ?>" maxlength="45" size="15"
                                                                           name="<?php echo 'a_'.$name; ?>"
                                                                           <?php echo ('0' == $p_types[$p_name][$name]) ? 'disabled ': ''; ?>
                                                                           value="<?php echo $p_amounts['a_'.$name]; ?>" />
                                                                </td> <?php
                                                                break;
                                                            case 'one-year';
                                                                $name = $p_name . "_" . $selected_custom_role; ?>
                                                                <td><input id="<?php echo 'a_'.$name; ?>" maxlength="45" size="15"
                                                                           name="<?php echo 'a_'.$name; ?>"
                                                                           <?php echo ('0' == $p_types[$p_name][$name]) ? 'disabled ': ''; ?>
                                                                           value="<?php echo $p_amounts['a_'.$name]; ?>" />
                                                                </td> <?php
                                                                break;
                                                        }
                                                    } ?>
                                            </tr> <?php
                                        } ?>
                                    </table> <?php
                                } ?>
                            </li> <?php
                            } ?>
                    </ul>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
                    </p>
                </form>
            </div> <!-- End of wrap --> <?php
        }
    }
}

if (class_exists('psb_Settings'))
{
    //instantiates this class
    $psb_settings = new psb_Settings($_POST);
	
    if (isset($psb_settings))
    {
	//loads settings page css script
	add_action('admin_init', array(&$psb_settings, 'psb_admin_init'));
	//initializes display of settings page
	add_action('admin_menu', array(&$psb_settings, 'create_menu'));
    }
}
