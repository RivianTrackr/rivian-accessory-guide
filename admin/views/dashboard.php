<?php
/**
 * Dashboard view for the Rivian Accessory Guide admin.
 *
 * @package Rivian_Accessory_Guide
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Gather stats.
$total_accessories = wp_count_posts( 'rivian_accessory' );
$total_published   = intval( $total_accessories->publish ?? 0 );

$categories = get_terms( array(
	'taxonomy'   => 'rivian_accessory_category',
	'hide_empty' => false,
) );
$total_categories = is_wp_error( $categories ) ? 0 : count( $categories );

$vehicles       = get_terms( array(
	'taxonomy'   => 'rivian_accessory_vehicle',
	'hide_empty' => false,
) );
$total_vehicles = is_wp_error( $vehicles ) ? 0 : count( $vehicles );

// Count accessories with buy links.
$with_links_query = new WP_Query( array(
	'post_type'      => 'rivian_accessory',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'meta_query'     => array(
		array(
			'key'     => '_rag_buy_link',
			'compare' => 'EXISTS',
		),
		array(
			'key'     => '_rag_buy_link',
			'value'   => '',
			'compare' => '!=',
		),
	),
) );
$with_links = $with_links_query->found_posts;
wp_reset_postdata();

// Count accessories without thumbnails.
$without_images_query = new WP_Query( array(
	'post_type'      => 'rivian_accessory',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'meta_query'     => array(
		array(
			'key'     => '_thumbnail_id',
			'compare' => 'NOT EXISTS',
		),
	),
) );
$without_images = $without_images_query->found_posts;
wp_reset_postdata();

// Recent accessories.
$recent = get_posts( array(
	'post_type'      => 'rivian_accessory',
	'post_status'    => 'publish',
	'posts_per_page' => 5,
	'orderby'        => 'date',
	'order'          => 'DESC',
) );

// Category breakdown.
$cat_list = array();
if ( ! is_wp_error( $categories ) ) {
	foreach ( $categories as $cat ) {
		$cat_list[] = array(
			'name'  => $cat->name,
			'count' => $cat->count,
		);
	}
	usort( $cat_list, function ( $a, $b ) {
		return $b['count'] - $a['count'];
	} );
}
$max_cat_count = ! empty( $cat_list ) ? $cat_list[0]['count'] : 1;
?>

<div class="rag-wrap">

	<div class="rag-page-header">
		<h1 class="rag-page-title">Accessory Guide</h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=rag-accessory-edit' ) ); ?>" class="rag-page-title-action">Add New</a>
	</div>

	<!-- Stats Grid -->
	<div class="rag-stats-grid">
		<div class="rag-stat-card">
			<div class="rag-stat-value"><?php echo esc_html( $total_published ); ?></div>
			<div class="rag-stat-label">Total Accessories</div>
		</div>
		<div class="rag-stat-card">
			<div class="rag-stat-value"><?php echo esc_html( $total_categories ); ?></div>
			<div class="rag-stat-label">Categories</div>
		</div>
		<div class="rag-stat-card">
			<div class="rag-stat-value"><?php echo esc_html( $total_vehicles ); ?></div>
			<div class="rag-stat-label">Vehicles</div>
		</div>
		<div class="rag-stat-card">
			<div class="rag-stat-value"><?php echo esc_html( $with_links ); ?></div>
			<div class="rag-stat-label">With Buy Links</div>
		</div>
		<div class="rag-stat-card">
			<div class="rag-stat-value"><?php echo esc_html( $without_images ); ?></div>
			<div class="rag-stat-label">Missing Images</div>
		</div>
	</div>

	<!-- Dashboard Grid -->
	<div class="rag-dashboard-grid">

		<!-- Recent Accessories -->
		<div class="rag-card">
			<div class="rag-card-header">
				<h2>Recent Accessories</h2>
			</div>
			<div class="rag-card-body">
				<?php if ( empty( $recent ) ) : ?>
					<p style="color: var(--rag-text-muted);">No accessories yet. <a href="<?php echo esc_url( admin_url( 'admin.php?page=rag-accessory-edit' ) ); ?>">Add your first one</a>.</p>
				<?php else : ?>
					<ul class="rag-mini-list">
						<?php foreach ( $recent as $post ) : ?>
							<li class="rag-mini-list-item">
								<?php if ( has_post_thumbnail( $post->ID ) ) : ?>
									<?php echo get_the_post_thumbnail( $post->ID, 'thumbnail', array( 'class' => 'rag-mini-list-thumb' ) ); ?>
								<?php else : ?>
									<span class="rag-mini-list-thumb-placeholder"></span>
								<?php endif; ?>
								<div class="rag-mini-list-info">
									<div class="rag-mini-list-name">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=rag-accessory-edit&id=' . $post->ID ) ); ?>">
											<?php echo esc_html( $post->post_title ); ?>
										</a>
									</div>
									<div class="rag-mini-list-meta">
										<?php echo esc_html( get_the_date( 'M j, Y', $post ) ); ?>
									</div>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>

		<!-- Category Breakdown -->
		<div class="rag-card">
			<div class="rag-card-header">
				<h2>Categories</h2>
			</div>
			<div class="rag-card-body">
				<?php if ( empty( $cat_list ) ) : ?>
					<p style="color: var(--rag-text-muted);">No categories created yet.</p>
				<?php else : ?>
					<?php foreach ( $cat_list as $cat ) : ?>
						<div class="rag-bar-item">
							<span class="rag-bar-label"><?php echo esc_html( $cat['name'] ); ?></span>
							<span class="rag-bar-track">
								<span class="rag-bar-fill" style="width: <?php echo esc_attr( round( ( $cat['count'] / max( $max_cat_count, 1 ) ) * 100 ) ); ?>%;"></span>
							</span>
							<span class="rag-bar-count"><?php echo esc_html( $cat['count'] ); ?></span>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>

	</div>

	<!-- Shortcode Info -->
	<div class="rag-card">
		<div class="rag-card-header">
			<h2>Shortcode</h2>
			<p>Use the shortcode below to display your accessories on any page or post.</p>
		</div>
		<div class="rag-card-body">
			<code style="display:inline-block;padding:8px 16px;background:var(--rag-bg-light);border-radius:var(--rag-radius-input);font-size:14px;">[rivian_accessories]</code>
		</div>
	</div>

</div>
