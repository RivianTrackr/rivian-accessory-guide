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
            'title'    => '',
            'subtitle' => '',
            'limit'    => -1,
        ), $atts, 'rivian_accessories' );

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

        // Track IDs of accessories already displayed in a category.
        $displayed_ids = array();

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

            <?php
            // Detect uncategorized accessories so the category filter can offer an "Other" chip.
            $uncat_probe = new WP_Query( array(
                'post_type'      => 'rivian_accessory',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'rivian_accessory_category',
                        'operator' => 'NOT EXISTS',
                    ),
                ),
            ) );
            $has_uncategorized = $uncat_probe->have_posts();
            wp_reset_postdata();

            // Only worth showing a category filter when there is more than one bucket to choose from.
            $show_category_filter = ! empty( $categories ) && ( count( $categories ) > 1 || $has_uncategorized );

            // Detect whether any accessory has a price tier, so the price filter only shows when useful.
            $price_probe = new WP_Query( array(
                'post_type'      => 'rivian_accessory',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'meta_query'     => array(
                    array(
                        'key'     => '_rag_price_tier',
                        'compare' => 'EXISTS',
                    ),
                ),
            ) );
            $has_price = $price_probe->have_posts();
            wp_reset_postdata();

            $price_labels = array(
                1 => 'Budget — under $50',
                2 => 'Moderate — $50 to $150',
                3 => 'Premium — $150 to $500',
                4 => 'Splurge — $500+',
            );
            ?>

            <?php if ( ! empty( $vehicles ) || $show_category_filter || $has_price ) : ?>
                <button type="button" class="rag-filter-toggle" aria-expanded="false" aria-controls="rag-filters">
                    <svg width="15" height="15" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M2 4h12M4 8h8M6 12h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    <span class="rag-filter-toggle-label">Filters</span>
                    <span class="rag-filter-toggle-count" hidden aria-hidden="true"></span>
                    <svg class="rag-filter-toggle-chevron" width="14" height="14" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="rag-filters" id="rag-filters">
                    <?php if ( ! empty( $vehicles ) ) : ?>
                        <div class="rag-filter-group">
                            <span class="rag-filter-label">Vehicle</span>
                            <div class="rag-filter-bar" data-filter="vehicle" role="group" aria-label="Filter accessories by vehicle">
                                <button type="button" class="rag-filter-chip is-active" data-value="all" aria-pressed="true">All</button>
                                <?php foreach ( $vehicles as $vehicle ) : ?>
                                    <button type="button" class="rag-filter-chip" data-value="<?php echo esc_attr( $vehicle->slug ); ?>" aria-pressed="false"><?php echo esc_html( $vehicle->name ); ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ( $show_category_filter ) : ?>
                        <div class="rag-filter-group">
                            <span class="rag-filter-label">Category</span>
                            <div class="rag-filter-bar" data-filter="category" role="group" aria-label="Filter accessories by category">
                                <button type="button" class="rag-filter-chip is-active" data-value="all" aria-pressed="true">All</button>
                                <?php foreach ( $categories as $category ) : ?>
                                    <button type="button" class="rag-filter-chip" data-value="<?php echo esc_attr( $category->slug ); ?>" aria-pressed="false"><?php echo esc_html( $category->name ); ?></button>
                                <?php endforeach; ?>
                                <?php if ( $has_uncategorized ) : ?>
                                    <button type="button" class="rag-filter-chip" data-value="uncategorized" aria-pressed="false">Other</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ( $has_price ) : ?>
                        <div class="rag-filter-group">
                            <span class="rag-filter-label">Price</span>
                            <div class="rag-filter-bar" data-filter="price" role="group" aria-label="Filter accessories by price">
                                <button type="button" class="rag-filter-chip is-active" data-value="all" aria-pressed="true">All</button>
                                <?php for ( $t = 1; $t <= 4; $t++ ) : ?>
                                    <button type="button" class="rag-filter-chip" data-value="<?php echo esc_attr( $t ); ?>" aria-pressed="false" title="<?php echo esc_attr( $price_labels[ $t ] ); ?>"><?php echo esc_html( str_repeat( '$', $t ) ); ?></button>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $categories ) || $has_uncategorized ) : ?>
                <div class="rag-toolbar">
                    <p class="rag-result-count" aria-live="polite"><span class="rag-count-num"></span> accessories</p>
                    <label class="rag-sort">
                        <span class="rag-sort-label">Sort</span>
                        <select class="rag-sort-select" data-sort>
                            <option value="default">Featured</option>
                            <option value="price-asc">Price: Low to High</option>
                            <option value="price-desc">Price: High to Low</option>
                            <option value="name-asc">Name: A to Z</option>
                        </select>
                    </label>
                </div>
            <?php endif; ?>

            <?php foreach ( $categories as $category ) : ?>
                <?php
                $query = new WP_Query( array(
                    'post_type'      => 'rivian_accessory',
                    'posts_per_page' => intval( $atts['limit'] ),
                    'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
                    'tax_query'      => array(
                        array(
                            'taxonomy' => 'rivian_accessory_category',
                            'terms'    => $category->term_id,
                        ),
                    ),
                ) );

                if ( ! $query->have_posts() ) {
                    wp_reset_postdata();
                    continue;
                }
                ?>
                <div class="rag-section" data-category="<?php echo esc_attr( $category->slug ); ?>">
                    <h2 class="rag-section-title"><?php echo esc_html( $category->name ); ?></h2>
                    <div class="rag-cards">
                        <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                            <?php
                            $displayed_ids[] = get_the_ID();
                            echo self::render_card( get_the_ID() );
                            ?>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php wp_reset_postdata(); ?>
            <?php endforeach; ?>

            <?php
            // Uncategorized accessories.
            $uncategorized_query = new WP_Query( array(
                'post_type'      => 'rivian_accessory',
                'posts_per_page' => intval( $atts['limit'] ),
                'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
                'post__not_in'   => $displayed_ids,
            ) );

            if ( $uncategorized_query->have_posts() ) :
            ?>
                <div class="rag-section" data-category="uncategorized">
                    <h2 class="rag-section-title">Other</h2>
                    <div class="rag-cards">
                        <?php while ( $uncategorized_query->have_posts() ) : $uncategorized_query->the_post(); ?>
                            <?php echo self::render_card( get_the_ID() ); ?>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php wp_reset_postdata(); ?>
            <?php endif; ?>

            <?php if ( empty( $displayed_ids ) && ! $uncategorized_query->have_posts() ) : ?>
                <p class="rag-empty">No accessories found.</p>
            <?php endif; ?>

            <p class="rag-empty rag-filter-empty" hidden>No accessories match these filters.</p>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single accessory card.
     */
    private static function render_card( $post_id ) {
        $buy_link    = get_post_meta( $post_id, '_rag_buy_link', true );
        $vendor      = get_post_meta( $post_id, '_rag_vendor', true );
        $discount    = get_post_meta( $post_id, '_rag_discount', true );
        $price_tier  = (int) get_post_meta( $post_id, '_rag_price_tier', true );
        $title       = get_the_title( $post_id );
        $description = wp_trim_words( get_the_content(), 20, '&hellip;' );
        $has_link    = ! empty( $buy_link );

        // Vehicles this accessory fits.
        $vehicle_terms = wp_get_object_terms( $post_id, 'rivian_accessory_vehicle' );
        if ( is_wp_error( $vehicle_terms ) ) {
            $vehicle_terms = array();
        }
        $vehicle_slugs = wp_list_pluck( $vehicle_terms, 'slug' );

        $tag        = $has_link ? 'a' : 'div';
        $link_attrs = $has_link
            ? ' href="' . esc_url( $buy_link ) . '" target="_blank" rel="noopener noreferrer"'
            : '';

        ob_start();
        ?>
        <<?php echo $tag; ?> class="rag-card"<?php echo $link_attrs; ?> data-vehicles="<?php echo esc_attr( implode( ' ', $vehicle_slugs ) ); ?>" data-price="<?php echo esc_attr( ( $price_tier >= 1 && $price_tier <= 4 ) ? $price_tier : '' ); ?>" data-name="<?php echo esc_attr( $title ); ?>">
            <div class="rag-card-image">
                <?php if ( has_post_thumbnail( $post_id ) ) : ?>
                    <?php echo get_the_post_thumbnail( $post_id, 'thumbnail', array( 'loading' => 'lazy' ) ); ?>
                <?php else : ?>
                    <svg class="rag-card-placeholder" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="56" height="56" rx="6" fill="#1f2228"/>
                        <path d="M20 36l6-8 4 5 6-8 8 11H12z" fill="#3a3e45"/>
                        <circle cx="22" cy="22" r="3" fill="#3a3e45"/>
                    </svg>
                <?php endif; ?>
            </div>
            <div class="rag-card-content">
                <div class="rag-card-heading">
                    <h3 class="rag-card-title"><?php echo esc_html( $title ); ?></h3>
                    <?php if ( $has_link && ! empty( $discount ) ) : ?>
                        <span class="rag-card-discount"><?php echo esc_html( $discount ); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ( ! empty( $vendor ) || ( $price_tier >= 1 && $price_tier <= 4 ) ) : ?>
                    <div class="rag-card-meta">
                        <?php if ( ! empty( $vendor ) ) : ?>
                            <span class="rag-card-vendor"><?php echo esc_html( $vendor ); ?></span>
                        <?php endif; ?>
                        <?php if ( $price_tier >= 1 && $price_tier <= 4 ) : ?>
                            <?php echo self::render_price_tier( $price_tier ); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ( $description ) : ?>
                    <p class="rag-card-desc"><?php echo esc_html( $description ); ?></p>
                <?php endif; ?>
                <?php if ( ! empty( $vehicle_terms ) ) : ?>
                    <div class="rag-card-vehicles">
                        <?php foreach ( $vehicle_terms as $vehicle ) : ?>
                            <span class="rag-vehicle-badge"><?php echo esc_html( $vehicle->name ); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ( $has_link ) : ?>
                <span class="rag-card-arrow">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
            <?php endif; ?>
        </<?php echo $tag; ?>>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the price-tier badge ($ to $$$$).
     *
     * Always prints four glyphs; the first $tier are filled, the rest dimmed.
     *
     * @param int $tier Price tier, 1–4.
     */
    private static function render_price_tier( $tier ) {
        $tier   = max( 1, min( 4, (int) $tier ) );
        $labels = array(
            1 => 'Budget — under $50',
            2 => 'Moderate — $50 to $150',
            3 => 'Premium — $150 to $500',
            4 => 'Splurge — $500+',
        );
        $label = $labels[ $tier ];

        $out  = '<span class="rag-card-price" title="' . esc_attr( $label ) . '" aria-label="' . esc_attr( 'Price range: ' . $label ) . '">';
        for ( $i = 1; $i <= 4; $i++ ) {
            $cls  = $i <= $tier ? 'rag-price-on' : 'rag-price-off';
            $out .= '<span class="' . esc_attr( $cls ) . '" aria-hidden="true">$</span>';
        }
        $out .= '</span>';

        return $out;
    }
}
