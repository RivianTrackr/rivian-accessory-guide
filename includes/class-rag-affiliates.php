<?php
/**
 * Affiliate links & promo codes.
 *
 * Site-wide partner links and discount codes that are not tied to a single
 * accessory (e.g. "10% off everything at Vendor X with code TRACKR"). They
 * are stored in a single option and rendered as a panel at the top of the
 * accessory guide, or anywhere via the [rivian_affiliate_links] shortcode.
 *
 * @package Rivian_Accessory_Guide
 */

defined( 'ABSPATH' ) || exit;

class RAG_Affiliates {

	/**
	 * Option name holding the list of affiliate entries.
	 */
	const OPTION = 'rag_affiliates';

	/**
	 * Register the standalone shortcode.
	 */
	public static function register() {
		add_shortcode( 'rivian_affiliate_links', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Fetch all affiliate entries, sorted by display order then name.
	 *
	 * @return array[] List of entries with keys: id, name, url, code, note, order.
	 */
	public static function get_all() {
		$items = get_option( self::OPTION, array() );
		if ( ! is_array( $items ) ) {
			return array();
		}

		$items = array_values( array_map( array( __CLASS__, 'normalize' ), $items ) );

		usort( $items, function ( $a, $b ) {
			if ( $a['order'] !== $b['order'] ) {
				return $a['order'] - $b['order'];
			}
			return strcasecmp( $a['name'], $b['name'] );
		} );

		return $items;
	}

	/**
	 * Fetch a single entry by ID.
	 *
	 * @param string $id Entry ID.
	 * @return array|null
	 */
	public static function get( $id ) {
		foreach ( self::get_all() as $item ) {
			if ( $item['id'] === $id ) {
				return $item;
			}
		}
		return null;
	}

	/**
	 * Create or update an entry.
	 *
	 * @param array  $data Raw (unslashed) field values.
	 * @param string $id   Existing entry ID to update, or empty to create.
	 * @return string|WP_Error The saved entry ID.
	 */
	public static function save( $data, $id = '' ) {
		$entry = self::normalize( array_merge( $data, array( 'id' => $id ) ) );

		if ( '' === $entry['name'] ) {
			return new WP_Error( 'rag_affiliate_name', 'Name is required.' );
		}
		if ( '' === $entry['url'] && '' === $entry['code'] ) {
			return new WP_Error( 'rag_affiliate_empty', 'Enter a link, a code, or both.' );
		}

		$items = self::get_all();
		$found = false;

		if ( '' !== $entry['id'] ) {
			foreach ( $items as $i => $item ) {
				if ( $item['id'] === $entry['id'] ) {
					$items[ $i ] = $entry;
					$found       = true;
					break;
				}
			}
		}

		if ( ! $found ) {
			$entry['id'] = self::new_id();
			$items[]     = $entry;
		}

		update_option( self::OPTION, array_values( $items ), false );

		return $entry['id'];
	}

	/**
	 * Delete an entry by ID.
	 *
	 * @param string $id Entry ID.
	 */
	public static function delete( $id ) {
		$items = array_filter( self::get_all(), function ( $item ) use ( $id ) {
			return $item['id'] !== $id;
		} );
		update_option( self::OPTION, array_values( $items ), false );
	}

	/**
	 * Sanitize a raw entry into the canonical shape.
	 *
	 * @param array $raw Raw values.
	 * @return array
	 */
	private static function normalize( $raw ) {
		$raw = is_array( $raw ) ? $raw : array();

		return array(
			'id'    => sanitize_key( $raw['id'] ?? '' ),
			'name'  => sanitize_text_field( $raw['name'] ?? '' ),
			'url'   => esc_url_raw( trim( (string) ( $raw['url'] ?? '' ) ) ),
			'code'  => sanitize_text_field( $raw['code'] ?? '' ),
			'note'  => sanitize_text_field( $raw['note'] ?? '' ),
			'order' => intval( $raw['order'] ?? 0 ),
		);
	}

	/**
	 * Generate a unique entry ID.
	 *
	 * @return string
	 */
	private static function new_id() {
		return 'aff_' . substr( md5( uniqid( '', true ) ), 0, 10 );
	}

	/**
	 * [rivian_affiliate_links] shortcode handler.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'title'    => 'Affiliate Links & Codes',
			'subtitle' => 'auto',
		), $atts, 'rivian_affiliate_links' );

		$items = self::get_all();
		if ( empty( $items ) ) {
			return '';
		}

		RAG_Shortcode::$enqueued = true;

		return '<div class="rag-container rag-container-affiliates">'
			. self::render_panel( $items, $atts['title'], $atts['subtitle'] )
			. '</div>';
	}

	/**
	 * Build the default subtitle: a short how-to, plus the shared code when
	 * most partners take the same one.
	 *
	 * @param array $items Entries from get_all().
	 * @return string HTML (already escaped).
	 */
	private static function auto_subtitle( $items ) {
		$html   = esc_html( 'Tap a code to copy it, then enter it at checkout.' );
		$counts = array();
		foreach ( $items as $item ) {
			if ( '' !== $item['code'] ) {
				$key            = strtoupper( $item['code'] );
				$counts[ $key ] = ( $counts[ $key ] ?? 0 ) + 1;
			}
		}
		if ( ! empty( $counts ) ) {
			arsort( $counts );
			$top = array_key_first( $counts );
			if ( $counts[ $top ] >= 3 && $counts[ $top ] > count( $items ) / 2 ) {
				$html .= ' Most stores take <b>' . esc_html( $top ) . '</b>.';
			}
		}
		return $html;
	}

	/**
	 * Render the affiliate panel markup.
	 *
	 * @param array  $items    Entries from get_all().
	 * @param string $title    Panel heading (empty hides it).
	 * @param string $subtitle Line under the heading: "auto" generates one,
	 *                         empty hides it, anything else is shown as-is.
	 * @return string
	 */
	public static function render_panel( $items, $title = 'Affiliate Links & Codes', $subtitle = 'auto' ) {
		if ( empty( $items ) ) {
			return '';
		}

		$count         = count( $items );
		$subtitle_html = '';
		if ( 'auto' === $subtitle ) {
			$subtitle_html = self::auto_subtitle( $items );
		} elseif ( '' !== $subtitle ) {
			$subtitle_html = esc_html( $subtitle );
		}

		// Collapse behind a "Show all" button on phones only when the list is long enough to matter.
		$collapsible = $count > 5;

		ob_start();
		?>
		<section class="rag-affiliates" aria-label="<?php echo esc_attr( '' !== $title ? $title : 'Affiliate links and codes' ); ?>">
			<?php if ( '' !== $title || '' !== $subtitle_html ) : ?>
				<div class="rag-affiliates-header">
					<div class="rag-affiliates-heading">
						<?php if ( '' !== $title ) : ?>
							<h2 class="rag-affiliates-title">
								<svg width="15" height="15" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
									<path d="M2.5 8.5V3.25c0-.41.34-.75.75-.75H8.5l5 5-5.25 5.25-5.75-5.75z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
									<circle cx="5.75" cy="5.75" r="1" fill="currentColor"/>
								</svg>
								<?php echo esc_html( $title ); ?>
							</h2>
						<?php endif; ?>
						<?php if ( '' !== $subtitle_html ) : ?>
							<p class="rag-affiliates-subtitle"><?php echo $subtitle_html; // Escaped in auto_subtitle() / above. ?></p>
						<?php endif; ?>
					</div>
					<span class="rag-affiliates-count"><?php echo esc_html( $count . ' ' . ( 1 === $count ? 'partner' : 'partners' ) ); ?></span>
				</div>
			<?php endif; ?>
			<ul class="rag-affiliate-list<?php echo $collapsible ? ' is-collapsed' : ''; ?>">
				<?php foreach ( $items as $item ) : ?>
					<?php
					$has_code = '' !== $item['code'];
					$has_url  = '' !== $item['url'];
					$note     = $item['note'];
					if ( '' === $note ) {
						$note = $has_code ? 'Use code at checkout' : 'Affiliate link';
					}
					$initial = function_exists( 'mb_substr' ) ? mb_substr( $item['name'], 0, 1 ) : substr( $item['name'], 0, 1 );
					?>
					<li class="rag-affiliate">
						<span class="rag-affiliate-mono" aria-hidden="true"><?php echo esc_html( strtoupper( $initial ) ); ?></span>
						<div class="rag-affiliate-info">
							<span class="rag-affiliate-name"><?php echo esc_html( $item['name'] ); ?></span>
							<span class="rag-affiliate-note<?php echo '' === $item['note'] ? ' is-default' : ''; ?>"><?php echo esc_html( $note ); ?></span>
						</div>
						<div class="rag-affiliate-actions">
							<?php if ( $has_code ) : ?>
								<button type="button" class="rag-affiliate-code" data-code="<?php echo esc_attr( $item['code'] ); ?>" aria-label="<?php echo esc_attr( 'Copy code ' . $item['code'] ); ?>" title="Copy code">
									<span class="rag-affiliate-code-value"><?php echo esc_html( $item['code'] ); ?></span>
									<span class="rag-affiliate-copied" aria-live="polite"></span>
									<svg class="rag-affiliate-copy-icon" width="13" height="13" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
										<rect x="5.5" y="5.5" width="8" height="8" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
										<path d="M10.5 5.5V3.75A1.25 1.25 0 009.25 2.5h-5.5A1.25 1.25 0 002.5 3.75v5.5a1.25 1.25 0 001.25 1.25H5.5" stroke="currentColor" stroke-width="1.5"/>
									</svg>
								</button>
							<?php endif; ?>
							<?php if ( $has_url ) : ?>
								<a class="rag-affiliate-link<?php echo $has_code ? '' : ' is-labeled'; ?>" href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener noreferrer sponsored" aria-label="<?php echo esc_attr( 'Shop ' . $item['name'] ); ?>" title="<?php echo esc_attr( 'Shop ' . $item['name'] ); ?>">
									<?php if ( ! $has_code ) : ?><span class="rag-affiliate-link-label">Shop</span><?php endif; ?>
									<svg width="14" height="14" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
										<path d="M6.5 3.5H3.5a1 1 0 00-1 1v8a1 1 0 001 1h8a1 1 0 001-1V9.5M9.5 2.5h4v4M13.5 2.5L7.5 8.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
									</svg>
								</a>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( $collapsible ) : ?>
				<button type="button" class="rag-affiliates-more" aria-expanded="false" data-show-all="<?php echo esc_attr( 'Show all ' . $count . ' partners' ); ?>" data-show-fewer="Show fewer">Show all <?php echo esc_html( $count ); ?> partners</button>
			<?php endif; ?>
		</section>
		<?php
		return ob_get_clean();
	}
}
