<?php if ( ! defined( 'ABSPATH' ) ) exit; 
/*
	Plugin Name: Injection Guard
	Plugin URI: https://www.androidbubbles.com/extends/wordpress/plugins/injection-guard
	Description: Blocks unauthorized and irrelevant query string requests by redirecting them to a safe error page, enhancing security without bloating your site.
	Version: 1.2.8
	Author: Fahad Mahmood
	Author URI: https://www.androidbubbles.com
	Text Domain: injection-guard
	Domain Path: /languages/
	License: GPL2
	
	This WordPress plugin is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 2 of the License, or any later version.
	
	This free software is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this software. If not, see https://www.gnu.org/licenses/gpl-2.0.html
*/


	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	include_once('functions.php');


	global $ig_logs;
	global $ig_blacklisted;
    global $ig_rs, $ig_pro, $ig_pro_link, $ig_dir, $ig_url, $ig_data, $ig_title, $ig_title_v;	
	
	
	$ig_rs = array();  
	$ig_pro_link = 'https://shop.androidbubbles.com/product/injection-guard';    
	$ig_rs[] = '<a target="_blank" href="plugin-install.php?tab=search&s=wp+mechanic&plugin-search-input=Search+Plugins">Install WP Mechanic</a>';
	$ig_rs[] = '<a target="_blank" href="http://androidbubble.com/blog/contact">Contact Developer</a>';
	

	$ig_dir = plugin_dir_path( __FILE__ );
    $ig_url = plugin_dir_url( __FILE__ );
	$ig_data = get_plugin_data(__FILE__, true, false);

	
	$ig_pro_file = realpath($ig_dir.'pro/functions.php');
	$ig_pro = file_exists($ig_pro_file);     
	
	$ig_title = ($ig_data['Name'].' &#128737; '.($ig_pro?' Pro':''));
	$ig_title_v = $ig_title.' ('.$ig_data['Version'].')';	
	
	if($ig_pro){
		//include_once($ig_pro_file);
	}
	
	
	function ig_menu(){
		
		global $ig_title, $ig_title;

		add_options_page($ig_title, $ig_title, 'activate_plugins', 'ig_settings', 'ig_settings');
			
		add_users_page(
			__( 'Capability Audit', 'injection-guard' ).' &#128462;',
			__( 'Capability Audit', 'injection-guard' ).' &#128462;',
			'manage_options',
			'capability-audit',
			'ig_capability_audit_page'
		);


	}
	
	
	function ig_settings() { 

		if ( !current_user_can( 'administrator' ) )  {

			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
 			
		}

		global $ig_logs, $ig_blacklisted, $ig_title, $ig_title_v, $ig_pro, $ig_pro_link;
		$guard_obj = new guard_wordpress;
		$ig_blacklisted = $guard_obj->get_blacklisted();
		$ig_logs = $guard_obj->get_requests_log();

		
		
		
		if(is_array($ig_logs) && !empty($ig_logs)){
			ksort($ig_logs);
		}

		
		
		
		$blog_info = get_bloginfo('admin_email');


		$salt = date('YmddmY')+date('m');

		//DEFAULT BACKUP RECIPIENT EMAIL ADDRESS	
		$default_email = get_bloginfo('admin_email');
		
		$default_email = $default_email!=''?$default_email:'info@'.str_replace('www.','',$_SERVER['HTTP_HOST']); 

		
		include('ig_settings.php');			

	}	
	
	
	
	function register_ig_styles($hook_suffix) {



		if(!in_array($hook_suffix, array('settings_page_ig_settings', 'users_page_capability-audit')))
		return false;

		

	
		
		wp_register_style( 'ig-style', plugins_url('css/style.css', __FILE__) , array(), time());
		
		wp_register_style( 'ig-fa', plugins_url('css/fontawesome.min.css', __FILE__) , array(), time());
		
		
		wp_register_style( 'ig-bsm', plugins_url('css/bootstrap.min.css', __FILE__) , array(), time());
		wp_register_style( 'ig-bsr', plugins_url('css/bootstrap-responsive.min.css', __FILE__) , array(), time());
		wp_register_style( 'ig-bsi', plugins_url('css/bootstrap.icon-large.min.css', __FILE__) , array(), time());

		wp_enqueue_style('ig-fa');
		wp_enqueue_style('ig-style');
		wp_enqueue_style('ig-bsm');
		wp_enqueue_style('ig-bsr');
		wp_enqueue_style('ig-bsi');
				

		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script("jquery-effects-core");
		
		wp_enqueue_script(
			'bootstrap.min',
			plugins_url('js/bootstrap.min.js', __FILE__),
			array(), time()
		);		

		wp_enqueue_script(
			'ig_script',
			plugins_url('js/script.js', __FILE__),
			array(), time()
		);
		
		wp_enqueue_script(
			'jquery.blockUI',
			plugins_url('js/jquery.blockUI.js', __FILE__),
			array(), time()
		);
		

		$ig_translation = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'ig_nonce' => wp_create_nonce('ig_nonce_action'),
			'ig_super_admin' => is_super_admin(),
			'ig_super_admin_msg' => __('You need administrator privileges to proceed.', 'injection-guard')
		);

		wp_localize_script('ig_script', 'ig_obj', $ig_translation);
	}
	


	
	if(is_admin()){
		add_action( 'admin_menu', 'ig_menu' );	

		add_action( 'admin_enqueue_scripts', 'register_ig_styles' );
		
		add_action( 'wp_ajax_ig_update', 'ig_update' );
		add_action( 'wp_ajax_ig_update_bulk_backlist', 'ig_update_bulk_backlist' );
			
		$plugin = plugin_basename(__FILE__); 
		add_filter("plugin_action_links_$plugin", 'ig_plugin_links' );		
		
	}else{
		//ACTION TIME
		add_action('init', 'ig_start', 1);	
	}
	
	