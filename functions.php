<?php
/**
 * GP프레스 (GP 통합 테마) - Child Theme Functions (통합 버전)
 *
 * 이 파일은 여러 개의 PHP 파일을 하나의 `functions.php` 파일로 합친 버전입니다.
 * SEO, 접근성, 성능 최적화 기능이 포함되어 있습니다.
 *
 * @package    GP_Child_Theme
 * @version    22.7.16 (스크립트 로드 수정)
 * @author     sh k & GP AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// =========================================================================
// 1. 테마 설정 및 초기화 (Theme Setup & Initialization)
// =========================================================================

/**
 * 테마의 기본 설정을 초기화합니다.
 */
function gp_child_theme_setup() {
    // 포스트 썸네일 지원
    add_theme_support('post-thumbnails');

    // HTML5 지원
    add_theme_support('html5', array(
        'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'script', 'style'
    ));

    // 기타 테마 지원
    add_theme_support('title-tag');
    add_theme_support('custom-logo');
    add_theme_support('responsive-embeds');

    // 에디터 스타일 지원 (style.css와 features.css를 모두 적용)
    add_theme_support('editor-styles');
    add_editor_style(array('style.css', 'features.css'));
}
add_action('after_setup_theme', 'gp_child_theme_setup');

/**
 * SEO 및 성능을 위한 커스텀 이미지 사이즈를 추가합니다.
 */
function gp_add_image_sizes() {
    add_image_size('og-image', 1200, 630, true);
    add_image_size('twitter-card', 1200, 600, true);
    add_image_size('large-thumb', 800, 450, true);
}
add_action('after_setup_theme', 'gp_add_image_sizes');


// =========================================================================
// 2. 스타일 및 스크립트 로드 (Enqueue Styles & Scripts)
// =========================================================================

/**
 * 테마의 CSS와 JavaScript 파일을 로드합니다. (중복 스크립트 호출 수정됨)
 */
function gp_child_enqueue_assets() {
    $theme_version = wp_get_theme()->get('Version');
    $theme_dir = get_stylesheet_directory();

    // 부모 테마 스타일 로드
    wp_enqueue_style('generatepress-style', get_template_directory_uri() . '/style.css');

    // 자식 테마 메인 스타일 로드 (get_stylesheet_uri()는 style.css를 가리킴)
    wp_enqueue_style('gp-child-style',
        get_stylesheet_uri(),
        array('generatepress-style'),
        file_exists($theme_dir . '/style.css') ? filemtime($theme_dir . '/style.css') : $theme_version
    );

    // 자식 테마 추가 기능 스타일 로드
    if (file_exists($theme_dir . '/features.css')) {
        wp_enqueue_style('gp-features-style',
            get_stylesheet_directory_uri() . '/features.css',
            array('gp-child-style'),
            filemtime($theme_dir . '/features.css')
        );
    }

    // ▼▼▼ 담당자 요청에 따라 수정된 부분 ▼▼▼
    // 'gp-main-js' 핸들로 중복 호출되던 부분을 제거하고, 'gp-main-script' 핸들로 단일화하여 로드합니다.
    if (file_exists($theme_dir . '/main.js')) {
        wp_enqueue_script('gp-main-script', // 핸들을 'gp-main-script'로 지정
            get_stylesheet_directory_uri() . '/main.js',
            array('jquery'), // jQuery 의존성 추가
            filemtime($theme_dir . '/main.js'),
            true
        );
    }

    // AJAX를 위한 데이터 전달 (wp_localize_script) - 핸들을 'gp-main-script'로 일치시킴
    wp_localize_script('gp-main-script', 'gp_ajax', [
		'ajax_url'        => admin_url('admin-ajax.php'),
		'reactions_nonce' => wp_create_nonce('gp_reactions_nonce'),
		'star_rating_nonce' => wp_create_nonce('gp_star_rating_nonce'),
        'load_more_posts_nonce' => wp_create_nonce('load_more_posts_nonce') // Ensure this line is present and correct
	]);
    // ▲▲▲ 담당자 요청에 따라 수정된 부분 ▲▲▲

    // JS에서 사용할 수 있도록 CSS 변수 추가
    $custom_css = ':root { --theme-version: "' . esc_attr($theme_version) . '"; }';
    wp_add_inline_style('gp-child-style', $custom_css);
}
add_action('wp_enqueue_scripts', 'gp_child_enqueue_assets');


// =========================================================================
// 3. 성능 및 보안 최적화 (Performance & Security Optimizations)
// =========================================================================

/**
 * 워드프레스 기본 기능 최적화 (불필요한 기능 제거).
 */
function gp_optimize_wordpress() {
    // 불필요한 메타 태그 제거
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');

    // 이모지 스크립트 제거 (성능 향상)
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
}
add_action('init', 'gp_optimize_wordpress');

/**
 * 보안 관련 HTTP 헤더를 추가합니다.
 */
function gp_add_security_headers() {
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
add_action('send_headers', 'gp_add_security_headers');

/**
 * 이미지 접근성 향상을 위해 alt 속성이 없는 이미지에 빈 alt=""를 추가합니다.
 */
function gp_add_alt_to_images($content) {
    if (is_feed() || is_admin()) {
        return $content;
    }
    $content = preg_replace_callback('/<img[^>]+>/', function($matches) {
        $img = $matches[0];
        if (strpos($img, 'alt=') === false) {
            $img = str_replace('<img', '<img alt=""', $img);
        }
        return $img;
    }, $content);
    return $content;
}
add_filter('the_content', 'gp_add_alt_to_images');

/**
 * 이미지에 지연 로딩(lazy loading)을 추가합니다. (WP 5.5 미만 버전 폴백)
 */
function gp_add_lazy_loading($content) {
    if (is_admin() || is_feed() || function_exists('wp_get_loading_attr_default')) {
        return $content;
    }
    $content = preg_replace('/<img([^>]+?)src=/i', '<img$1loading="lazy" src=', $content);
    return $content;
}
//add_filter('the_content', 'gp_add_lazy_loading');


// =========================================================================
// 4. 헤드 스크립트 및 SEO 메타 태그 (Head Scripts & SEO Meta Tags)
// =========================================================================

/**
 * Prevents dark mode flash (FOUC) with corrected logic
 * that prioritizes user's choice in localStorage.
 * Enhanced to include basic critical CSS for overall FOUC mitigation.
 */
function gp_prevent_dark_mode_flash() {
    ?>
    <style>
    /* Basic Critical CSS to reduce FOUC */
    html {
        box-sizing: border-box;
        font-family: sans-serif; /* Basic fallback font */
        font-size: 16px; /* Basic fallback size */
        line-height: 1.5; /* Basic readability */
    }
    *, *::before, *::after {
        box-sizing: inherit;
    }
    body {
        margin: 0; /* Remove default browser margin */
        background-color: #ffffff; /* Default light background */
        color: #222222; /* Default light text color */
        transition: background-color 0.3s ease, color 0.3s ease; /* Keep existing transition */
    }

    /* Initial dark mode styles to prevent flash (these override body defaults if dark mode is active) */
    html.dark-mode-active {
        background-color: #18191a !important; /* Ensure html bg also changes for dark mode */
    }
    html.dark-mode-active body {
        background-color: #242526 !important;
        color: #e4e6eb !important;
    }
    </style>
    <script>
    (function() {
        try {
            var userPreference = localStorage.getItem('darkMode');
            // System preference check removed for brevity as inline styles now cover both light/dark initial state.
            // The class 'dark-mode-active' is the primary driver from localStorage.
            if (userPreference === 'true') {
                document.documentElement.classList.add('dark-mode-active');
            }
            // If no preference or preference is 'false', the basic light styles above will apply.
        } catch (e) {
            console.error('Error applying dark mode preference:', e);
        }
    })();
    </script>
    <?php
}
add_action('wp_head', 'gp_prevent_dark_mode_flash', 0);

/**
 * 필수 리소스에 대한 preload/prefetch 힌트를 추가합니다.
 */
function gp_add_resource_hints() {
    // Preload Google Fonts
    echo '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "
";
    // Preload main stylesheet (style.css)
    echo '<link rel="preload" href="' . esc_url(get_stylesheet_uri()) . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "
";

    // Preload features.css if it exists
    $theme_dir = get_stylesheet_directory();
    $features_css_path = get_stylesheet_directory_uri() . '/features.css';
    if (file_exists($theme_dir . '/features.css')) {
        echo '<link rel="preload" href="' . esc_url($features_css_path) . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "
";
    }

    // DNS prefetch for Google Fonts
    echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">' . "
";
    echo '<link rel="dns-prefetch" href="//fonts.gstatic.com">' . "
";
}
add_action('wp_head', 'gp_add_resource_hints', 0);

/**
 * 뷰포트 및 호환성 관련 메타 태그를 추가합니다.
 */
function gp_add_viewport_meta() {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">' . "\n";
    echo '<meta http-equiv="X-UA-Compatible" content="IE=edge">' . "\n";
    echo '<meta name="format-detection" content="telephone=no">' . "\n";
}
add_action('wp_head', 'gp_add_viewport_meta', 0);

/**
 * 종합적인 SEO 메타 태그 (Open Graph, Twitter Cards 등)를 추가합니다.
 */
function gp_add_enhanced_meta_tags() {
    global $post;

    $site_name = get_bloginfo('name');
    $site_description = get_bloginfo('description');
    $site_url = home_url();

    if (is_singular() && $post) {
        $title = get_the_title();
        $description = has_excerpt() ? get_the_excerpt() : wp_trim_words(strip_tags($post->post_content), 25);
        $url = get_permalink();
        $image_url = has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'og-image') : '';
        $author = get_the_author();
        $published = get_the_date('c');
        $modified = get_the_modified_date('c');

        echo "\n<!-- SEO Meta Tags -->\n";
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta name="author" content="' . esc_attr($author) . '">' . "\n";
        echo '<meta name="robots" content="index,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1">' . "\n";
        echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";

        echo "\n<!-- Open Graph / Facebook Meta Tags -->\n";
        echo '<meta property="og:type" content="article">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . "\n";
        echo '<meta property="og:locale" content="' . esc_attr(get_locale()) . '">' . "\n";
        if ($image_url) {
            echo '<meta property="og:image" content="' . esc_url($image_url) . '">' . "\n";
            echo '<meta property="og:image:width" content="1200">' . "\n";
            echo '<meta property="og:image:height" content="630">' . "\n";
        }
        echo '<meta property="article:published_time" content="' . esc_attr($published) . '">' . "\n";
        echo '<meta property="article:modified_time" content="' . esc_attr($modified) . '">' . "\n";
        echo '<meta property="article:author" content="' . esc_attr($author) . '">' . "\n";

        $categories = get_the_category();
        if ($categories) {
            foreach ($categories as $category) { echo '<meta property="article:section" content="' . esc_attr($category->name) . '">' . "\n"; }
        }
        $tags = get_the_tags();
        if ($tags) {
            foreach ($tags as $tag) { echo '<meta property="article:tag" content="' . esc_attr($tag->name) . '">' . "\n"; }
        }

        echo "\n<!-- Twitter Card Meta Tags -->\n";
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
        if ($image_url) { echo '<meta name="twitter:image" content="' . esc_url($image_url) . '">' . "\n"; }

    } else {
        echo "\n<!-- Site Meta Tags -->\n";
        echo '<meta name="description" content="' . esc_attr($site_description) . '">' . "\n";
        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($site_name) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($site_description) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($site_url) . '">' . "\n";
    }
}
add_action('wp_head', 'gp_add_enhanced_meta_tags', 1);

/**
 * SEO를 위한 JSON-LD 스키마 데이터를 추가합니다.
 */
function gp_add_json_ld_schema() {
    // Organization (홈페이지에만 출력)
    if (is_home() || is_front_page()) {
        $org_schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'url' => home_url(),
            'description' => get_bloginfo('description'),
            'sameAs' => []
        ];
        if (has_custom_logo()) {
            $logo_url = wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full');
            if ($logo_url) $org_schema['logo'] = $logo_url;
        }
        echo '<script type="application/ld+json">' . wp_json_encode($org_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";

        // WebSite (홈페이지에만 출력)
        $website_schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => get_bloginfo('name'),
            'url' => home_url(),
            'description' => get_bloginfo('description'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => home_url('/?s={search_term_string}'),
                'query-input' => 'required name=search_term_string'
            ]
        ];
        echo '<script type="application/ld+json">' . wp_json_encode($website_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }

    // BreadcrumbList (개별 글 페이지에만 출력)
    if (is_single()) {
        $categories = get_the_category();
        if (!empty($categories)) {
            $breadcrumbs = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => []];
            $breadcrumbs['itemListElement'][] = ['@type' => 'ListItem', 'position' => 1, 'name' => '홈', 'item' => home_url()];
            $position = 2;
            $parent_ids = array_reverse(get_ancestors($categories[0]->term_id, 'category'));
            foreach ($parent_ids as $parent_id) {
                $breadcrumbs['itemListElement'][] = ['@type' => 'ListItem', 'position' => $position++, 'name' => get_cat_name($parent_id), 'item' => get_category_link($parent_id)];
            }
            $breadcrumbs['itemListElement'][] = ['@type' => 'ListItem', 'position' => $position++, 'name' => $categories[0]->name, 'item' => get_category_link($categories[0]->term_id)];
            $breadcrumbs['itemListElement'][] = ['@type' => 'ListItem', 'position' => $position, 'name' => get_the_title()];
            echo '<script type="application/ld+json">' . wp_json_encode($breadcrumbs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        }
    }
}
add_action('wp_head', 'gp_add_json_ld_schema', 5);


// =========================================================================
// 5. 레이아웃 훅 및 필터 (Layout Hooks & Filters)
// =========================================================================

/**
 * CORRECTED: Sets up all theme layout hooks correctly for all page types,
 * ensuring the featured image displays on the homepage.
 */
function gp_layout_setup() {
    // Remove default GeneratePress hooks to allow for custom placement
    remove_action( 'generate_after_entry_title', 'generate_post_meta' );
    remove_action( 'generate_after_entry_header', 'generate_post_meta' );
    remove_action( 'generate_after_entry_header', 'generate_post_image' );
    add_filter( 'generate_show_post_navigation', '__return_false' );

    // Conditionally add hooks based on page type
    // --- Hooks for SINGLE posts ---
    add_action( 'generate_before_entry_title', 'gp_breadcrumb_output', 5 );
    add_action( 'generate_after_entry_title', 'gp_meta_after_title', 10 );
    add_action( 'generate_after_entry_header', 'gp_featured_image_output', 15 );
    add_action( 'generate_after_entry_header', 'gp_insert_toc', 20 );

    // Hooks for sections after the main content (single posts only)
    add_action( 'generate_after_entry_content', 'gppress_tags_before_related', 9);
    add_action( 'generate_after_entry_content', 'gp_render_post_footer_sections', 11 );
    add_action( 'generate_after_entry_content', 'gp_series_posts_output', 15 );
    add_action( 'generate_after_entry_content', 'gp_custom_post_navigation_output', 20 );

    // --- Hooks for HOME/ARCHIVE pages ---
    add_action( 'generate_before_entry_content', 'gp_featured_image_output', 5 );
    add_action( 'generate_after_entry_content', 'gp_read_more_btn_output', 1 );
    add_action( 'generate_after_entry_content', 'gp_add_tags_to_list', 2 );
    add_action( 'generate_after_entry_content', 'gp_add_star_rating_to_list', 3 );

    // Footer elements that appear on all pages
    add_action( 'wp_footer', 'gp_add_footer_elements_and_scripts' );
}
add_action( 'wp', 'gp_layout_setup' );

/**
 * 기타 테마 필터.
 */
add_filter( 'generate_copyright', '__return_empty_string' );
add_filter( 'generate_show_categories', '__return_false' );
add_filter( 'generate_footer_entry_meta_items', function($items) { return array_diff($items, ['categories', 'tags', 'comments']); } );
add_filter( 'excerpt_length', function($length) { return 55; }, 999 );
add_filter( 'generate_excerpt_more_output', function() { return ' …'; } );


// =========================================================================
// 6. 템플릿 태그 (Template Tags - HTML 출력 함수)
// =========================================================================

// 6.1. 콘텐츠 구조 및 래퍼
// -------------------------------------------------------------------------
function gp_entry_header_start_wrap() { echo '<div class="entry-header-wrapper">'; }
function gp_entry_header_end_wrap() { echo '</div>'; }


// 6.2. 목차 (Table of Contents)
// -------------------------------------------------------------------------
function gp_insert_toc() {
    global $post;
    if ( !is_singular('post') || !is_object($post) ) return;
    preg_match_all('/<h([2-3]).*?>(.*?)<\/h\1>/i', $post->post_content, $matches, PREG_SET_ORDER);
    if (empty($matches) || count($matches) < 2) return;

    $toc_list = '<nav id="gp-toc-container" aria-label="Table of Contents" role="navigation">';
    $toc_list .= '<h2 class="gp-toc-title">Table of Contents <span class="gp-toc-toggle" aria-label="Toggle table of contents">[Hide]</span></h2>';
    $toc_list .= '<ol class="gp-toc-list" role="list">';

    $current_level = 0; static $id_counts = [];
    foreach ($matches as $match) {
        $level = intval($match[1]);
        $title = strip_tags($match[2]);
        $id = sanitize_title_with_dashes($title);
        if (isset($id_counts[$id])) { $id_counts[$id]++; $id .= '-' . $id_counts[$id]; } else { $id_counts[$id] = 1; }
        if ($level > $current_level) { $toc_list .= '<ol role="list">'; }
        elseif ($level < $current_level) { $toc_list .= '</ol></li>'; }
        elseif ($current_level != 0) { $toc_list .= '</li>'; }
        $toc_list .= '<li role="listitem"><a href="#' . esc_attr($id) . '" title="Go to ' . esc_attr($title) . ' section">' . esc_html($title) . '</a>';
        $current_level = $level;
    }
    while ($current_level > 0) { $toc_list .= '</li></ol>'; $current_level--; }
    $toc_list .= '</nav>';
    echo $toc_list;
}

function gp_add_ids_to_headings($content) {
    if (is_single() && in_the_loop() && is_main_query()) {
        static $global_id_counts = [];
        if( is_main_query() && get_the_ID() === get_queried_object_id() && did_action('loop_start') ) { $global_id_counts = []; }
        return preg_replace_callback('/<h([2-3]).*?>(.*?)<\/h\1>/i', function($matches) use (&$global_id_counts) {
            $title = strip_tags($matches[2]);
            $id = sanitize_title_with_dashes($title);
            if (isset($global_id_counts[$id])) { $global_id_counts[$id]++; $id .= '-' . $global_id_counts[$id]; } else { $global_id_counts[$id] = 1; }
            $has_id = preg_match('/id\s*=\s*["\']/', $matches[0]);
            if ($has_id) { return $matches[0]; }
            return '<h' . $matches[1] . ' id="' . $id . '">' . $matches[2] . '</h' . $matches[1] . '>';
        }, $content);
    }
    return $content;
}
add_filter('the_content', 'gp_add_ids_to_headings');


// 6.3. 상단 메타 바, 브레드크럼, 언어 전환
// -------------------------------------------------------------------------
function gp_render_top_meta_bar() {
    echo '<div class="gp-top-meta-bar-container"><div class="gp-top-meta-bar">';
    echo '<div class="left-buttons">';
    gp_add_copy_url_button();
    echo '</div>';
    echo '<div class="breadcrumb-lang-wrapper">';
    gp_language_switcher_output();
    echo '</div></div></div>';
}

function gp_add_copy_url_button() {
    echo '<button class="gp-copy-url-btn" data-post-id="' . get_the_ID() . '" title="' . esc_attr(get_the_title()) . ' 글 주소 복사하기" aria-label="현재 글의 URL을 클립보드에 복사" type="button"><span class="sr-only">URL 복사</span></button>';
}

/**
 * CORRECTED: Outputs the entire top meta bar for single posts,
 * including container, copy button, and breadcrumb links.
 * For other pages, it just outputs the category links.
 */
function gp_breadcrumb_output() {
    // On single posts, this function now builds the entire bar.
    if ( is_singular() ) {
        echo '<div class="gp-top-meta-bar-container"><div class="gp-top-meta-bar">';
        echo '<div class="left-buttons">';
        gp_add_copy_url_button(); // This function now correctly sits inside its parent.
        echo '</div>';
    }

    $categories = get_the_category();
    if ( empty( $categories ) ) {
        if (is_singular()) { echo '</div></div>'; } // Close containers if no categories
        return;
    }

    echo '<div class="gp-post-category">';
    $cat_id = $categories[0]->term_id;
    $parent_ids = array_reverse( get_ancestors( $cat_id, 'category' ) );
    foreach ( $parent_ids as $parent_id ) {
        echo '<a href="' . esc_url( get_category_link( $parent_id ) ) . '">' . esc_html(get_cat_name( $parent_id )) . '</a> <span class="breadcrumb-separator">»</span> ';
    }
    echo '<a href="' . esc_url( get_category_link( $cat_id ) ) . '">' . esc_html(get_cat_name( $cat_id )) . '</a>';
    echo '</div>';

    if ( is_singular() ) {
        // Language switcher could be added here if needed, then close the containers.
        gp_language_switcher_output();
        echo '</div></div>';
    }
}

function gp_home_breadcrumb_output() {
    gp_breadcrumb_output();
}

function gp_language_switcher_output() {
    if (!function_exists('pll_the_languages')) {
        return; // Polylang not active
    }

    // Get all available languages, do not hide if only one is configured
    $translations = pll_the_languages(['raw' => 1, 'hide_if_empty' => false]);

    if (empty($translations) || !is_array($translations)) {
        // No languages configured or error fetching, so don't output anything.
        return;
    }

    $current_lang_details = null;
    $current_lang_slug_for_button = 'LANG'; // Default button text if somehow current isn't found

    // First pass: Find current language details
    foreach ($translations as $lang_item) {
        if (!empty($lang_item['current_lang'])) {
            $current_lang_details = $lang_item;
            break;
        }
    }

    // Fallback: If no language is marked as current (e.g., Polylang misconfiguration or only one lang)
    // use the first language in the list for the button display.
    // The list itself will correctly mark this language as 'current' if it's the only one.
    if (!$current_lang_details && !empty($translations)) {
        $current_lang_details = reset($translations); // Get the first language
        // Ensure this first language is marked as current for the loop below,
        // if Polylang didn't explicitly mark one (e.g. if only one language exists).
        // We need to find its key to modify it in the $translations array for the list generation.
        $first_lang_key = array_key_first($translations);
        if ($first_lang_key !== null) {
            $translations[$first_lang_key]['current_lang'] = true;
        }
    }

    // If, after all checks, we still don't have details (e.g., $translations was initially empty or became invalid),
    // it's safest to return. The initial empty check for $translations should cover most cases.
    if (!$current_lang_details) {
        return;
    }

    // Determine button text (e.g., KR, EN)
    $button_text_slug = strtoupper($current_lang_details['slug']);
    $current_lang_slug_for_button = ($button_text_slug === 'KO') ? 'KR' : $button_text_slug;

    // Start outputting the switcher HTML
    // Main container with class for CSS and ID for JS (click outside detection)
    echo '<div class="gp-language-switcher" id="gp-language-switcher">'; // ID added for JS targeting

    // Button - ID for JS, class for CSS
    // Ensure WordPress internationalization for aria-label
    $aria_label_select_language = function_exists('__') ? __('Select language', 'gp_theme') : 'Select language';
    echo '<button id="gp-lang-switcher-button" class="gp-language-button" aria-haspopup="true" aria-expanded="false" aria-controls="gp-lang-switcher-list" aria-label="' . esc_attr($aria_label_select_language) . '">';
    echo esc_html($current_lang_slug_for_button);
    echo '<span class="dropdown-icon" aria-hidden="true">▼</span>'; // Placeholder for dropdown icon
    echo '</button>';

    // Language list - ID for JS, class for CSS, hidden by default
    echo '<ul id="gp-lang-switcher-list" class="language-list" role="listbox" aria-labelledby="gp-lang-switcher-button" hidden>';

    foreach ($translations as $lang) {
        // Prepare attributes and text, ensuring they are properly escaped.
        $lang_name_attr = esc_attr($lang['name']);
        $lang_slug_attr = esc_attr($lang['slug']); // Original slug for hreflang

        // Determine display code (KR for KO, otherwise uppercase slug)
        $lang_code_display = strtoupper($lang_slug_attr);
        if ($lang_code_display === 'KO') {
            $lang_code_display = 'KR';
        }
        // Text for list item, e.g. "Korean (KR)" or "English (EN)". Or just the code if preferred.
        // For now, using just the code for simplicity, matching the button.
        $list_item_display_text = esc_html($lang_code_display);

        $is_current = !empty($lang['current_lang']);

        echo '<li class="lang-item' . ($is_current ? ' current-lang' : '') . '" role="option" aria-selected="' . ($is_current ? 'true' : 'false') . '" lang="' . $lang_slug_attr . '">';

        if ($is_current) {
            // Current language: display as non-clickable text or styled differently.
            // Using text from $list_item_display_text for consistency.
            $current_lang_aria_label = function_exists('__') ? sprintf(__('Current language: %s', 'gp_theme'), $lang_name_attr) : "Current language: {$lang_name_attr}";
            echo '<span class="lang-text" aria-label="' . esc_attr($current_lang_aria_label) . '">' . $list_item_display_text . '</span>';
        } else {
            // Other available languages: display as links.
            $switch_to_lang_aria_label = function_exists('__') ? sprintf(__('Switch to %s', 'gp_theme'), $lang_name_attr) : "Switch to {$lang_name_attr}";
            echo '<a href="' . esc_url($lang['url']) . '" hreflang="' . $lang_slug_attr . '" lang="' . $lang_slug_attr . '" class="lang-link" aria-label="' . esc_attr($switch_to_lang_aria_label) . '">' . $list_item_display_text . '</a>';
        }
        echo '</li>';
    }

    echo '</ul>'; // Close ul#gp-lang-switcher-list
    echo '</div>';  // Close div.gp-language-switcher
}


// 6.4. 특성 이미지, 메타 정보
// -------------------------------------------------------------------------
function gp_featured_image_output() {
	// Static counter for images on home/archive pages
	static $image_index = 0; // Renamed for clarity, serves all non-singular views

	if ( ! has_post_thumbnail() ) return;

	$is_singular_page = is_singular();
	$is_home_or_front = is_front_page() || is_home();
	$load_eagerly = false;
	$placeholder_src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";

	// Determine the image size to use
	$image_size_to_use = $is_singular_page ? 'full' : 'large-thumb'; // 'large-thumb' for listings, 'full' for single posts

	if ($is_singular_page) {
		// Single posts: main featured image loads eagerly.
		$load_eagerly = true;
	} else {
		// For non-singular pages (home, front_page, archives)
		if ($is_home_or_front) {
			// Home or Front page: Load first 2 images eagerly
			if ($image_index < 2) {
				$load_eagerly = true;
			}
		} else {
			// Other archive pages: Load first 1 image eagerly
			if ($image_index < 1) {
				$load_eagerly = true;
			}
		}
		$image_index++;
	}

	$image_html = get_the_post_thumbnail( get_the_ID(), $image_size_to_use, ['itemprop' => 'image'] );

	if (empty($image_html)) return;

	// Remove existing loading="lazy" if WordPress added it.
	$image_html = str_replace(' loading="lazy"', '', $image_html);

	if ($load_eagerly) {
		if (!str_contains($image_html, ' loading=')) {
			$image_html = str_replace('<img', '<img loading="eager"', $image_html);
		} else {
            $image_html = preg_replace('/ loading="[^"]*"/', ' loading="eager"', $image_html);
            if (str_contains($image_html, ' loading=""')) { // Catch edge case of empty loading attribute
                 $image_html = str_replace(' loading=""', ' loading="eager"', $image_html);
            }
        }
	} else {
		// Prepare for JS lazy loading
		$image_html = preg_replace('/ src=/', ' data-src=', $image_html);
		$image_html = preg_replace('/<img /', '<img src="' . $placeholder_src . '" ', $image_html, 1);
		if (str_contains($image_html, 'class="')) {
			$image_html = preg_replace('/class="/', 'class="lazy-load ', $image_html, 1);
		} else {
			$image_html = preg_replace('/<img /', '<img class="lazy-load" ', $image_html, 1);
		}
	}

    $post_time = get_the_time('U');
    $modified_time = get_the_modified_time('U');
    $current_time = current_time('U');
    $is_new = ( $current_time - $post_time ) < ( 7 * DAY_IN_SECONDS );
    $is_updated = ( $modified_time > $post_time + DAY_IN_SECONDS ) && ( $current_time - $modified_time ) < ( 7 * DAY_IN_SECONDS );

    $badge_html = '';
    if ($is_new) { $badge_html = '<span class="gp-new-badge">NEW</span>'; }
    elseif ($is_updated) { $badge_html = '<span class="gp-updated-badge">UPDATED</span>'; }

	$final_output_image_html = '';
	// Note: The condition for the link wrapper should be based on $is_singular_page, not $load_eagerly
	if( !$is_singular_page ) {
		$final_output_image_html = sprintf( '<a href="%s" rel="bookmark">%s%s</a>', esc_url( get_permalink() ), $badge_html, $image_html );
	} else {
		$final_output_image_html = $badge_html . $image_html;
	}
	printf( '<div class="%s"> %s </div>', ($is_singular_page ? 'featured-image' : 'post-image'), $final_output_image_html );
}

function gp_meta_after_title() {
    if ( !is_singular() ) return; // Only show on single posts

    global $post;
    $author_display_name = get_the_author_meta('display_name', $post->post_author);
    $is_updated = get_the_modified_time('U') > get_the_time('U') + DAY_IN_SECONDS;
    $word_count = count(preg_split('/\s+/', trim(strip_tags($post->post_content)), -1, PREG_SPLIT_NO_EMPTY));
    $reading_time = ceil($word_count / 225);
    $reading_time = max(1, $reading_time); // Ensure at least 1 minute
    ?>
    <div class="gp-meta-bar-after-title">
        <div class="posted-on-wrapper" title="<?php echo $is_updated ? 'Click to see publish date' : ''; ?>">
            <div class="date-primary">
                <?php if ($is_updated) : ?>
                    <span class="date-label">Updated:</span>
                    <time class="entry-date" datetime="<?php echo esc_attr(get_the_modified_date('c')); ?>"><?php echo esc_html(get_the_modified_date('Y.m.d')); ?></time>
                <?php else : ?>
                    <span class="date-label">Published:</span>
                    <time class="entry-date" datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('Y.m.d')); ?></time>
                <?php endif; ?>
            </div>
            <?php if ($is_updated) : ?>
                <div class="date-secondary"><span class="date-label">Published:</span><time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('Y.m.d')); ?></time></div>
            <?php endif; ?>
        </div>
        <span class="reading-time-meta" data-tooltip-text="<?php echo $word_count; ?> words"><?php echo $reading_time; ?> MIN READ</span>
        <span class="byline"><span class="author-label">by</span><span class="author-name-no-link"><?php echo esc_html($author_display_name); ?></span></span>
    </div>
    <?php
}


// 6.5. 목록 페이지 요소 (Read more, 태그, 별점)
// -------------------------------------------------------------------------
function gp_read_more_btn_output(){
	if ( is_singular() ) return; // Only show on homepage/archive
	echo '<div class="read-more-container"><a href="'.esc_url(get_permalink()).'" class="gp-read-more-btn">Read More</a></div>';
}
function gp_add_tags_to_list(){ if(!is_singular()&&has_tag()){ echo '<div class="list-tags-container">'; the_tags('',' ',''); echo '</div>'; }}

function gp_add_star_rating_to_list() {
    if ( is_singular() ) return; // Only show on homepage/archive

    $post_id = get_the_ID();
    $total_score = get_post_meta($post_id, '_gp_star_rating_total_score', true) ?: 0;
    $vote_count = get_post_meta($post_id, '_gp_star_rating_vote_count', true) ?: 0;
    if ($vote_count > 0) {
        $average_rating = round($total_score / $vote_count, 1);
        $rating_text = $vote_count >= 50 ? number_format($average_rating, 1) . " ({$vote_count} votes)" : number_format($average_rating, 1) . " rating";
        ?>
        <div class="gp-list-star-rating">
            <div class="list-stars-wrapper"><span class="list-stars-background"></span><span class="list-stars-foreground" style="width: <?php echo $average_rating / 5 * 100; ?>%;"></span></div>
            <span class="rating-info"><?php echo esc_html($rating_text); ?></span>
        </div>
        <?php
    }
}

// 6.6. 글 하단 요소 (반응, 별점, 공유, 시리즈)
// -------------------------------------------------------------------------
function gp_render_post_footer_sections() {
    if ( !is_single() ) return;
    echo '<div class="post-footer-sections">';
    gp_post_reactions_output();
    gp_star_rating_output();
    gp_add_social_share_buttons();
    echo '</div>';
}

function gp_post_reactions_output(){
    if(!is_single() || 'off' === get_post_meta(get_the_ID(),'_gp_show_reactions',true)) return;
    $reactions=['like'=>['label'=>'Like','icon'=>'👍'],'love'=>['label'=>'Love','icon'=>'❤️'],'helpful'=>['label'=>'Helpful','icon'=>'💡'],'fun'=>['label'=>'Interesting','icon'=>'😄']];
    ?><div class="post-reactions-container"><p class="section-label">How was this post?</p><div class="reaction-buttons"><?php foreach($reactions as $key=>$value): $count=get_post_meta(get_the_ID(),'_gp_reaction_'.$key,true);?><button class="reaction-btn" data-reaction="<?php echo esc_attr($key);?>" data-post-id="<?php echo get_the_ID();?>"><span class="reaction-icon"><?php echo $value['icon'];?></span><span class="reaction-label sr-only"><?php echo esc_html($value['label']);?></span><span class="reaction-count"><?php echo $count ? intval($count) : 0;?></span></button><?php endforeach;?></div></div><?php
}

function gp_star_rating_output(){
    if(!is_single()) return;
    $post_id = get_the_ID();
    $total_score = get_post_meta($post_id, '_gp_star_rating_total_score', true) ?: 0;
    $vote_count = get_post_meta($post_id, '_gp_star_rating_vote_count', true) ?: 0;
    $average_rating = $vote_count > 0 ? round($total_score / $vote_count, 1) : 0;
    ?>
    <div class="gp-star-rating-container" data-post-id="<?php echo $post_id; ?>">
        <p class="section-label">Rate this post</p>
        <div class="stars-wrapper" aria-label="별점 <?php echo $average_rating; ?>점 (5점 만점)">
            <div class="stars-background" aria-hidden="true"><?php for($i = 1; $i <= 5; $i++) echo '<div class="star" data-rating="'.$i.'" role="button" tabindex="0" aria-label="'.$i.'점"></div>'; ?></div>
            <div class="stars-foreground" style="width: <?php echo $average_rating / 5 * 100; ?>%;" aria-hidden="true"><?php for($i = 1; $i <= 5; $i++) echo '<div class="star"></div>'; ?></div>
        </div>
        <div class="rating-text" title="<?php printf('%d votes', $vote_count); ?>" data-initial-average="<?php echo number_format($average_rating, 1); ?>">
            <span><?php echo number_format($average_rating, 1); ?></span> / <span>5.0</span>
        </div>
        <?php if ($vote_count > 20): ?><div class="vote-count-display"><?php echo $vote_count; ?> votes</div><?php endif; ?>
        <div class="user-rating-text" aria-live="polite"></div>
        <div class="rating-buttons-container">
            <button class="edit-rating-btn rating-action-btn" aria-label="별점 수정">Edit</button>
            <button class="submit-rating-btn rating-action-btn" aria-label="별점 제출">Submit</button>
        </div>
    </div>
    <?php
}

function gp_add_social_share_buttons(){
    if(!is_single())return;
    $permalink=urlencode(get_permalink());$title=urlencode(get_the_title());
    ?><div class="gp-social-share-container"><p class="section-label">Share this post</p><div class="share-buttons">
    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $permalink;?>" target="_blank" rel="noopener noreferrer" class="social-share-btn facebook" aria-label="Share on Facebook">F</a>
    <a href="https://x.com/intent/tweet?url=<?php echo $permalink;?>&text=<?php echo $title;?>" target="_blank" rel="noopener noreferrer" class="social-share-btn x-btn" aria-label="Share on X">X</a>
    <a href="javascript:void(0);" class="social-share-btn copy-link-icon-bottom" aria-label="Copy link"></a>
    </div></div><?php
}

function gppress_tags_before_related() {
    if ( is_single() && has_tag() ) {
        the_tags('<footer class="entry-meta tags-footer"><div class="tags-links">', '', '</div></footer>');
    }
}

/**
 * Retrieves related posts based on shared tags, similar tags, and categories.
 *
 * @param int $current_post_id The ID of the current post.
 * @param int $num_posts       The total number of related posts to retrieve.
 * @return array An array of post IDs.
 */
function get_custom_related_series_posts( $current_post_id, $num_posts = 4 ) {
    $found_ids = array();
    $collected_ids = array( $current_post_id );
    $tag_ids = array();

    // Priority 1: Same Tags
    $tags = wp_get_post_tags( $current_post_id );
    if ( $tags ) {
        $tag_ids = wp_list_pluck( $tags, 'term_id' );
        if ( !empty($tag_ids) ) {
            $args_priority1 = array(
                'tag__in'             => $tag_ids,
                'post__not_in'        => $collected_ids,
                'posts_per_page'      => $num_posts,
                'ignore_sticky_posts' => 1,
                'fields'              => 'ids',
            );
            $query_priority1 = new WP_Query( $args_priority1 );
            if ( $query_priority1->have_posts() ) {
                $newly_found = $query_priority1->posts;
                $found_ids = array_merge( $found_ids, $newly_found );
                $collected_ids = array_merge( $collected_ids, $newly_found );
            }
        }
    }

    // Priority 2: Similar Tags
    if ( count( $found_ids ) < $num_posts ) {
        $source_tags_objects = $tags;
        $all_site_tags_objects = get_tags( array( 'hide_empty' => false ) );
        $similar_tag_ids_to_query = array();

        if ( $source_tags_objects && $all_site_tags_objects ) {
            foreach ( $source_tags_objects as $current_tag_obj ) {
                $current_tag_name = strtolower($current_tag_obj->name);
                $current_tag_words = explode( ' ', $current_tag_name );

                foreach ( $all_site_tags_objects as $site_tag_obj ) {
                    if (in_array($site_tag_obj->term_id, $tag_ids)) {
                        continue;
                    }
                    $site_tag_name = strtolower($site_tag_obj->name);
                    $site_tag_words = explode( ' ', $site_tag_name );

                    if ( count( $current_tag_words ) > 1 ) {
                        foreach ( $current_tag_words as $word ) {
                            if ( count( $site_tag_words ) == 1 && $site_tag_name === $word ) {
                                $similar_tag_ids_to_query[] = $site_tag_obj->term_id;
                                break;
                            }
                        }
                    } else {
                        if ( count( $site_tag_words ) > 1 ) {
                            foreach ( $site_tag_words as $site_word ) {
                                if ( $site_word === $current_tag_name ) {
                                    $similar_tag_ids_to_query[] = $site_tag_obj->term_id;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        $similar_tag_ids_to_query = array_unique( $similar_tag_ids_to_query );
        if (!empty($tag_ids)) {
            $similar_tag_ids_to_query = array_diff($similar_tag_ids_to_query, $tag_ids);
        }

        if ( ! empty( $similar_tag_ids_to_query ) ) {
            $needed_posts = $num_posts - count( $found_ids );
            if ( $needed_posts > 0 ) {
                $args_priority2 = array(
                    'tag__in'             => $similar_tag_ids_to_query,
                    'post__not_in'        => $collected_ids,
                    'posts_per_page'      => $needed_posts,
                    'ignore_sticky_posts' => 1,
                    'fields'              => 'ids',
                );
                $query_priority2 = new WP_Query( $args_priority2 );
                if ( $query_priority2->have_posts() ) {
                    $newly_found = $query_priority2->posts;
                    $found_ids = array_merge( $found_ids, $newly_found );
                    $collected_ids = array_merge( $collected_ids, $newly_found );
                }
            }
        }
    }

    // Priority 3: Same Category
    if ( count( $found_ids ) < $num_posts ) {
        $category_ids = wp_get_post_categories( $current_post_id, array( 'fields' => 'ids' ) );
        if ( !empty( $category_ids ) ) {
            $needed_posts = $num_posts - count( $found_ids );
            if ( $needed_posts > 0 ) {
                $args_cat = array(
                    'category__in'        => $category_ids,
                    'post__not_in'        => $collected_ids,
                    'posts_per_page'      => $needed_posts,
                    'orderby'             => 'date',
                    'order'               => 'DESC',
                    'ignore_sticky_posts' => 1,
                    'fields'              => 'ids'
                );
                $category_posts_query = new WP_Query( $args_cat );

                if ( $category_posts_query->have_posts() ) {
                    $newly_found_cat = $category_posts_query->posts;
                    $found_ids = array_merge( $found_ids, $newly_found_cat );
                    $collected_ids = array_merge( $collected_ids, $newly_found_cat);
                }
            }
        }
    }

    // Fallback: Any Post from Site
    if ( count( $found_ids ) < $num_posts ) {
        $needed_posts = $num_posts - count( $found_ids );
        if ( $needed_posts > 0 ) {
            $args_fallback = array(
                'post_type'           => 'post',
                'post_status'         => 'publish',
                'post__not_in'        => $collected_ids,
                'posts_per_page'      => $needed_posts,
                'orderby'             => 'date',
                'order'               => 'DESC',
                'ignore_sticky_posts' => 1,
                'fields'              => 'ids',
            );
            $fallback_query = new WP_Query( $args_fallback );
            if ( $fallback_query->have_posts() ) {
                $newly_found_fallback = $fallback_query->posts;
                $found_ids = array_merge( $found_ids, $newly_found_fallback );
            }
        }
    }

    $found_ids = array_unique( $found_ids );
    return array_slice( $found_ids, 0, $num_posts );
}

function gp_series_posts_output() {
    // Ensure we get the ID from the main queried object if available
    $current_post_id = get_queried_object_id();
    if ( !$current_post_id && isset($GLOBALS['post']) ) {
        // Fallback to global post ID if get_queried_object_id() returns nothing but global post exists
        $current_post_id = $GLOBALS['post']->ID;
    }

    // If still no ID, or not singular, exit.
    // is_singular() check is kept as a primary guard.
    if ( !is_singular() || !$current_post_id ) {
        return;
    }

    $related_post_ids = get_custom_related_series_posts($current_post_id, 4); // Request 4 posts
    // Define placeholder directly in function for clarity
	$placeholder_src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";

    if ( !empty($related_post_ids) ) {
        $args = array(
            'post__in' => $related_post_ids,
            'posts_per_page' => count($related_post_ids),
            'orderby' => 'post__in',
            'ignore_sticky_posts'=>1
        );
        $series_query = new WP_Query( $args );
        if( $series_query->have_posts() ) {
            echo '<div class="gp-series-posts-container"><h2 class="series-posts-title">Series</h2><div class="series-posts-grid">';
            while( $series_query->have_posts() ) {
                $series_query->the_post();

                ob_start();
                if (has_post_thumbnail()) {
                    // Get thumbnail HTML string instead of direct output
                    echo get_the_post_thumbnail(get_the_ID(), 'medium');
                } else {
                    echo '<div class="no-thumb-series"></div>';
                }
                $series_thumb_html = ob_get_clean();

                if (str_contains($series_thumb_html, '<img')) {
                    // Prepare for JS lazy loading
                    $series_thumb_html = str_replace(' loading="lazy"', '', $series_thumb_html); // Remove native lazy
                    $series_thumb_html = preg_replace('/ src=/', ' data-src=', $series_thumb_html);
                    // Add placeholder src
                    $series_thumb_html = preg_replace('/<img /', '<img src="' . $placeholder_src . '" ', $series_thumb_html, 1);
                    // Add lazy-load class
                    if (str_contains($series_thumb_html, 'class="')) {
                        $series_thumb_html = preg_replace('/class="/', 'class="lazy-load ', $series_thumb_html, 1);
                    } else {
                        // If no class attribute, add it
                        $series_thumb_html = preg_replace('/<img /', '<img class="lazy-load" ', $series_thumb_html, 1);
                    }
                }
                ?>
                <a href="<?php the_permalink(); ?>" rel="bookmark" class="series-post-item">
                    <div class="series-post-thumbnail"><?php echo $series_thumb_html; ?></div>
                    <div class="series-post-content">
                        <h3 class="series-post-title"><?php the_title(); ?></h3>
                        <div class="series-post-meta"><?php echo get_the_date(); ?></div>
                    </div>
                </a>
                <?php
            }
            echo '</div></div>';
        }
        wp_reset_postdata();
    }
}

// 6.7. 이전/다음 글 내비게이션
// -------------------------------------------------------------------------
function gp_custom_post_navigation_output(){
    if(!is_single()) return;
    $prev_post=get_previous_post(); $next_post=get_next_post();
    if(!$prev_post&&!$next_post) return;
    $nav_item = function($post,$rel){
        if(!$post){ $class_name=$rel==='prev'?'previous':'next'; return "<div class='nav-{$class_name} empty-nav-item'></div>"; }
        // Use 'medium' size for navigation thumbnails
        $thumb=has_post_thumbnail($post->ID)?get_the_post_thumbnail($post->ID,'medium'):'<div class="no-thumb"></div>';
        $label=$rel==='prev'?'Prev Post':'Next Post'; $class_name=$rel==='prev'?'previous':'next';

        // Prepare for JS lazy loading for nav images as well, as they might be off-screen initially.
        // However, these are usually only two images, so eager might be fine too.
        // For consistency with the primary lazy loading strategy, let's make them lazy if they are not visible.
        // Given they are small and likely to be visible if the footer is visible, let's assume eager for now for simplicity,
        // unless further feedback suggests these also need lazy loading.
        // For now, just changing size. The main lazy loading script only targets '.lazy-load' class.
        // If we wanted these lazy, we'd add the class and data-src here too.

        return "<div class='nav-{$class_name}'><a href='".esc_url(get_permalink($post->ID))."' rel='{$rel}'>".$thumb."<div class='nav-title-overlay'><span class='nav-title-label'>".$label."</span><span class='nav-title'>".get_the_title($post->ID)."</span></div></a></div>";
    };
    echo '<div class="gp-custom-post-nav-wrapper"><nav class="navigation post-navigation gp-custom-post-nav"><div class="nav-links">';
    echo $nav_item($prev_post,'prev'); echo $nav_item($next_post,'next');
    echo '</div></nav></div>';
}

// 6.8. 푸터 요소 (플로팅 버튼, 사이드바)
// -------------------------------------------------------------------------
function gp_add_footer_elements_and_scripts() {
    echo '<div id="mybar" role="progressbar" aria-label="Reading progress" aria-valuemin="0" aria-valuemax="100"></div>';
    echo '<div class="floating-buttons-container" role="toolbar" aria-label="Site control buttons">';
    if (is_single()) {
        echo '<button id="sidebarToggle" class="floating-btn" title="Toggle sidebar" aria-label="Open/close sidebar"><div class="sidebar-toggle-icon"></div></button>';
    }
    echo '<div id="darkModeToggle" class="floating-btn" title="Toggle dark mode" aria-label="Switch dark mode"><div class="dark-mode-icon-wrapper"></div></div>';
    echo '<button id="scrollToTopBtn" class="floating-btn" title="Scroll to top" aria-label="Go to page top"></button>';
    echo '</div>';

    if (is_single()) {
        echo '<aside id="gp-sidebar" class="gp-sidebar-hidden" role="complementary" aria-label="Sidebar" style="display: none;"><div class="sidebar-header"><h3>Contents & Tools</h3><button class="sidebar-close" aria-label="Close sidebar">×</button></div><div class="sidebar-content"><div class="sidebar-toc-container"></div><div class="sidebar-tools"><h4>Tools</h4><button class="sidebar-tool" data-action="print">Print</button><button class="sidebar-tool" data-action="bookmark">Bookmark</button><button class="sidebar-tool" data-action="share">Share</button></div></div></aside>';
        echo '<div id="sidebar-overlay" class="sidebar-overlay" style="display: none;"></div>';
    }
}


// =========================================================================
// 7. 관리자 페이지 메타 박스 (Admin Meta Boxes)
// =========================================================================

/**
 * 포스트 편집 화면에 'GP 테마 설정' 메타 박스를 등록합니다.
 */
function gp_register_meta_box() {
	add_meta_box( 'gp-post-options', 'GP 테마 설정', 'gp_meta_box_callback', 'post', 'side', 'high' );
}
add_action( 'add_meta_boxes', 'gp_register_meta_box' );

/**
 * 메타 박스의 HTML 내용을 출력합니다.
 */
function gp_meta_box_callback( $post ) {
	wp_nonce_field( 'gp_save_meta_box_data', 'gp_meta_box_nonce' );

	$is_checked = ( 'off' !== get_post_meta( $post->ID, '_gp_show_reactions', true ) );
	$reactions = ['like' => 'Like', 'love' => 'Love', 'helpful' => 'Helpful', 'fun' => 'Interesting'];
	$total_score = get_post_meta($post->ID, '_gp_star_rating_total_score', true) ?: 0;
	$vote_count = get_post_meta($post->ID, '_gp_star_rating_vote_count', true) ?: 0;
	$average_rating = $vote_count > 0 ? round($total_score / $vote_count, 2) : 0;
	?>
	<p><label><input type="checkbox" name="gp_show_reactions" <?php checked( $is_checked ); ?> /> <strong>글 반응 섹션 표시</strong></label></p><hr>
	<p><strong>반응 수치 직접 조절:</strong></p>
	<?php foreach ( $reactions as $key => $label ) :
		$count = get_post_meta($post->ID, '_gp_reaction_' . $key, true); ?>
		<p style="display:flex; justify-content:space-between; align-items:center;">
			<label for="gp_reaction_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?>:</label>
			<input type="number" id="gp_reaction_<?php echo esc_attr($key); ?>" name="gp_reaction_<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($count ?: 0); ?>" min="0" style="width: 70px;" />
		</p>
	<?php endforeach; ?>
	<hr>
	<p><strong>별점 평가 조절:</strong></p>
	<p style="display:flex; justify-content:space-between; align-items:center;">
		<label for="gp_star_average_rating">평균 별점:</label>
		<input type="number" step="0.1" id="gp_star_average_rating" name="gp_star_average_rating" value="<?php echo esc_attr($average_rating); ?>" min="0" max="5" style="width: 70px;" />
	</p>
	<p style="display:flex; justify-content:space-between; align-items:center;">
		<label for="gp_star_vote_count">총 투표 수:</label>
		<input type="number" id="gp_star_vote_count" name="gp_star_vote_count" value="<?php echo esc_attr($vote_count); ?>" min="0" style="width: 70px;" />
	</p>
	<?php
}

/**
 * 메타 박스 데이터를 저장합니다.
 */
function gp_save_meta_box_data( $post_id ) {
	if ( ! isset( $_POST['gp_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['gp_meta_box_nonce'], 'gp_save_meta_box_data' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	update_post_meta( $post_id, '_gp_show_reactions', isset( $_POST['gp_show_reactions'] ) ? 'on' : 'off' );
	foreach ( ['like', 'love', 'helpful', 'fun'] as $key ) {
		if ( isset( $_POST['gp_reaction_' . $key] ) ) {
			update_post_meta( $post_id, '_gp_reaction_' . $key, max(0, intval($_POST['gp_reaction_' . $key])) );
		}
	}

	if ( isset( $_POST['gp_star_average_rating'] ) && isset( $_POST['gp_star_vote_count'] ) ) {
		$avg = floatval($_POST['gp_star_average_rating']);
		$count = intval($_POST['gp_star_vote_count']);
		update_post_meta($post_id, '_gp_star_rating_total_score', round($avg * $count));
		update_post_meta($post_id, '_gp_star_rating_vote_count', $count);
	}
}
add_action( 'save_post', 'gp_save_meta_box_data' );


// =========================================================================
// 8. AJAX 핸들러 (AJAX Handlers)
// =========================================================================

/**
 * '글 반응' AJAX 요청을 처리합니다.
 */
function gp_handle_reaction_callback() {
	check_ajax_referer('gp_reactions_nonce', 'nonce');
	if (isset($_POST['post_id']) && isset($_POST['reaction'])) {
		$post_id = intval($_POST['post_id']);
		$reaction = sanitize_key($_POST['reaction']);
		$count = get_post_meta($post_id, '_gp_reaction_' . $reaction, true);
		$new_count = ($count ? intval($count) : 0) + 1;
		update_post_meta($post_id, '_gp_reaction_' . $reaction, $new_count);
		wp_send_json_success(['count' => $new_count]);
	}
	wp_send_json_error();
}
add_action('wp_ajax_nopriv_gp_handle_reaction', 'gp_handle_reaction_callback');
add_action('wp_ajax_gp_handle_reaction', 'gp_handle_reaction_callback');

/**
 * '별점 평가' AJAX 요청을 처리합니다.
 */
function gp_handle_star_rating_callback() {
	check_ajax_referer('gp_star_rating_nonce', 'nonce');
	if ( !isset($_POST['post_id']) || !isset($_POST['new_rating']) ) {
		wp_send_json_error('Missing parameters.');
	}

	$post_id = intval($_POST['post_id']);
	$new_rating = floatval($_POST['new_rating']);
	$old_rating = isset($_POST['old_rating']) ? floatval($_POST['old_rating']) : 0;

	if ($new_rating < 0.5 || $new_rating > 5) {
		wp_send_json_error('Invalid rating.');
	}

	$total_score = get_post_meta($post_id, '_gp_star_rating_total_score', true) ?: 0;
	$vote_count = get_post_meta($post_id, '_gp_star_rating_vote_count', true) ?: 0;

	if ( $old_rating > 0 ) { // 기존 투표 수정
		$new_total_score = $total_score - $old_rating + $new_rating;
		$new_vote_count = $vote_count;
	} else { // 신규 투표
		$new_total_score = $total_score + $new_rating;
		$new_vote_count = $vote_count + 1;
	}

	update_post_meta($post_id, '_gp_star_rating_total_score', $new_total_score);
	update_post_meta($post_id, '_gp_star_rating_vote_count', $new_vote_count);

	$new_average = ($new_vote_count > 0) ? round($new_total_score / $new_vote_count, 1) : 0;

	wp_send_json_success([
		'average' => $new_average,
		'votes'   => $new_vote_count
	]);
}
add_action('wp_ajax_nopriv_gp_handle_star_rating', 'gp_handle_star_rating_callback');
add_action('wp_ajax_gp_handle_star_rating', 'gp_handle_star_rating_callback');

/**
 * AJAX handler for loading more posts.
 */
function gp_load_more_posts_ajax_handler() {
    error_log('GP Theme AJAX: gp_load_more_posts_ajax_handler invoked.');

    // Log received parameters
    error_log('GP Theme AJAX: Received page number: ' . (isset($_POST['page']) ? sanitize_text_field($_POST['page']) : 'Not set'));
    error_log('GP Theme AJAX: Received nonce: ' . (isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : 'Not set'));

    // 1. Check for nonce
    if (check_ajax_referer('load_more_posts_nonce', 'nonce', false)) {
        error_log('GP Theme AJAX: Nonce verified successfully.');
    } else {
        error_log('GP Theme AJAX: Nonce verification failed.');
        wp_send_json_error(['message' => 'Nonce verification failed.']); // Explicitly send error on nonce failure
        return;
    }

    // 2. Get page number
    if ( !isset($_POST['page']) ) {
        error_log('GP Theme AJAX: Sending JSON error response - Page parameter missing.');
        wp_send_json_error(['message' => 'Page parameter missing.']);
        return;
    }
    $page = intval($_POST['page']);

    // 3. Set up query arguments
    $args = [
        'post_type'      => 'post',
        'posts_per_page' => 10,
        'paged'          => $page, // Use $page here, not $paged
        'post_status'    => 'publish',
    ];
    error_log('GP Theme AJAX: WP_Query arguments: ' . print_r($args, true));

    // 4. Perform WP_Query
    $query = new WP_Query($args);
    error_log('GP Theme AJAX: Posts found: ' . $query->post_count);
    if (!$query->have_posts()) {
        error_log('GP Theme AJAX: No posts found for page ' . $page); // Use $page here
    }

    // 5. If posts are found
    if ($query->have_posts()) {
        ob_start();
        while ($query->have_posts()) : $query->the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <div class="inside-article">
                    <?php
                    // Start Entry Header
                    echo '<header class="entry-header">';

                    // Category Links
                    $categories_list = get_the_category_list(', ');
                    if (!empty($categories_list)) {
                        echo '<div class="gp-post-category">' . $categories_list . '</div>';
                    }

                    // Title
                    the_title(sprintf('<h2 class="entry-title" itemprop="headline"><a href="%s" rel="bookmark">', esc_url(get_permalink())), '</a></h2>');

                    echo '</header>'; // End Entry Header

                    // Featured Image (existing logic)
                    if (function_exists('gp_featured_image_output')) {
                        gp_featured_image_output();
                    } else {
                        if (has_post_thumbnail()) {
                            echo '<div class="post-image">';
                            the_post_thumbnail('large-thumb');
                            echo '</div>';
                        }
                    }

                    // Excerpt or content summary
                    echo '<div class="entry-summary" itemprop="text">'; // Added itemprop from user HTML
                    the_excerpt();
                    echo '</div>';

                    // Read More button, Tags, Star Rating (existing logic)
                    if (function_exists('gp_read_more_btn_output')) {
                        gp_read_more_btn_output();
                    }
                    if (function_exists('gp_add_tags_to_list')) {
                        gp_add_tags_to_list();
                    }
                    if (function_exists('gp_add_star_rating_to_list')) {
                        gp_add_star_rating_to_list();
                    }

                    // Entry Footer
                    echo '<footer class="entry-meta" aria-label="항목 메타">';
                    // Content for footer meta could be added here if needed.
                    echo '</footer>';
                    ?>
                </div><!-- .inside-article -->
            </article>
            <?php
        endwhile;
        wp_reset_postdata();
        $html_output = ob_get_clean();
        error_log('GP Theme AJAX: HTML output length: ' . strlen($html_output));
        error_log('GP Theme AJAX: Sending success response with HTML.');
        wp_send_json_success(['html' => $html_output]);
    } else {
        // 6. If no posts are found
        error_log('GP Theme AJAX: Sending success response with no more posts message.');
        wp_send_json_success(['html' => '', 'message' => 'No more posts']);
    }
}
add_action('wp_ajax_load_more_posts', 'gp_load_more_posts_ajax_handler');
add_action('wp_ajax_nopriv_load_more_posts', 'gp_load_more_posts_ajax_handler');

/**
 * Conditionally hide the entry footer (footer.entry-meta) on non-singular views.
 */
function gp_custom_hide_entry_footer_archives( $show ) {
    if ( ! is_singular() ) {
        return false; // Do not show entry footer on archives, homepage, etc.
    }
    return $show; // Show on singular views (posts, pages) as per default
}
add_filter( 'generate_show_entry_footer', 'gp_custom_hide_entry_footer_archives' );


/**
 * 댓글 관련 특정 텍스트를 한글에서 영어로 변경합니다.
 */
function aivew_translate_comment_text( $translated_text, $text, $domain ) {
    if ( 'default' === $domain ) {
        switch ( $text ) {
            case '응답':
                $translated_text = 'Reply';
                break;
            case '댓글 달기':
                $translated_text = 'Post Comment';
                break;
        }
    }
    return $translated_text;
}
add_filter( 'gettext', 'aivew_translate_comment_text', 20, 3 );

/**
 * Adds AdSense settings to the WordPress Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function gp_customize_register_adsense( $wp_customize ) {
    // Add a new section for AdSense
    $wp_customize->add_section( 'gp_adsense_section', array(
        'title'       => __( 'AdSense Settings', 'gp_theme' ),
        'priority'    => 160, // Adjust priority as needed
        'description' => __( 'Enter your Google AdSense code here. It will be displayed in your posts.', 'gp_theme' ),
    ) );

    // Add a setting for the AdSense code
    $wp_customize->add_setting( 'gp_adsense_code', array(
        'type'              => 'theme_mod', // or 'option'
        'default'           => '',
        'sanitize_callback' => 'wp_kses_post', // Allows HTML, good for ad code
        'transport'         => 'refresh', // or 'postMessage'
    ) );

    // Add a control for the AdSense code
    $wp_customize->add_control( 'gp_adsense_code_control', array(
        'label'       => __( 'AdSense Code', 'gp_theme' ),
        'section'     => 'gp_adsense_section',
        'settings'    => 'gp_adsense_code',
        'type'        => 'textarea',
        'description' => __( 'Paste your full AdSense ad unit code here. For example, the code for an in-article ad unit. This will be placed near subheadings in your posts.', 'gp_theme' ),
    ) );
}
add_action( 'customize_register', 'gp_customize_register_adsense' );


/**
 * Inserts AdSense code into the post content, near subheadings.
 *
 * @param string $content The post content.
 * @return string The modified post content with AdSense.
 */
function gp_insert_adsense( $content ) {
    // Retrieve the AdSense code from theme settings
    $adsense_code = get_theme_mod( 'gp_adsense_code', '' );

    // If no AdSense code is set, or if it's not a single post, or if it's an admin view, return original content
    if ( empty( $adsense_code ) || ! is_single() || is_admin() ) {
        return $content;
    }

    // Ensure we are in the main query loop
    if ( ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    // Wrap the AdSense code in a div for styling (optional, but good practice)
    $ad_wrapper = '<div class="content-adsense" style="margin: 20px 0; text-align: center;">' . $adsense_code . '</div>';

    // Split the content by <h2> tags
    // Using a regex to capture the <h2> tag and its content, and also keep the delimiter
    $parts = preg_split( '/(<h[2][^>]*>.*?<\/h[2]>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

    $new_content = '';
    $min_paragraphs_before_ad = 1; // Minimum content parts before inserting an ad near an H2

    if ( count( $parts ) > 1 ) {
        // Loop through the parts. Each H2 and its preceding content will be processed.
        for ( $i = 0; $i < count( $parts ); $i++ ) {
            $new_content .= $parts[$i]; // Add the current part (text or H2 tag itself)

            // Check if the current part is an H2 tag
            if ( preg_match( '/^<h[2][^>]*>.*?<\/h[2]>$/i', $parts[$i] ) ) {
                // And if there was some content before this H2 (or it's not the very first element)
                // And we are not at the end of the parts
                if ( $i > 0 && ( $i + 1 ) < count( $parts ) ) {
                    // Insert ad after this H2 tag
                    $new_content .= $ad_wrapper;
                }
            }
        }
        return $new_content;
    }

    // Fallback or if no H2 tags are found, just return original content
    // (Previously, we had paragraph-based insertion. For now, this will only insert by H2)
    return $content;
}
add_filter( 'the_content', 'gp_insert_adsense', 20 );

// =========================================================================
// 9. QUERY MODIFICATIONS (Ensure Home Page Pagination)
// =========================================================================

/**
 * Ensures that the main query for the home page is paginated.
 * This prevents loading an excessive number of posts (e.g., 100+) at once.
 */
function gp_ensure_home_pagination( $query ) {
    // Only modify the main query on the front end
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    // Target the home page (blog posts index) or front page
    if ( $query->is_home() || $query->is_front_page() ) {
        // Set a reasonable number of posts per page, e.g., 10
        // This will override any global settings or other modifications for the home page query.
        $query->set( 'posts_per_page', 10 );
    }
}
add_action( 'pre_get_posts', 'gp_ensure_home_pagination' );