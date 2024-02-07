<?php
/**
 * Template Name: Discussion (single)
 */

get_header();

if (have_posts()) : while (have_posts()) : the_post(); ?>
    <div class="wrap-inner">
        <h1 class="entry-title"><?php the_title(); ?></h1>

        <div class="wrap-content">

            <?php
            // Get user details
            $user_info = get_userdata($post->post_author);

            // Get category details
            $quote_cats = get_the_terms($post->ID, MINGLE_FORUM_SLUG);

            foreach ($quote_cats as $cat) {
                $quote_cat = $cat->name;
                $quote_slug = 'topic-' . $cat->slug;
            }

            // Set post views
            ghSetDiscussionViewCount($post->ID);
            ?>
            <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <?php
                /*
                if (function_exists('yoast_breadcrumb')) {
                    yoast_breadcrumb('<p id="breadcrumbs">','</p>');
                }
                /**/
                ?>

                <p class="has-cyan-bluish-gray-color has-text-color has-small-font-size">
                    <?php echo human_time_diff(strtotime($post->post_date), current_time('timestamp')) . ' ago by ' . $user_info->display_name . ' [' . $quote_cat . '] (' . str_word_count(strip_tags($post->post_content), 0) . ' words, ' . ghGetDiscussionViewCount($post->ID) . ' views, ' . get_comment_count($post->ID)['approved'] . ' replies)'; ?>
                </p>

                <?php
                if (function_exists('vote5_get')) {
                    vote5_get('get');
                }

                the_content();

                $discussionUrl = (string) get_post_meta($post->ID, '_discussion_url', true);

                echo ($discussionUrl !== '') ? '<p><a href="' . $discussionUrl . '" rel="external noopener">Read more</a><br><small class="has-cyan-bluish-gray-color has-text-color">' . $discussionUrl . '</small>' : '';
                ?>
            </div>
            <?php comments_template(); ?>

            <?php /* ?>
            <h3>Related discussions</h3>

            <?php echo get_related_discussions($post->ID); ?>
            <?php /**/ ?>
        </div>
    </div>
<?php endwhile; endif; ?>

<?php get_footer();
