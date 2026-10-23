<?php
defined('ABSPATH') || exit;
function gopa_upgrade_additional_events() {
    if (get_option('gopa_additional_events_v1')) return;
    $events=[
        ['slug'=>'imec-international-missionaries-evangelists-conference','title'=>'iMEC: International Missionaries & Evangelists Conference','image'=>'gopa-event-imec.png','description'=>'An international gathering of missionaries and evangelists focused on Gospel mission and reaching the nations.'],
        ['slug'=>'night-of-gratitude','title'=>'Night of Gratitude','image'=>'gopa-event-gratitude.png','description'=>'A gathering for worship, thanksgiving and praise, giving thanks to God together.'],
    ];
    global $wpdb;
    $order=(int)$wpdb->get_var($wpdb->prepare("SELECT MAX(menu_order) FROM {$wpdb->posts} WHERE post_type=%s AND post_status NOT IN ('trash','auto-draft')",'gopa_event'));
    foreach ($events as $event) {
        $post=get_page_by_path($event['slug'],OBJECT,'gopa_event');
        // Preserve any later admin edits and avoid duplicate entries on retries.
        if ($post && !get_post_meta($post->ID,'_gopa_additional_events_v1',true)) throw new RuntimeException('An existing event with this name needs review.');
        if ($post && get_post_meta($post->ID,'_gopa_event_import_complete',true)) continue;
        $images=get_posts(['post_type'=>'attachment','post_status'=>'inherit','numberposts'=>1,'meta_key'=>'_gopa_event_artwork','meta_value'=>$event['slug']]);
        $image_id=$images ? $images[0]->ID : 0;
        if (!$image_id) {
            $upload=wp_upload_bits($event['image'],null,file_get_contents(get_theme_file_path('assets/'.$event['image'])));
            if ($upload['error']) throw new RuntimeException($upload['error']);
            $image_id=wp_insert_attachment(['post_title'=>$event['title'].' — event poster','post_mime_type'=>'image/png','post_status'=>'inherit','meta_input'=>['_gopa_event_artwork'=>$event['slug']]],$upload['file'],0,true);
            if (is_wp_error($image_id)) throw new RuntimeException($image_id->get_error_message());
            require_once ABSPATH.'wp-admin/includes/image.php';
            wp_update_attachment_metadata($image_id,wp_generate_attachment_metadata($image_id,$upload['file']));
            update_post_meta($image_id,'_wp_attachment_image_alt',$event['title'].' — Gospel Panthers of Great Britain');
        }
        $id=$post ? $post->ID : wp_insert_post([
            'post_type'=>'gopa_event','post_status'=>'publish','post_name'=>$event['slug'],'post_title'=>wp_slash($event['title']),
            'post_excerpt'=>wp_slash($event['description']),
            'post_content'=>wp_slash('<!-- wp:paragraph --><p>'.esc_html($event['description']).'</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Contact the Gospel Panthers team for confirmed dates, venue and registration details.</p><!-- /wp:paragraph -->'),
            'menu_order'=>++$order,
            'meta_input'=>['_gopa_additional_events_v1'=>'1','gopa_label'=>'Gatherings / GOPA','gopa_button'=>'Explore'],
        ],true);
        if (is_wp_error($id)) throw new RuntimeException($id->get_error_message());
        set_post_thumbnail($id,$image_id);
        update_post_meta($id,'_gopa_event_import_complete',true);
    }
    // Ensure a collection that was limited to four also includes the new events.
    foreach (get_posts(['post_type'=>'page','post_status'=>['publish','draft','private'],'numberposts'=>-1]) as $page) {
        $content=preg_replace_callback('/\[gopa_collection\b[^\]]*\]/',function($match){
            if (!preg_match('/\btype=["\x27]gopa_event["\x27]/',$match[0])) return $match[0];
            return preg_replace('/\blimit=(["\x27])[1-5]\1/','limit="6"',$match[0]);
        },$page->post_content);
        if ($content===$page->post_content) continue;
        wp_save_post_revision($page->ID);
        $saved=wp_update_post(['ID'=>$page->ID,'post_content'=>wp_slash($content)],true);
        if (is_wp_error($saved)) throw new RuntimeException($saved->get_error_message());
    }
    update_option('gopa_additional_events_v1',true,false);
    echo "iMEC and Night of Gratitude added to the editable Events collection.\n";
}
