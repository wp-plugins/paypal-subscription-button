<?php
/* 
 * This class is ran by cron.
 * It checks the status of the sbuscriber whether or not his subscription has expired.
 */

if (!class_exists('psb_Check_Status'))
{
    class psb_Check_Status
    {
        var $psb_query;
        var $result;

        function __construct()
        {
            if (class_exists('psb_Query'))
            {
                $this->psb_query = new psb_Query();
                $this->result = $this->psb_query->get_active_users();
            }

            if (class_exists('psb_IPN'))
            {

            }
        }
    }
}
