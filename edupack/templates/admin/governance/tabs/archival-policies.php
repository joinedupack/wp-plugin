<?php
/**
 * Edupack archival policies
 *
 * @package edupack
 * @version 1.0.0
 */


// Get the settings handler
$helper = new Edupack();

$excemptions = $helper->get_setting_option( 'edupack_archival_policies', 'exemptions' ) ? $helper->get_setting_option( 'edupack_archival_policies', 'exemptions' ) : array();

$sites = array();

// Get the select data for the dropdown.
foreach( $excemptions as $excemption ) {
	$sites[] = array(
		'id' => $excemption,
		'name' => $helper->get_blog_title( $excemption ),
	);
}

$archiving_permissions = $helper->get_setting_option( 'edupack_archival_policies', 'archiving_permissions' );
$publishing_permissions = $helper->get_setting_option( 'edupack_archival_policies', 'publishing_permissions' );
$warning_email_period_metric = $helper->get_setting_option( 'edupack_archival_policies', 'warning_email_period_metric' );
$inactive_site_period_metric = $helper->get_setting_option( 'edupack_archival_policies', 'inactive_site_period_metric' );
$warning_email_period = $helper->get_setting_option( 'edupack_archival_policies', 'warning_email_period' );
$inactive_site_period = $helper->get_setting_option( 'edupack_archival_policies', 'inactive_site_period' );

?>
<div id="intro">
	<?php
	$flash = new Edupack_Flash_Message( 'archival_form_' );
	$flash->display_success_message();
	$flash->display_error_message();
	?>
	<h2>Archival Policies</h2>
	<p>Configure settings for archiving inactive sites</p>
</div>

<form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
	<!-- Archival Sites Period -->
	<div class="input-select-combined section">
		<h3>Archival of inactive sites</h3>
		<label for="inactive_site_period">Specify the period of inactivity after which a site is archived.</label>
		<input type="number" name="inactive_site_period" value="<?php echo esc_attr( $inactive_site_period ); ?>"/>
		<select name="inactive_site_period_metric">
			<option <?php $helper->is_selected( $inactive_site_period_metric, 'weeks' ); ?> value="weeks">Weeks</option>
			<option <?php $helper->is_selected( $inactive_site_period_metric, 'months' ); ?> value="months">Months</option>
			<option <?php $helper->is_selected( $inactive_site_period_metric, 'years' ); ?> value="years">Years</option>
		</select>
	</div>

	<!-- Warning Email -->
	<div class="input-select-combined section">
		<h3>Warning Email</h3>
		<label for="warning_email_period">Specify the time before a site is archived that a warning email is sent.</label>
		<input type="number" name="warning_email_period" value="<?php echo esc_attr( $warning_email_period ); ?>"/>
		<select name="warning_email_period_metric">
			<option value="weeks" <?php $helper->is_selected( $warning_email_period_metric, 'years' ); ?>>Weeks</option>
			<option value="months" <?php $helper->is_selected( $warning_email_period_metric, 'months' ); ?>>Months</option>
			<option value="years" <?php $helper->is_selected( $warning_email_period_metric, 'years' ); ?>>Years</option>
		</select>
	</div>

	<!-- Exemptions -->
	<div class="section">
		<h3>Exemptions</h3>
		<label for="exemptions">Select sites that are exempt from the archival policy</label>
		<select class="is-block" id="site_selector" name="exemptions[]" multiple>
			<?php foreach ( $sites as $site ) : ?>
				<option value="<?php echo esc_attr( $site['id'] ); ?>" selected> <?php echo esc_attr( $site['name'] ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>

	<!-- Site publishing permissions -->
	<div class="boxed">
		<h3>Site publishing permissions</h3>
		<p>Allow administrators to publish their own sites</p>

		<input type="radio" name="allow_admins_to_publish" id="allow_admins_to_publish" value="1" <?php $helper->is_checked( $publishing_permissions, '1' ); ?>>
		<label for="allow_admins_to_publish">Allow administrators to publish sites</label>

		<br/>

		<input type="radio" name="allow_admins_to_publish" id="prevent_admins_to_publish" value="0" <?php $helper->is_checked( $publishing_permissions, '0' ); ?>>
		<label for="prevent_admins_to_publish">Prevent administrators from publishing sites</label>
	</div>

	<!-- Site archival permissions -->
	<div class="boxed">
		<h3>Site archival permissions</h3>
		<p>Allow administrators to archive their own sites</p>

		<input type="radio" name="allow_admins_to_archive" id="allow_admins_to_archive" value="1" <?php $helper->is_checked( $archiving_permissions, '1' ); ?>>
		<label for="allow_admins_to_archive">Allow administrators to archive sites</label>

		<br/>

		<input type="radio" name="allow_admins_to_archive" id="prevent_admins_to_archive" value="0" <?php $helper->is_checked( $archiving_permissions, '0' ); ?>>
		<label for="prevent_admins_to_archive">Prevent administrators from archiving sites</label>
	</div>

	<?php wp_nonce_field( 'archival_policies', 'archival_policies_nonce' ); ?>
	<input type="hidden" name="action" value="edupack_archival_policies">
	<input class="button button-primary" type="submit" value="Save"/>
</form>