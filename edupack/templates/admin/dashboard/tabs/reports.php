<?php
/**
 * Render the site report template.
 *
 * @package Edupack
 */

$helper = new Edupack();

$download_args = array(
	'_wpnonce' => wp_create_nonce( 'edulab-download-nonce' ),
);

// Stale download link.
$download_args['edupack_download'] = 'stale-over-time-stats';

$stale_download = add_query_arg(
	$download_args,
	$helper->full_url( $_SERVER )
);

$download_args['edupack_download'] = 'activated-over-time-stats';

$activated_overtime_download = add_query_arg(
	$download_args,
	$helper->full_url( $_SERVER )
);

$download_args['edupack_download'] = 'current-status-stats';

$current_status_download = add_query_arg(
	$download_args,
	$helper->full_url( $_SERVER )
);

?>

<div id="intro">
	<h2>Site Reporting</h2>
	<p>A series of graphs and charts to help you understand your current sites <br/>and how they're performing.</p>
</div>

<div class="edupack-grid">

	<div class="edupack-metabox small">
		<div class="boxed">
			<div class="edupack-metabox__header">
				<h3>Active & Archived Sites</h3>
				<a target="_blank" id="edupack-current-status-download" class="edupack-is-hidden" download rel="nofollow noopener" href="<?php echo esc_url( $current_status_download ); ?>">
					<img width="16" src="<?php echo esc_url( $helper->get_asset_url( 'download-icon.svg' ) ); ?>" alt="Download icon" title="Download this data-set" />
				</a>
			</div>
			<div class="edupack-metabox__body">
				<div id="edupack-current-status-loader" class="edupack-loader"><div></div><div></div><div></div><div></div></div>
				<canvas class="doughnut edupack-is-hidden" id="edupack-active-archived-chart" width="400" height="190"></canvas>
			</div>
		</div>
	</div>

	<div class="edupack-metabox medium">
		<div class="boxed">
			<div class="edupack-metabox__header">
				<h3>Sites Activated & Archived Over Time</h3>
				<a target="_blank" id="edupack-over-time-download" class="edupack-is-hidden" download rel="nofollow noopener" href="<?php echo esc_url( $activated_overtime_download ); ?>">
					<img width="16" src="<?php echo esc_url( $helper->get_asset_url( 'download-icon.svg' ) ); ?>" alt="Download icon" title="Download this data-set" />
				</a>
			</div>
			<div class="edupack-metabox__body">
				<div id="edupack-over-time-loader" class="edupack-loader"><div></div><div></div><div></div><div></div></div>
				<canvas class="edupack-is-hidden" id="edupack-archived-over-time-chart" width="400" height="190"></canvas>
			</div>
		</div>
	</div>

	<div class="edupack-metabox medium">
		<div class="boxed">
			<div class="edupack-metabox__header">
				<h3>Stale Sites Over Time</h3>
				<a target="_blank" id="edupack-stale-over-time-download" class="edupack-is-hidden" download rel="nofollow noopener" href="<?php echo esc_url( $stale_download ); ?>">
					<img width="16" src="<?php echo esc_url( $helper->get_asset_url( 'download-icon.svg' ) ); ?>" alt="Download icon" title="Download this data-set" />
				</a>
			</div>
			<div class="edupack-metabox__body">
				<div id="edupack-stale-over-time-loader" class="edupack-loader"><div></div><div></div><div></div><div></div></div>
				<canvas class="edupack-is-hidden" id="edupack-stale-over-time-chart" width="400" height="190"></canvas>
			</div>
		</div>
	</div>
</div>
