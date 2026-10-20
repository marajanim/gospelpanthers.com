<?php
defined('ABSPATH') || exit;
function gopa_britain_flag_url() {
    return gopa_setting('britain_flag_image', get_theme_file_uri('assets/union-jack.svg'));
}
add_action('customize_register', function ($customizer) {
    $customizer->add_setting('gopa_britain_flag_enabled', ['default'=>true,'sanitize_callback'=>'rest_sanitize_boolean']);
    $customizer->add_control('gopa_britain_flag_enabled', ['section'=>'gopa_brand','label'=>'Show the British flag in the homepage photo collage','type'=>'checkbox']);
    $customizer->add_setting('gopa_britain_flag_image', ['default'=>get_theme_file_uri('assets/union-jack.svg'),'sanitize_callback'=>'esc_url_raw']);
    $customizer->add_control(new WP_Customize_Image_Control($customizer, 'gopa_britain_flag_image', ['section'=>'gopa_brand','label'=>'Homepage flag image']));
});
// Add the flag to the photo composition; all original pictures and text remain editable.
add_filter('render_block_core/group', function ($html, $block) {
    if (!is_front_page() || !gopa_setting('britain_flag_enabled', true) || !in_array('hero-art', explode(' ', $block['attrs']['className'] ?? ''), true) || str_contains($html, 'hero-britain-flag')) return $html;
    $flag='<figure class="hero-britain-flag"><img src="'.esc_url(gopa_britain_flag_url()).'" alt="Union Jack — Great Britain" width="280" height="140" decoding="async"></figure>';
    return preg_replace_callback('/(<\/(?:div|section)>\s*)$/', function ($match) use ($flag) { return $flag.$match[0]; }, $html, 1);
}, 10, 2);
