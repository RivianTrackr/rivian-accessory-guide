<?php
/**
 * Add / Edit accessory view for the admin panel.
 *
 * @package Rivian_Accessory_Guide
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$editing_id  = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
$post        = $editing_id ? get_post( $editing_id ) : null;
$is_edit     = (bool) $post && 'rivian_accessory' === ( $post->post_type ?? '' );
$page_title  = $is_edit ? 'Edit Accessory' : 'Add New Accessory';

// Default values.
$v = array(
	'title'       => $is_edit ? $post->post_title : '',
	'description' => $is_edit ? $post->post_content : '',
	'buy_link'    => $is_edit ? get_post_meta( $editing_id, '_rag_buy_link', true ) : '',
	'menu_order'  => $is_edit ? $post->menu_order : 0,
);

// Current category.
$current_cat = 0;
if ( $is_edit ) {
	$terms = wp_get_object_terms( $editing_id, 'rivian_accessory_category', array( 'fields' => 'ids' ) );
	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		$current_cat = $terms[0];
	}
}

// Current vehicles (multiple).
$current_vehicles = array();
if ( $is_edit ) {
	$v_terms = wp_get_object_terms( $editing_id, 'rivian_accessory_vehicle', array( 'fields' => 'ids' ) );
	if ( ! is_wp_error( $v_terms ) ) {
		$current_vehicles = array_map( 'intval', $v_terms );
	}
}

// Current featured image.
$thumbnail_id  = $is_edit ? get_post_thumbnail_id( $editing_id ) : 0;
$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'medium' ) : '';

// All categories for dropdown.
$categories = get_terms( array(
	'taxonomy'   => 'rivian_accessory_category',
	'hide_empty' => false,
) );
if ( is_wp_error( $categories ) ) {
	$categories = array();
}

// All vehicles for the multi-select.
$vehicles = get_terms( array(
	'taxonomy'   => 'rivian_accessory_vehicle',
	'hide_empty' => false,
	'orderby'    => 'name',
	'order'      => 'ASC',
) );
if ( is_wp_error( $vehicles ) ) {
	$vehicles = array();
}

// Notices.
$message = isset( $_GET['message'] ) ? sanitize_text_field( $_GET['message'] ) : '';
?>

<div class="rag-wrap">

	<?php if ( 'error' === $message ) : ?>
		<div class="rag-notice rag-notice-error">
			<span>An error occurred while saving.</span>
			<button type="button" class="rag-notice-dismiss" aria-label="Dismiss">&times;</button>
		</div>
	<?php endif; ?>

	<div class="rag-page-header">
		<h1 class="rag-page-title"><?php echo esc_html( $page_title ); ?></h1>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<?php wp_nonce_field( 'rag_accessory_save', 'rag_accessory_nonce' ); ?>
		<input type="hidden" name="rag_accessory_save" value="1">
		<input type="hidden" name="editing_id" value="<?php echo esc_attr( $editing_id ); ?>">

		<div class="rag-edit-grid">

			<!-- Details -->
			<div class="rag-card">
				<div class="rag-card-header">
					<h2>Details</h2>
				</div>
				<div class="rag-card-body">
					<div class="rag-field-row">
						<div class="rag-field-label-row">
							<label class="rag-field-label" for="accessory_title">Title <span class="rag-badge-required">Required</span></label>
						</div>
						<input type="text" id="accessory_title" name="accessory_title" value="<?php echo esc_attr( $v['title'] ); ?>" required class="rag-input-wide">
					</div>
					<div class="rag-field-row">
						<div class="rag-field-label-row">
							<label class="rag-field-label" for="accessory_description">Description</label>
						</div>
						<p class="rag-field-description">A short description shown on the accessory card.</p>
						<textarea id="accessory_description" name="accessory_description" class="rag-input-wide"><?php echo esc_textarea( $v['description'] ); ?></textarea>
					</div>
					<div class="rag-field-row">
						<div class="rag-field-label-row">
							<label class="rag-field-label" for="accessory_category">Category</label>
						</div>
						<select id="accessory_category" name="accessory_category">
							<option value="">Uncategorized</option>
							<?php foreach ( $categories as $cat ) : ?>
								<option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( $current_cat, $cat->term_id ); ?>><?php echo esc_html( $cat->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="rag-field-row">
						<div class="rag-field-label-row">
							<label class="rag-field-label">Vehicles</label>
						</div>
						<p class="rag-field-description">Select every vehicle this accessory fits. Leave all unchecked if it applies to any Rivian.</p>
						<?php if ( empty( $vehicles ) ) : ?>
							<p class="rag-field-description">No vehicles yet. <a href="<?php echo esc_url( admin_url( 'admin.php?page=rag-vehicles' ) ); ?>">Add a vehicle</a> first.</p>
						<?php else : ?>
							<div class="rag-checkbox-grid">
								<?php foreach ( $vehicles as $vehicle ) : ?>
									<label class="rag-checkbox-item">
										<input type="checkbox" name="accessory_vehicles[]" value="<?php echo esc_attr( $vehicle->term_id ); ?>" <?php checked( in_array( (int) $vehicle->term_id, $current_vehicles, true ) ); ?>>
										<span><?php echo esc_html( $vehicle->name ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Media & Links -->
			<div class="rag-card">
				<div class="rag-card-header">
					<h2>Media &amp; Links</h2>
				</div>
				<div class="rag-card-body">
					<div class="rag-field-row">
						<div class="rag-field-label-row">
							<label class="rag-field-label">Featured Image</label>
						</div>
						<div class="rag-image-picker">
							<input type="hidden" id="featured_image_id" name="featured_image_id" value="<?php echo esc_attr( $thumbnail_id ); ?>">
							<?php if ( $thumbnail_url ) : ?>
								<img id="rag-image-preview" class="rag-image-picker-preview" src="<?php echo esc_url( $thumbnail_url ); ?>" alt="Preview">
							<?php else : ?>
								<img id="rag-image-preview" class="rag-image-picker-preview" src="" alt="Preview" style="display:none;">
							<?php endif; ?>
							<div class="rag-image-picker-actions">
								<button type="button" id="rag-select-image" class="rag-btn rag-btn-secondary">
									<?php echo $thumbnail_url ? 'Change Image' : 'Select Image'; ?>
								</button>
								<button type="button" id="rag-remove-image" class="rag-btn rag-btn-secondary" <?php echo $thumbnail_url ? '' : 'style="display:none;"'; ?>>Remove</button>
							</div>
						</div>
					</div>
					<div class="rag-field-row">
						<div class="rag-field-label-row">
							<label class="rag-field-label" for="buy_link">Buy / Affiliate Link</label>
						</div>
						<p class="rag-field-description">External URL where visitors can purchase this accessory.</p>
						<input type="url" id="buy_link" name="buy_link" value="<?php echo esc_attr( $v['buy_link'] ); ?>" class="rag-input-wide" placeholder="https://example.com/product">
					</div>
					<div class="rag-field-row">
						<div class="rag-field-label-row">
							<label class="rag-field-label" for="menu_order">Display Order</label>
						</div>
						<p class="rag-field-description">Lower numbers appear first. Accessories with the same order are sorted alphabetically.</p>
						<input type="number" id="menu_order" name="menu_order" value="<?php echo esc_attr( $v['menu_order'] ); ?>" min="0" class="rag-input-small">
					</div>
				</div>
			</div>

		</div>

		<div class="rag-footer-actions">
			<button type="submit" class="rag-btn rag-btn-primary"><?php echo $is_edit ? 'Update Accessory' : 'Add Accessory'; ?></button>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=rag-accessories' ) ); ?>" class="rag-btn rag-btn-secondary">Cancel</a>
		</div>
	</form>

</div>
