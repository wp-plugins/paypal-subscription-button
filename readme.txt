=== Paypal Subscription Button ===
Contributors: adred
Tags:  paypal, subscription, membership, members
Requires at least: 3.0
Tested up to: 3.0.5
Stable tag: trunk

== Description ==

Integrates Paypal subscription button into wordpress.

Features:
Unordered list:
* Supports multiple membership levels.
* Intuitive and clean backend for configuration.
* Allows sending all IPNs to your email address to monitor every transaction.
* Supports hosted/not-hosted button.

== Installation ==

Note: This plugin requires User Access Manager(UAM) plugin to work. 

1. Download and extract into wp-content/plugin directory.
2. Download User Access Manager Plugin and install.
3. Create custom roles. There are plugins out there for this or you can do it manually( http://adred.tumblr.com/psb for details ).
3. Create user group names or membership levels and assign them to your custom roles. This is handled via GUI using user access manager.
2. Activate psb plugin and fill out the settings form.
3. Login to paypal and setup your IPN. Make sure to use the link to the page which the plugin has generated as the IPN url( http://adred.tumblr.com/psb for details ). 
4. Create a subscription button, hosted or not hosted. 
5. Embed the code anywhere in your theme as long as it's inside the loop. 
6. Insert a hidden input with the current_user_id as the value. Got to http:adred.tumblr.com/psb for details.
7. That's all it! When you create a post or page, just assign it to a membership level you created using user access manager.

== Changelog ==

== Credits ==

== Screenshots ==
1. plugin dashboard

== License ==
   Copyright (C) 2010-2011 Redeye Joba Adaya, adred.tumblr.com

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
   Go to adred.tumblr.com/psb and post a comment there.

