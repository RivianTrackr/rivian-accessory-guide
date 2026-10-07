<?php
/**
 * Affiliate links & codes management view for the admin panel.
 *
 * @package Rivian_Accessory_Guide
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Notices.
$message = isset( $_GET['message'] ) ? sanitize_text_field( $_GET['message'] ) : '';
$notices = array(
	'added'   => array( 'success', 'Affiliate link saved.' ),
	'updated' => array( 'success', 'Affiliate link updated.' ),
	'deleted' => array( 'success', 'Affiliate link deleted.' ),
	'error'   => array( 'error', 'Could not save. A name is required, plus a link, a code, or both.' ),
);

// Editing an existing entry?
$editing_id   = isset( $_GET['edit'] ) ? sanitize_key( $_GET['edit'] ) : '';
$editing_item = '' !== $editing_id ? RAG_Affiliates::get( $editing_id ) : null;
if ( ! $editing_item ) {
	$editing_id = '';
}

$f = array(
	'name'  => $editing_item ? $editing_item['name'] : '',
	'url'   => $editing_item ? $editing_item['url'] : '',
	'code'  => $editing_item ? $editing_item['code'] : '',
	'note'  => $editing_item ? $editing_item['note'] : '',
	'order' => $editing_item ? $editing_item['order'] : 0,
);

$affiliates = RAG_Affiliates::get_all();
?>

<div class="rag-wrap">

	<?php if ( $message && isset( $notices[ $message ] ) ) : ?>
		<div class="rag-notice rag-notice-<?php echo esc_attr( $notices[ $message ][0] ); ?>">
			<span><?php echo esc_html( $notices[ $message ][1] ); ?></span>
			<button type="button" class="rag-notice-dismiss" aria-label="Dismiss">&times;</button>
		</div>
	<?php endif; ?>

	<div class="rag-page-header">
		<h1 class="rag-page-title">Affiliate Links &amp; Codes</h1>
	</div>

	<div class="rag-edit-grid">

		<!-- Add / Edit Form -->
		<div class="rag-card">
			<div class="rag-card-header">
				<h2><?php echo $editing_item ? 'Edit Affiliate Link' : 'Add Affiliate Link'; ?></h2>
				<p><?php echo $editing_item ? 'Update this link or code.' : 'Add a vendor link and/or discount code. These show in a panel at the top of your accessory guide so readers can find them without hunting.'; ?></p>
			</div>
			<div class="rag-card-body">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
					<?php wp_nonce_field( 'rag_affiliate_save', 'rag_affiliate_nonce' ); ?>
					<input type="hidden" name="rag_affiliate_save" value="1">
					<input type="hidden" name="editing_id" value="<?php echo esc_attr( $editing_id ); ?>">

					<div class="rag-field-row">
						<div class="rag-field-label-row">
							<label class="rag-field-label" for="affiliate_name">Name <span class="rag-badge-required">Required</span></label>
						</div>
						<p class="rag-field-description">The vendor or brand this link or code is for.</p>
						<input type="text" id="affiliate_name" name="affiliate_name" value="<?php echo esc_attr( $f['name'] ); ?>" required class="rag-input-wide" placeholder="e.g. Weathertech">
					</div>

					<div class="rag-field-row">
						<div class="rag-field-label-row">
							<label class="rag-field-label" for="affiliate_url">Affiliate Link</label>
						</div>
						<p class="rag-field-description">Your tracked URL. Opens in a new tab with a "Shop" button.</p>
						<input type="url" id="affiliate_url" name="affiliate_url" value="<?php echo esc_attr( $f['url'] ); ?>" class="rag-input-wide" placeholder="https://example.com/?ref=riviantrackr">
					</div>

					<div class="rag-field-row">
						<div class="rag-field-label-row">
							<label class="rag-field-label" for="affiliate_code">Promo Code</label>
						</div>
						<p class="rag-field-description">Optional discount code. Readers get a one-click copy button.</p>
						<input type="text" id="affiliate_code" name="affiliate_code" value="<?php echo esc_attr( $f['code'] ); ?>" class="rag-input-wide" placeholder="e.g. RIVIANTRACKR">
					</div>

					<div class="rag-field-row">
						<div class="rag-field-label-row">
							<label class="rag-field-label" for="affiliate_note">Offer</label>
						</div>
						<p class="rag-field-description">Short description of the deal, shown under the name.</p>
						<input type="text" id="affiliate_note" name="affiliate_note" value="<?php echo esc_attr( $f['note'] ); ?>" class="rag-input-wide" placeholder="e.g. 10% off sitewide">
					</div>

					<div class="rag-field-row">
						<div class="rag-field-label-row">
							<label class="rag-field-label" for="affiliate_order">Display Order</label>
						</div>
						<p class="rag-field-description">Lower numbers appear first. Ties are sorted alphabetically.</p>
						<input type="number" id="affiliate_order" name="affiliate_order" value="<?php echo esc_attr( $f['order'] ); ?>" min="0" class="rag-input-small">
					</div>

					<div style="margin-top: 20px; display: flex; gap: 8px;">
						<button type="submit" class="rag-btn rag-btn-primary">
							<?php echo $editing_item ? 'Update Link' : 'Add Link'; ?>
						</button>
						<?php if ( $editing_item ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=rag-affiliates' ) ); ?>" class="rag-btn rag-btn-secondary">Cancel</a>
						<?php endif; ?>
					</div>
				</form>
			</div>
		</div>

		<!-- Existing Links -->
		<div class="rag-card">
			<div class="rag-card-header">
				<h2>Your Links &amp; Codes</h2>
				<p><?php echo esc_html( count( $affiliates ) ); ?> <?php echo count( $affiliates ) !== 1 ? 'entries' : 'entry'; ?> total</p>
			</div>

			<?php if ( empty( $affiliates ) ) : ?>
				<div class="rag-card-body">
					<div class="rag-empty-state" style="padding: 40px 20px;">
						<span class="dashicons dashicons-tag"></span>
						<h3>No affiliate links yet</h3>
						<p>Use the form to add your first link or code.</p>
					</div>
				</div>
			<?php else : ?>
				<div class="rag-table-wrapper">
					<table class="rag-table">
						<thead>
							<tr>
								<th>Name</th>
								<th>Code</th>
								<th>Link</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $affiliates as $item ) : ?>
								<tr>
									<td class="column-primary">
										<strong>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=rag-affiliates&edit=' . $item['id'] ) ); ?>">
												<?php echo esc_html( $item['name'] ); ?>
											</a>
										</strong>
										<?php if ( '' !== $item['note'] ) : ?>
											<p style="margin: 2px 0 0; font-size: 13px; color: var(--rag-text-muted);"><?php echo esc_html( $item['note'] ); ?></p>
										<?php endif; ?>
										<div class="row-actions">
											<span class="edit">
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=rag-affiliates&edit=' . $item['id'] ) ); ?>">Edit</a> |
											</span>
											<span class="delete">
												<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=rag-affiliates&action=delete_affiliate&affiliate_id=' . $item['id'] ), 'rag_delete_affiliate_' . $item['id'] ) ); ?>" onclick="return confirm('Delete this affiliate link?');">Delete</a>
											</span>
										</div>
									</td>
									<td>
										<?php if ( '' !== $item['code'] ) : ?>
											<code style="font-size: 13px; color: var(--rag-text-secondary);"><?php echo esc_html( $item['code'] ); ?></code>
										<?php else : ?>
											<span style="color: var(--rag-text-muted);">&mdash;</span>
										<?php endif; ?>
									</td>
									<td>
										<?php if ( '' !== $item['url'] ) : ?>
											<a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener noreferrer" style="color: var(--rag-action-primary); text-decoration: none; font-weight: 600;">Open &rarr;</a>
										<?php else : ?>
											<span style="color: var(--rag-text-muted);">&mdash;</span>
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
