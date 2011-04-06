<?php
/* 
 * This class is ran by cron.
 * It checks the status of the sbuscriber whether or not his subscription has expired.
 */

if (!class_exists('psb_Cron'))
{
    class psb_Cron
    {
        var $psb_query;
        var $result;
        var $psb_ipn;

        function __construct()
        {
            if (class_exists('psb_Query'))
            {
                $this->psb_query = new psb_Query();
            }

            if (class_exists('psb_IPN'))
            {
                $this->psb_ipn = new psb_IPN();
            }
        }

        function reg_cron_event()
        {
            if (!wp_next_scheduled('psb_twicedaily_event'))
            {
		wp_schedule_event(time(), 'twicedaily', 'psb_twicedaily_event');
            }
        }

        function check_member_status()
        {
            //$this->psb_ipn->temp_email('Yay! Cron is working!');
            $this->result = $this->psb_query->get_active_users();
        }
    }
}
