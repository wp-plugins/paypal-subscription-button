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
            if (!wp_next_scheduled('psb_cron_event'))
            {
		wp_schedule_event(time(), 'hourly', 'psb_cron_event');
            }
        }

        function update_member_status()
        {
            //Get the wp user id of users who are due.
            $this->result = $this->psb_query->get_due_users();
            
            foreach ($this->result as $value)
            {
                $this->wp_user_obj = new WP_User($value);

                //Deactive user subscription.
                $this->wp_user_obj->set_role('subscriber');
                //Update user status in the db.
                $this->psb_query->update_status('expired', $value);
                //Log the end of subscription.
                $this->psb_query->log_end($value);
            }
        }
    }
}
