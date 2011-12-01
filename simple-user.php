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
	public static $login_url = 'login';
	public static $login_redirect = 'profile';
	public static $logout_url = 'logout';
	public static $profile_url = 'profile';
	var $wpSimpleAuth;
	var $wpSimpleProfile;
	
	function __construct()
	{
		add_action('parse_request',  array($this, 'parse_request'), 0, 1);
		add_filter('login_url', array($this, 'login_url'));
		add_filter('logout_url', array($this, 'logout_url'));
		add_filter('register', array($this, 'register_url'));
		add_filter('lostpassword_url', array($this, 'lostpassword_url'));
		add_action( 'admin_init', array($this, 'restrict_admin'));
	}
	
	function parse_request() {
		global $wp;
		// Check if we are on the profile page
		$current_user = wp_get_current_user();
		if($wp->query_vars['pagename']===self::$login_url) {
			if (0 == $current_user->ID) {
				
			} else {
				wp_redirect(self::$profile_url);
			}
		}
		
		if($wp->query_vars['pagename']===self::$profile_url) {
			// If we are, check if we are logged in
			
			if (0 == $current_user->ID) {
				wp_redirect(self::$login_url);
			}
		}
	}

	function login_form_bottom()
	{
		$registerLink = wp_register('', '', false);
		$lostpassLink = '<a href="'.wp_lostpassword_url().'" title="Recupero password<">Recupero password</a>';
		return $registerLink.' | '.$lostpassLink;
	}

	function login_url($url)
	{
		$url = site_url(self::$login_url);
		return $url;
	}
	
	function logout_url($url)
	{
		$url = site_url(self::$login_url.'?action=logout', 'login');
		return $url;
	}

	function register_url($url)
	{
		$url = site_url(self::$login_url.'?action=register', 'login');
		return $url;
	}

	function lostpassword_url($url)
	{
		$url = site_url(self::$login_url.'?action=lostpassword', 'login');
		return $url;
	}

	function restrict_admin(){
		global $current_user;
		get_currentuserinfo();
		
		//if not admin, die with message
		if ( $current_user->user_level <  8 ) {
			wp_redirect(site_url(self::$profile_url));
			//wp_die( __('You are not allowed to access this part of the site') );
		}
	}
}


function wpSimpleUserSetup() {
	$current_user = wp_get_current_user();
	$wpSimpleUser = new wpSimpleUser();
	$wpSimpleAuth = new wpSimpleAuth();
	$wpSimpleProfile = new wpSimpleProfile($current_user->ID);
}

add_action('plugins_loaded', 'wpSimpleUserSetup');

