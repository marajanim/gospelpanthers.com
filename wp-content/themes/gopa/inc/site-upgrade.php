<?php
/** One-time, CLI-only production content/store migration. No payment credentials. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = realpath($argv[1] ?? '');
if (!$root || !is_file($root.'/wp-load.php')) throw new RuntimeException('WordPress root required.');
ini_set('memory_limit', '512M');
$_SERVER['HTTP_HOST'] = 'www.gospelpanthers.com';
$_SERVER['REQUEST_METHOD'] = 'GET';
define('DISABLE_WP_CRON', true);
define('FS_METHOD', 'direct');
require $root.'/wp-load.php';
if (!in_array(wp_parse_url(home_url(), PHP_URL_HOST), ['www.gospelpanthers.com','gospelpanthers.com','gopa.local'], true)) throw new RuntimeException('Unexpected WordPress site.');
require_once __DIR__.'/about-upgrade.php';
gopa_upgrade_about_page();
require_once __DIR__.'/mission-cards-upgrade.php';
gopa_upgrade_mission_cards();
require_once __DIR__.'/cyber-image-upgrade.php';
gopa_upgrade_cyber_image();
if (!get_option('gopa_facebook_page_v1')) {
    set_theme_mod('gopa_facebook', 'https://www.facebook.com/share/1JPgvywZXK');
    update_option('gopa_facebook_page_v1', true, false);
    echo "Facebook page link updated.\n";
}
// Set the requested logo once through WordPress's native Site Icon setting.
// Later admin changes remain authoritative.
if (!get_option('gopa_logo_favicon_v1')) {
    $logo_id = (int)get_theme_mod('custom_logo');
    $source = $logo_id ? get_attached_file($logo_id) : get_theme_file_path('assets/logo.png');
    $editor = wp_get_image_editor($source);
    if (is_wp_error($editor)) throw new RuntimeException('Cannot prepare the logo site icon.');
    $resized = $editor->resize(512, 512, true);
    if (is_wp_error($resized)) throw new RuntimeException('Cannot resize the logo site icon.');
    $uploads = wp_upload_dir();
    if ($uploads['error']) throw new RuntimeException('Cannot access site icon uploads.');
    $filename = wp_unique_filename($uploads['path'], 'gospel-panthers-site-icon.png');
    $saved = $editor->save($uploads['path'].'/'.$filename, 'image/png');
    if (is_wp_error($saved)) throw new RuntimeException('Cannot save the logo site icon.');
    require_once ABSPATH.'wp-admin/includes/image.php';
    foreach ([32,180,192,270] as $size) add_image_size('site_icon-'.$size, $size, $size, true);
    $icon_id = wp_insert_attachment(['post_title'=>'Gospel Panthers site icon','post_mime_type'=>'image/png','post_status'=>'inherit'], $saved['path'], 0, true);
    if (is_wp_error($icon_id)) throw new RuntimeException('Cannot add the site icon to the Media Library.');
    wp_update_attachment_metadata($icon_id, wp_generate_attachment_metadata($icon_id, $saved['path']));
    update_post_meta($icon_id, '_wp_attachment_image_alt', 'Gospel Panthers logo');
    update_option('site_icon', $icon_id);
    update_option('gopa_logo_favicon_v1', true, false);
    echo "Gospel Panthers logo set as the WordPress site icon.\n";
}
if (get_option('gopa_store_ready') === '2026-10-v1') { echo "GOPA store migration already complete.\n"; exit; }
require_once ABSPATH.'wp-admin/includes/admin.php';
require_once ABSPATH.'wp-admin/includes/plugin.php';
require_once ABSPATH.'wp-admin/includes/plugin-install.php';
require_once ABSPATH.'wp-admin/includes/class-wp-upgrader.php';
wp_set_current_user((int)(get_users(['role'=>'administrator','number'=>1,'fields'=>'ID'])[0] ?? 0));
$plugins_changed = false;
$new_woocommerce = !is_file(WP_PLUGIN_DIR.'/woocommerce/woocommerce.php');
foreach (['woocommerce'=>'woocommerce/woocommerce.php','woocommerce-gateway-stripe'=>'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php'] as $slug=>$plugin) {
    if (!is_file(WP_PLUGIN_DIR.'/'.$plugin)) {
        $info = plugins_api('plugin_information', ['slug'=>$slug,'fields'=>['sections'=>false]]);
        if (is_wp_error($info)) throw new RuntimeException($info->get_error_message());
        if (!str_starts_with($info->download_link, 'https://downloads.wordpress.org/plugin/')) throw new RuntimeException('Unexpected plugin source.');
        $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
        $installed = $upgrader->install($info->download_link);
        if (is_wp_error($installed) || !$installed) throw new RuntimeException('Could not install '.$slug);
    }
    if (!is_plugin_active($plugin)) {
        $result = activate_plugin($plugin);
        if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
        $plugins_changed = true;
    }
}
if ($new_woocommerce) {
    update_option('woocommerce_currency','GBP');
    update_option('woocommerce_default_country','GB');
    update_option('woocommerce_coming_soon','no');
}
if ($plugins_changed) {
    // Re-bootstrap so both plugins receive their normal init lifecycle.
    $process = proc_open([PHP_BINARY,__FILE__,$root], [0=>STDIN,1=>STDOUT,2=>STDERR], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Cannot restart store setup.');
    exit(proc_close($process));
}
if (!class_exists('WC_Install')) throw new RuntimeException('WooCommerce unavailable.');
if (!get_option('gopa_store_before')) {
    $menus=[];
    foreach(wp_get_nav_menus() as $menu) $menus[$menu->term_id]=wp_get_nav_menu_items($menu->term_id);
    update_option('gopa_store_before',['menus'=>$menus,'recorded_at'=>gmdate('c')],false);
}
WC_Install::create_pages();
function gopa_store_page($title,$slug,$content,$excerpt='') {
    $existing = get_page_by_path($slug);
    if ($existing) return $existing->ID;
    $id = wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$title,'post_name'=>$slug,'post_content'=>$content,'post_excerpt'=>$excerpt], true);
    if (is_wp_error($id)) throw new RuntimeException($id->get_error_message());
    return $id;
}
function gopa_store_heading($text) { return '<!-- wp:heading --><h2 class="wp-block-heading">'.esc_html($text).'</h2><!-- /wp:heading -->'; }
function gopa_store_paragraph($text) { return '<!-- wp:paragraph --><p>'.esc_html($text).'</p><!-- /wp:paragraph -->'; }
function gopa_store_shortcode($shortcode) { return '<!-- wp:shortcode -->'.$shortcode.'<!-- /wp:shortcode -->'; }
$legacy = get_page_by_path('events-resources');
$event_content = '';
$resource_content = '';
if ($legacy) {
    // Split at the existing Resources heading, preserving all client-edited blocks.
    $resource_started = false;
    foreach (parse_blocks($legacy->post_content) as $block) {
        if ($block['blockName']==='core/heading' && (str_contains($block['innerHTML'], 'id="resources"') || preg_match('/resources/i', wp_strip_all_tags($block['innerHTML'])))) $resource_started = true;
        if ($resource_started) $resource_content .= serialize_block($block);
        else $event_content .= serialize_block($block);
    }
    if (!$resource_started || !str_contains($event_content, 'gopa_event') || !str_contains($resource_content, 'gopa_resource')) throw new RuntimeException('Could not safely split combined page; original retained.');
}
if (!$event_content) $event_content = gopa_store_heading('Gather. Be equipped. Go.').gopa_store_shortcode('[gopa_collection type="gopa_event" limit="12" paginate="yes"]');
if (!$resource_content) $resource_content = gopa_store_heading('Free resource library').gopa_store_shortcode('[gopa_resource_filters]').gopa_store_shortcode('[gopa_collection type="gopa_resource" limit="12" paginate="yes"]');
$events = gopa_store_page('Events','events',$event_content,'Gatherings that equip, encourage and mobilise people for Gospel mission.');
$resources = gopa_store_page('Resources','resources',gopa_store_heading('Equip your faith. Support the mission.').gopa_store_paragraph('Explore our shop, give towards Gospel mission, or find teaching and practical tools in the free library.').gopa_store_shortcode('[gopa_resource_shop]').gopa_store_heading('From the shop').gopa_store_shortcode('[gopa_shop_preview]').$resource_content,'Teaching, tools and resources for everyday mission.');
$donate = gopa_store_page('Make a donation','donate',gopa_store_heading('Give towards Gospel mission.').gopa_store_paragraph('Your generosity helps equip believers, support outreach and make Jesus known. Choose a one-time gift below.').gopa_store_shortcode('[gopa_donation]'),'Support the work of Gospel Panthers with a one-time gift.');
if ($legacy && has_post_thumbnail($legacy->ID)) foreach ([$events,$resources] as $id) if (!has_post_thumbnail($id)) set_post_thumbnail($id,get_post_thumbnail_id($legacy->ID));
if (!get_option('gopa_donation_product')) {
    $existing = get_posts(['post_type'=>'product','post_status'=>'any','meta_key'=>'_gopa_donation','meta_value'=>'yes','numberposts'=>1]);
    if ($existing) $donation_id = $existing[0]->ID;
    else {
        $product = new WC_Product_Simple();
        $product->set_name('Support the Gospel mission'); $product->set_status('publish'); $product->set_virtual(true); $product->set_sold_individually(true); $product->set_catalog_visibility('hidden'); $product->set_regular_price('0'); $product->set_tax_status('none'); $product->update_meta_data('_gopa_donation','yes'); $donation_id=$product->save();
    }
    update_option('gopa_donation_product',$donation_id);
}
// Update only links to the old combined page; keep unrelated menu edits/order.
foreach (wp_get_nav_menus() as $menu) {
    $items = wp_get_nav_menu_items($menu->term_id) ?: [];
    $has_resources = false;
    foreach ($items as $item) if ((int)$item->object_id===$resources || str_contains($item->url,'/resources/')) $has_resources = true;
    $insert_position = null;
    foreach ($items as $item) {
        if (!str_contains($item->url,'/events-resources/')) continue;
        $is_resource = str_contains($item->url,'#resources') || preg_match('/^resources$/i',$item->title);
        $id=$is_resource?$resources:$events;
        if ($is_resource) $has_resources = true;
        $result=wp_update_nav_menu_item($menu->term_id,$item->ID,['menu-item-object-id'=>$id,'menu-item-object'=>'page','menu-item-type'=>'post_type','menu-item-title'=>$is_resource?'Resources':'Events','menu-item-status'=>'publish','menu-item-parent-id'=>$item->menu_item_parent,'menu-item-position'=>$item->menu_order]);
        if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
        if (!$is_resource && $insert_position===null) $insert_position=$item->menu_order+1;
    }
    if (!$has_resources && $insert_position!==null) {
        foreach ($items as $item) if ($item->menu_order >= $insert_position) wp_update_post(['ID'=>$item->ID,'menu_order'=>$item->menu_order+1]);
        wp_update_nav_menu_item($menu->term_id,0,['menu-item-object-id'=>$resources,'menu-item-object'=>'page','menu-item-type'=>'post_type','menu-item-title'=>'Resources','menu-item-status'=>'publish','menu-item-position'=>$insert_position]);
    }
}
update_option('gopa_store_ready','2026-10-v1');
flush_rewrite_rules(false);
echo "GOPA Events, Resources, shop and donation pages prepared. Stripe connection required in WordPress admin.\n";
