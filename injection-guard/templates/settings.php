<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="ig-settings-form">
	<input type="hidden" name="action" value="ig_save_settings">
	<?php wp_nonce_field('ig_save_settings', 'ig_settings_nonce'); ?>

	<h3><?php esc_html_e('Logging', 'injection-guard'); ?></h3>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e('Record parameters', 'injection-guard'); ?></th>
			<td>
				<label><input type="checkbox" name="ig[logging]" value="1" <?php checked(!empty($ig_cfg['logging'])); ?>> <?php esc_html_e('Log the names of query-string parameters seen on the site (values are never stored)', 'injection-guard'); ?></label>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="ig_log_limit"><?php esc_html_e('Keep at most', 'injection-guard'); ?></label></th>
			<td>
				<input type="number" min="10" max="5000" step="10" id="ig_log_limit" name="ig[log_limit]" value="<?php echo esc_attr((int)$ig_cfg['log_limit']); ?>" class="small-text"> <?php esc_html_e('URLs in the log', 'injection-guard'); ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="ig_log_retention"><?php esc_html_e('Forget entries after', 'injection-guard'); ?></label></th>
			<td>
				<input type="number" min="0" max="3650" id="ig_log_retention" name="ig[log_retention_days]" value="<?php echo esc_attr((int)$ig_cfg['log_retention_days']); ?>" class="small-text"> <?php esc_html_e('days (0 = keep until the limit above is reached)', 'injection-guard'); ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="ig_safe_params"><?php esc_html_e('Safe parameters', 'injection-guard'); ?></label></th>
			<td>
				<textarea id="ig_safe_params" name="ig[safe_params]" rows="4" class="large-text code" placeholder="utm_*&#10;fbclid&#10;gclid"><?php echo esc_textarea($ig_cfg['safe_params']); ?></textarea>
				<p class="description"><?php esc_html_e('One per line. These are never logged and never blocked. End with * to match a prefix (for example utm_*).', 'injection-guard'); ?></p>
			</td>
		</tr>
	</table>

	<h3><?php esc_html_e('Blocking', 'injection-guard'); ?></h3>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e('Response for blocked requests', 'injection-guard'); ?></th>
			<td>
				<label><input type="radio" name="ig[block_status]" value="404" <?php checked((int)$ig_cfg['block_status'], 404); ?>> <?php esc_html_e('404 Not Found (default: reveals nothing to scanners)', 'injection-guard'); ?></label><br>
				<label><input type="radio" name="ig[block_status]" value="403" <?php checked((int)$ig_cfg['block_status'], 403); ?>> <?php esc_html_e('403 Forbidden', 'injection-guard'); ?></label>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="ig_global_blacklist"><?php esc_html_e('Global blacklist', 'injection-guard'); ?></label></th>
			<td>
				<textarea id="ig_global_blacklist" name="ig[global_blacklist]" rows="4" class="large-text code" placeholder="author&#10;cmd&#10;exec"><?php echo esc_textarea($ig_cfg['global_blacklist']); ?></textarea>
				<p class="description"><?php esc_html_e('Parameters blocked on every URL of the site. One per line, prefix* is supported. Be careful: blocking a parameter WordPress or a plugin needs will break that feature.', 'injection-guard'); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e('Administrators', 'injection-guard'); ?></th>
			<td>
				<label><input type="checkbox" name="ig[exempt_admins]" value="1" <?php checked(!empty($ig_cfg['exempt_admins'])); ?>> <?php esc_html_e('Never block or log logged-in administrators (prevents locking yourself out)', 'injection-guard'); ?></label>
			</td>
		</tr>
	</table>

	<h3><?php esc_html_e('Attack signature scan', 'injection-guard'); ?></h3>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e('Mode', 'injection-guard'); ?></th>
			<td>
				<label><input type="radio" name="ig[heuristics]" value="off" <?php checked($ig_cfg['heuristics'], 'off'); ?>> <?php esc_html_e('Off', 'injection-guard'); ?></label><br>
				<label><input type="radio" name="ig[heuristics]" value="log" <?php checked($ig_cfg['heuristics'], 'log'); ?>> <?php esc_html_e('Detect and record only (default)', 'injection-guard'); ?></label><br>
				<label><input type="radio" name="ig[heuristics]" value="block" <?php checked($ig_cfg['heuristics'], 'block'); ?>> <?php esc_html_e('Detect and block (HTTP 403)', 'injection-guard'); ?></label>
				<p class="description"><?php esc_html_e('Looks inside query strings for typical SQL injection, script injection, path traversal and PHP-execution patterns. Start with "record only", check the Blocked tab for a few days, then switch to blocking.', 'injection-guard'); ?></p>
			</td>
		</tr>
	</table>

	<h3><?php esc_html_e('Privacy and network', 'injection-guard'); ?></h3>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e('Blocked list', 'injection-guard'); ?></th>
			<td>
				<label><input type="checkbox" name="ig[log_blocked]" value="1" <?php checked(!empty($ig_cfg['log_blocked'])); ?>> <?php esc_html_e('Keep a list of blocked and suspicious requests', 'injection-guard'); ?></label><br>
				<label><input type="checkbox" name="ig[anonymize_ip]" value="1" <?php checked(!empty($ig_cfg['anonymize_ip'])); ?>> <?php esc_html_e('Anonymise visitor IP addresses in that list (recommended for GDPR)', 'injection-guard'); ?></label><br>
				<label for="ig_blocked_limit"><?php esc_html_e('Keep at most', 'injection-guard'); ?></label>
				<input type="number" min="20" max="1000" step="10" id="ig_blocked_limit" name="ig[blocked_limit]" value="<?php echo esc_attr((int)$ig_cfg['blocked_limit']); ?>" class="small-text"> <?php esc_html_e('entries', 'injection-guard'); ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="ig_ip_header"><?php esc_html_e('Where does the visitor IP come from?', 'injection-guard'); ?></label></th>
			<td>
				<select id="ig_ip_header" name="ig[ip_header]">
					<?php foreach(ig_ip_header_choices() as $ig_hv => $ig_hl): ?>
					<option value="<?php echo esc_attr($ig_hv); ?>" <?php selected($ig_cfg['ip_header'], $ig_hv); ?>><?php echo esc_html($ig_hl); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e('Only change this if your site really sits behind that proxy or CDN. Forwarded headers can be forged by anyone when no proxy is in front of the site.', 'injection-guard'); ?></p>
			</td>
		</tr>
	</table>

	<?php if($ig_pro): ?>
	<h3><?php esc_html_e('Pro settings', 'injection-guard'); ?></h3>
	<table class="form-table" role="presentation">
		<?php do_action('ig_settings_pro_fields', $ig_cfg); ?>
	</table>
	<?php endif; ?>

	<p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e('Save Settings', 'injection-guard'); ?></button></p>
</form>
