<?php
defined('ABSPATH') || exit;
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('gopa-commerce', get_theme_file_uri('assets/commerce.css'), ['gopa-design'], filemtime(get_theme_file_path('assets/commerce.css')));
});
remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);
add_action('woocommerce_before_main_content', function () { echo '<main id="main" class="container page-main gopa-store">'; }, 10);
add_action('woocommerce_after_main_content', function () { echo '</main>'; }, 10);
add_shortcode('gopa_resource_shop', function () {
    if (!function_exists('WC')) return '';
    $shop = get_permalink(wc_get_page_id('shop'));
    return '<div class="resource-shopping"><a class="button" href="'.esc_url($shop).'">'.esc_html(gopa_text('shop_action')).' '.gopa_arrow().'</a><a class="text-link" href="'.esc_url(gopa_url('donate')).'">'.esc_html(gopa_text('donation_action')).' '.gopa_arrow().'</a><a class="text-link" href="'.esc_url(wc_get_cart_url()).'">'.esc_html(gopa_text('cart_action')).' '.gopa_arrow().'</a></div>';
});
add_shortcode('gopa_shop_preview', function () {
    if (!function_exists('WC')) return '';
    $products = wc_get_products(['status'=>'publish','visibility'=>'catalog','limit'=>1]);
    return $products ? do_shortcode('[products limit="6" columns="3" visibility="catalog" orderby="date" order="DESC"]') : '<p class="empty-state">'.esc_html(gopa_text('shop_empty')).'</p>';
});
add_shortcode('gopa_donation', function () {
    if (!function_exists('WC')) return '';
    $product = wc_get_product((int)get_option('gopa_donation_product'));
    if (!$product || !$product->is_purchasable()) return '';
    ob_start(); wc_print_notices(); ?>
    <form class="gopa-donation-form" method="post" action="<?php echo esc_url(get_permalink()); ?>">
      <label for="gopa-donation-amount"><?php echo esc_html(gopa_text('donation_amount')); ?> (<?php echo esc_html(get_woocommerce_currency()); ?>)</label>
      <div class="donation-input-row"><span aria-hidden="true"><?php echo esc_html(get_woocommerce_currency_symbol()); ?></span><input id="gopa-donation-amount" name="gopa_donation_amount" type="number" inputmode="decimal" min="1" max="100000" step="0.01" placeholder="25.00" required></div>
      <p><?php echo esc_html(gopa_text('donation_note')); ?></p>
      <?php wp_nonce_field('gopa_donate', 'gopa_donation_nonce'); ?>
      <input type="hidden" name="gopa_donation_submit" value="1">
      <button class="button" type="submit"><?php echo esc_html(gopa_text('donation_continue')); ?> <?php echo gopa_arrow(); ?></button>
    </form>
    <?php return ob_get_clean();
});
// Keep old bookmarks useful; the original editable combined page is retained.
add_action('template_redirect', function () {
    if (is_page('events-resources') && get_page_by_path('events')) { wp_safe_redirect(gopa_url('events'), 301); exit; }
});
