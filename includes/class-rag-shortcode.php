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
            'title'    => 'Rivian Accessory Guide',
            'subtitle' => 'Curated accessories and gear for your Rivian.',
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

        // Track IDs of accessories already displayed in a category.
        $displayed_ids = array();

        ob_start();
        ?>
        <div class="rag-container">

            <div class="rag-header">
                <h1><?php echo esc_html( $atts['title'] ); ?></h1>
                <p><?php echo esc_html( $atts['subtitle'] ); ?></p>
            </div>

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
                <div class="rag-section">
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
                <div class="rag-section">
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

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single accessory card.
     */
    private static function render_card( $post_id ) {
        $buy_link    = get_post_meta( $post_id, '_rag_buy_link', true );
        $title       = get_the_title( $post_id );
        $description = wp_trim_words( get_the_content(), 20, '&hellip;' );
        $has_link    = ! empty( $buy_link );

        $tag        = $has_link ? 'a' : 'div';
        $link_attrs = $has_link
            ? ' href="' . esc_url( $buy_link ) . '" target="_blank" rel="noopener noreferrer"'
            : '';

        ob_start();
        ?>
        <<?php echo $tag; ?> class="rag-card"<?php echo $link_attrs; ?>>
            <div class="rag-card-image">
                <?php if ( has_post_thumbnail( $post_id ) ) : ?>
                    <?php echo get_the_post_thumbnail( $post_id, 'thumbnail', array( 'loading' => 'lazy' ) ); ?>
                <?php else : ?>
                    <svg class="rag-card-placeholder" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="56" height="56" rx="6" fill="#1a2a3a"/>
                        <path d="M20 36l6-8 4 5 6-8 8 11H12z" fill="#374151"/>
                        <circle cx="22" cy="22" r="3" fill="#374151"/>
                    </svg>
                <?php endif; ?>
            </div>
            <div class="rag-card-content">
                <h3 class="rag-card-title"><?php echo esc_html( $title ); ?></h3>
                <?php if ( $description ) : ?>
                    <p class="rag-card-desc"><?php echo esc_html( $description ); ?></p>
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
}
