<?php
/*
Plugin Name: LTKG for Rank Math
Description: توليد حتى 5 جمل طويلة الذيل وحفظها في Rank Math.
Version: 1.1
Author: Mona Jalal
*/

add_action('save_post', function($post_id) {
    if (get_post_type($post_id) !== 'post' || wp_is_post_revision($post_id)) return;

    $text = get_the_title($post_id) . '. ' . wp_strip_all_tags(get_post_field('post_content', $post_id));
    $keywords = ltkg_extract_long_tail_phrases($text, 5);

    update_post_meta($post_id, 'rank_math_focus_keyword', implode(', ', $keywords));
}, 20, 1);

function ltkg_extract_long_tail_phrases($text, $limit = 5) {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^\p{L}\p{N}\s]+/u', '', $text);
    $words = preg_split('/\s+/', $text);
    $phrases = [];
    $stop_words = array_merge(ltkg_arabic_stopwords(), ltkg_english_stopwords());

    for ($i = 0; $i < count($words) - 2; $i++) {
        $chunk = array_slice($words, $i, 3);
        $filtered = array_filter($chunk, fn($w) => !in_array($w, $stop_words));
        if (count($filtered) >= 2) $phrases[] = implode(' ', $chunk);
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
?>