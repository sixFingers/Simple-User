<?php
/**
 * Plugin Name: Simple User
 * Plugin URI: http://www.bedroom-coder.com/
 * Description: The simple user management tool you need. Sets new frontend login, logout, register and recover pages plus a bonus profile page. Implements a simple Facebook connect feature.
 * Version: 1.0.6
 * Author: Ignazio Setti
 * Author URI: http://www.bedroom-coder.com/
 */

if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    exit('Please don\'t access this file directly.');
}

include_once plugin_dir_path(__FILE__).'/functions.php';
include_once plugin_dir_path(__FILE__).'/auth-page.php';
include_once plugin_dir_path(__FILE__).'/profile-page.php';

class wpSimpleUser
{
	static $instance = false;
	var $login_url = 'login';
	var $profile_url = 'profile';
	var $wpSimpleAuth;
	var $wpSimpleProfile;
	
	function __construct()
	{
		if(is_admin()) {
			$this->wpSimpleProfile = new wpSimpleProfile($this->login_url, $this->profile_url);
		}
		add_action('parse_request',  array($this, 'parse_request'), 0, 1);
	}
	
	function getInstance() {
		if ( !self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	function parse_request() {
		global $wp;
		
		// Close direct access to wp-login and wp-register
		if (in_array($GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php'))) {
			wp_redirect($this->login_url);
			exit;
		}
		
		// Check if we are on the profile page
		if($wp->query_vars['pagename']==$this->login_url) {
			$this->wpSimpleAuth = new wpSimpleAuth($this->login_url, $this->profile_url);
		}
		if($wp->query_vars['pagename']==$this->profile_url) {
			// If we are, check if we are logged in
			$current_user = wp_get_current_user();
			if (0 == $current_user->ID) {
				wp_redirect(site_url($this->login_url));
			} else {
				$this->wpSimpleProfile = new wpSimpleProfile($this->login_url, $this->profile_url, $current_user->ID );
			}
		}
	}	
}


function wpSimpleUserSetup() {
	$wpSimpleUser = wpSimpleUser::getInstance();
}

add_action('plugins_loaded', 'wpSimpleUserSetup');