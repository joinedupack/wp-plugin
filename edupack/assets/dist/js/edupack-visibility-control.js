jQuery(document).ready(function($) {
  $("a.toplevel_page_edupack-visibility-control-publish").click(function(e) {
    $("div.wp-menu-image img").attr('src', '/wp-includes/images/wpspin.gif');
  })
  $("a.toplevel_page_edupack-visibility-control-unpublish").click(function(e) {
    $("div.wp-menu-image img").attr('src', '/wp-includes/images/wpspin.gif');
  })
});
