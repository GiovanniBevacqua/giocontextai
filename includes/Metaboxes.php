<?php
namespace GioContextAi;

class Metaboxes {

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
        add_action( 'save_post', [ $this, 'save_meta_box' ] );

        $this->init_filters();
    }

    private function init_filters() {
        add_filter( 'giocontextai_render_brand_voice', function() {
            return get_option('giocontextai_brand_voice', '');
        });

        add_filter( 'giocontextai_render_ai_instructions', function() {
            return get_option('giocontextai_ai_instructions', '');
        });

        add_filter( 'giocontextai_post_item_output', function( $item_output, $post_id ) {
            if ( get_post_meta( $post_id, '_giocontextai_exclude_ai', true ) === '1' ) {
                return '';
            }
            
            return $item_output;
        }, 10, 2 );
    }

    public function add_meta_box() {
        $included_types = get_option( 'giocontextai_included_post_types', [ 'post', 'page' ] );

        foreach ( (array) $included_types as $post_type ) {
            add_meta_box(
                'giocontextai_meta_box',
                __( 'GioContextAi Settings', 'giocontextai' ),
                [ $this, 'render_meta_box' ],
                $post_type,
                'side',
                'default'
            );
        }
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'giocontextai_save_meta_box', 'giocontextai_meta_box_nonce' );

        $exclude_ai = get_post_meta( $post->ID, '_giocontextai_exclude_ai', true );
        ?>
        <p>
            <label>
                <input type="checkbox" name="giocontextai_exclude_ai" value="1" <?php checked( $exclude_ai, '1' ); ?>>
                <?php esc_html_e( 'Exclude from AI context files', 'giocontextai' ); ?>
            </label>
        </p>
        <?php 
        do_action( 'giocontextai_meta_box_after_exclude', $post );
    }

    public function save_meta_box( $post_id ) {
        if ( ! isset( $_POST['giocontextai_meta_box_nonce'] ) ) {
            return;
        }

        $nonce = sanitize_text_field( wp_unslash( $_POST['giocontextai_meta_box_nonce'] ) );
        if ( ! wp_verify_nonce( $nonce, 'giocontextai_save_meta_box' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        if ( isset( $_POST['giocontextai_exclude_ai'] ) ) {
            update_post_meta( $post_id, '_giocontextai_exclude_ai', '1' );
        } else {
            delete_post_meta( $post_id, '_giocontextai_exclude_ai' );
        }
    }
}