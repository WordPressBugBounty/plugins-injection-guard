<?php if ( ! defined( 'ABSPATH' ) ) exit;
/*
	Plugin Name: Injection Guard
	Plugin URI: https://www.androidbubbles.com/extends/wordpress/plugins/injection-guard
	Description: Blocks unauthorized and irrelevant query string requests by redirecting them to a safe error page, enhancing security without bloating your site.
	Version: 1.4.0
	Requires at least: 5.3
	Requires PHP: 7.2
	Author: Fahad Mahmood
	Author URI: https://www.androidbubbles.com
	Text Domain: injection-guard
	Domain Path: /languages/
	License: GPLv2 or later
	License URI: https://www.gnu.org/licenses/gpl-2.0.html

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

	if ( ! defined( 'IG_VERSION' ) ) define( 'IG_VERSION', '1.4.0' );
	if ( ! defined( 'IG_DIR' ) )     define( 'IG_DIR', plugin_dir_path( __FILE__ ) );

	include_once( IG_DIR . 'functions.php' );

	global $ig_logs;
	global $ig_blacklisted;
	global $ig_rs, $ig_pro, $ig_pro_link, $ig_dir, $ig_url, $ig_data, $ig_title, $ig_title_v;

	$ig_rs = array();
	$ig_pro_link = 'https://shop.androidbubbles.com/product/injection-guard';
	$ig_rs[] = '<a target="_blank" href="plugin-install.php?tab=search&s=wp+mechanic&plugin-search-input=Search+Plugins">Install WP Mechanic</a>';
	$ig_rs[] = '<a target="_blank" href="http://androidbubble.com/blog/contact">Contact Developer</a>';

	$ig_dir = IG_DIR;
	$ig_url = plugin_dir_url( __FILE__ );

	// no file parsing on public page views
	$ig_data = array( 'Name' => 'Injection Guard', 'Version' => IG_VERSION );

	$ig_pro_file = realpath($ig_dir.'pro/functions.php');
	$ig_pro = ($ig_pro_file && file_exists($ig_pro_file));

	$ig_title = ($ig_data['Name'].' &#128737; '.($ig_pro?' Pro':''));
	$ig_title_v = $ig_title.' ('.$ig_data['Version'].')';

	if($ig_pro){
		include_once($ig_pro_file);
	}

	function ig_load_textdomain(){
		load_plugin_textdomain( 'injection-guard', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
	add_action( 'init', 'ig_load_textdomain' );
	add_action( 'plugins_loaded', 'ig_maybe_upgrade' );

	register_activation_hook( __FILE__, 'ig_maybe_upgrade' );

	function ig_menu(){

		global $ig_title;

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

		if ( !current_user_can( 'manage_options' ) )  {

			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'injection-guard' ) );

		}

		global $ig_logs, $ig_blacklisted, $ig_title, $ig_title_v, $ig_pro, $ig_pro_link, $ig_dir;
		$guard_obj = new guard_wordpress;
		$ig_blacklisted = $guard_obj->get_blacklisted();
		$ig_logs = $guard_obj->get_requests_log();

		if(is_array($ig_logs) && !empty($ig_logs)){
			ksort($ig_logs);
		}

		include($ig_dir.'ig_settings.php');

	}

	function register_ig_styles($hook_suffix) {

		if(!in_array($hook_suffix, array('settings_page_ig_settings', 'users_page_capability-audit')))
		return false;

		wp_register_style( 'ig-style', plugins_url('css/style.css', __FILE__) , array(), IG_VERSION);

		wp_register_style( 'ig-fa', plugins_url('css/fontawesome.min.css', __FILE__) , array(), IG_VERSION);

		wp_register_style( 'ig-bsm', plugins_url('css/bootstrap.min.css', __FILE__) , array(), IG_VERSION);
		wp_register_style( 'ig-bsr', plugins_url('css/bootstrap-responsive.min.css', __FILE__) , array(), IG_VERSION);
		wp_register_style( 'ig-bsi', plugins_url('css/bootstrap.icon-large.min.css', __FILE__) , array(), IG_VERSION);

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
			array(), IG_VERSION
		);

		wp_enqueue_script(
			'ig_script',
			plugins_url('js/script.js', __FILE__),
			array('jquery'), IG_VERSION
		);

		wp_enqueue_script(
			'jquery.blockUI',
			plugins_url('js/jquery.blockUI.js', __FILE__),
			array('jquery'), IG_VERSION
		);

		$ig_translation = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'ig_nonce' => wp_create_nonce('ig_nonce_action'),
			'ig_super_admin' => is_super_admin(),
			'ig_super_admin_msg' => __('You need administrator privileges to proceed.', 'injection-guard'),
			'confirm_clear' => __('This permanently deletes the selected list. Continue?', 'injection-guard'),
			'error_generic' => __('Something went wrong. Please reload the page and try again.', 'injection-guard'),
			'audit_done' => __('All accounts loaded.', 'injection-guard'),
			'audit_count' => __('%d accounts loaded…', 'injection-guard'),
		);

		wp_localize_script('ig_script', 'ig_obj', $ig_translation);
	}

	if(is_admin()){
		add_action( 'admin_menu', 'ig_menu' );

		add_action( 'admin_enqueue_scripts', 'register_ig_styles' );

		add_action( 'wp_ajax_ig_update', 'ig_update' );
		add_action( 'wp_ajax_ig_update_bulk_backlist', 'ig_update_bulk_backlist' );
		add_action( 'wp_ajax_ig_clear_data', 'ig_clear_data' );
		add_action( 'admin_post_ig_save_settings', 'ig_save_settings' );
		add_action( 'admin_post_ig_export', 'ig_export_csv' );

		$plugin = plugin_basename(__FILE__);
		add_filter("plugin_action_links_$plugin", 'ig_plugin_links' );

	}else{
		//ACTION TIME
		add_action('init', 'ig_start', 1);
	}
