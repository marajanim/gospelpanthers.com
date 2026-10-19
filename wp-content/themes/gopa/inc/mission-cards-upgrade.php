<?php
defined('ABSPATH') || exit;
function gopa_upgrade_mission_cards() {
    if (get_option('gopa_mission_cards_v1')) return;
    $page=get_page_by_path('about-us'); if(!$page) return;
    $document=parse_blocks($page->post_content);
    $cards=[]; $pictures=[]; $found=false;
    $collect=function($blocks) use (&$collect,&$cards,&$pictures) {
        foreach($blocks as $block) {
            if($block['blockName']==='core/image') { $pictures[]=$block; continue; }
            if($block['blockName']==='core/list') {
                preg_match_all('/<li\b[^>]*>(.*?)<\/li>/su',serialize_block($block),$items);
                foreach($items[1] as $item) {
                    if(!preg_match('/<strong\b[^>]*>(.*?)<\/strong>/su',$item,$title)) throw new RuntimeException('A mission card heading needs review.');
                    $name=trim(preg_replace('/[\s–—-]+$/u','',wp_strip_all_tags(html_entity_decode($title[1],ENT_QUOTES,'UTF-8'))));
                    $body=preg_replace('/<strong\b[^>]*>.*?<\/strong>/su','',$item,1);
                    $body=preg_replace('/^(?:\s|&nbsp;|&#(?:8211|8212);|&(?:ndash|mdash);|[–—-])+/u','',$body);
                    if(!$name || trim(wp_strip_all_tags($body))==='') throw new RuntimeException('A mission card is missing its title or description.');
                    $cards[]=['title'=>$name,'body'=>'<!-- wp:paragraph --><p>'.wp_kses_post(trim($body)).'</p><!-- /wp:paragraph -->'];
                }
                continue;
            }
            if(!empty($block['innerBlocks'])) $collect($block['innerBlocks']);
            elseif(trim(wp_strip_all_tags($block['innerHTML']??''))!=='') throw new RuntimeException('Unrecognised mission content retained for review.');
        }
    };
    $replace=function(&$blocks) use (&$replace,&$found,$collect,&$pictures) {
        foreach($blocks as &$block) {
            $classes=explode(' ',$block['attrs']['className']??'');
            if(in_array('about-action-grid',$classes,true)) {
                if($found) throw new RuntimeException('Multiple mission grids need review.');
                $found=true; $collect($block['innerBlocks']);
                $block=parse_blocks('<!-- wp:shortcode -->[gopa_mission_cards]<!-- /wp:shortcode -->')[0];
            } elseif(!empty($block['innerBlocks'])) $replace($block['innerBlocks']);
        }
        unset($block);
    };
    $replace($document);
    if(!$found || count($cards)<5) throw new RuntimeException('Mission migration stopped: existing cards could not be safely identified.');
    foreach($cards as $index=>$card) {
        $key=$page->ID.':'.sanitize_title($card['title']);
        $existing=get_posts(['post_type'=>'gopa_mission_step','post_status'=>'any','numberposts'=>1,'meta_key'=>'_gopa_mission_source','meta_value'=>$key]);
        if($existing) continue;
        $id=wp_insert_post(['post_type'=>'gopa_mission_step','post_status'=>'publish','post_title'=>$card['title'],'post_content'=>wp_slash($card['body']),'menu_order'=>($index+1)*10,'meta_input'=>['_gopa_mission_source'=>$key]],true);
        if(is_wp_error($id)) throw new RuntimeException($id->get_error_message());
    }
    // Keep the existing photograph as a compact section-header image, outside the card grid.
    foreach($document as &$section) {
        if(!in_array('about-mission',explode(' ',$section['attrs']['className']??''),true)) continue;
        $lead=''; $rest='';
        foreach($section['innerBlocks'] as $block) {
            if(in_array($block['blockName'],['core/heading','core/paragraph'],true)) $lead.=serialize_block($block);
            else $rest.=serialize_block($block);
        }
        $photo=''; foreach($pictures as $picture) $photo.=serialize_block($picture);
        $replacement=gopa_about_group('about-mission',gopa_about_group('about-mission-lead',gopa_about_group('about-mission-lead-copy',$lead).$photo).$rest,'section');
        $section=parse_blocks($replacement)[0];
    }
    unset($section);
    if(!get_post_meta($page->ID,'_gopa_missions_before_dynamic_v1',true)) update_post_meta($page->ID,'_gopa_missions_before_dynamic_v1',$page->post_content);
    wp_save_post_revision($page->ID);
    $saved=wp_update_post(['ID'=>$page->ID,'post_content'=>wp_slash(serialize_blocks($document))],true);
    if(is_wp_error($saved)) throw new RuntimeException($saved->get_error_message());
    update_option('gopa_mission_cards_v1',true,false);
    echo count($cards)." mission cards imported, including client additions.\n";
}
