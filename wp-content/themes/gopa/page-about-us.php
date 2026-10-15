<?php get_header(); ?>
<main id="main" class="about-page"><div id="top"></div>
<?php while (have_posts()): the_post(); ?>
<header class="about-heading container"><div><span class="eyebrow"><?php echo esc_html(gopa_text('page_prefix')); ?></span><h1><?php echo esc_html(rtrim(get_the_title(), '.')); ?><span class="gold">.</span></h1></div><?php if (has_excerpt()): ?><p class="about-intro-text"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?></header>
<?php if (has_post_thumbnail()): ?><figure class="about-opening-image container"><?php the_post_thumbnail('full', ['loading'=>'eager','fetchpriority'=>'high']); ?></figure><?php endif; ?>
<div class="container about-content"><?php the_content(); ?></div>
<?php endwhile; ?></main><?php get_footer(); ?>
