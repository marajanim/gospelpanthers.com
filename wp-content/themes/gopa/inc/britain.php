<?php
defined('ABSPATH') || exit;
function gopa_britain_flag_url() {
    return gopa_setting('britain_flag_image', get_theme_file_uri('assets/union-jack.svg'));
}
add_action('customize_register', function ($customizer) {
    $customizer->add_setting('gopa_britain_flag_enabled', ['default'=>true,'sanitize_callback'=>'rest_sanitize_boolean']);
    $customizer->add_control('gopa_britain_flag_enabled', ['section'=>'gopa_brand','label'=>'Show the Great Britain flag on the homepage','type'=>'checkbox']);
    $customizer->add_setting('gopa_britain_flag_image', ['default'=>get_theme_file_uri('assets/union-jack.svg'),'sanitize_callback'=>'esc_url_raw']);
    $customizer->add_control(new WP_Customize_Image_Control($customizer, 'gopa_britain_flag_image', ['section'=>'gopa_brand','label'=>'Homepage flag image']));
});
add_action('wp_enqueue_scripts', function () {
    if (!is_front_page()) return;
    $enabled = (bool)gopa_setting('britain_flag_enabled', true);
    wp_add_inline_style('gopa-design', ':root{--gopa-britain-flag:url("'.esc_url(gopa_britain_flag_url()).'");--gopa-britain-opacity:'.($enabled?'0.23':'0').';}');
    if ($enabled) wp_add_inline_style('gopa-design', '.home .hero{background:radial-gradient(ellipse at 87% 90%,#c8102e1c,transparent 42%),#07101e}.home .hero-baseline{border-bottom:3px solid;border-image:linear-gradient(90deg,#012169 0%,#012169 33.33%,#fff 33.33%,#fff 66.66%,#c8102e 66.66%) 1;}');
}, 20);
// Retain the client's editable hero wording; the flag is controlled in Customize.
add_filter('render_block_core/group', function ($html, $block) {
    if (!is_front_page() || !gopa_setting('britain_flag_enabled', true) || !in_array('hero-copy', explode(' ', $block['attrs']['className'] ?? ''), true)) return $html;
    $flag = '<img class="britain-flag" src="'.esc_url(gopa_britain_flag_url()).'" alt="Union Jack — Great Britain" width="44" height="22" decoding="async">';
    return preg_replace_callback('/<p\b[^>]*class="[^"]*\beyebrow\b[^"]*"[^>]*>/', function ($match) use ($flag) { return $match[0].$flag; }, $html, 1);
}, 10, 2);
