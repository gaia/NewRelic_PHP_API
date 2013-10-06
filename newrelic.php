<?php

###
# NewRelic PHP API central file
# Theme URI: http://MATTERmedia.com
# Description: Allows PHP installs using mod-fgcid to set newrelic_set_appname
# Usage: Inside PHP.ini for each vhost in your server,
# point to this script using: auto_prepend_file = "newrelic.php"
# Where you place the script depends on your include_path setting.
# See http://www.php.net/manual/en/ini.core.php#ini.auto-prepend-file
# Author: Eduardo Barcellos
# Version: 0.6
#
# Copyright (c) 2013 Eduardo Barcellos.  All rights reserved.
# http://MATTERmedia.com
#
# This script is released under the GNU General Public License, version 2 (GPL).
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
###

	if (extension_loaded('newrelic')) {
		if (!isset($_SERVER['HTTP_HOST'])) {
			newrelic_set_appname ("NewRelic.php");
			} else {
				# If UseCanonicalName is set to Off, Apache will use (user inputted) HTTP_HOST for SERVER_NAME
				# Best is to rely on HTTP_HOST and validate it against a list of allowed hosts.
				# See http://shiflett.org/blog/2006/mar/server-name-versus-http-host
				$host = strtolower($_SERVER['HTTP_HOST']);
				$path = strtolower($_SERVER['PHP_SELF']);
				# Easily disable any vhost from sending data to newrelic.
				$disabled_hosts = array('foo.example.com');
				# Add a secondary AppName
				$secondary_appname = ';All Virtual Hosts';			
				if (valid_path($path) && (valid_host($host)) && (!in_array($host, $disabled_hosts))) {
					  # You need to strip :80 from requests that unecessarily use it, such as the Baidu Spider,
						# otherwise you get two names for the same app (one with :80 and the other without).
						# See http://stackoverflow.com/questions/19123633/does-apache-pass-to-php-whether-the-port-80-was-explicitly-requested/
						if (endsWith ($host, ':80'))
						{
							$host = substr($host, 0, -3);
						}
						if (beginsWith ($host, 'www.'))
						{
							$host = substr($host, 4);
						}	
						newrelic_set_appname($host.$secondary_appname);
					} else {
						newrelic_ignore_transaction();
						# technically you wouldn't need to disable_autorum when you ignore_transaction, but it's good practice.
						newrelic_disable_autorum();
				} 			
			}
	}

####
# Host validation is needed to keep remote clients sending rogue requests
# (such as probing for proxies) from creating an application in your newrelic dashboard.
# The match is done via a function (instead of in_array) so that you don't need to
# spell out every variation of FQDN your server is designed to accept.
# E.g. mydomain.com and mydomain.eu are accepted as long as
# you place 'mydomain.' in the array.
####

function valid_host($host) {
	# If host header ends in "." do not track it, it will get redirected by Apache
	# to the proper host header (sans ".") and be tracked when it comes back the 2nd time.
	if (endsWith ($host, '.'))
	{
		return false;
	}
	$allowed_hosts = array('example1.', 'example2.', 'example3.', 'example4.');
	foreach($allowed_hosts as $allowed_host)
	{
	  if(strpos($host,$allowed_host) !== false)
	  {
	    return true;
	  }
	}
	return false;
}

function valid_path($path)	{
	$disallowed_paths = array('/wp-cron.php', '/wp-admin/', '/wp-login.php', '/cron.php', '/cleanup.php', '/index.php/admin');
	foreach($disallowed_paths as $disallowed_path)
	{
		if(beginsWith($path, $disallowed_path))
	  {
	    return false;
	  }
	}
	return true;
}

function beginsWith( $haystack, $needle ) {
	return $needle === "" || strpos($haystack, $needle) === 0;
}

function endsWith( $haystack, $needle ) {
	return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}

?>