<?php require_once('guard.php');



	

	

	//FOR QUICK DEBUGGING



	if(!function_exists('pre')){

	function pre($data){

			if(isset($_GET['debug'])){

				pree($data);

			}

		}	 

	} 	

	if(!function_exists('pree')){

	function pree($data){

				echo '<pre>';

				print_r($data);

				echo '</pre>';	

		

		}	 

	} 







	if(!function_exists('ig_start')){





		function ig_start(){	

				

				$guard_obj = new guard_wordpress;

				$guard_obj->init();

				$guard_obj->update_log();

				$ig_logs = $guard_obj->get_requests_log();

				$ig_blacklisted = $guard_obj->get_blacklisted();

				$uri = $guard_obj->wp_uri_cleaned();

				$aus = $guard_obj->available_uri_strings();

				

				

				if(isset($ig_blacklisted[$uri]))

				{

					$diff = array_intersect($ig_blacklisted[$uri], $aus);

					

					if(!empty($diff)){

						global $wp_query;

						$wp_query->set_404();

						status_header( 404 );

						get_template_part( 404 ); 

						exit();

					}

				}



		}	





	}

	



	if(!function_exists('ig_update')){

		function ig_update(){	

		

			$ret = array('status'=>true);

			if ( 
				! isset( $_POST['ig_nonce'] ) 
				|| ! wp_verify_nonce( $_POST['ig_nonce'], 'ig_nonce_action' ) 
			) {
			
			   print __('Sorry, your nonce did not verify.','injection-guard');
			   exit;
			
			} elseif(is_super_admin()) {

				$val  = isset($_POST['val']) ? sanitize_text_field($_POST['val']) : '';

	
				$type = isset($_POST['type']) && in_array($_POST['type'], ['whitelist','blacklist']) ? $_POST['type'] : 'blacklist';

	
				$uri = isset($_POST['uri_index'])?esc_attr($_POST['uri_index']):'';
				
	
				
	
				$guard_obj = new guard_wordpress;
	
				
	
				if($type=='whitelist'){
	
					$guard_obj->update_blacklisted($val, $uri, false);
	
				}else{
	
					$guard_obj->update_blacklisted($val, $uri, true);
	
				}
	

				wp_send_json_success($ret);

				
			}


		}

	}

	

	function ig_plugin_links($links) { 

		global $ig_pro, $ig_pro_link;		
		
		$settings_link = '<a href="options-general.php?page=ig_settings">'.__('Settings', 'injection-guard').'</a>'; 
		
		$premium_link = '';
		
		if(!$ig_pro){
			$premium_link = '<a href="'.$ig_pro_link.'" title="'.__('Go Premium', 'injection-guard').'" target="_blank">'.__('Go Premium', 'injection-guard').'</a>'; 
		}
		
		array_unshift($links, $settings_link,$premium_link); 
		
		return $links; 

	}
	
	function ig_get_ip() 
	{
		$ip      = '';
		$sources = array (
			'REMOTE_ADDR',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_CLIENT_IP',
		);
	
		foreach ( $sources as $source ) {
			if ( isset ( $_SERVER[ $source ] ) )  {
				$ip = $_SERVER[ $source ];
			} elseif ( getenv( $source ) ) {
				$ip = getenv( $source );
			}
		}
	
		return $ip;
	}			
	
	function ig_user_last_login( $user_login, $user ) {
		update_user_meta( $user->ID, 'last_login', time() );
		$ips = get_user_meta( $user->ID, 'ip_logs',  true);
		$ips = is_array($ips)?$ips:array();
		$ips[] = ig_get_ip();
		$ips = array_unique($ips);
		update_user_meta( $user->ID, 'ip_logs', $ips);
		
	}
	add_action( 'wp_login', 'ig_user_last_login', 10, 2 );	
		
	function ig_get_customer_total_order($user_id=0) {
		global $wpdb;
		$customer_orders = get_posts( array(
			'numberposts' => - 1,
			'meta_key'    => '_customer_user',
			'meta_value'  => $user_id?$user_id:get_current_user_id(),
			'post_type'   => array( 'shop_order' ),
			'post_status' => array( 'wc-completed' )
		) );
		//pree($customer_orders);
		$total = 0;
		$products = array();
		if(!empty($customer_orders)){
			foreach ( $customer_orders as $customer_order ) {
				//pree($customer_order->ID);
				$order = wc_get_order( $customer_order );
				//pree($order);
				//pree($order->get_items());
				if(!empty($order) && count($order->get_items())>0){
					foreach ($order->get_items() as $item_id => $item_data) {
						$product = $item_data->get_product();
						if(!empty($product)){
							
							$product_name = $product->get_name();
							$item_quantity = $item_data->get_quantity();
							$permissions_query = $wpdb->prepare( "
									SELECT * FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
									WHERE order_id = %d ORDER BY product_id
								", $order->id );
							//pree($permissions_query);
							$download_permissions = $wpdb->get_results($permissions_query);
							//pree($download_permissions);
							$download_count = 0;
							if(!empty($download_permissions)){
								$for_download_count = current($download_permissions);
								$download_count = $for_download_count->download_count;
							}
								
							$products[] = array('qty'=>$item_quantity, 'product'=>$product_name, 'download_count'=>$download_count);
						}
					}
				}
				//pree($order);
				$total += $order->get_total();
			}
		}
	
		return array(count($customer_orders), $total, $products);
	}	

	if (!function_exists('ig_update_bulk_backlist')) {
		function ig_update_bulk_backlist() {
			// Verify nonce first
			if (!isset($_POST['ig_nonce']) || !wp_verify_nonce($_POST['ig_nonce'], 'ig_nonce_action')) {
				wp_send_json_error(['message' => __('Sorry, your nonce did not verify.', 'injection-guard')], 400);
			}
	
			// Only allow super admins
			if (!is_super_admin()) {
				wp_send_json_error(['message' => __('Unauthorized action.', 'injection-guard')], 403);
			}
	
			// Sanitize posted data with depth-safe function
			$posted_data = sanitize_ig_data($_POST);
	
			$ig_type     = $posted_data['ig_type'] ?? 'blacklist';
			$ig_post_obj = $posted_data['ig_post_obj'] ?? [];
	
			$guard_obj = new guard_wordpress;
	
			if (!empty($ig_post_obj)) {
				foreach ($ig_post_obj as $uri => $val_array) {
					if (!empty($val_array)) {
						foreach ($val_array as $val) {
							if ($ig_type === 'whitelist') {
								$guard_obj->update_blacklisted($val, $uri, false);
							} else {
								$guard_obj->update_blacklisted($val, $uri, true);
							}
						}
					}
				}
			}
	
			wp_send_json_success(['message' => __('Updated successfully.', 'injection-guard')]);
		}
	}

	add_action( 'wp_login', function( $user_login, $user ) {
		update_user_meta( $user->ID, 'ig_last_login', time() );
		update_user_meta( $user->ID, 'ig_session_start', time() );
	}, 10, 2 );
	
	add_action( 'wp_logout', function() {
		$user_id = get_current_user_id();
		if ( $user_id ) {
			$start = get_user_meta( $user_id, 'ig_session_start', true );
			$end   = time();
	
			if ( $start ) {
				$duration = $end - $start;
				update_user_meta( $user_id, 'ig_last_session_duration', $duration );
				update_user_meta( $user_id, 'ig_last_logout', $end );
			}
		}
	});
	
	add_filter( 'manage_users_columns', function( $columns ) {
		$columns['ig_last_login']     = __( 'Last Login', 'injection-guard' );
		$columns['ig_last_logout']    = __( 'Last Logout', 'injection-guard' );
		$columns['ig_session_length'] = __( 'Session Duration', 'injection-guard' );
		return $columns;
	} );
	
	add_filter( 'manage_users_custom_column', function( $value, $column_name, $user_id ) {
		if ( $column_name === 'ig_last_login' ) {
			$login  = get_user_meta( $user_id, 'ig_last_login', true );
			$start  = get_user_meta( $user_id, 'ig_session_start', true );
			$display = $login ?: $start;
			return $display ? date( 'Y-m-d H:i:s', $display ) : '—';
		}
	
		if ( $column_name === 'ig_last_logout' ) {
			$logout = get_user_meta( $user_id, 'ig_last_logout', true );
			return $logout ? date( 'Y-m-d H:i:s', $logout ) : '—';
		}
	
		if ( $column_name === 'ig_session_length' ) {
			$start     = get_user_meta( $user_id, 'ig_session_start', true );
			$last_seen = get_user_meta( $user_id, 'ig_last_seen', true );
			if ( $start && $last_seen && $last_seen > $start ) {
				$duration = $last_seen - $start;
				return gmdate( 'H:i:s', $duration );
			}
			return '—';
		}
	
		return $value;
	}, 10, 3 );
	

	
	
	function ig_capability_audit_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You are not allowed to access this page.', 'injection-guard' ) );
		}
		?>
		<div class="wrap">
			<h1><?php _e( 'Capability Audit', 'injection-guard' ); ?> &#128462;</h1>
			<table id="cap-audit-table">
				<thead>
                  <tr>
                    <th><?php _e( 'ID', 'injection-guard' ); ?></th>
                    <th><?php _e( 'Username', 'injection-guard' ); ?></th>
                    <th><?php _e( 'Email', 'injection-guard' ); ?></th>
                    <th><?php _e( 'Capabilities', 'injection-guard' ); ?></th>
                  </tr>
                </thead>

				<tbody id="cap-audit-results"></tbody>
			</table>
		
		</div>
		<?php
	}
	
	add_action('wp_ajax_ig_load_capability_audit', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}
	
		check_ajax_referer( 'ig_nonce_action', 'security' );

		
		global $wpdb;
		$offset = intval($_GET['offset'] ?? 0);
		$per_page = intval($_GET['per_page'] ?? 100);
		$cap_key = $wpdb->prefix . 'capabilities';
	
		$results = $wpdb->get_results( $wpdb->prepare("
			SELECT u.ID as user_id, u.user_login, u.user_email, um.meta_value as caps
			FROM {$wpdb->users} u
			INNER JOIN {$wpdb->usermeta} um ON um.user_id = u.ID
			WHERE um.meta_key = %s
			ORDER BY u.ID ASC
			LIMIT %d OFFSET %d
		", $cap_key, $per_page, $offset) );
	
		if ( $offset === 0 ) {
			echo '<style>table { border-collapse: collapse; width: 100%; margin-top: 1em; } td, th { border: 1px solid #ccc; padding: 6px; font-size: 13px; } .flag { background: #fff3f3; color: #b00; font-weight: bold; }</style>';
		}
	
		foreach ( $results as $row ) {
			$caps = maybe_unserialize( $row->caps );
			$cap_list = [];
			$suspicious = false;
	
			foreach ( (array) $caps as $cap => $value ) {
				if ( $value ) {
					$cap_list[] = $cap;
					if ( in_array( $cap, [ 'manage_options', 'edit_users', 'install_plugins', 'delete_users' ] ) ) {
						$suspicious = true;
					}
				}
			}
	
			$user_link = admin_url("user-edit.php?user_id={$row->user_id}");
			$class = ($suspicious || trim($row->user_login, '-') === '') ? 'flag' : '';
	
			echo "<tr class='{$class}'>";
			echo "<td><a href='" . esc_url($user_link) . "' target='_blank'>" . esc_html($row->user_id) . "</a></td>";
			echo "<td>" . esc_html($row->user_login) . "</td>";
			echo "<td>" . esc_html($row->user_email) . "</td>";
			echo "<td>" . esc_html(implode(', ', $cap_list ?: ['—'])) . "</td>";
			echo "</tr>";
		}
	
		wp_die();
	});
	
	add_action( 'init', 'ig_track_user_session_ping', 1 );
	add_action( 'admin_init', 'ig_track_user_session_ping', 1 );
	
	function ig_track_user_session_ping() {
		if ( ! is_user_logged_in() ) return;
	
		$user_id = get_current_user_id();
		$now     = time();
	
		$start     = get_user_meta( $user_id, 'ig_session_start', true );
		$last_seen = get_user_meta( $user_id, 'ig_last_seen', true );
	
		if ( empty( $start ) || ! is_numeric( $start ) || $start > $now ) {
			update_user_meta( $user_id, 'ig_session_start', $now );
			update_user_meta( $user_id, 'ig_last_seen', $now );
			update_user_meta( $user_id, 'ig_last_session_duration', 0 );
			return;
		}
	
		if ( empty( $last_seen ) || ( $now - intval( $last_seen ) ) >= 300 ) {
			update_user_meta( $user_id, 'ig_last_seen', $now );
			update_user_meta( $user_id, 'ig_last_session_duration', $now - intval( $start ) );
		}
	}
