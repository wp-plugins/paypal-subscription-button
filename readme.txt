=== Paypal Subscription Button ===
Contributors: adred
Tags:  subscription plugin, membership plugin, paypal plugin, paypal, subscription, membership, subscription button, paypal button, recurring payment, paypal subscription
Requires at least: 3.0
Tested up to: 3.1.1
Stable tag: 1.2.1

== Description ==

Integrates Paypal Subscription and Buy Now button into Wordpress. This plugin is primarily for membership sites.

I created this plugin because I couldn't find one that integrates well and FREE. So far, all of the paypal plugins I tested are broken. :-) I hope this one will finally end the agony of the people who seek the same functionality as me.

Appeal: If it works click the "Works" button, if it does not work click the "Broken" button and send me an error report. That is the only way to find bugs and see if the plugin works properly. Thanks.

<em>Feature list</em>

* Supports multiple membership levels
* Supports one time payment using Buy Now button
* Allows custom number of days for one time payment using Buy Now button
* Uses Wordpress cron to automatically check members status who are subscribed to one time payment
* Intuitive and clean backend for configuration
* Supports sandbox/testing mode
* Allows sending all IPNs to your email address to monitor every transaction

== Installation ==

Note: This plugin requires User Access Manager(UAM) plugin to work.

1. Download and extract into wp-content/plugin directory.
1. Download User Access Manager Plugin and install.
1. Create custom roles. There are plugins out there for this or you can do it manually( visit <http://adred.tumblr.com/psb> for details ).
1. Create user group names or membership levels and assign them to your custom roles. This is handled via GUI using user access manager.
1. Activate psb plugin and fill out the settings form.
1. Login to paypal and setup your IPN. Make sure to use the link to the page which the plugin has generated as the IPN url( visit <http://adred.tumblr.com/psb> for details ).
1. Create a subscription button, hosted or not hosted.
1. Embed the code anywhere in your theme as long as it's inside the loop.
1. Insert a hidden input with the current_user_id as the value. Go to <http:adred.tumblr.com/psb> for details.
1. That's all it! When you create a post or page, just assign it to a membership level you created using user access manager.

== Changelog ==

Here you found the changes in each version.

    Version		Date      	Changes
    
    1.2.1       2011-4-27   Fix: Check first if value returned from query for due users is array or not.
                            Fix: Change add_option to update_option for dynamic version update during installation.
    1.2.0       2011-4-11   Add support for buy now button
                            Add support for wordpress cron.
                            Fix: Disable deletion of custom tables after uninstall.

    1.0.2		2011-3-19	Fix: No styling in the admin interface

    1.0.1       2011-2-10	First release.

== Credits ==

== License ==
   Copyright (C) 2010-2011 Redeye Joba Adaya, <http://adred.tumblr.com>

   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 3 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>.

== Frequently Asked Questions ==
1. Where do I ask for support?
   Go to <http://adred.tumblr.com/psb> and post a comment there.

== Screenshots ==
1. Plugin Dashboard. Bigger: <http://dl.dropbox.com/u/14483154/screenshot-1.jpg>

