<?php
/**
 * Runs when the plugin is deleted from the Plugins screen.
 * Removes the data Injection Guard created. The long-standing user meta keys
 * "last_login" and "ip_logs" are left alone because other software may share those names.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

function ig_uninstall_site() {

	$options = array(
		'ig_requests_log',
		'ig_blacklisted',
		'ig_blocked_log',
		'ig_settings',
		'ig_version',
		'ig_pro_bans',
	);

	foreach ( $options as $option ) {
		delete_option( $option );
	}

	delete_transient( 'ig_blocked_write_lock' );
	delete_transient( 'ig_alert_attack' );
	delete_transient( 'ig_alert_ban' );
	delete_transient( 'ig_alert_test' );

	global $wpdb;

	// strike counters, geo cache and per-user alert locks
	$wpdb->query(
		"DELETE FROM {$wpdb->options}
		 WHERE option_name LIKE '\_transient\_ig\_%'
		    OR option_name LIKE '\_transient\_timeout\_ig\_%'"
	);

	// per-user data (session tracking)
	$wpdb->query(
		"DELETE FROM {$wpdb->usermeta}
		 WHERE meta_key IN ('ig_last_login','ig_last_logout','ig_session_start','ig_last_seen','ig_last_session_duration')"
	);
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $ig_site_id ) {
		switch_to_blog( $ig_site_id );
		ig_uninstall_site();
		restore_current_blog();
	}
} else {
	ig_uninstall_site();
}
