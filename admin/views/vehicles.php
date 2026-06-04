<?php
/**
 * Vehicles management view for the admin panel.
 *
 * @package Rivian_Accessory_Guide
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Notices.
$message = isset( $_GET['message'] ) ? sanitize_text_field( $_GET['message'] ) : '';
$notices = array(
	'added'   => array( 'success', 'Vehicle created successfully.' ),
	'updated' => array( 'success', 'Vehicle updated successfully.' ),
	'deleted' => array( 'success', 'Vehicle deleted successfully.' ),
	'error'   => array( 'error', 'An error occurred. Make sure the name is not empty.' ),
);

// Editing an existing vehicle?
$editing_id   = isset( $_GET['edit'] ) ? intval( $_GET['edit'] ) : 0;
$editing_term = $editing_id ? get_term( $editing_id, 'rivian_accessory_vehicle' ) : null;
if ( is_wp_error( $editing_term ) ) {
	$editing_term = null;
	$editing_id   = 0;
}

// All vehicles.
$vehicles = get_terms( array(
	'taxonomy'   => 'rivian_accessory_vehicle',
	'hide_empty' => false,
	'orderby'    => 'name',
	'order'      => 'ASC',
) );
if ( is_wp_error( $vehicles ) ) {
	$vehicles = array();
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
		<h1 class="rag-page-title">Vehicles</h1>
	</div>

	<div class="rag-edit-grid">

		<!-- Add / Edit Form -->
		<div class="rag-card">
			<div class="rag-card-header">
				<h2><?php echo $editing_term ? 'Edit Vehicle' : 'Add New Vehicle'; ?></h2>
				<p><?php echo $editing_term ? 'Update this vehicle\'s details.' : 'Create a vehicle (e.g. R1T, R1S, R2) to tag your accessories.'; ?></p>
			</div>
			<div class="rag-card-body">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
					<?php wp_nonce_field( 'rag_vehicle_save', 'rag_vehicle_nonce' ); ?>
					<input type="hidden" name="rag_vehicle_save" value="1">
					<input type="hidden" name="editing_id" value="<?php echo esc_attr( $editing_id ); ?>">

					<div class="rag-field-row">
						<div class="rag-field-label-row">
							<label class="rag-field-label" for="vehicle_name">Name <span class="rag-badge-required">Required</span></label>
						</div>
						<input type="text" id="vehicle_name" name="vehicle_name" value="<?php echo esc_attr( $editing_term ? $editing_term->name : '' ); ?>" required>
					</div>

					<div class="rag-field-row">
						<div class="rag-field-label-row">
							<label class="rag-field-label" for="vehicle_slug">Slug</label>
						</div>
						<p class="rag-field-description">URL-friendly version of the name. Leave blank to auto-generate.</p>
						<input type="text" id="vehicle_slug" name="vehicle_slug" value="<?php echo esc_attr( $editing_term ? $editing_term->slug : '' ); ?>">
					</div>

					<div class="rag-field-row">
						<div class="rag-field-label-row">
							<label class="rag-field-label" for="vehicle_description">Description</label>
						</div>
						<textarea id="vehicle_description" name="vehicle_description" class="rag-input-wide" rows="3"><?php echo esc_textarea( $editing_term ? $editing_term->description : '' ); ?></textarea>
					</div>

					<div style="margin-top: 20px; display: flex; gap: 8px;">
						<button type="submit" class="rag-btn rag-btn-primary">
							<?php echo $editing_term ? 'Update Vehicle' : 'Add Vehicle'; ?>
						</button>
						<?php if ( $editing_term ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=rag-vehicles' ) ); ?>" class="rag-btn rag-btn-secondary">Cancel</a>
						<?php endif; ?>
					</div>
				</form>
			</div>
		</div>

		<!-- Existing Vehicles -->
		<div class="rag-card">
			<div class="rag-card-header">
				<h2>Existing Vehicles</h2>
				<p><?php echo esc_html( count( $vehicles ) ); ?> <?php echo count( $vehicles ) !== 1 ? 'vehicles' : 'vehicle'; ?> total</p>
			</div>

			<?php if ( empty( $vehicles ) ) : ?>
				<div class="rag-card-body">
					<div class="rag-empty-state" style="padding: 40px 20px;">
						<span class="dashicons dashicons-car"></span>
						<h3>No vehicles yet</h3>
						<p>Use the form to add your first vehicle.</p>
					</div>
				</div>
			<?php else : ?>
				<div class="rag-table-wrapper">
					<table class="rag-table">
						<thead>
							<tr>
								<th>Name</th>
								<th>Slug</th>
								<th>Count</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $vehicles as $vehicle ) : ?>
								<tr>
									<td class="column-primary">
										<strong>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=rag-vehicles&edit=' . $vehicle->term_id ) ); ?>">
												<?php echo esc_html( $vehicle->name ); ?>
											</a>
										</strong>
										<?php if ( $vehicle->description ) : ?>
											<p style="margin: 2px 0 0; font-size: 13px; color: var(--rag-text-muted);"><?php echo esc_html( $vehicle->description ); ?></p>
										<?php endif; ?>
										<div class="row-actions">
											<span class="edit">
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=rag-vehicles&edit=' . $vehicle->term_id ) ); ?>">Edit</a> |
											</span>
											<span class="delete">
												<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=rag-vehicles&action=delete_vehicle&term_id=' . $vehicle->term_id ), 'rag_delete_vehicle_' . $vehicle->term_id ) ); ?>" onclick="return confirm('Delete this vehicle? It will be removed from any accessories assigned to it.');">Delete</a>
											</span>
										</div>
									</td>
									<td><code style="font-size: 13px; color: var(--rag-text-secondary);"><?php echo esc_html( $vehicle->slug ); ?></code></td>
									<td>
										<?php if ( $vehicle->count > 0 ) : ?>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=rag-accessories&filter_vehicle=' . $vehicle->term_id ) ); ?>" style="color: var(--rag-action-primary); text-decoration: none; font-weight: 600;">
												<?php echo esc_html( $vehicle->count ); ?>
											</a>
										<?php else : ?>
											<span style="color: var(--rag-text-muted);">0</span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>

	</div>

</div>
