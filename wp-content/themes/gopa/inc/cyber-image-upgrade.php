<?php
defined('ABSPATH') || exit;
function gopa_upgrade_cyber_image() {
    if (get_option('gopa_cyber_image_v1')) return;
    $field=get_page_by_path('cyber', OBJECT, 'gopa_field');
    $page=get_page_by_path('about-us');
    if (!$field || !$page) throw new RuntimeException('CYBER field and About page required.');
    $images=get_posts(['post_type'=>'attachment','post_status'=>'inherit','numberposts'=>1,'meta_key'=>'_gopa_cyber_image_v1','meta_value'=>'1']);
    $id=$images ? $images[0]->ID : 0;
    if (!$id) {
        $upload=wp_upload_bits('gopa-cyber-discipleship.png',null,file_get_contents(get_theme_file_path('assets/gopa-cyber-discipleship.png')));
        if ($upload['error']) throw new RuntimeException($upload['error']);
        $id=wp_insert_attachment(['post_title'=>'CYBER — worldwide digital discipleship','post_mime_type'=>'image/png','post_status'=>'inherit','meta_input'=>['_gopa_cyber_image_v1'=>'1']],$upload['file'],0,true);
        if (is_wp_error($id)) throw new RuntimeException($id->get_error_message());
        require_once ABSPATH.'wp-admin/includes/image.php';
        wp_update_attachment_metadata($id,wp_generate_attachment_metadata($id,$upload['file']));
        update_post_meta($id,'_wp_attachment_image_alt','CYBER — working spirit-filled disciples to reach online users worldwide');
    }
    $blocks=parse_blocks($page->post_content); $changed=0;
    $replace=function(&$items) use (&$replace,&$changed,$id) {
        foreach ($items as &$block) {
            if (in_array('about-field-card',explode(' ',$block['attrs']['className']??''),true) && preg_match('/<strong\b[^>]*>\s*CYBER\s*<\/strong>/iu',serialize_block($block))) {
                foreach ($block['innerBlocks'] as &$child) {
                    if ($child['blockName']!=='core/image') continue;
                    $attrs=['id'=>(int)$id,'sizeSlug'=>'large','linkDestination'=>'none','className'=>'about-field-image'];
                    $image='<!-- wp:image '.wp_json_encode($attrs).' --><figure class="wp-block-image size-large about-field-image"><img src="'.esc_url(wp_get_attachment_image_url($id,'large')).'" alt="'.esc_attr(get_post_meta($id,'_wp_attachment_image_alt',true)).'" class="wp-image-'.(int)$id.'"/></figure><!-- /wp:image -->';
                    $child=parse_blocks($image)[0]; $changed++;
                }
                unset($child);
            } elseif (!empty($block['innerBlocks'])) $replace($block['innerBlocks']);
        }
        unset($block);
    };
    $replace($blocks);
    if ($changed!==1) throw new RuntimeException('Expected one About CYBER image; page left unchanged.');
    wp_save_post_revision($page->ID);
    $saved=wp_update_post(['ID'=>$page->ID,'post_content'=>wp_slash(serialize_blocks($blocks))],true);
    if (is_wp_error($saved)) throw new RuntimeException($saved->get_error_message());
    set_post_thumbnail($field->ID,$id);
    update_option('gopa_cyber_image_v1',true,false);
    echo "CYBER artwork updated in the Media Library, mission field and About page.\n";
}
