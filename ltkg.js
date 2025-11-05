jQuery(document).ready(function($) {
    $('#ltkg-generate-btn').click(function(e) {
      e.preventDefault();
  
      var post_id = $('#post_ID').val();
      var nonce = $('#ltkg_nonce_field').val();
  
      $('#ltkg-response').html('جاري التوليد...');
  
      $.post(ajaxurl, {
        action: 'ltkg_generate_single_post',
        post_id: post_id,
        nonce: nonce
      }, function(response) {
        if (response.success) {
          $('#ltkg-response').html('<span style="color:green;">✅ تم توليد الكلمات بنجاح</span>');
        } else {
          $('#ltkg-response').html('<span style="color:red;">❌ فشل التوليد</span>');
        }
      });
    });
  });
  add_action('admin_enqueue_scripts', function($hook) {
    global $post;
    if ($hook == 'post.php' || $hook == 'post-new.php') {
        wp_enqueue_script('ltkg-js', plugins_url('ltkg.js', __FILE__), ['jquery'], null, true);
    }
});
