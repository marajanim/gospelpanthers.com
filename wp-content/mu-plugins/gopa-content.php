<?php
/** Plugin Name: GOPA Content & Enquiries
 * Description: Permanent mission collections and private, admin-managed enquiries. Independent of the active theme.
 */
defined('ABSPATH') || exit;
// Add the sixth field once, then leave all copy/images under normal admin control.
add_action('init', function () {
    if (get_option('gopa_cyber_field_v1') || !post_type_exists('gopa_field')) return;
    if (!add_option('gopa_cyber_field_lock', time(), '', false)) return;
    try {
        $field = get_page_by_path('cyber', OBJECT, 'gopa_field');
        if (!$field) {
            $description = 'Working spirit-filled disciples to reach online users worldwide.';
            $id = wp_insert_post(['post_type'=>'gopa_field','post_status'=>'publish','post_title'=>'CYBER','post_name'=>'cyber','menu_order'=>5,'post_excerpt'=>$description,'post_content'=>'<!-- wp:paragraph --><p>'.$description.'</p><!-- /wp:paragraph -->'], true);
            if (is_wp_error($id)) return;
            update_post_meta($id, 'gopa_label', '06 / Mission field');
            update_post_meta($id, 'gopa_button', 'Explore');
            $images = get_posts(['post_type'=>'attachment','post_status'=>'inherit','numberposts'=>1,'meta_query'=>[['key'=>'_wp_attached_file','value'=>'gopa-campaign-calling.jpg','compare'=>'LIKE']]]);
            if ($images) set_post_thumbnail($id, $images[0]->ID);
        }
        $home_id = (int)get_option('page_on_front');
        $home = get_post($home_id);
        if ($home) {
            $content = preg_replace_callback('/\[gopa_collection\b[^\]]*\]/', function ($match) {
                if (!preg_match('/\btype=["\x27]gopa_field["\x27]/', $match[0])) return $match[0];
                return preg_replace('/\blimit=["\x27]5["\x27]/', 'limit="6"', $match[0]);
            }, $home->post_content);
            $content = str_replace('Five mission fields.', 'Six mission fields.', $content);
            if ($content !== $home->post_content) {
                wp_save_post_revision($home_id);
                $updated = wp_update_post(['ID'=>$home_id,'post_content'=>wp_slash($content)], true);
                if (is_wp_error($updated)) return;
            }
        }
        update_option('gopa_cyber_field_v1', true, false);
    } finally { delete_option('gopa_cyber_field_lock'); }
}, 20);
add_action('init',function(){
    foreach(['field'=>['Mission fields','Mission field','dashicons-admin-site-alt3'],'course'=>['Academy courses','Academy course','dashicons-welcome-learn-more'],'event'=>['Events','Event','dashicons-calendar-alt'],'resource'=>['Resources','Resource','dashicons-book-alt'],'hub'=>['Panther hubs','Panther hub','dashicons-location-alt']] as $key=>$v){
        register_post_type('gopa_'.$key,['labels'=>['name'=>$v[0],'singular_name'=>$v[1],'add_new_item'=>'Add '.$v[1],'edit_item'=>'Edit '.$v[1]],'public'=>true,'show_in_rest'=>true,'menu_icon'=>$v[2],'supports'=>['title','editor','excerpt','thumbnail','page-attributes','revisions'],'has_archive'=>false,'rewrite'=>['slug'=>$key==='field'?'mission':$key.'s']]);
    }
    register_post_type('gopa_enquiry',['labels'=>['name'=>'Enquiries','singular_name'=>'Enquiry'],'public'=>false,'show_ui'=>true,'show_in_rest'=>false,'menu_icon'=>'dashicons-email-alt','supports'=>['title','editor'],'capability_type'=>'post','capabilities'=>['create_posts'=>'do_not_allow'],'map_meta_cap'=>true]);
    register_taxonomy('gopa_resource_type','gopa_resource',['label'=>'Resource categories','public'=>true,'show_in_rest'=>true,'hierarchical'=>true]);
});
add_action('add_meta_boxes',function(){foreach(['gopa_field','gopa_course','gopa_event','gopa_resource','gopa_hub','post'] as $type){add_meta_box('gopa_details','Display & destination','gopa_details_box',$type,'side');}});
function gopa_details_box($post){wp_nonce_field('gopa_details','gopa_details_nonce'); foreach(['gopa_button'=>'Action button text','gopa_label'=>'Small label (e.g. Training / Academy)','gopa_url'=>'Registration or resource URL','gopa_date'=>'Event date (YYYY-MM-DD)','gopa_location'=>'Location'] as $key=>$label){echo '<p><label for="'.esc_attr($key).'">'.esc_html($label).'</label><input class="widefat" id="'.esc_attr($key).'" name="'.esc_attr($key).'" type="'.($key==='gopa_date'?'date':($key==='gopa_url'?'url':'text')).'" value="'.esc_attr(get_post_meta($post->ID,$key,true)).'"></p>';}}
add_action('save_post',function($id){if(!isset($_POST['gopa_details_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['gopa_details_nonce'])),'gopa_details') || !current_user_can('edit_post',$id) || wp_is_post_revision($id) || (defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)) return; foreach(['gopa_button','gopa_label','gopa_url','gopa_date','gopa_location'] as $key){if(isset($_POST[$key])) update_post_meta($id,$key,$key==='gopa_url'?esc_url_raw(wp_unslash($_POST[$key])):sanitize_text_field(wp_unslash($_POST[$key])));}});
function gopa_handle_enquiry(){
    $back=wp_get_referer() ?: home_url('/contact/'); $back=remove_query_arg('enquiry',$back);
    if(!isset($_POST['gopa_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['gopa_nonce'])),'gopa_enquiry')){wp_safe_redirect(add_query_arg('enquiry','invalid',$back));exit;}
    if(!empty($_POST['website'])){wp_safe_redirect(add_query_arg('enquiry','received',$back));exit;}
    $person=sanitize_text_field(wp_unslash($_POST['person']??'')); $email=sanitize_email(wp_unslash($_POST['email']??'')); $interest=sanitize_text_field(wp_unslash($_POST['interest']??'')); $message=sanitize_textarea_field(wp_unslash($_POST['message']??''));
    $key='gopa_rate_'.hash('sha256',($_SERVER['REMOTE_ADDR']??'unknown'));
    if(!$person || strlen($person)>150 || !is_email($email) || !$message || strlen($message)>5000 || empty($_POST['consent']) || (int)get_transient($key)>=5){wp_safe_redirect(add_query_arg('enquiry','invalid',$back));exit;}
    $id=wp_insert_post(['post_type'=>'gopa_enquiry','post_status'=>'private','post_title'=>$interest.' — '.$person,'post_content'=>"Name: $person\nEmail: $email\nInterest: $interest\n\n$message\n\nConsent: Agreed to contact about this enquiry."],true);
    if(is_wp_error($id)||!$id){wp_safe_redirect(add_query_arg('enquiry','invalid',$back));exit;}
    update_post_meta($id,'gopa_email',$email); set_transient($key,(int)get_transient($key)+1,HOUR_IN_SECONDS);
    $recipient=get_theme_mod('gopa_email','info@gospelpanthers.com');
    $sent=wp_mail($recipient,'GOPA enquiry: '.$interest,"$person <$email>\n\n$message\n\nView the enquiry in WordPress: ".admin_url('post.php?post='.$id.'&action=edit'),['Reply-To: '.$email]);
    update_post_meta($id,'gopa_email_sent',$sent?'yes':'no');
    wp_safe_redirect(add_query_arg('enquiry','received',$back));exit;
}
add_action('admin_post_gopa_enquiry','gopa_handle_enquiry');add_action('admin_post_nopriv_gopa_enquiry','gopa_handle_enquiry');
add_filter('manage_gopa_enquiry_posts_columns',function($c){$c['gopa_notification']='Email notification';return $c;});
add_action('manage_gopa_enquiry_posts_custom_column',function($c,$id){if($c==='gopa_notification') echo get_post_meta($id,'gopa_email_sent',true)==='yes'?'Sent':'Stored — check mail delivery';},10,2);
add_action('wp_dashboard_setup',function(){if(current_user_can('edit_pages'))wp_add_dashboard_widget('gopa_help','Your GOPA website',function(){echo '<p><strong>Pages</strong>: edit all page copy and home sections in the block editor.</p><p><strong>Mission fields, Academy courses, Events, Resources</strong>: add content, featured images, excerpts and destination links. These appear automatically on the website.</p><p><strong>Posts</strong>: publish news and stories. <strong>Appearance → Menus</strong>: navigation. <strong>Appearance → Customize</strong>: logo, palette, contact, social and media links.</p><p><strong>Enquiries</strong>: private submissions, including registrations, baptism requests, partnerships and testimonies. Configure reliable email delivery before launch.</p>';});});
// Expose event information where it belongs: on the event itself.
// Donation amounts are validated server-side and stored with the WooCommerce cart/order.
function gopa_donation_amount($raw) {
    if (!is_scalar($raw) || !preg_match('/^\d{1,6}(?:\.\d{1,2})?$/D', (string)$raw)) return null;
    $amount = (float)$raw;
    return $amount >= 1 && $amount <= 100000 ? number_format($amount, 2, '.', '') : null;
}
function gopa_is_donation($product_id) { return (int)$product_id === (int)get_option('gopa_donation_product', 0) && (int)$product_id > 0; }
add_filter('woocommerce_is_purchasable', function ($allowed, $product) { return gopa_is_donation($product->get_id()) && $product->get_status()==='publish' ? true : $allowed; }, 10, 2);
add_action('template_redirect', function () {
    if (empty($_POST['gopa_donation_submit']) || !function_exists('WC')) return;
    $nonce = sanitize_text_field(wp_unslash($_POST['gopa_donation_nonce'] ?? ''));
    $amount = gopa_donation_amount(wp_unslash($_POST['gopa_donation_amount'] ?? ''));
    if (!wp_verify_nonce($nonce, 'gopa_donate') || $amount === null) {
        wc_add_notice('Please enter a donation between 1 and 100,000 and try again.', 'error');
        wp_safe_redirect(home_url('/donate/')); exit;
    }
    $product_id = (int)get_option('gopa_donation_product');
    if (WC()->cart && WC()->cart->add_to_cart($product_id, 1, 0, [], ['gopa_donation_amount'=>$amount])) {
        wp_safe_redirect(wc_get_cart_url()); exit;
    }
    wp_safe_redirect(home_url('/donate/')); exit;
}, 5);
add_filter('woocommerce_add_to_cart_validation', function ($valid, $product_id, $quantity, $variation_id=0, $variations=[], $data=[]) {
    if (!gopa_is_donation($product_id)) return $valid;
    if ((int)$quantity !== 1 || gopa_donation_amount($data['gopa_donation_amount'] ?? '') === null) {
        wc_add_notice('Choose your gift amount on the donation page first.', 'error'); return false;
    }
    return $valid;
}, 10, 6);
add_action('woocommerce_before_calculate_totals', function ($cart) {
    foreach ($cart->get_cart() as $key=>$item) {
        if (!gopa_is_donation($item['product_id'])) continue;
        $amount = gopa_donation_amount($item['gopa_donation_amount'] ?? '');
        if ($amount === null) { $cart->remove_cart_item($key); continue; }
        $item['data']->set_price($amount);
    }
});
add_filter('woocommerce_get_item_data', function ($data, $item) {
    if (gopa_is_donation($item['product_id']) && isset($item['gopa_donation_amount'])) $data[]=['key'=>'Gift','value'=>'One-time donation'];
    return $data;
}, 10, 2);
add_action('woocommerce_checkout_create_order_line_item', function ($item, $key, $values) {
    if (gopa_is_donation($values['product_id'])) $item->add_meta_data('Gift', 'One-time donation', true);
}, 10, 3);
add_action('admin_notices', function () {
    if (!current_user_can('manage_woocommerce') || !class_exists('WC_Payment_Gateways') || !get_option('gopa_store_ready')) return;
    $gateways = WC()->payment_gateways()->payment_gateways();
    $stripe = $gateways['stripe'] ?? null;
    if (!$stripe || $stripe->enabled !== 'yes' || $stripe->get_option('testmode') !== 'no' || (!$stripe->get_option('secret_key') && !$stripe->get_option('publishable_key'))) {
        echo '<div class="notice notice-warning"><p><strong>GOPA shop:</strong> Connect and enable your live Stripe account in <a href="'.esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=stripe')).'">WooCommerce → Settings → Payments → Stripe</a> before accepting payments. Add sale items in Products. The one-time donation page is already prepared.</p></div>';
    }
});
add_filter('the_content',function($content){if(!(is_singular('gopa_event')||is_singular('gopa_hub'))||!in_the_loop()||!is_main_query())return $content;$date=get_post_meta(get_the_ID(),'gopa_date',true);$location=get_post_meta(get_the_ID(),'gopa_location',true);$meta='';if($date)$meta.='<p><strong>'.esc_html(function_exists('gopa_text')?gopa_text('date_label'):'Date').':</strong> '.esc_html(date_i18n(get_option('date_format'),strtotime($date))).'</p>';if($location)$meta.='<p><strong>'.esc_html(function_exists('gopa_text')?gopa_text('location_label'):'Location').':</strong> '.esc_html($location).'</p>';return $meta.$content;});

