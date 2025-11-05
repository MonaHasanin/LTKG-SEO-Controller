jQuery(document).ready(function($) {

    $('#ltkg-generate-all').click(function() {
      $('#ltkg-bulk-response').html('...جاري توليد الكلمات لكل المقالات');
  
      $.post(ajaxurl, {
        action: 'ltkg_generate_all_posts',
        nonce: $('#ltkg_nonce_field').val()
      }, function(response) {
        if (response.success) {
          let html = '<strong>✅ تم التوليد:</strong><ul>';
          response.data.forEach(item => {
            html += '<li><strong>' + item.title + '</strong> (' + item.status + ')</li>';
          });
          html += '</ul>';
          $('#ltkg-bulk-response').html(html);
        } else {
          $('#ltkg-bulk-response').text('❌ فشل التوليد');
        }
      });
    });
  
    $('#ltkg-generate-today').click(function() {
      $('#ltkg-bulk-response').html('...جاري توليد الكلمات لمقالات اليوم');
  
      $.post(ajaxurl, {
        action: 'ltkg_generate_today_posts',
        nonce: $('#ltkg_nonce_field').val()
      }, function(response) {
        if (response.success) {
          let html = '<strong>✅ تم التوليد:</strong><ul>';
          response.data.forEach(item => {
            html += '<li><strong>' + item.title + '</strong> (' + item.status + ')</li>';
          });
          html += '</ul>';
          $('#ltkg-bulk-response').html(html);
        } else {
          $('#ltkg-bulk-response').text('❌ فشل التوليد');
        }
      });
    });
  
  });
  add_action('admin_menu', function () {
    add_management_page('LTKG Bulk Generate', 'LTKG Bulk SEO', 'manage_options', 'ltkg-seo-bulk', 'ltkg_bulk_page_callback');
});

function ltkg_bulk_page_callback() {
    wp_nonce_field('ltkg_bulk_action', 'ltkg_nonce_field');

    echo '<div class="wrap"><h1>توليد جماعي للكلمات الطويلة الذيل</h1>';
    echo '<button id="ltkg-generate-all" class="button button-primary">توليد لكل المقالات</button> ';
    echo '<button id="ltkg-generate-today" class="button">توليد لمقالات اليوم فقط</button>';
    echo '<div id="ltkg-bulk-response" style="margin-top:20px;"></div>';
    echo '</div>';
}

add_action('admin_enqueue_scripts', function($hook) {
    if ($hook == 'tools_page_ltkg-seo-bulk') {
        wp_enqueue_script('ltkg-bulk-js', plugins_url('ltkg-bulk.js', __FILE__), ['jquery'], null, true);
    }
});
