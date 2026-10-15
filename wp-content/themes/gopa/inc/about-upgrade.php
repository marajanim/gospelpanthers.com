<?php
defined('ABSPATH') || exit;
function gopa_about_group($class, $blocks, $tag='div') {
    $attrs=['className'=>$class]; if($tag!=='div') $attrs['tagName']=$tag;
    return '<!-- wp:group '.wp_json_encode($attrs).' --><'.$tag.' class="wp-block-group '.esc_attr($class).'">'.$blocks.'</'.$tag.'><!-- /wp:group -->';
}
function gopa_about_image($name, $class) {
    $images=get_posts(['post_type'=>'attachment','post_status'=>'inherit','numberposts'=>1,'meta_query'=>[['key'=>'_wp_attached_file','value'=>'gopa-'.$name,'compare'=>'LIKE']]]);
    $id=$images ? $images[0]->ID : 0;
    $url=$id ? wp_get_attachment_image_url($id,'large') : get_theme_file_uri('assets/'.$name);
    $alt=$id ? get_post_meta($id,'_wp_attachment_image_alt',true) : 'Gospel Panthers campaign artwork';
    $attrs=['sizeSlug'=>'large','linkDestination'=>'none','className'=>$class]; if($id) $attrs['id']=$id;
    return '<!-- wp:image '.wp_json_encode($attrs).' --><figure class="wp-block-image size-large '.esc_attr($class).'"><img src="'.esc_url($url).'" alt="'.esc_attr($alt).'"'.($id?' class="wp-image-'.absint($id).'"':'').'/></figure><!-- /wp:image -->';
}
function gopa_about_details($block, $summary) {
    return '<!-- wp:details {"className":"about-more"} --><details class="wp-block-details about-more"><summary>'.esc_html($summary).'</summary>'.serialize_block($block).'</details><!-- /wp:details -->';
}
function gopa_upgrade_about_page() {
    if(get_option('gopa_about_visual_v1')) return;
    $page=get_page_by_path('about-us'); if(!$page) return;
    if(str_contains($page->post_content,'about-chapter')) { update_option('gopa_about_visual_v1',true,false); return; }
    $chapters=['intro'=>[],'vision'=>[],'mission'=>[],'message'=>[],'mandate'=>[],'fields'=>[],'values'=>[]];
    $current='intro';
    foreach(parse_blocks($page->post_content) as $block) {
        if($block['blockName']==='core/heading') {
            $heading=strtoupper(trim(html_entity_decode(wp_strip_all_tags($block['innerHTML']),ENT_QUOTES,'UTF-8')));
            foreach(['VISION'=>'vision','MISSION'=>'mission','MESSAGE'=>'message','MANDATE'=>'mandate','FIELDS'=>'fields','VALUES'=>'values'] as $word=>$key) if(str_contains($heading,$word)) { $current=$key; break; }
            if($heading==='WHO WE ARE') {
                $chapters['intro'][]=parse_blocks('<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Who we are</p><!-- /wp:paragraph -->')[0];
                continue;
            }
        }
        $chapters[$current][]=$block;
    }
    // Abort rather than discard a client section we do not recognise.
    foreach($chapters as $blocks) if(!$blocks) throw new RuntimeException('About page structure needs review before redesign.');
    $images=['intro'=>'campaign-unity.jpg','vision'=>'campaign-generation.jpg','message'=>'campaign-bible.jpg','mandate'=>'campaign-service.jpg','values'=>'campaign-worship.jpg'];
    $result='';
    foreach($chapters as $key=>$blocks) {
        $copy=''; $cards=[];
        foreach($blocks as $block) {
            if($block['blockName']==='core/list' && in_array($key,['mission','mandate','fields','values'],true)) { $cards[]=$block; continue; }
            if($block['blockName']==='core/list' && in_array($key,['vision','message'],true)) {
                $copy.=gopa_about_details($block,$key==='vision'?'Our vision for Great Britain':'Read what we believe'); continue;
            }
            $copy.=serialize_block($block);
        }
        if($key==='mission') {
            $grid=''; foreach($cards as $card) $grid.=gopa_about_group('about-action-card',serialize_block($card));
            $grid.=gopa_about_image('campaign-witness.jpg','about-action-photo');
            $result.=gopa_about_group('about-mission',$copy.gopa_about_group('about-action-grid',$grid),'section'); continue;
        }
        if($key==='fields') {
            $copy=str_replace(['Five Mission Fields','five mission fields'],['Six Mission Fields','six mission fields'],$copy);
            $names=['campaign-unity.jpg','campaign-community.jpg','campaign-generation.jpg','campaign-witness.jpg','campaign-city.jpg','campaign-calling.jpg'];
            $grid=''; foreach($cards as $i=>$card) $grid.=gopa_about_group('about-field-card',gopa_about_image($names[$i%count($names)],'about-field-image').serialize_block($card));
            $result.=gopa_about_group('about-fields',$copy.gopa_about_group('about-field-grid',$grid),'section'); continue;
        }
        if($cards) {
            $grid=''; foreach($cards as $card) $grid.=gopa_about_group('about-value-card',serialize_block($card));
            $copy.=gopa_about_group($key==='mandate'?'about-mandate-grid':'about-values-grid',$grid);
        }
        $result.=gopa_about_group('about-chapter about-'.$key,gopa_about_image($images[$key],'about-chapter-image').gopa_about_group('about-chapter-copy',$copy),'section');
    }
    $normalise=function($html) { return trim(preg_replace('/[\s\x{00a0}]+/u',' ',html_entity_decode(wp_strip_all_tags($html),ENT_QUOTES,'UTF-8'))); };
    $result_text=$normalise($result);
    foreach(parse_blocks($page->post_content) as $block) {
        $original_text=$normalise(str_replace(['Five Mission Fields','five mission fields'],['Six Mission Fields','six mission fields'],serialize_block($block)));
        if($original_text!=='' && !str_contains($result_text,$original_text)) throw new RuntimeException('About redesign stopped because original copy was not preserved.');
    }
    if(!get_post_meta($page->ID,'_gopa_about_original_v1',true)) update_post_meta($page->ID,'_gopa_about_original_v1',$page->post_content);
    wp_save_post_revision($page->ID);
    $saved=wp_update_post(['ID'=>$page->ID,'post_content'=>wp_slash($result)],true);
    if(is_wp_error($saved)) throw new RuntimeException($saved->get_error_message());
    update_option('gopa_about_visual_v1',true,false);
    echo "About page redesigned with editable image and group blocks.\n";
}
