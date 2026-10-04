<?php
namespace GioContextAi;

class Analytics {

    public function __construct() {
        add_filter( 'giocontextai/readiness_score', [ $this, 'calculate_real_score' ], 10, 1 );
        add_action( 'giocontextai_analytics_page_content', [ $this, 'render_analytics_page' ] );

        add_filter( 'giocontextai_analytics_content', [ $this, 'analytics_section' ], 10, 1 );
    }

    public function calculate_real_score( $score = null ) {
        if ( null !== $score ) {
            return $score;
        }

        $audit = $this->run_readiness_audit();
        return $audit['score'];
    }

    public function run_readiness_audit() {
        $total_score = 0;

        $permalinks_ok = (bool) get_option( 'permalink_structure' );
        
        $sitemap_detected = file_exists( ABSPATH . 'sitemap.xml' ) || 
                            file_exists( ABSPATH . 'sitemap_index.xml' ) ||
                            class_exists( 'WPSEO_Options' ) || 
                            class_exists( 'RankMath' ) || 
                            class_exists( 'SEOPress' ) ||
                            get_query_var( 'sitemap' );

        $robots_ok = true;
        $robots_path = ABSPATH . 'robots.txt';
        if ( file_exists( $robots_path ) ) {
            $robots_content = file_get_contents( $robots_path );
            if ( $robots_content !== false && ( stripos( $robots_content, 'Disallow: /' ) !== false && stripos( $robots_content, 'Disallow: /wp-admin/' ) === false ) ) {
                $robots_ok = false;
            }
        }

        $tech_checks = [ $permalinks_ok, $sitemap_detected, $robots_ok ];
        $tech_ratio = array_sum( array_map( 'intval', $tech_checks ) ) / count( $tech_checks );
        $total_score += round( $tech_ratio * 25, 1 );

        $published_posts = wp_count_posts( 'post' );
        $total_posts = isset( $published_posts->publish ) ? intval( $published_posts->publish ) : 0;
        
        $coverage_ratio = min( 1.0, $total_posts / 15 );
        $total_score += round( $coverage_ratio * 35, 1 );

        $freshness_score = 0;
        $latest_posts = get_posts( [
            'numberposts' => 1,
            'post_status' => 'publish',
            'orderby'     => 'date',
            'order'       => 'DESC',
        ] );

        if ( ! empty( $latest_posts ) ) {
            $last_post_date = strtotime( $latest_posts[0]->post_date );
            $days_diff = ( time() - $last_post_date ) / DAY_IN_SECONDS;

            if ( $days_diff <= 30 ) {
                $freshness_score = 15;
            } elseif ( $days_diff <= 90 ) {
                $freshness_score = 10;
            } elseif ( $days_diff <= 180 ) {
                $freshness_score = 5;
            }
        }
        $total_score += $freshness_score;

        $has_name = trim( get_option( 'blogname', '' ) ) !== '';
        $has_desc = trim( get_option( 'blogdescription', '' ) ) !== '';
        $has_icon = (bool) get_option( 'site_icon' );

        $identity_checks = [ $has_name, $has_desc, $has_icon ];
        $identity_ratio = array_sum( array_map( 'intval', $identity_checks ) ) / count( $identity_checks );
        $total_score += round( $identity_ratio * 25, 1 );

        return [
            'score' => (int) round( max( 0, min( 100, $total_score ) ) ),
        ];
    }

    public function render_analytics_page() {
        ?>
        <div class="wrap giocontextai-wrap">
            <h1><?php esc_html_e( 'AI Analytics & Readiness', 'giocontextai' ); ?></h1>
            <?php echo wp_kses_post( apply_filters( 'giocontextai_analytics_content', '' ) ); ?>
        </div>
        <?php
    }

    public function analytics_section( $content = '' ) {
        if ( '' !== $content ) {
            return $content;
        }

        $audit = $this->run_readiness_audit();
        $score = $audit['score'];
        $badge_class = $score >= 80 ? 'giocontextai-badge-success' : 'giocontextai-badge-warning';

        $published_posts = wp_count_posts( 'post' );
        $total_posts = isset( $published_posts->publish ) ? intval( $published_posts->publish ) : 0;

        ob_start();
        ?>
        <div class="giocontextai-analytics-card">
            <div class="giocontextai-score-banner">
                <div>
                    <span class="giocontextai-score-label"><?php esc_html_e( 'AI Readiness Audit Score', 'giocontextai' ); ?></span>
                    <strong class="giocontextai-score-value"><?php echo intval( $score ); ?> / 100</strong>
                </div>
                <div>
                    <span class="giocontextai-badge <?php echo esc_attr( $badge_class ); ?>">
                        <?php echo $score >= 80 ? esc_html__( 'Good', 'giocontextai' ) : esc_html__( 'Standard Setup', 'giocontextai' ); ?>
                    </span>
                </div>
            </div>

            <div class="giocontextai-checks-section">
                <h3 class="giocontextai-checks-title"><?php esc_html_e( 'Site Context Status', 'giocontextai' ); ?></h3>
                <ul class="giocontextai-checks-list">
                    <li>
                        <strong><?php esc_html_e( 'Published Posts Available', 'giocontextai' ); ?>:</strong>
                        <span><?php echo intval( $total_posts ); ?> <?php esc_html_e( 'posts indexed', 'giocontextai' ); ?></span>
                    </li>
                    <li>
                        <strong><?php esc_html_e( 'Site Description Meta', 'giocontextai' ); ?>:</strong>
                        <span><?php echo get_option('blogdescription') ? '<span class="giocontextai-check-pass">✔ Present</span>' : '<span class="giocontextai-check-fail">✖ Missing</span>'; ?></span>
                    </li>
                </ul>
            </div>

            <hr class="giocontextai-separator">

            <div class="giocontextai-files-links">
                <h4><?php esc_html_e( 'AI Context Endpoint', 'giocontextai' ); ?></h4>
                <p><?php esc_html_e( 'Direct link to the ai.txt context file served to AI crawlers:', 'giocontextai' ); ?></p>
                <p class="giocontextai-endpoint-actions">
                    <a href="<?php echo esc_url( home_url( '/ai.txt' ) ); ?>" target="_blank" class="button button-secondary"><?php esc_html_e( 'View /ai.txt', 'giocontextai' ); ?></a>
                    <a href="<?php echo esc_url( home_url( '/llms.txt' ) ); ?>" target="_blank" class="button button-secondary"><?php esc_html_e( 'View /llms.txt', 'giocontextai' ); ?></a>
                    <a href="<?php echo esc_url( home_url( '/llms-full.txt' ) ); ?>" target="_blank" class="button button-secondary"><?php esc_html_e( 'View /llms-full.txt', 'giocontextai' ); ?></a>
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}