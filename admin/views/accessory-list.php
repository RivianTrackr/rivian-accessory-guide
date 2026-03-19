<?php
/**
 * Accessory list view for the admin panel.
 *
 * @package Rivian_Accessory_Guide
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Notices.
$message = isset( $_GET['message'] ) ? sanitize_text_field( $_GET['message'] ) : '';
$notices = array(
	'added'        => array( 'success', 'Accessory added successfully.' ),
	'updated'      => array( 'success', 'Accessory updated successfully.' ),
	'deleted'      => array( 'success', 'Accessory deleted successfully.' ),
	'bulk_deleted' => array( 'success', 'Selected accessories deleted successfully.' ),
	'error'        => array( 'error', 'An error occurred.' ),
);

// Search and filters.
$search          = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
$filter_category = isset( $_GET['filter_category'] ) ? intval( $_GET['filter_category'] ) : 0;
$paged           = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
$per_page        = 20;

// Build query args.
$query_args = array(
	'post_type'      => 'rivian_accessory',
	'post_status'    => 'publish',
	'posts_per_page' => $per_page,
	'paged'          => $paged,
	'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
);

if ( $search ) {
	$query_args['s'] = $search;
}

if ( $filter_category > 0 ) {
	$query_args['tax_query'] = array(
		array(
			'taxonomy' => 'rivian_accessory_category',
			'terms'    => $filter_category,
		),
	);
}

$query       = new WP_Query( $query_args );
$accessories = $query->posts;
$total       = $query->found_posts;
$total_pages = $query->max_num_pages;
wp_reset_postdata();

// Categories for filter dropdown.
$categories = get_terms( array(
	'taxonomy'   => 'rivian_accessory_category',
	'hide_empty' => false,
) );
if ( is_wp_error( $categories ) ) {
	$categories = array();
}
?>

<div class="rag-wrap">

	<?php if ( $message && isset( $notices[ $message ] ) ) : ?>
		<div class="rag-notice rag-notice-<?php echo esc_attr( $notices[ $message ][0] ); ?>">
			<span><?php echo esc_html( $notices[ $message ][1] ); ?></span>
			<button type="button" class="rag-notice-dismiss" aria-label="Dismiss">&times;</button>
		</div>
	<?php endif; ?>

	<div class="rag-page-header">
		<h1 class="rag-page-title">All Accessories</h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=rag-accessory-edit' ) ); ?>" class="rag-page-title-action">Add New</a>
	</div>

	<!-- Search & Filters -->
	<form method="get">
		<input type="hidden" name="page" value="rag-accessories">
		<div class="rag-search-box">
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search accessories...">
			<select name="filter_category" style="min-width:160px;">
				<option value="">All Categories</option>
				<?php foreach ( $categories as $cat ) : ?>
					<option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( $filter_category, $cat->term_id ); ?>><?php echo esc_html( $cat->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="rag-btn rag-btn-secondary">Filter</button>
			<?php if ( $search || $filter_category ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rag-accessories' ) ); ?>" class="rag-btn rag-btn-secondary" style="text-decoration:none;">Clear</a>
			<?php endif; ?>
		</div>
	</form>

	<!-- Table -->
	<form method="post">
		<?php wp_nonce_field( 'rag_bulk_action', 'rag_bulk_nonce' ); ?>

		<div class="rag-card">

			<!-- Table nav (top) -->
			<div class="rag-tablenav rag-tablenav-top">
				<div class="rag-bulk-actions">
					<select name="rag_bulk_action">
						<option value="">Bulk Actions</option>
						<option value="delete">Delete</option>
					</select>
					<button type="submit" class="rag-btn rag-btn-secondary">Apply</button>
				</div>
				<div class="rag-pagination">
					<span class="rag-pagination-count"><?php echo esc_html( $total ); ?> accessory<?php echo 1 !== $total ? 'ies' : ''; ?></span>
					<?php if ( $total_pages > 1 ) : ?>
						<span class="rag-pagination-links">
							<?php if ( $paged > 1 ) : ?>
								<a href="<?php echo esc_url( add_query_arg( 'paged', $paged - 1 ) ); ?>">&lsaquo;</a>
							<?php endif; ?>
							<span class="current-page"><?php echo esc_html( $paged ); ?> of <?php echo esc_html( $total_pages ); ?></span>
							<?php if ( $paged < $total_pages ) : ?>
								<a href="<?php echo esc_url( add_query_arg( 'paged', $paged + 1 ) ); ?>">&rsaquo;</a>
							<?php endif; ?>
						</span>
					<?php endif; ?>
				</div>
			</div>

			<!-- Data Table -->
			<div class="rag-table-wrapper">
				<table class="rag-table">
					<thead>
						<tr>
							<th class="column-cb"><input type="checkbox" id="cb-select-all"></th>
							<th class="column-image">Image</th>
							<th>Title</th>
							<th>Category</th>
							<th>Buy Link</th>
							<th>Date</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $accessories ) ) : ?>
							<tr>
								<td colspan="6">
									<div class="rag-empty-state">
										<span class="dashicons dashicons-car"></span>
										<h3>No accessories found</h3>
										<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=rag-accessory-edit' ) ); ?>">Add your first accessory</a>.</p>
									</div>
								</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $accessories as $accessory ) : ?>
								<?php
								$acc_id       = $accessory->ID;
								$acc_cats     = wp_get_object_terms( $acc_id, 'rivian_accessory_category', array( 'fields' => 'names' ) );
								$acc_cat_name = ! empty( $acc_cats ) && ! is_wp_error( $acc_cats ) ? implode( ', ', $acc_cats ) : '—';
								$acc_link     = get_post_meta( $acc_id, '_rag_buy_link', true );
								?>
								<tr>
									<td class="column-cb">
										<input type="checkbox" name="post_ids[]" value="<?php echo esc_attr( $acc_id ); ?>">
									</td>
									<td class="column-image">
										<?php if ( has_post_thumbnail( $acc_id ) ) : ?>
											<?php echo get_the_post_thumbnail( $acc_id, 'thumbnail', array( 'class' => 'accessory-thumb' ) ); ?>
										<?php else : ?>
											<span class="accessory-thumb-placeholder"></span>
										<?php endif; ?>
									</td>
									<td class="column-primary">
										<strong>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=rag-accessory-edit&id=' . $acc_id ) ); ?>">
												<?php echo esc_html( $accessory->post_title ); ?>
											</a>
										</strong>
										<div class="row-actions">
											<span class="edit">
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=rag-accessory-edit&id=' . $acc_id ) ); ?>">Edit</a> |
											</span>
											<span class="view">
												<a href="<?php echo esc_url( get_permalink( $acc_id ) ); ?>" target="_blank" rel="noopener noreferrer">View</a> |
											</span>
											<span class="delete">
												<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=rag-accessories&action=delete&post_id=' . $acc_id ), 'rag_delete_' . $acc_id ) ); ?>" onclick="return confirm('Delete this accessory?');">Delete</a>
											</span>
										</div>
									</td>
									<td><?php echo esc_html( $acc_cat_name ); ?></td>
									<td>
										<?php if ( $acc_link ) : ?>
											<a href="<?php echo esc_url( $acc_link ); ?>" class="rag-link-url" target="_blank" rel="noopener noreferrer">
												<?php echo esc_html( wp_parse_url( $acc_link, PHP_URL_HOST ) ); ?>
											</a>
										<?php else : ?>
											<span class="rag-link-empty">None</span>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( get_the_date( 'M j, Y', $accessory ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

		</div>
	</form>

</div>
