<?php
defined('ABSPATH') || exit;
add_shortcode('gopa_mission_cards', function () {
    static $rendering=false;
    if ($rendering) return '';
    $cards=get_posts(['post_type'=>'gopa_mission_step','post_status'=>'publish','posts_per_page'=>-1,'orderby'=>['menu_order'=>'ASC','ID'=>'ASC']]);
    if (!$cards) return '';
    $rendering=true;
    try {
        $html='<div class="about-action-grid">';
        foreach ($cards as $card) {
            $html.='<article class="about-action-card"><h3>'.esc_html($card->post_title).'</h3><div class="about-action-copy">'.apply_filters('the_content',$card->post_content).'</div></article>';
        }
        return $html.'</div>';
    } finally { $rendering=false; }
});
