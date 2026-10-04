<?php
namespace GioContextAi;

class Products {

    public function __construct() {
        add_action( 'init', [ $this, 'register_rewrite_rules' ] );
        add_filter( 'query_vars', [ $this, 'register_query_var' ] );
        // Priority 0: before redirect_canonical(), which would add a trailing slash (301).
        add_action( 'template_redirect', [ $this, 'maybe_render_feed' ], 0 );
    }

    public function register_rewrite_rules() {
        add_rewrite_rule( '^products\.json$', 'index.php?gca_product_feed=1', 'top' );
    }

    public function register_query_var( $vars ) {
        $vars[] = 'gca_product_feed';
        return $vars;
    }

    public function maybe_render_feed() {
        if ( get_query_var( 'gca_product_feed' ) != '1' ) {
            return;
        }

        if ( ! class_exists( 'WooCommerce' ) ) {
            wp_die( esc_html__( 'WooCommerce is required for this product feed.', 'giocontextai' ), 'WooCommerce Required', [ 'response' => 404 ] );
        }

        status_header( 200 );
        header( 'Content-Type: application/json; charset=utf-8' );

        $products_data = [];
        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 100,
        ];

        $query = new \WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $product = wc_get_product( get_the_ID() );

                if ( ! $product ) {
                    continue;
                }

                $products_data[] = [
                    'id'           => (string) $product->get_id(),
                    'sku'          => (string) $product->get_sku(),
                    'title'        => Generator::plain_text( get_the_title() ),
                    'description'  => Generator::plain_text( get_the_excerpt() ),
                    'url'          => (string) esc_url_raw( get_permalink() ),
                    'image'        => (string) wp_get_attachment_url( $product->get_image_id() ),
                    'price'        => (float) $product->get_price(),
                    'currency'     => (string) get_woocommerce_currency(),
                    'availability' => $product->is_in_stock() ? 'in_stock' : 'out_of_stock',
                ];
            }
            wp_reset_postdata();
        }

        echo wp_json_encode( $products_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
        exit;
    }
}