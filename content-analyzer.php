<?php
/**
 * Plugin Name: Content Analyzer
 * Description: Analyzes posts for word count and keyword density with frontend display and REST API
 * Version: 1.0.0
 * Author: Darko Sakaliev
 * Text Domain: content-analyzer
 */

namespace ContentAnalyzer;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ContentAnalyzer {
    private static $instance = null;
    private $version = '1.0.0';

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init();
    }

    private function init() {
        add_action('init', [$this, 'initializePlugin']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        add_shortcode('content_analyzer', [$this, 'renderAnalyzerTable']);
    }

    public function initializePlugin() {
        // Initialize plugin functionality
    }

    public function enqueueAssets() {
        wp_enqueue_style(
            'content-analyzer',
            plugins_url('assets/css/style.css', __FILE__),
            [],
            $this->version
        );

        wp_enqueue_script(
            'content-analyzer',
            plugins_url('assets/js/script.js', __FILE__),
            ['jquery'],
            $this->version,
            true
        );

        wp_localize_script('content-analyzer', 'contentAnalyzerData', [
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
            'restUrl' => rest_url('content-analyzer/v1')
        ]);
    }

    public function registerRestRoutes() {
        register_rest_route('content-analyzer/v1', '/analyze', [
            'methods' => 'GET',
            'callback' => [$this, 'getAnalysisData'],
            'permission_callback' => '__return_true',
            'args' => [
                'keyword' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'page' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 1,
                ],
                'per_page' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 10,
                ],
            ],
        ]);
    }

    public function getAnalysisData($request) {
        try {
            $keyword = $request->get_param('keyword');
            $page = (int)$request->get_param('page');
            $per_page = (int)$request->get_param('per_page');

            $analyzer = new ContentAnalysis();
            $data = $analyzer->getAnalyzedPosts($keyword, $page, $per_page);
            
            return new \WP_REST_Response($data, 200);
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function renderAnalyzerTable($atts) {
        $atts = shortcode_atts([
            'keyword' => '',
        ], $atts);

        ob_start();
        ?>
        <div class="content-analyzer-container">
            <input type="text" id="keyword-input" placeholder="Enter keyword" value="<?php echo esc_attr($atts['keyword']); ?>">
            <table class="content-analyzer-table">
                <thead>
                    <tr>
                        <th data-sort="title">Post Title</th>
                        <th data-sort="word_count">Word Count</th>
                        <th data-sort="density">Keyword Density</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be populated via JavaScript -->
                </tbody>
            </table>
            <div class="pagination"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}

class ContentAnalysis {
    public function getAnalyzedPosts($keyword, $page = 1, $per_page = 10) {
        $args = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
        ];

        $query = new \WP_Query($args);
        $posts = [];

        if (!$query->have_posts()) {
            return [
                'posts' => [],
                'total' => 0,
                'pages' => 0
            ];
        }

        foreach ($query->posts as $post) {
            $content = wp_strip_all_tags($post->post_content);
            $word_count = str_word_count(strip_tags($content));
            
            // Calculate keyword density
            $keyword_count = substr_count(strtolower($content), strtolower($keyword));
            $density = $word_count > 0 ? ($keyword_count / $word_count) * 100 : 0;

            $posts[] = [
                'id' => $post->ID,
                'title' => get_the_title($post->ID),
                'word_count' => $word_count,
                'keyword_density' => round($density, 2),
                'url' => get_permalink($post->ID)
            ];
        }

        return [
            'posts' => $posts,
            'total' => $query->found_posts,
            'pages' => ceil($query->found_posts / $per_page)
        ];
    }
}

// Initialize the plugin
ContentAnalyzer::getInstance();
