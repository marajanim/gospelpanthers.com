<?php
defined('ABSPATH') || exit;
function gopa_content_fields(){
    return [
        'Navigation'=>[
            'skip_label'=>['Skip link','Skip to content'], 'home_label'=>['Logo link accessible name','Gospel Panthers home'],
            'page_prefix'=>['Page breadcrumb prefix','Gospel Panthers'],
            'menu_label'=>['Mobile menu','Menu'], 'nav_label'=>['Navigation accessible name','Main navigation'],
            'join_label'=>['Header and footer action','Join the movement'], 'join_url'=>['Header and footer action URL','','url'],
            'back_top'=>['Back to top','Back to top'], 'back_movement'=>['Return to collection','Back to the movement'],
            'copyright'=>['Copyright suffix','All Rights Reserved.'],
        ],
        'Cards and resources'=>[
            'card_action'=>['Default card action','Explore'], 'card_label'=>['Default card small label','The movement'],
            'empty_collection'=>['Empty collection message','More updates will be shared here soon.'],
            'all_resources'=>['All resources filter','All resources'], 'filter_label'=>['Resource filter accessible name','Filter resources'],
            'open_resource'=>['Resource action','Open resource'], 'register_action'=>['Registration action','Register / find out more'],
            'enquire_action'=>['Enquiry action','Enquire with the team'], 'date_label'=>['Event date label','Date'], 'location_label'=>['Event location label','Location'],
            'giving_label'=>['Giving action','Support the mission'], 'podcast_label'=>['Podcast action','Listen to the podcast'],
            'livestream_label'=>['Live stream action','Watch live stream'],
            'media_empty'=>['Unconfigured media message','Podcast and live stream links will be shared here when available.'],
            'hub_empty'=>['No published hubs message','Contact the team to find a Panther hub near you.'],
            'previous_label'=>['Pagination previous','Previous'], 'next_label'=>['Pagination next','Next'],
            'read_more'=>['Archive action','Read more'], 'search_title'=>['Search results heading','Search results'], 'news_title'=>['Default archive heading','News & stories'],
        ],
        'Enquiry forms'=>[
            'contact_eyebrow'=>['Small heading','Start a conversation'],
            'form_name'=>['Name field','Your name'], 'form_email'=>['Email field','Email address'],
            'form_interest'=>['Interest field','I’m interested in'], 'form_message'=>['Message field','Your message'],
            'form_options'=>['Interest choices — one per line',"General enquiry\nBecome a Gospel Panther\nSTORM Academy registration\nChurch / GOPA Alliance\nMissionbearer partnership\nVolunteer or find a hub\nGet baptised\nShare my story",'textarea'],
            'form_consent'=>['Consent wording','I agree to be contacted about this enquiry.'],
            'privacy_label'=>['Privacy link','Privacy notice'], 'privacy_url'=>['Privacy URL override','','url'],
            'form_submit'=>['Submit action','Send enquiry'],
            'form_success'=>['Success message','Thank you. Your enquiry has been received. The team will be in touch.'],
            'form_error'=>['Validation or rate limit message','Please check your details and try again.'],
        ],
        'Social labels'=>[
            'facebook_label'=>['Facebook','Facebook'],'instagram_label'=>['Instagram','Instagram'],'youtube_label'=>['YouTube','YouTube'],
            'tiktok_label'=>['TikTok','TikTok'],'x_label'=>['X','X'],'linkedin_label'=>['LinkedIn','LinkedIn'],
            'social_empty'=>['No configured social links','Social links will be shared here when available.'],
        ],
        'Not found page'=>[
            '404_eyebrow'=>['Small heading','404 / A different direction'], '404_title'=>['Heading','Let’s find your way.'],
            '404_body'=>['Description','This page may have moved. Explore the movement from our home page.'], '404_action'=>['Action','Back to home'],
        ],
    ];
}
function gopa_text($key){foreach(gopa_content_fields() as $fields)if(isset($fields[$key]))return gopa_setting($key,$fields[$key][1]);return '';}
function gopa_join_url(){return gopa_setting('join_url') ?: gopa_url('get-involved');}
function gopa_interests(){return array_values(array_filter(array_map('trim',preg_split('/\r\n|\r|\n/',gopa_text('form_options')))));}
add_action('admin_menu',function(){add_theme_page('Website text & links','Website text & links','manage_options','gopa-content','gopa_content_admin');});
function gopa_content_admin(){
    if(!current_user_can('manage_options'))return;
    if(isset($_POST['gopa_content_save'])){
        check_admin_referer('gopa_content_save');
        foreach(gopa_content_fields() as $fields)foreach($fields as $key=>$field){
            if(!isset($_POST['gopa_copy'][$key])||!is_string($_POST['gopa_copy'][$key]))continue;
            $value=wp_unslash($_POST['gopa_copy'][$key]);$type=$field[2]??'text';
            set_theme_mod('gopa_'.$key,$type==='url'?esc_url_raw($value):($type==='textarea'?sanitize_textarea_field($value):sanitize_text_field($value)));
        }
        echo '<div class="notice notice-success is-dismissible"><p>Website text saved.</p></div>';
    }
    echo '<div class="wrap"><h1>Website text & links</h1><p>Edit shared buttons, form labels, messages and navigation text here. Edit page content and photographs in <a href="'.esc_url(admin_url('edit.php?post_type=page')).'">Pages</a>. Change the logo, brand, colours, contact and social URLs in <a href="'.esc_url(admin_url('customize.php')).'">Customize</a>.</p><form method="post">';
    wp_nonce_field('gopa_content_save');
    foreach(gopa_content_fields() as $group=>$fields){echo '<h2>'.esc_html($group).'</h2><table class="form-table" role="presentation">';foreach($fields as $key=>$field){$id='gopa-copy-'.$key;$value=gopa_text($key);$type=$field[2]??'text';echo '<tr><th scope="row"><label for="'.esc_attr($id).'">'.esc_html($field[0]).'</label></th><td>';if($type==='textarea')echo '<textarea class="large-text" rows="8" id="'.esc_attr($id).'" name="gopa_copy['.esc_attr($key).']">'.esc_textarea($value).'</textarea>';else echo '<input class="regular-text" type="'.esc_attr($type).'" id="'.esc_attr($id).'" name="gopa_copy['.esc_attr($key).']" value="'.esc_attr($value).'">';echo '</td></tr>';}echo '</table>';}
    submit_button('Save website text','primary','gopa_content_save');echo '</form></div>';
}
