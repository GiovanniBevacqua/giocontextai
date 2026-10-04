<?php
namespace GioContextAi;

class Generator {

    const BATCH_SIZE = 200;

    const CACHE_VERSION = 3;

    public function __construct() {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_action('init', [$this, 'maybe_flush_rewrite_rules'], 99);
        add_action('init', [$this, 'maybe_invalidate_cache'], 20);
        add_filter('query_vars', [$this, 'register_vars']);
        // Priority 0: serve the files before redirect_canonical() (priority 10), which would
        // otherwise add a trailing slash (301 to /llms.txt/) on sites with a static front page.
        add_action('template_redirect', [$this, 'serve_files'], 0);
        add_filter('redirect_canonical', [$this, 'disable_canonical_redirect']);

        add_action('save_post', [$this, 'clear_cache']);
        add_action('deleted_post', [$this, 'clear_cache']);
        add_action('edited_term', [$this, 'clear_cache'], 10, 3);
        add_action('created_term', [$this, 'clear_cache'], 10, 3);
        add_action('delete_term', [$this, 'clear_cache'], 10, 3);
        add_action('update_option_giocontextai_included_post_types', [$this, 'clear_cache']);
    }

    /**
     * Converts a value to clean plain text: no HTML tags, no HTML entities, no line breaks.
     * The generated files are served as text/plain, so HTML escaping would only corrupt them.
     */
    public static function plain_text($value) {
        // Block-level tags become spaces, so "<h2>A</h2><p>B</p>" reads "A B" and not "AB".
        $value = preg_replace('/<\/?(?:p|div|h[1-6]|li|ul|ol|br|hr|tr|td|th|table|blockquote|section|article|figure|figcaption)\b[^>]*>/i', ' ', (string) $value);
        $value = wp_strip_all_tags($value);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    public function disable_canonical_redirect($redirect_url) {
        if (get_query_var('giocontext_type') || get_query_var('gca_product_feed')) {
            return false;
        }
        return $redirect_url;
    }

    public function maybe_invalidate_cache() {
        $stored_version = get_option('giocontextai_cache_version', 0);

        if ((int) $stored_version !== self::CACHE_VERSION) {
            $this->clear_cache();
            update_option('giocontextai_cache_version', self::CACHE_VERSION);
        }
    }

    public function add_rewrite_rules() {
        add_rewrite_rule('^llms\.txt$', 'index.php?giocontext_type=llms', 'top');
        add_rewrite_rule('^llms-full\.txt$', 'index.php?giocontext_type=giocontext_full', 'top');
        add_rewrite_rule('^ai\.txt$', 'index.php?giocontext_type=ai', 'top');
    }

    /**
     * Runs after every rule (including products.json) has been registered, so a flush
     * never persists an incomplete set of rules.
     */
    public function maybe_flush_rewrite_rules() {
        // With plain permalinks no rules are stored: flushing would run on every request.
        if (!get_option('permalink_structure')) return;

        $rules = get_option('rewrite_rules');
        $required = ['^llms\.txt$', '^llms-full\.txt$', '^ai\.txt$', '^products\.json$'];

        foreach ($required as $rule) {
            if (empty($rules) || !isset($rules[$rule])) {
                flush_rewrite_rules(false);
                $this->clear_cache();
                return;
            }
        }
    }

    public function register_vars($vars) {
        $vars[] = 'giocontext_type';
        return $vars;
    }

    public function clear_cache() {
        delete_transient('giocontextai_llms_cache');
        delete_transient('giocontextai_llms_full_cache');
        wp_cache_delete('giocontextai_manually_excluded_posts', 'giocontextai');
        wp_cache_delete('giocontextai_noindex_posts', 'giocontextai');
    }

    public function serve_files() {
        $type = get_query_var('giocontext_type');
        if (!$type) return;

        status_header(200);
        header('Content-Type: text/plain; charset=utf-8');

        if ($type === 'llms') {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content is already sanitized at the individual field level (esc_html/esc_url) in generate_llms_content(); the file is served as text/plain, not HTML, so an additional esc_html() on the entire blob would corrupt the markdown.
            echo $this->get_cached_llms();
        } elseif ($type === 'giocontext_full') {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Same reason as the previous branch.
            echo $this->get_cached_llms_full();
        } else {
            $this->render_ai();
        }
        exit;
    }

    private function get_cached_llms() {
        $cached = get_transient('giocontextai_llms_cache');
        if ($cached !== false) return $cached;

        $content = $this->generate_llms_content(false);
        set_transient('giocontextai_llms_cache', $content, 12 * HOUR_IN_SECONDS);
        return $content;
    }

    private function get_cached_llms_full() {
        $cached = get_transient('giocontextai_llms_full_cache');
        if ($cached !== false) return $cached;

        $content = $this->generate_llms_content(true);
        set_transient('giocontextai_llms_full_cache', $content, 12 * HOUR_IN_SECONDS);
        return $content;
    }

    private function get_excluded_post_ids() {
        $system_excluded_ids = array_filter([
            (int) get_option('woocommerce_shop_page_id'),
            (int) get_option('woocommerce_cart_page_id'),
            (int) get_option('woocommerce_checkout_page_id'),
            (int) get_option('woocommerce_myaccount_page_id'),
            (int) get_option('woocommerce_terms_page_id'),
            (int) get_option('wp_page_for_privacy_policy'),
        ]);

        global $wpdb;

        $cache_key   = 'giocontextai_manually_excluded_posts';
        $cache_group = 'giocontextai';

        $manually_excluded = wp_cache_get($cache_key, $cache_group);

        if (false === $manually_excluded) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $manually_excluded = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s",
                    '_giocontextai_exclude_ai',
                    '1'
                )
            );

            $manually_excluded = array_map('absint', $manually_excluded);
            wp_cache_set($cache_key, $manually_excluded, $cache_group, 12 * HOUR_IN_SECONDS);
        }

        return array_unique(array_merge($system_excluded_ids, $manually_excluded));
    }

    private function get_noindex_post_ids(array $post_ids) {
        if (empty($post_ids)) return [];

        $cache_key   = 'giocontextai_noindex_posts';
        $cache_group = 'giocontextai';

        $cached = wp_cache_get($cache_key, $cache_group);
        if (false !== $cached) {
            return array_values(array_intersect($cached, $post_ids));
        }

        global $wpdb;
        $post_ids = array_map('absint', $post_ids);

        // $ids_sql contains only integers (already passed through absint()), so it is
        // safe to interpolate directly into the IN clause: it is not user input.
        $ids_sql = implode(',', $post_ids);

        $yoast = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- No core API for bulk-querying meta of third-party SEO plugins; no caching layer available for this lookup.
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                 WHERE meta_key = %s AND meta_value = %s AND post_id IN ($ids_sql)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $ids_sql contains only integers sanitized via absint(), no user input.
                '_yoast_wpseo_meta-robots-noindex',
                '1'
            )
        );

        $seopress = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- See note above.
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                 WHERE meta_key = %s AND meta_value = %s AND post_id IN ($ids_sql)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- See note above: $ids_sql is already sanitized.
                '_seopress_robots_noindex',
                'yes'
            )
        );

        $rankmath = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- See note above.
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                 WHERE meta_key = %s AND meta_value LIKE %s AND post_id IN ($ids_sql)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- See note above: $ids_sql is already sanitized.
                'rank_math_robots',
                '%noindex%'
            )
        );

        $noindex = array_unique(array_map('absint', array_merge($yoast, $seopress, $rankmath)));
        wp_cache_set($cache_key, $noindex, $cache_group, 12 * HOUR_IN_SECONDS);

        return $noindex;
    }

    private function generate_llms_content($is_full = false) {
        $types = get_option('giocontextai_included_post_types', ['post', 'page']);

        $output = "# " . self::plain_text(get_bloginfo('name')) . "\n";
        $output .= "> " . self::plain_text(get_bloginfo('description')) . "\n\n";
        $output = apply_filters('giocontextai_before_resources', $output);
        $output .= "## " . __('Resources', 'giocontextai') . "\n";

        $excluded_ids = $this->get_excluded_post_ids();
        $full_content_buffer = "";

        $paged = 1;

        do {
            $args = [
                'post_type'              => $types,
                'posts_per_page'         => self::BATCH_SIZE,
                'paged'                  => $paged,
                'post_status'            => 'publish',
                'has_password'           => false,
                // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Limited list and small by design (WooCommerce/privacy system pages + posts with a single opt-out meta), not an unbounded blocklist.
                'post__not_in'           => $excluded_ids,
                'orderby'                => 'ID',
                'order'                  => 'ASC',
                'no_found_rows'          => true,
                // Meta cache primed per batch: the giocontextai_post_item_output filters read post meta.
                'update_post_meta_cache' => true,
                'update_post_term_cache' => false,
            ];

            $query = new \WP_Query($args);
            $posts = $query->posts;

            if (empty($posts)) break;

            $batch_ids = wp_list_pluck($posts, 'ID');
            $noindex_ids = $this->get_noindex_post_ids($batch_ids);

            foreach ($posts as $post) {
                $post_id = $post->ID;
                if (in_array($post_id, $noindex_ids, true)) continue;

                $title     = self::plain_text(get_the_title($post_id));
                $permalink = esc_url_raw(get_permalink($post_id));

                if ($title === '' || $permalink === '') continue;

                $item_output = apply_filters('giocontextai_post_item_output', "- [{$title}]({$permalink})", $post_id);
                if ($item_output === '' || $item_output === null) continue;

                $output .= $item_output . "\n";

                if ($is_full) {
                    $raw_content = strip_shortcodes($post->post_content);
                    $content     = html_entity_decode(
                        wp_strip_all_tags($raw_content),
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    $full_content_buffer .= "\n\n---\n\n## " . $title . "\n";
                    $full_content_buffer .= "URL: " . $permalink . "\n\n";
                    $full_content_buffer .= trim($content) . "\n";
                }
            }

            $paged++;
            $has_more = count($posts) === self::BATCH_SIZE;

            unset($posts, $query);

        } while ($has_more);

        if ($is_full && !empty($full_content_buffer)) {
            $output .= "\n\n# " . __('Detailed Content', 'giocontextai') . "\n";
            $output .= $full_content_buffer;
        }

        return $output;
    }

    private function render_ai() {
        echo "User-agent: *\nAllow: /\n";
        echo "Sitemap: " . esc_url(home_url('/sitemap.xml')) . "\n";
    }
}