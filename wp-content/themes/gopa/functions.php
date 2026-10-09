<?php
defined('ABSPATH') || exit;
add_action('after_setup_theme', function () {
    add_theme_support('title-tag'); add_theme_support('post-thumbnails'); add_theme_support('custom-logo');
    add_theme_support('html5', ['search-form','gallery','caption','style','script']);
    add_theme_support('align-wide'); add_theme_support('responsive-embeds'); add_theme_support('editor-styles');
    add_post_type_support('page','excerpt');
    add_editor_style('assets/editor.css');
    register_nav_menus(['primary'=>'Main navigation','footer'=>'Footer links','legal'=>'Legal navigation']);
});
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('gopa', get_theme_file_uri('assets/site.css'), [], filemtime(get_theme_file_path('assets/site.css')));
    wp_enqueue_style('gopa-design', get_theme_file_uri('assets/design.css'), ['gopa'], filemtime(get_theme_file_path('assets/design.css')));
    wp_enqueue_script('gopa', get_theme_file_uri('assets/site.js'), [], filemtime(get_theme_file_path('assets/site.js')), true);
});
function gopa_url($slug) { $page=get_page_by_path($slug); return $page ? get_permalink($page) : home_url('/'.$slug.'/'); }
function gopa_setting($key, $default='') { return get_theme_mod('gopa_'.$key, $default); }
function gopa_arrow() { return '<span aria-hidden="true">↗</span>'; }
function gopa_image($post, $class='') { if (has_post_thumbnail($post)) echo get_the_post_thumbnail($post, 'large', ['class'=>$class,'loading'=>'lazy']); }
add_action('customize_register', function ($c) {
    $c->add_section('gopa_brand', ['title'=>'GOPA brand & contact', 'priority'=>30]);
    foreach (['brand_title'=>['Header name','GOSPEL PANTHERS'],'brand_subline'=>['Header subline','OF GREAT BRITAIN'],'footer_mandate'=>['Footer mandate','Know Christ. Proclaim Christ. Demonstrate Christ.'],'contact_heading'=>['Form introduction heading','There’s a place for you here.'],'contact_description'=>['Form introduction text','Connect with the movement. Tell us how you’d like to get involved.'],'email'=>['Contact email','info@gospelpanthers.com'], 'footer_line'=>['Footer statement','The Gospel. The Mission. The Movement.'], 'donate_url'=>['Giving / donation URL',''], 'livestream_url'=>['Live stream URL',''], 'podcast_url'=>['Podcast URL',''], 'facebook'=>['Facebook URL',''], 'instagram'=>['Instagram URL',''], 'youtube'=>['YouTube URL',''], 'tiktok'=>['TikTok URL',''], 'x'=>['X URL',''], 'linkedin'=>['LinkedIn URL','']] as $key=>$v) {
        $text=in_array($key,['brand_title','brand_subline','footer_mandate','footer_line','contact_heading','contact_description'],true);
        $sanitize=$key==='email'?'sanitize_email':($text?'sanitize_text_field':'esc_url_raw');
        $c->add_setting('gopa_'.$key,['default'=>$v[1],'sanitize_callback'=>$sanitize]);
        $c->add_control('gopa_'.$key,['label'=>$v[0],'section'=>'gopa_brand','type'=>$key==='email'?'email':($text?'text':'url')]);
    }
    foreach (['gold'=>['Accent colour','#c9a45c'],'ink'=>['Dark colour','#111511'],'paper'=>['Light colour','#f6f5ef']] as $key=>$v) {
        $c->add_setting('gopa_'.$key,['default'=>$v[1],'sanitize_callback'=>'sanitize_hex_color']);
        $c->add_control(new WP_Customize_Color_Control($c,'gopa_'.$key,['label'=>$v[0],'section'=>'gopa_brand']));
    }
});
add_action('wp_head',function(){ printf('<style>:root{--gold:%s;--ink:%s;--paper:%s}</style>',esc_attr(gopa_setting('gold','#c9a45c')),esc_attr(gopa_setting('ink','#111511')),esc_attr(gopa_setting('paper','#f6f5ef'))); });
require_once get_theme_file_path('inc/content-settings.php');
require_once get_theme_file_path('inc/shortcodes.php');

