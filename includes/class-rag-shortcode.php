<?php
/**
 * Shortcode for rendering the accessory guide.
 *
 * @package Rivian_Accessory_Guide
 */

defined( 'ABSPATH' ) || exit;

class RAG_Shortcode {

    /**
     * Flag indicating the shortcode was used on this page.
     *
     * @var bool
     */
    public static $enqueued = false;

    /**
     * Register the shortcode.
     */
    public static function register() {
        add_shortcode( 'rivian_accessories', array( __CLASS__, 'render' ) );
    }

    /**
     * Render the shortcode output.
     */
    public static function render( $atts ) {
        self::$enqueued = true;

        $atts = shortcode_atts( array(
            'title'            => '',
            'subtitle'         => '',
            'limit'            => -1,
            'affiliates'          => 'true',
            'affiliates_title'    => 'Affiliate Links & Codes',
            'affiliates_subtitle' => 'auto',
            'affiliates_layout'   => 'compact',
        ), $atts, 'rivian_accessories' );

        // Site-wide affiliate links & promo codes, shown above the filters so
        // readers can find them without digging through cards.
        $show_affiliates = filter_var( $atts['affiliates'], FILTER_VALIDATE_BOOLEAN );
        $affiliates      = $show_affiliates ? RAG_Affiliates::get_all() : array();

        $categories = get_terms( array(
            'taxonomy'   => 'rivian_accessory_category',
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );

        if ( is_wp_error( $categories ) ) {
            $categories = array();
        }

        // Vehicles for the filter bar (only those with accessories attached).
        $vehicles = get_terms( array(
            'taxonomy'   => 'rivian_accessory_vehicle',
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );

        if ( is_wp_error( $vehicles ) ) {
            $vehicles = array();
        }

        ob_start();
        ?>
        <div class="rag-container">

            <?php if ( '' !== $atts['title'] || '' !== $atts['subtitle'] ) : ?>
                <div class="rag-header">
                    <?php if ( '' !== $atts['title'] ) : ?>
                        <h1><?php echo esc_html( $atts['title'] ); ?></h1>
                    <?php endif; ?>
                    <?php if ( '' !== $atts['subtitle'] ) : ?>
                        <p><?php echo esc_html( $atts['subtitle'] ); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $affiliates ) ) : ?>
                <?php if ( 'full' === $atts['affiliates_layout'] ) : ?>
                    <?php echo RAG_Affiliates::render_panel( $affiliates, $atts['affiliates_title'], $atts['affiliates_subtitle'] ); ?>
                <?php else : ?>
                    <?php echo RAG_Affiliates::render_compact_panel( $affiliates ); ?>
                <?php endif; ?>
            <?php endif; ?>

            <?php
            // Every accessory in one flat grid, in the order the old category
            // sections used: categories by name, then uncategorized, each by
            // menu order and title. An accessory in two categories shows once.
            $card_ids = array();
            foreach ( $categories as $category ) {
                $ids = get_posts( array(
                    'post_type'      => 'rivian_accessory',
                    'posts_per_page' => intval( $atts['limit'] ),
                    'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
                    'fields'         => 'ids',
                    'tax_query'      => array(
                        array(
                            'taxonomy' => 'rivian_accessory_category',
                            'terms'    => $category->term_id,
                        ),
                    ),
                ) );
                foreach ( $ids as $id ) {
                    $card_ids[ $id ] = true;
                }
            }

            $uncategorized_ids = get_posts( array(
                'post_type'      => 'rivian_accessory',
                'posts_per_page' => intval( $atts['limit'] ),
                'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
                'fields'         => 'ids',
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'rivian_accessory_category',
                        'operator' => 'NOT EXISTS',
                    ),
                ),
            ) );
            $has_uncategorized = ! empty( $uncategorized_ids );
            foreach ( $uncategorized_ids as $id ) {
                $card_ids[ $id ] = true;
            }
            $card_ids = array_keys( $card_ids );

            // Only worth showing a category filter when there is more than one bucket to choose from.
            $show_category_filter = ! empty( $categories ) && ( count( $categories ) > 1 || $has_uncategorized );
            ?>

            <?php if ( ! empty( $vehicles ) || $show_category_filter ) : ?>
                <div class="rag-filters">
                    <?php if ( ! empty( $vehicles ) ) : ?>
                        <div class="rag-filter-scroll">
                            <div class="rag-filter-bar" data-filter="vehicle" role="group" aria-label="Filter accessories by vehicle">
                                <button type="button" class="rag-filter-chip is-active" data-value="all" aria-pressed="true">All vehicles</button>
                                <?php foreach ( $vehicles as $vehicle ) : ?>
                                    <button type="button" class="rag-filter-chip" data-value="<?php echo esc_attr( $vehicle->slug ); ?>" aria-pressed="false"><?php echo esc_html( $vehicle->name ); ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ( $show_category_filter ) : ?>
                        <div class="rag-filter-scroll">
                            <div class="rag-filter-bar" data-filter="category" role="group" aria-label="Filter accessories by category">
                                <button type="button" class="rag-filter-chip is-active" data-value="all" aria-pressed="true">All categories</button>
                                <?php foreach ( $categories as $category ) : ?>
                                    <button type="button" class="rag-filter-chip" data-value="<?php echo esc_attr( $category->slug ); ?>" aria-pressed="false"><?php echo esc_html( $category->name ); ?></button>
                                <?php endforeach; ?>
                                <?php if ( $has_uncategorized ) : ?>
                                    <button type="button" class="rag-filter-chip" data-value="uncategorized" aria-pressed="false">Other</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $card_ids ) ) : ?>
                <div class="rag-toolbar">
                    <p class="rag-result-count" aria-live="polite"><span class="rag-count-num"><?php echo esc_html( count( $card_ids ) ); ?></span> accessories</p>
                    <label class="rag-sort">
                        <span class="rag-sort-label">Sort</span>
                        <select class="rag-sort-select" data-sort>
                            <option value="default">Featured</option>
                            <option value="price-asc">Price: low to high</option>
                            <option value="price-desc">Price: high to low</option>
                            <option value="name-asc">Name: A to Z</option>
                        </select>
                    </label>
                </div>

                <div class="rag-cards">
                    <?php foreach ( $card_ids as $id ) : ?>
                        <?php echo self::render_card( $id ); ?>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="rag-empty">No accessories found.</p>
            <?php endif; ?>

            <p class="rag-empty rag-filter-empty" hidden>No accessories match these filters.</p>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Price tier labels, shared by the card and its screen-reader text.
     *
     * @var array<int,string>
     */
    const PRICE_RANGES = array(
        1 => 'Under $50',
        2 => '$50 to $150',
        3 => '$150 to $500',
        4 => '$500+',
    );

    /**
     * Render a single accessory card.
     *
     * Image on top, then category and discount, name, vendor and fit, price
     * tier with any promo code, and a Buy cue. The whole card is the link.
     */
    private static function render_card( $post_id ) {
        $buy_link   = get_post_meta( $post_id, '_rag_buy_link', true );
        $vendor     = get_post_meta( $post_id, '_rag_vendor', true );
        $discount   = get_post_meta( $post_id, '_rag_discount', true );
        $promo_code = get_post_meta( $post_id, '_rag_promo_code', true );
        $price_tier = (int) get_post_meta( $post_id, '_rag_price_tier', true );
        $has_tier   = $price_tier >= 1 && $price_tier <= 4;
        $title      = get_the_title( $post_id );
        $has_link   = ! empty( $buy_link );

        $vehicle_terms = wp_get_object_terms( $post_id, 'rivian_accessory_vehicle' );
        if ( is_wp_error( $vehicle_terms ) ) {
            $vehicle_terms = array();
        }
        $category_terms = wp_get_object_terms( $post_id, 'rivian_accessory_category', array( 'orderby' => 'name' ) );
        if ( is_wp_error( $category_terms ) ) {
            $category_terms = array();
        }
        $category_slugs = empty( $category_terms ) ? array( 'uncategorized' ) : wp_list_pluck( $category_terms, 'slug' );
        $category_label = empty( $category_terms ) ? 'Other' : $category_terms[0]->name;

        $meta = array();
        if ( ! empty( $vendor ) ) {
            $meta[] = $vendor;
        }
        if ( ! empty( $vehicle_terms ) ) {
            $meta[] = implode( ', ', wp_list_pluck( $vehicle_terms, 'name' ) );
        }

        $tag        = $has_link ? 'a' : 'div';
        $link_attrs = $has_link
            ? ' href="' . esc_url( $buy_link ) . '" target="_blank" rel="noopener noreferrer sponsored"'
            : '';

        ob_start();
        ?>
        <<?php echo $tag; ?> class="rag-card"<?php echo $link_attrs; ?> data-vehicles="<?php echo esc_attr( implode( ' ', wp_list_pluck( $vehicle_terms, 'slug' ) ) ); ?>" data-category="<?php echo esc_attr( implode( ' ', $category_slugs ) ); ?>" data-price="<?php echo esc_attr( $has_tier ? $price_tier : '' ); ?>" data-name="<?php echo esc_attr( $title ); ?>">
            <div class="rag-card-image">
                <?php if ( has_post_thumbnail( $post_id ) ) : ?>
                    <?php
                    echo get_the_post_thumbnail( $post_id, 'medium_large', array(
                        'loading' => 'lazy',
                        'sizes'   => '(max-width: 600px) 50vw, (max-width: 1024px) 45vw, 400px',
                        'alt'     => $title,
                    ) );
                    ?>
                <?php else : ?>
                    <svg class="rag-card-placeholder" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M3 16l5-5 4 4 3-3 6 6" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                        <circle cx="16" cy="9" r="1.5" fill="currentColor"/>
                    </svg>
                <?php endif; ?>
            </div>
            <div class="rag-card-body">
                <div class="rag-card-top">
                    <span class="rag-card-category"><?php echo esc_html( $category_label ); ?></span>
                    <?php if ( $has_link && ! empty( $discount ) ) : ?>
                        <span class="rag-card-discount"><?php echo esc_html( $discount ); ?></span>
                    <?php endif; ?>
                </div>
                <h3 class="rag-card-title"><?php echo esc_html( $title ); ?></h3>
                <?php if ( ! empty( $meta ) ) : ?>
                    <p class="rag-card-meta"><?php echo esc_html( implode( ' · ', $meta ) ); ?></p>
                <?php endif; ?>
                <?php if ( $has_tier || ! empty( $promo_code ) || $has_link ) : ?>
                    <div class="rag-card-footer">
                        <div class="rag-card-pricing">
                            <?php if ( $has_tier ) : ?>
                                <?php echo self::render_price_tier( $price_tier ); ?>
                            <?php endif; ?>
                            <?php if ( ! empty( $promo_code ) ) : ?>
                                <p class="rag-card-code">Code <span class="rag-card-code-value"><?php echo esc_html( $promo_code ); ?></span></p>
                            <?php endif; ?>
                        </div>
                        <?php if ( $has_link ) : ?>
                            <span class="rag-card-buy" aria-hidden="true">Buy
                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3.5 8.5l5-5M4.5 3.5h4v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </<?php echo $tag; ?>>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the price tier: four $ glyphs, the first $tier filled, then the range.
     *
     * @param int $tier Price tier, 1–4.
     */
    private static function render_price_tier( $tier ) {
        $tier  = max( 1, min( 4, (int) $tier ) );
        $label = self::PRICE_RANGES[ $tier ];

        $out = '<p class="rag-card-price"><span class="rag-price-glyphs" role="img" aria-label="' . esc_attr( 'Price range: ' . $label ) . '">';
        for ( $i = 1; $i <= 4; $i++ ) {
            $out .= '<span class="' . ( $i <= $tier ? 'rag-price-on' : 'rag-price-off' ) . '">$</span>';
        }
        $out .= '</span><span class="rag-price-range" aria-hidden="true">' . esc_html( $label ) . '</span></p>';

        return $out;
    }
}
