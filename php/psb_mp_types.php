<?php
/**
 * This class determines the payment and membership type of the user.
 */

if(!class_exists('psb_MP_Types'))
{

    class psb_MP_Types
    {
        var $payment_amount;
        var $payment_type;
	var $membership_type;

	function __construct($payment_amount)
        {
            $this->payment_amount = $payment_amount;
            $psb_options = get_option('psb_admin_options');
            $mp_type = array();

            foreach ($psb_options['payment_amounts'] AS $key => $value)
            {
                //Determines the payment type and membership type of the current transaction
		if ($this->is_match_amount($value))
                {
                    $mp_type = $this->get_mp_type(explode('_', $key));
                    $this->payment_type = $mp_type[0];
                    $this->membership_type = $mp_type[1];
		}
            }
	}

	function get_mp_type($index)
        {
            //Forms an array out of index var a_monthly_silver.
            //The result should be something like: array('monthly', 'silver')

            $mp_type = array();

            foreach ($index AS $type)
            {
                if ($type !== 'a')
                {
                    $mp_type[] = $type;
                }
            }

            return $mp_type;
	}

	function is_match_amount($amount)
        {
            //Compares the amount from paypal to the ones from settings if there's a match
            //If so, it returns true
            if($amount == $this->payment_amount)
            {
                return true;
            }
            else
            {
                false;
            }
	}

	function get_membership_type()
        {
            //Returns the membership type: silver
            return $this->membership_type;
	}

	function get_payment_type()
        {
            //Returns the payment type: monthly
            return $this->payment_type;
	}
    }
}