<?php
/*
Plugin Name: LTKG SEO Controller
Description: تحكم موحد في توليد الكلمات المفتاحية الطويلة الذيل لأدوات السيو: Rank Math، Yoast، AIOSEO، WP Meta SEO.
Version: 1.0
Author: Mona Jalal
*/

// قائمة الإضافات المدعومة
function ltkg_supported_plugins() {
    return [
        'rank_math' => 'Rank Math',
        'yoast' => 'Yoast SEO',
        'aioseo' => 'All in One SEO',
        'wpmetaseo' => 'WP Meta SEO',
    ];
}

// صفحة الإعدادات
add_action('admin_menu', function () {
    add_options_page('LTKG SEO Controller', 'LTKG SEO Controller', 'manage_options', 'ltkg-seo-controller', 'ltkg_controller_settings_page');
});

function ltkg_controller_settings_page() {
    ?>
    <div class="wrap">
        <h1>LTKG SEO Controller</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('ltkg_seo_options');
            do_settings_sections('ltkg-seo-controller');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', function () {
    register_setting('ltkg_seo_options', 'ltkg_active_plugins');
    add_settings_section('ltkg_section', 'اختر الإضافات التي تريد توليد كلمات لها:', null, 'ltkg-seo-controller');
    foreach (ltkg_supported_plugins() as $key => $label) {
        add_settings_field($key, $label, function () use ($key) {
            $options = get_option('ltkg_active_plugins', []);
            $checked = in_array($key, $options) ? 'checked' : '';
            echo "<input type='checkbox' name='ltkg_active_plugins[]' value='{$key}' {$checked} />";
        }, 'ltkg-seo-controller', 'ltkg_section');
    }
});

// توليد تلقائي عند حفظ المقال
add_action('save_post', function($post_id) {
    if (get_post_type($post_id) !== 'post' || wp_is_post_revision($post_id)) return;

    $options = get_option('ltkg_active_plugins', []);
    if (empty($options)) return;

    $title = get_the_title($post_id);
    $content = wp_strip_all_tags(get_post_field('post_content', $post_id));
    $desc = wp_trim_words($content, 25, '...');
    $text = $title . '. ' . $content;
    $keywords = ltkg_extract_long_tail_phrases($text, 5);
    if (empty($keywords)) return;

    if (in_array('rank_math', $options)) {
        update_post_meta($post_id, 'rank_math_focus_keyword', implode(', ', $keywords));
    }
    if (in_array('yoast', $options)) {
        update_post_meta($post_id, '_yoast_wpseo_focuskw', $keywords[0]);
    }
    if (in_array('aioseo', $options)) {
        update_post_meta($post_id, '_aioseop_keywords', implode(', ', $keywords));
    }
    if (in_array('wpmetaseo', $options)) {
        update_post_meta($post_id, '_metaseo_metakeywords', implode(', ', $keywords));
        update_post_meta($post_id, '_metaseo_metatitle', $title);
        update_post_meta($post_id, '_metaseo_metadesc', $desc);
    }
}, 20, 1);

// دوال استخراج الكلمات الطويلة
function ltkg_extract_long_tail_phrases($text, $limit = 5) {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^\p{L}\p{N}\s]+/u', '', $text);
    $words = preg_split('/\s+/', $text);
    $phrases = [];
    $stop_words = array_merge(ltkg_arabic_stopwords(), ltkg_english_stopwords());
    for ($i = 0; $i < count($words) - 2; $i++) {
        $chunk = array_slice($words, $i, 3);
        $filtered = array_filter($chunk, fn($w) => !in_array($w, $stop_words));
        if (count($filtered) >= 2) {
            $phrases[] = implode(' ', $chunk);
        }
    }
    $frequency = array_count_values($phrases);
    arsort($frequency);
    return array_slice(array_keys($frequency), 0, $limit);
}

function ltkg_arabic_stopwords() {
    return ['في','من','على','إلى','عن','ما','لا','لم','لن','إن','أن','هذا','هذه','ذلك','كانت','كان','هو','هي','هم','كما','قد','أي','كل','أو','بل','ثم','إذا','بين','بعد','قبل','حتى','مع','نحن'];
}

function ltkg_english_stopwords() {
    return ['the','and','for','are','but','not','you','with','this','that','have','from','they','will','their','would','there','what','about','which','when','make','can','has','was','his','her','how','our'];
}


// ✅ توليد كلمات لمقال واحد (عند الضغط من صفحة المقال)
add_action('wp_ajax_ltkg_generate_single_post', function() {
    if (!current_user_can('edit_posts')) wp_send_json_error();

    $post_id = intval($_POST['post_id']);
    $plugins = get_option('ltkg_active_plugins', []);

    if (!$post_id || empty($plugins)) wp_send_json_error();

    $title = get_the_title($post_id);
    $content = wp_strip_all_tags(get_post_field('post_content', $post_id));
    $desc = wp_trim_words($content, 25, '...');
    $text = $title . '. ' . $content;
    $keywords = ltkg_extract_long_tail_phrases($text, 5);

    if (in_array('rank_math', $plugins)) {
        update_post_meta($post_id, 'rank_math_focus_keyword', implode(', ', $keywords));
    }
    if (in_array('yoast', $plugins)) {
        update_post_meta($post_id, '_yoast_wpseo_focuskw', $keywords[0]);
    }
    if (in_array('aioseo', $plugins)) {
        update_post_meta($post_id, '_aioseop_keywords', implode(', ', $keywords));
    }
    if (in_array('wpmetaseo', $plugins)) {
        update_post_meta($post_id, '_metaseo_metakeywords', implode(', ', $keywords));
        update_post_meta($post_id, '_metaseo_metatitle', $title);
        update_post_meta($post_id, '_metaseo_metadesc', $desc);
    }

    wp_send_json_success();
});

// ✅ توليد لكل المقالات
add_action('wp_ajax_ltkg_generate_all_posts', function() {
    if (!current_user_can('edit_posts')) wp_send_json_error();
    $plugins = get_option('ltkg_active_plugins', []);
    if (empty($plugins)) wp_send_json_error();

    $posts = get_posts([
        'post_type' => 'post',
        'posts_per_page' => -1,
        'post_status' => ['publish', 'future', 'draft']
    ]);

    $done = [];
    foreach ($posts as $post) {
        $done[] = ltkg_generate_keywords_for_post($post->ID, $plugins);
    }

    wp_send_json_success($done);
});

// ✅ توليد لمقالات اليوم فقط
add_action('wp_ajax_ltkg_generate_today_posts', function() {
    if (!current_user_can('edit_posts')) wp_send_json_error();
    $plugins = get_option('ltkg_active_plugins', []);
    if (empty($plugins)) wp_send_json_error();

    $today = date('Y-m-d');
    $posts = get_posts([
        'post_type' => 'post',
        'posts_per_page' => -1,
        'post_status' => ['publish', 'future', 'draft'],
        'date_query' => [
            [
                'after' => $today . ' 00:00:00',
                'before' => $today . ' 23:59:59',
                'inclusive' => true,
            ]
        ]
    ]);

    $done = [];
    foreach ($posts as $post) {
        $done[] = ltkg_generate_keywords_for_post($post->ID, $plugins);
    }

    wp_send_json_success($done);
});

// ✅ دالة تنفيذ التوليد لمقال معين
function ltkg_generate_keywords_for_post($post_id, $plugins) {
    $title = get_the_title($post_id);
    $content = wp_strip_all_tags(get_post_field('post_content', $post_id));
    $desc = wp_trim_words($content, 25, '...');
    $text = $title . '. ' . $content;
    $keywords = ltkg_extract_long_tail_phrases($text, 5);

    if (in_array('rank_math', $plugins)) {
        update_post_meta($post_id, 'rank_math_focus_keyword', implode(', ', $keywords));
    }
    if (in_array('yoast', $plugins)) {
        update_post_meta($post_id, '_yoast_wpseo_focuskw', $keywords[0]);
    }
    if (in_array('aioseo', $plugins)) {
        update_post_meta($post_id, '_aioseop_keywords', implode(', ', $keywords));
    }
    if (in_array('wpmetaseo', $plugins)) {
        update_post_meta($post_id, '_metaseo_metakeywords', implode(', ', $keywords));
        update_post_meta($post_id, '_metaseo_metatitle', $title);
        update_post_meta($post_id, '_metaseo_metadesc', $desc);
    }

    return [
        'id' => $post_id,
        'title' => $title,
        'status' => get_post_status($post_id)
    ];
}


// إضافة صندوق توليد في صفحة المقال زر توليد يدوي داخل صفحة تحرير المقالة
add_action('add_meta_boxes', function () {
    add_meta_box(
        'ltkg_metabox',
        'توليد الكلمات المفتاحية الطويلة الذيل',
        'ltkg_render_metabox',
        'post',
        'side'
    );
});

function ltkg_render_metabox($post) {
    wp_nonce_field('ltkg_generate_single', 'ltkg_nonce_field');
    echo '<button type="button" id="ltkg-generate-btn" class="button button-primary">توليد الآن</button>';
    echo '<p id="ltkg-response" style="margin-top:10px;"></p>';
}
?>