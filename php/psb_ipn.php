<?php
/**
 * This class handles the communication between the plugin and paypal.
 * It also provides the mail function.
 */

if (!class_exists("psb_IPN") )
{
	
    class psb_IPN
    {
        var $post_vars;
	var $res;
	var $send_time;
	var $admin_options;
		
	function __construct($post_vars = '', $admin_options = '')
        {
            $this->post_vars = $post_vars;
            $this->admin_options = $admin_options;
            $this->timeout = 120;
	}
		
	function postback()
        {
            $fp = @fsockopen($this->get_paypal_url(), 80, &$errno, &$errstr, 120);
            if (!$fp)
            {
                // HTTP ERROR
                $this->notify("PHP fsockopen() error: " . $errstr);
            }
            else
            {
                // read the post from PayPal system and add 'cmd'
		$req = 'cmd=_notify-validate';
				
		// prepare post_var for postback
		foreach ($_POST AS $key => $value)
                {
                    if (get_magic_quotes_gpc())
                    {
			$value = urlencode(stripslashes($value));
                    }
                    else
                    {
			$value = urlencode($value);
                    }
                        $req .= "&$key=$value";
                }
				
		// post back to PayPal system to validate
		$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
		fputs ($fp, $header . $req);
				
		$this->send_time = time();
				
		// get response from paypal
		while (!feof ($fp))
                {
                    $this->res .= fgets ($fp, 1024);
                    if ($this->send_time < time() - $this->timeout)
                    {
                        $this->notify("Timed out waiting for a response from PayPal. ($this->timeout seconds)" , "");
                    }
		}
		
                fclose($fp);
            }

            return;
	}
		
	function is_verified() 
        {
            //determine if post_vars are verified
            if (ereg("VERIFIED", $this->res))
            {
                return true;
            }
            else
            {
                return false;
            }
        }

	function notify($body_title)
        {
            // send notification to notification email address

            $from = $_SERVER['SERVER_NAME'] . "\n";
            $send_to = $this->admin_options['notify_email'];
            $reply_to = 'none';
            $subject = 'Paypal IPN';
            $message = "\n\nThe following data was received from PayPal:\n\n";
            foreach ($this->post_vars AS $key => $value)
            {
                $message .= $key . ':' . " \t$value\n";
            }

            // email header
            $em_headers  = "From: $from";
            $em_headers .= "Reply-To: $reply_to \n";
            $em_headers .= "X-Priority: 3\n";
			
            mail($send_to, $subject, $body_title . $message, $em_headers);

            return;
	}

        function temp_email($message)
        {
            mail('joannmarfe.delacruz@gmail.com', 'Cron Testing', $message, '');
        }

	function get_paypal_url() 
        {
            // get the paypal url set from the options page
            //Sandbox or live
            
            if ($this->admin_options['live'])
            {
                $url = "www.paypal.com";
            }
            else
            {
		$url = "www.sandbox.paypal.com";
            }
			
            return $url;
	}
    }
}