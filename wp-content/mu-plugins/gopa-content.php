<?php
/** Plugin Name: GOPA Content & Enquiries
 * Description: Permanent mission collections and private, admin-managed enquiries. Independent of the active theme.
 */
defined('ABSPATH') || exit;
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
add_filter('the_content',function($content){if(!(is_singular('gopa_event')||is_singular('gopa_hub'))||!in_the_loop()||!is_main_query())return $content;$date=get_post_meta(get_the_ID(),'gopa_date',true);$location=get_post_meta(get_the_ID(),'gopa_location',true);$meta='';if($date)$meta.='<p><strong>'.esc_html(function_exists('gopa_text')?gopa_text('date_label'):'Date').':</strong> '.esc_html(date_i18n(get_option('date_format'),strtotime($date))).'</p>';if($location)$meta.='<p><strong>'.esc_html(function_exists('gopa_text')?gopa_text('location_label'):'Location').':</strong> '.esc_html($location).'</p>';return $meta.$content;});

