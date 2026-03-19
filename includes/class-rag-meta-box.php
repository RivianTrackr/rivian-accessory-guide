<?php
/**
 * Meta box for the accessory buy/affiliate link.
 *
 * @package Rivian_Accessory_Guide
 */

defined( 'ABSPATH' ) || exit;

class RAG_Meta_Box {

    /**
     * Register the meta box.
     */
    public static function add() {
        add_meta_box(
            'rag_buy_link',
            'Buy Link',
            array( __CLASS__, 'render' ),
            'rivian_accessory',
            'side',
            'high'
        );
    }

    /**
     * Render the meta box content.
     */
    public static function render( $post ) {
        $value = get_post_meta( $post->ID, '_rag_buy_link', true );
        wp_nonce_field( 'rag_buy_link_save', 'rag_buy_link_nonce' );
        ?>
        <label for="rag-buy-link">
            Affiliate / Buy URL
        </label>
        <input
            type="url"
            id="rag-buy-link"
            name="rag_buy_link"
            value="<?php echo esc_attr( $value ); ?>"
            placeholder="https://example.com/product"
        />
        <?php
    }

    /**
     * Save the meta box data.
     */
    public static function save( $post_id ) {
        if ( ! isset( $_POST['rag_buy_link_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rag_buy_link_nonce'] ) ), 'rag_buy_link_save' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['rag_buy_link'] ) ) {
            $url = esc_url_raw( wp_unslash( $_POST['rag_buy_link'] ) );
            if ( $url ) {
                update_post_meta( $post_id, '_rag_buy_link', $url );
            } else {
                delete_post_meta( $post_id, '_rag_buy_link' );
            }
        }
    }
}
