<?php
/*
Plugin Name: LTKG for WP Meta SEO
Description: توليد حتى 5 جمل طويلة الذيل وحفظها في WP Meta SEO.
Version: 1.1
Author: Mona Jalal
*/

add_action('save_post', function($post_id) {
    if (get_post_type($post_id) !== 'post' || wp_is_post_revision($post_id)) return;

    $title = get_the_title($post_id);
    $content = wp_strip_all_tags(get_post_field('post_content', $post_id));
    $desc = wp_trim_words($content, 25, '...');
    $keywords = ltkg_extract_long_tail_phrases($title . '. ' . $content, 5);

    update_post_meta($post_id, '_metaseo_metakeywords', implode(', ', $keywords));
    update_post_meta($post_id, '_metaseo_metatitle', $title);
    update_post_meta($post_id, '_metaseo_metadesc', $desc);
}, 20, 1);

// استخدمي نفس دوال التوليد السابقة (ltkg_extract_long_tail_phrases و stopwords)
