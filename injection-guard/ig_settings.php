<?php if ( ! defined( 'ABSPATH' ) ) exit;

$ig_cfg     = ig_get_settings();
$ig_notice  = isset($_GET['ig_notice']) ? sanitize_key(wp_unslash($_GET['ig_notice'])) : '';
$ig_blocked = get_option('ig_blocked_log');
$ig_blocked = is_array($ig_blocked) ? $ig_blocked : array();
$ig_logs    = is_array($ig_logs) ? $ig_logs : array();
$ig_blacklisted = is_array($ig_blacklisted) ? $ig_blacklisted : array();
?>
<div class="wrap ig_settings">

<?php if(!$ig_pro): ?>
<a class="ig-member-link" title="<?php esc_attr_e('Click here to download pro version','injection-guard'); ?>" href="https://shop.androidbubbles.com/download/" target="_blank" rel="noopener"><?php esc_html_e('Already a Pro Member?','injection-guard'); ?></a>
<?php endif; ?>

<div class="icon32" id="icon-options-general"><br></div><h2>&#128137; <?php echo wp_kses_post($ig_title_v); ?> <?php if(!$ig_pro){ ?><a class="ig-gopro" target="_blank" rel="noopener" href="<?php echo esc_url($ig_pro_link); ?>"><?php esc_html_e("Go Premium",'injection-guard'); ?></a><?php } ?></h2>
<hr />

<?php if($ig_notice === 'saved'): ?>
<div class="notice notice-success is-dismissible ig-notice"><p><?php esc_html_e('Settings saved.', 'injection-guard'); ?></p></div>
<?php endif; ?>

<div class="list_head">
<a class="ig_how_link" href="#" role="button"><?php esc_html_e('How it works?', 'injection-guard'); ?></a>
<p class="hide welcome-panel"><strong><?php esc_html_e('How it works?', 'injection-guard'); ?></strong><br /><?php esc_html_e("It simply logs all the unique query strings which are trying to penetrate your website through URLs, either good or bad. By default, it neither blocks any query nor allows. Once you observe the activity on your website, mark parameters as good or bad and Injection Guard will deny the blocked parameters. It's not the ultimate solution, but it alarms you about malicious activity in progress so you can take security measures.", 'injection-guard'); ?> <a href="plugin-install.php?tab=search&amp;s=wp+mechanic" target="_blank" rel="noopener" title="<?php esc_attr_e('Click here to install WordPress Mechanic plugin and get $60 worth help for free', 'injection-guard'); ?>"><?php esc_html_e('Install WordPress Mechanic', 'injection-guard'); ?></a> <?php esc_html_e('and ask for a free diagnosis.', 'injection-guard'); ?><a class="ig_dismiss_link welcome-panel-close" href="#" role="button"><?php esc_html_e('Dismiss', 'injection-guard'); ?></a>
</p>
</div>

<ul class="nav nav-tabs" role="tablist">
	<li class="nav-item mb-0" role="presentation">
		<button class="nav-link active" data-bs-toggle="tab" data-bs-target="#ig_dashboard" type="button" role="tab" aria-controls="ig_dashboard" aria-selected="true"><?php esc_html_e('Dashboard', 'injection-guard'); ?></button>
	</li>
	<li class="nav-item mb-0" role="presentation">
		<button class="nav-link" data-bs-toggle="tab" data-bs-target="#ig_logs" type="button" role="tab" aria-controls="ig_logs" aria-selected="false"><?php esc_html_e('Log', 'injection-guard'); ?></button>
	</li>
	<li class="nav-item mb-0" role="presentation">
		<button class="nav-link" data-bs-toggle="tab" data-bs-target="#ig_blocked" type="button" role="tab" aria-controls="ig_blocked" aria-selected="false"><?php esc_html_e('Blocked', 'injection-guard'); ?></button>
	</li>
	<li class="nav-item mb-0" role="presentation">
		<button class="nav-link" data-bs-toggle="tab" data-bs-target="#ig_options" type="button" role="tab" aria-controls="ig_options" aria-selected="false"><?php esc_html_e('Settings', 'injection-guard'); ?></button>
	</li>
	<li class="nav-item mb-0" role="presentation">
		<button class="nav-link" data-bs-toggle="tab" data-bs-target="#ig_pro" type="button" role="tab" aria-controls="ig_pro" aria-selected="false"><?php echo $ig_pro ? esc_html__('Pro', 'injection-guard') : esc_html__('Go Pro', 'injection-guard'); ?></button>
	</li>
</ul>

<div class="tab-content">

  <div id="ig_dashboard" class="tab-pane fade show active">

    <?php include( IG_DIR . 'templates/overview.php' ); ?>

    <?php include( IG_DIR . 'templates/dashboard.php' ); ?>

  </div>

  <div id="ig_logs" class="tab-pane fade">

	<div class="row mt-3">
		<div class="col-md-12">
			<div class="ms-3 h4"><span class="icon-list-alt"></span><?php esc_html_e('Logged Requests', 'injection-guard'); ?>:</div>
		</div>
	</div>

	<div class="ig_toolbar">
		<input type="search" id="ig_log_filter" class="regular-text" placeholder="<?php esc_attr_e('Search URLs or parameters…', 'injection-guard'); ?>" aria-label="<?php esc_attr_e('Search the log', 'injection-guard'); ?>">
		<select id="ig_log_status" aria-label="<?php esc_attr_e('Filter by status', 'injection-guard'); ?>">
			<option value=""><?php esc_html_e('All parameters', 'injection-guard'); ?></option>
			<option value="blocked"><?php esc_html_e('Blocked only', 'injection-guard'); ?></option>
			<option value="allowed"><?php esc_html_e('Not blocked only', 'injection-guard'); ?></option>
			<option value="today"><?php esc_html_e('Seen today', 'injection-guard'); ?></option>
		</select>
		<a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=ig_export&type=log'), 'ig_export')); ?>"><?php esc_html_e('Export CSV', 'injection-guard'); ?></a>
		<?php if(!empty($ig_logs)): ?>
		<button type="button" class="button ig_clear" data-what="requests"><?php esc_html_e('Clear log', 'injection-guard'); ?></button>
		<?php endif; ?>
		<span id="ig_log_count" class="description"></span>
	</div>

	<div class="row mt-3">
		<div class="col-md-12">

			<ul class="ig_bulk_action">
				<li>
					<ul class="mb-3">
						<li>
							<div class="ig_params">
								<label for="ig_select_all">
									<input type="checkbox" name="" id="ig_select_all">
									<?php esc_html_e('Select All', 'injection-guard'); ?>
								</label>
							</div>

							<div class="ig_actions_selected">
								<a href="#" title="<?php esc_attr_e('Click to whitelist all selected', 'injection-guard'); ?>" data-type="whitelist"><i class="fa fa-thumbs-up text-primary"></i></a>

								<a href="#" title="<?php esc_attr_e('Click to blacklist all selected', 'injection-guard'); ?>" data-type="blacklist"><i class="fa fa-thumbs-down text-danger"></i></a>
							</div>

							<hr class="w-50">

						</li>
					</ul>

				</li>
			</ul>

			<ul class="ig_log_list">

				<?php if(!empty($ig_logs)): ?>

				<?php

					$today_date  = wp_date('y_m_d');
					$date_format = get_option( 'date_format' , "F j, Y");
					foreach($ig_logs as $log_head => $params):
						if(!is_array($params)){ continue; }
						asort($params);

						?>
						<?php $count_blacklisted = isset($ig_blacklisted[$log_head])?count($ig_blacklisted[$log_head]):0; ?>
							<li class="ig_uri_row" data-uri-text="<?php echo esc_attr(strtolower((string)$log_head)); ?>">
								<i class="fa fa-flag"></i>&nbsp;

								<?php echo esc_html($log_head.' ('.$count_blacklisted.'/'.count($params).')'); ?>
								<?php if(!empty($params)): ?>

									<ul class="mt-2">
										<?php

											$today_hr = false;
											$counter = 0;
											foreach($params as $param_key => $param):
												$counter++;

												$param_key = (string)$param_key;
												$current_compare_date = wp_date('y_m_d', (int)$param);

												if($counter == 1 && $today_date == $current_compare_date){
													echo '<li class="ig_sep"><strong class="mt-1">'.esc_html__('Today', 'injection-guard').':</strong></li>';
												}
												if($today_date != $current_compare_date && !$today_hr){
													$today_hr = true;
													echo '<li class="ig_sep"><hr class="w-50"></li>';
												}

												$blacklisted = (isset($ig_blacklisted[$log_head]) && in_array($param_key, array_map('strval', (array)$ig_blacklisted[$log_head]), true));
												?>
												<li class="ig_param_row" data-name="<?php echo esc_attr(strtolower($param_key)); ?>" data-blocked="<?php echo $blacklisted ? '1' : '0'; ?>" data-today="<?php echo ($today_date == $current_compare_date) ? '1' : '0'; ?>">
													<div class="ig_params">
													<input type="checkbox" data-uri="<?php echo esc_attr($log_head); ?>" value="<?php echo esc_attr($param_key); ?>">
													<i class="fa fa-question-circle"></i> <?php echo esc_html($param_key); ?> | <?php echo esc_html(wp_date($date_format, (int)$param)); ?>
													</div>

													<div class="ig_actions" data-uri="<?php echo esc_attr($log_head); ?>" data-val="<?php echo esc_attr($param_key); ?>">

													<a href="#" title="<?php esc_attr_e('Click to whitelist', 'injection-guard'); ?>" data-type="whitelist" class="<?php echo $blacklisted?'':'hide'; ?>"><i class="fa fa-thumbs-up"></i></a>

													<a href="#" title="<?php esc_attr_e('Click to blacklist', 'injection-guard'); ?>" data-type="blacklist" class="<?php echo $blacklisted?'hide':''; ?>"><i class="fa fa-thumbs-down text-danger"></i></a>
													</div>
												</li>
										<?php

											endforeach; ?>
									</ul>


								<?php endif; ?>
							</li>
					<?php endforeach; ?>
				<?php else: ?>
				<li><?php esc_html_e('There are no logged requests to show at the moment.', 'injection-guard'); ?></li>
				<?php endif; ?>
			</ul>

		</div>
	</div>


<div class="wm_help">
<div class="wp_sep"></div>
<strong><?php esc_html_e('Need help?', 'injection-guard'); ?></strong><br />

<a href="https://wordpress.org/plugins/wp-mechanic/" target="_blank" rel="noopener" title="WordPress Mechanic"><img src="https://plugins.svn.wordpress.org/wp-mechanic/assets/icon-128x128.png" alt="WordPress Mechanic" /></a>
<a href="plugin-install.php?tab=search&amp;s=wp+mechanic" target="_blank" rel="noopener" style="border-left:4px solid #eee; margin-left:60px;" title="<?php esc_attr_e('Click here to install WordPress Mechanic plugin and get $60 worth help for free', 'injection-guard'); ?>"><img src="https://plugins.svn.wordpress.org/wp-mechanic/assets/banner-772x250.png" width="60%" alt="WordPress Mechanic" /></a>
<div class="wp_sep last"></div>
</div>

  </div>

  <div id="ig_blocked" class="tab-pane fade">
	<?php include( IG_DIR . 'templates/blocked.php' ); ?>
  </div>

  <div id="ig_options" class="tab-pane fade">
	<?php include( IG_DIR . 'templates/settings.php' ); ?>
  </div>

  <div id="ig_pro" class="tab-pane fade">
	<?php include( IG_DIR . 'templates/pro.php' ); ?>
  </div>

</div>

</div>
