<?php
/**
 * Plugin Name: Mingle Forums & Discussion Board
 * Plugin URI: https://getbutterfly.com/wordpress-plugins/mingle-forum/
 * Description: Allows registered users to submit forums, threads and discussions.
 * Version: 3.0.1
 * Author: Ciprian Popescu
 * Author URI: https://getbutterfly.com/
 * License: GNU General Public License v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */
define( 'MINGLE_VERSION', '3.0.1' );
define( 'MINGLE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'MINGLE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'MINGLE_FORUM_SLUG', 'forum' );
define( 'MINGLE_THREAD_SLUG', 'thread' );

include 'includes/functions.php';
include 'includes/settings.php';
include 'includes/meta.php';
//include 'includes/mod-pmpro.php';

include MINGLE_PLUGIN_PATH . '/includes/updater.php';




add_shortcode( 'mingle_form', 'mingle_thread_form' );


/**
 * Generate submission form
 *
 * @return string
 */
function mingle_thread_form() {
    $out = '';

    if ( ! is_user_logged_in() ) {
        return '<p>' . __( 'You need to be logged in to start a new thread.', 'mingle' ) . '</p>';
    }

    global $current_user;

    if ( isset( $_POST['mingle_form_create_quote_submitted'] ) && wp_verify_nonce( $_POST['mingle_form_create_quote_submitted'], 'mingle_form_create_quote' ) ) {
        $mingle_quote_author = trim( $_POST['mingle_quote_author'] );
        $mingle_quote_text   = trim( $_POST['mingle_quote_text'] );
        $mingle_quote_url    = '';

        if ( trim( $_POST['mingle_quote_url'] ) !== '' ) {
            $mingle_quote_url = 'https://' . trim( $_POST['mingle_quote_url'] );
            $mingle_quote_url = filter_var( $mingle_quote_url, FILTER_SANITIZE_URL );
        }

        if ( $mingle_quote_author !== '' && $mingle_quote_text !== '' ) {
            $mingle_moderate = (int) get_option( 'mingle_moderate' );

            $thread_status  = ( $mingle_moderate === 1 ) ? 'pending' : 'publish';
            $thread_message = ( $mingle_moderate === 1 ) ? __( 'Thread created and awaiting moderation!', 'mingle' ) : __( 'Thread published!', 'mingle' );

            $quote_data = [
                'post_title'     => $mingle_quote_author,
                'post_content'   => $mingle_quote_text,
                'post_status'    => $thread_status,
                'post_author'    => $current_user->ID,
                'post_type'      => MINGLE_THREAD_SLUG,
                'comment_status' => 'open',
            ];

            if ( $quote_id = wp_insert_post( $quote_data ) ) {
                wp_set_object_terms( $quote_id, (int) $_POST['mingle_quote_category'], MINGLE_FORUM_SLUG );

                update_post_meta( $quote_id, '_pinned_discussion', 0 );
                update_post_meta( $quote_id, '_discussion_url', $mingle_quote_url );

                $out .= '<p>' . $thread_message . '</p>';
            }
        } else {
            $out .= '<p>' . __( 'An error occurred!', 'mingle' ) . '</p>';
        }
    }

    $out .= '<form method="post" class="mingle-form--new">';

        $out .= wp_nonce_field( 'mingle_form_create_quote', 'mingle_form_create_quote_submitted' );

        $out .= '<p>
            <label for="mingle_quote_author">' . __( 'Title', 'mingle' ) . '</label><br>
            <input type="text" id="mingle_quote_author" name="mingle_quote_author" value="' . $mingle_quote_author . '">
        </p>
        <p>
            <label for="mingle_quote_text">' . __( 'Post/Content', 'mingle' ) . '</label><br>
            <textarea id="mingle_quote_text" name="mingle_quote_text" rows="12">' . $mingle_quote_text . '</textarea>
        </p>
        <p>
            <label for="mingle_quote_text">' . __( 'Link (optional)', 'mingle' ) . '</label><br>
            <input type="text" id="mingle_quote_url" name="mingle_quote_url" placeholder="https://" value="' . $mingle_quote_url . '">
        </p>
        <p>
            <label for="mingle_quote_category">' . __( 'Forum', 'mingle' ) . '</label><br>';

            $term_id = 0;

            if ( isset( $_GET['forum'] ) ) {
                $forum   = sanitize_title( $_GET['forum'] );
                $term    = get_term_by( 'slug', $forum, MINGLE_FORUM_SLUG );
                $term_id = $term->term_id;
            }

            $out .= mingle_get_quote_categories_dropdown( MINGLE_FORUM_SLUG, $term_id ) . '
        </p>

        <p>
            <input type="submit" id="mingle_submit" name="mingle_submit" class="mingle-button mingle-button--regular" value="' . __( 'Add', 'mingle' ) . '">
        </p>
    </form>';

    return $out;
}

function inbound_get_user_contributions( $user_id ) {
    $out = '<h3>' . __( 'My Contributions', 'mingle' ) . '</h3>';

    $args = [
        'author'      => $user_id,
        'post_type'   => MINGLE_THREAD_SLUG,
        'post_status' => [ 'publish', 'pending' ],
    ];

    $posts = new WP_Query( $args );

    if ( ! $posts->post_count ) {
        $out .= '<p>' . __( 'You have no contributions.', 'mingle' ) . '</p>';
    } else {
        $out .= '<p><b>' . __( 'Your contributions', 'mingle' ) . '</b></p>
        <table id="quotes">
            <thead>
                <th>' . __( 'Title', 'mingle' ) . '</th>
                <th>' . __( 'Forum', 'mingle' ) . '</th>
            </thead>';

            foreach ( $posts->posts as $post ) {
                $topics = get_the_terms( $post->ID, MINGLE_FORUM_SLUG );

                foreach ( $topics as $topic ) {
                    $quote_cat  = $topic->name;
                    $quote_slug = 'topic-' . $topic->slug;
                }

                $badge_topic = '<span class="mingle-badge mingle-badge-topic mingle-badge-' . $quote_slug . '"><i class="icofont-clip"></i> ' . $quote_cat . '</span>';

                $out .= '<tr>';

                    if ( get_post_status( $post->ID ) === 'publish' ) {
                        $out .= '<td><a href="' . get_permalink( $post->ID ) . '">' . $post->post_title . '</a></td>';
                    } else {
                        $out .= '<td>' . $post->post_title . ' (' . get_post_status( $post->ID ) . ')</td>';
                    }

                    $out .= '<td>' . $badge_topic . '</td>';
                $out .= '</tr>';
            }

        $out .= '</table>';
    }

    return $out;
}

add_shortcode( 'inbound-contributions', 'inbound_get_user_contributions' );



function mingle_enqueue_scripts() {
    wp_register_style( 'icofont', plugins_url( '/assets/fonts/icofont/icofont.min.css', __FILE__ ), [], MINGLE_VERSION );

    wp_enqueue_style( 'mingle', plugins_url( '/assets/style.css', __FILE__ ), [], MINGLE_VERSION );
}

add_action( 'wp_enqueue_scripts', 'mingle_enqueue_scripts' );



function mingle_get_quote_categories_dropdown( $taxonomy, $selected ) {
    return wp_dropdown_categories(
        [
            'taxonomy'   => $taxonomy,
            'name'       => 'mingle_quote_category',
            'selected'   => $selected,
            'hide_empty' => 0,
            'echo'       => 0,
        ]
    );
}


add_action('init', 'mingle_plugin_init');



function mingle_plugin_init() {
    $quote_type_labels = [
        'name' => _x('Threads', 'post type general name'),
        'singular_name' => _x('Thread', 'post type singular name'),
        'add_new' => _x('Add New Thread', 'quote', 'mingle' ),
        'add_new_item' => __('Add New Thread', 'mingle' ),
        'edit_item' => __('Edit Thread', 'mingle' ),
        'new_item' => __('Add New Thread', 'mingle' ),
        'all_items' => __('View Threads', 'mingle' ),
        'view_item' => __('View Thread', 'mingle' ),
        'search_items' => __('Search Threads', 'mingle' ),
        'not_found' =>  __('No Threads Found', 'mingle' ),
        'not_found_in_trash' => __('No Threads Found in Trash', 'mingle' ),
        'parent_item_colon' => '',
        'menu_name' => 'Threads'
    ];

    $quote_type_args = [
        'labels' => $quote_type_labels,
        'public' => true,
        'query_var' => true,
        'rewrite' => true,
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => ['title', 'editor', 'author'],
        'show_in_rest' => true,
    ];

    register_post_type(MINGLE_THREAD_SLUG, $quote_type_args);

    $topicLabels = [
        'name' => _x('Forums', 'taxonomy general name'),
        'singular_name' => _x('Forum', 'taxonomy singular name'),
        'search_items' =>  __('Search Forums', 'mingle' ),
        'all_items' => __('All Forums', 'mingle' ),
        'parent_item' => __('Parent Forum', 'mingle' ),
        'parent_item_colon' => __('Parent Forum:', 'mingle' ),
        'edit_item' => __('Edit Forum', 'mingle' ),
        'update_item' => __('Update Forum', 'mingle' ),
        'add_new_item' => __('Add New Forum', 'mingle' ),
        'new_item_name' => __('New Forum Name', 'mingle' ),
        'menu_name' => __('Forums', 'mingle' )
    ];

    $topicArguments = [
        'hierarchical' => true,
        'labels' => $topicLabels,
        'show_ui' => true,
        'query_var' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => MINGLE_FORUM_SLUG]
    ];

    register_taxonomy(MINGLE_FORUM_SLUG, [MINGLE_THREAD_SLUG], $topicArguments);

    /*
    $defaultTopics = ['SEO', 'Email Marketing', 'Inbound Marketing', 'Growth Hacking', 'Experiments'];

    foreach ($defaultTopics as $topic) {
        if (!term_exists($topic, MINGLE_FORUM_SLUG)) {
            wp_insert_term($topic, MINGLE_FORUM_SLUG);
        }
    }
    /**/
}




function ghGetDiscussionViewCount($postId) {
    $count = get_post_meta($postId, '_discussion_view_count', true);
    $count = empty($count) ? 0 : $count;

    update_post_meta($postId, '_discussion_view_count', $count);

    return $count;
}
function ghSetDiscussionViewCount($postId) {
    $count = get_post_meta($postId, '_discussion_view_count', true);
    $count = empty($count) ? 1 : $count + 1;

    update_post_meta($postId, '_discussion_view_count', $count);
}





add_action('add_meta_boxes', 'inbound_add_meta_boxes');
add_action('save_post', 'discussion_save_meta_box_data');

function inbound_add_meta_boxes($post) {
    add_meta_box('discussion_meta_box', 'Thread Settings', 'discussion_build_meta_box', [MINGLE_THREAD_SLUG], 'side', 'low');
}

/**
 * Build custom field meta box
 *
 * @param post $post The post object
 */
function discussion_build_meta_box($post) {
	wp_nonce_field(basename(__FILE__), 'discussion_meta_box_nonce');

    $currentPinnedDiscussion = get_post_meta($post->ID, '_pinned_discussion', true);
    $currentDiscussionUrl = get_post_meta($post->ID, '_discussion_url', true);
	?>
    <div class="inside">
		<p>
			<input type="checkbox" name="pinned_discussion" id="pinned_discussion" value="1" <?php checked($currentPinnedDiscussion, 1); ?>><label for="pinned_discussion">Pin thread</label>
            <br><small>This option will pin the current thread and display it at the top.</small>
		</p>
		<p>
            <label for="discussion_url">Thread URL</label><br>
			<input type="url" name="discussion_url" id="discussion_url" style="width: 100%;" value="<?php echo $currentDiscussionUrl; ?>">
		</p>
	</div>
	<?php
}

/**
 * Store custom field meta box data
 *
 * @param int $post_id The post ID.
 */
function discussion_save_meta_box_data($post_id) {
    if (!isset($_POST['discussion_meta_box_nonce']) || !wp_verify_nonce($_POST['discussion_meta_box_nonce'], basename(__FILE__))) {
		return;
	}
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

    $pinned_discussion = isset($_POST['pinned_discussion']) ? sanitize_text_field($_POST['pinned_discussion']) : '';
    $discussion_url = isset($_POST['discussion_url']) ? sanitize_text_field($_POST['discussion_url']) : '';

    update_post_meta($post_id, '_pinned_discussion', $pinned_discussion);
    update_post_meta($post_id, '_discussion_url', $discussion_url);
}





function get_related_discussions($discussionId) {
    $out = '';

    // Get category details
    $discussionTopics = get_the_terms($discussionId, MINGLE_FORUM_SLUG);

    foreach ($discussionTopics as $topic) {
        $topicName = $topic->name;
        $topicSlug = (string) $topic->slug;
    }

    $args = [
        'post_type' => MINGLE_THREAD_SLUG,
        'post_status' => 'publish',
        'posts_per_page' => 4,
        'tax_query' => [
            [
                'taxonomy' => MINGLE_FORUM_SLUG,
                'field' => 'slug',
                'terms' => $topicSlug
            ]
        ]
    ];

    $posts = new WP_Query($args);

    if ($posts->post_count) {
        $out .= '<div id="discussions">';

        foreach ($posts->posts as $post) {
            $user_info = get_userdata($post->post_author);

            $quote_cats = get_the_terms($post->ID, MINGLE_FORUM_SLUG);

            foreach ($quote_cats as $cat) {
                $quote_cat = $cat->name;
                $quote_slug = 'topic-' . $cat->slug;
            }

            $badge_pinned_discussion = ((int) get_post_meta($post->ID, '_pinned_discussion', true)) ? '<span class="mingle-badge mingle-badge-pinned"><svg class="svg-inline--fa fa-thumbtack fa-w-12 fa-fw" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="thumbtack" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" data-fa-i2svg=""><path fill="currentColor" d="M298.028 214.267L285.793 96H328c13.255 0 24-10.745 24-24V24c0-13.255-10.745-24-24-24H56C42.745 0 32 10.745 32 24v48c0 13.255 10.745 24 24 24h42.207L85.972 214.267C37.465 236.82 0 277.261 0 328c0 13.255 10.745 24 24 24h136v104.007c0 1.242.289 2.467.845 3.578l24 48c2.941 5.882 11.364 5.893 14.311 0l24-48a8.008 8.008 0 0 0 .845-3.578V352h136c13.255 0 24-10.745 24-24-.001-51.183-37.983-91.42-85.973-113.733z"></path></svg> ' . __( 'Pinned', 'mingle' ) . '</span>' : '';

            $out .= '<div class="discussion-item">
                <div class="discussion-content">
                    <h3><a href="' . get_permalink($post->ID) . '">' . $post->post_title . '</a></h3>

                    <span class="discussion-excerpt">
                        ' . wp_trim_words($post->post_content, 20, ' [...]') . '
                    </span>

                    <span class="discussion-meta">
                        ' . $badge_pinned_discussion . '
                        ' . human_time_diff(strtotime($post->post_date), current_time('timestamp')) . ' ago by ' . $user_info->display_name . ' <span class="' . $quote_slug . '">(' . $quote_cat . ')</span> (' . str_word_count(strip_tags($post->post_content), 0) . ' words, ' . ghGetDiscussionViewCount($post->ID) . ' views)
                    </span>
                </div>
                <div class="discussion-stats">
                    ' . get_comment_count($post->ID)['approved'] . ' <svg class="svg-inline--fa fa-comment fa-w-16 fa-fw" aria-hidden="true" focusable="false" data-prefix="far" data-icon="comment" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg=""><path fill="currentColor" d="M256 32C114.6 32 0 125.1 0 240c0 47.6 19.9 91.2 52.9 126.3C38 405.7 7 439.1 6.5 439.5c-6.6 7-8.4 17.2-4.6 26S14.4 480 24 480c61.5 0 110-25.7 139.1-46.3C192 442.8 223.2 448 256 448c141.4 0 256-93.1 256-208S397.4 32 256 32zm0 368c-26.7 0-53.1-4.1-78.4-12.1l-22.7-7.2-19.5 13.8c-14.3 10.1-33.9 21.4-57.5 29 7.3-12.1 14.4-25.7 19.9-40.2l10.6-28.1-20.6-21.8C69.7 314.1 48 282.2 48 240c0-88.2 93.3-160 208-160s208 71.8 208 160-93.3 160-208 160z"></path></svg>
                </div>
            </div>';
        }

        $out .= '</div>';

        return $out;
    }
}



add_action('save_post', function ($postId) {
    add_post_meta($postId, '_liked', 0, true);
    add_post_meta($postId, '_discussion_view_count', 0, true);
});
add_action('update_post', function ($postId) {
    add_post_meta($postId, '_liked', 0, true);
    add_post_meta($postId, '_discussion_view_count', 0, true);
});


add_filter('comments_open', 'inbound_comments_open', 10, 2);

function inbound_comments_open($open, $post_id) {
    $post = get_post($post_id);

    if (MINGLE_THREAD_SLUG == $post->post_type) {
        $open = true;
    }

    return $open;
}



function inbound_get_community_stats() {
    $total_users    = count_users();
    $total_posts    = wp_count_posts( MINGLE_THREAD_SLUG );
    $total_comments = wp_count_comments();

    $out = '<div class="wp-block-columns inbound--stats">
        <div class="wp-block-column">
            <p class="inbound--counter inbound-count-up has-text-color" style="font-size:72px">' . $total_users['total_users'] . '</p>
            <h2 style="font-size: 32px; margin-top: 0;">' . __( 'Contributors', 'mingle' ) . '</h2>
        </div>
        <div class="wp-block-column">
            <p class="inbound--counter inbound-count-up has-text-color" style="font-size:72px">' . $total_posts->publish . '</p>
            <h2 style="font-size: 32px; margin-top: 0;">' . __( 'Threads', 'mingle' ) . '</h2>
        </div>
        <div class="wp-block-column">
            <p class="inbound--counter inbound-count-up has-text-color" style="font-size:72px">' . $total_comments->approved . '</p>
            <h2 style="font-size: 32px; margin-top: 0;">' . __( 'Comments', 'mingle' ) . '</h2>
        </div>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", () => {
        /**
         * Count-up
         */
        if (document.querySelector(".inbound-count-up")) {
            const counterAnim = (target, start = 0, end, duration = 1000) => {
                let startTimestamp = null;
                const step = (timestamp) => {
                    if (!startTimestamp) startTimestamp = timestamp;
                    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                    target.innerText = Math.floor(progress * (end - start) + start);
                    if (progress < 1) {
                        window.requestAnimationFrame(step);
                    }
                };
                window.requestAnimationFrame(step);
            };

            const observerCallback = (entries, observer) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const target = entry.target;
                        const end = parseInt(target.innerText);
                        counterAnim(target, 0, end, 1500);
                        observer.unobserve(target);
                    }
                });
            };

            const observerOptions = {
                root: null,
                rootMargin: "0px",
                threshold: 0,
            };

            const observer = new IntersectionObserver(
                observerCallback,
                observerOptions
            );
            document.querySelectorAll(".inbound-count-up").forEach((element) => {
                observer.observe(element);
            });
        }
    });
    </script>';

    return $out;
}

add_shortcode( 'inbound-stats', 'inbound_get_community_stats' );



function inbound_get_community( $atts ) {
    wp_enqueue_style( 'icofont' );

    $attributes = shortcode_atts(
        [
            'count' => 48,
            'view'  => 'threads', // threads|forums
        ],
        $atts
    );

    global $current_user;

    $out = '<div class="mingle-button-bar">
        <a href="' . esc_url( add_query_arg( 'view', 'forums', get_permalink() ) ) . '" class="mingle-button">' . __( 'All Forums', 'mingle' ) . '</a>
        <a href="' . esc_url( add_query_arg( 'view', 'threads', get_permalink() ) ) . '" class="mingle-button">' . __( 'All Threads', 'mingle' ) . '</a>';

        if (
            (string) get_option( 'mingle_page_login' ) !== '' ||
            (string) get_option( 'mingle_page_signup' ) !== ''
        ) {
            $out .= ' | ';
        }

        if ( (string) get_option( 'mingle_page_login' ) !== '' ) {
            $out .= '<a href="' . esc_url( get_option( 'mingle_page_login' ) ) . '" class="mingle-button">' . __( 'Login', 'mingle' ) . '</a>';
        }
        if ( (string) get_option( 'mingle_page_signup' ) !== '' ) {
            $out .= '<a href="' . esc_url( get_option( 'mingle_page_signup' ) ) . '" class="mingle-button">' . __( 'Signup', 'mingle' ) . '</a>';
        }

    $out .= '</div>';

    if (
        ( isset( $_GET['view'] ) && (string) $_GET['view'] !== 'threads' ) &&
        (
            (string) $attributes['view'] === 'forums' && ( ! isset( $_GET['forum'] ) || (string) $_GET['forum'] === '' ) ||
            ( isset( $_GET['view'] ) && (string) $_GET['view'] === 'forums' )
        )
    ) {
        $out .= inbound_get_terms();

        return $out;
    }

    $args = [
        'post_type'      => MINGLE_THREAD_SLUG,
        'post_status'    => 'publish',
        'posts_per_page' => $attributes['count'],
    ];

    if ( isset( $_GET['forum'] ) && (string) $_GET['forum'] !== '' ) {
        $args['tax_query'] = [
            [
                'taxonomy' => MINGLE_FORUM_SLUG,
                'field'    => 'slug',
                'terms'    => sanitize_title( $_GET['forum'] ),
            ],
        ];
    }

    $args['meta_query'] = [
        'relation'      => 'AND',
        'pinned_clause' => [
            'key' => '_pinned_discussion',
        ],
        'liked_clause'  => [
            'key' => '_liked',
        ],
    ];

    $args['orderby'] = [
        'pinned_clause' => 'DESC',
        'liked_clause'  => 'DESC',
        'date'          => 'DESC',
    ];

    $posts = new WP_Query( $args );

    $out .= '<div id="discussions" class="inbound-threads">
        <div class="inbound-thread inbound-thread--head">
            <div class="inbound-thread--title"></div>
            <div class="inbound-thread--comments"><i class="icofont-speech-comments"></i></div>
            <div class="inbound-thread--likes"><i class="icofont-heart"></i></div>
            <div class="inbound-thread--views"><i class="icofont-eye-alt"></i></div>
            <div class="inbound-thread--activity">' . __( 'Recent Activity', 'mingle' ) . '</div>
        </div>';

    foreach ( $posts->posts as $post ) {
        // Retrieve the latest comment user for the current thread
        $latest_comment_query = new WP_Query(
            [
                'post_type'      => 'thread',
                'p'              => $post->ID,
                'posts_per_page' => 1,
                'orderby'        => 'comment_date',
                'order'          => 'DESC',
                'fields'         => 'ids',
            ]
        );

        $latest_comment_user_id    = '';
        $latest_comment_user_name  = '';
        $latest_comment_user_email = '';

        $avatar = '';

        // Get the user information for the latest comment
        if ( $latest_comment_query->have_posts() ) {
            $latest_comment_post_id = $latest_comment_query->posts[0];
            $latest_comment         = get_comments(
                [
                    'post_id' => $latest_comment_post_id,
                    'number'  => 1,
                    'status'  => 'approve',
                    'order'   => 'DESC',
                ]
            );

            if ( ! empty( $latest_comment ) ) {
                // Retrieve user information
                $latest_comment_user_name  = $latest_comment[0]->comment_author;
                $latest_comment_user_email = $latest_comment[0]->comment_author_email;

                // Check if the user is registered
                $user_id = email_exists( $latest_comment_user_email );

                if ( $user_id ) {
                    $latest_comment_user_id = $user_id;

                    $avatar = get_avatar( $user_id, 32 );
                } elseif ( $latest_comment_user_email !== '' ) {
                    $avatar = get_avatar( $latest_comment_user_email, 32 );
                } else {
                    $avatar = '';
                }
            }
        }

        if ( $latest_comment_user_name === '' ) {
            // If there are no comments, use the thread author and avatar
            $latest_comment_user_name = get_the_author_meta( 'display_name', $post->post_author );
            $latest_comment_user_id   = $post->post_author;
            $avatar                   = get_avatar( $post->post_author, 32 );
        }







        $user_info = get_userdata( $post->post_author );

        $quote_cats = get_the_terms( $post->ID, MINGLE_FORUM_SLUG );

        foreach ( $quote_cats as $cat ) {
            $quote_cat  = $cat->name;
            $quote_slug = 'topic-' . $cat->slug;
        }

        $quote_cats = get_the_terms( $post->ID, MINGLE_FORUM_SLUG );

        if ( $quote_cats && ! is_wp_error( $quote_cats ) ) {
            $term_names = [];

            foreach ( $quote_cats as $term ) {
                $term_names[] = '<a href="' . esc_url( add_query_arg( 'forum', $term->slug, get_permalink() ) ) . '">' . $term->name . '</a>';
            }

            $linked_terms = implode( ', ', $term_names );

            // Now $linked_terms contains the linked taxonomy term names as a comma-separated string.
            $quote_cat = $linked_terms;
        }

        $icon_pinned_discussion  = ( (int) get_post_meta( $post->ID, '_pinned_discussion', true ) ) ? '<span><i class="icofont-safety-pin"></i></span> ' : '';
        $badge_pinned_discussion = ( (int) get_post_meta( $post->ID, '_pinned_discussion', true ) ) ? '<span class="mingle-badge mingle-badge-pinned"><i class="icofont-safety-pin"></i> ' . __( 'Pinned', 'mingle' ) . '</span>' : '';
        $badge_discussion_url    = ( (string) get_post_meta( $post->ID, '_discussion_url', true ) !== '' ) ? '<span class="mingle-badge mingle-badge-link"><i class="icofont-link"></i> ' . __( 'Link', 'mingle' ) . '</span>' : '<span class="mingle-badge mingle-badge-discussion"><i class="icofont-brand-tata-indicom"></i> ' . __( 'Discussion', 'mingle' ) . '</span>';
        $badge_topic             = '<span class="mingle-badge mingle-badge-topic mingle-badge-' . $quote_slug . '"><i class="icofont-clip"></i> ' . $quote_cat . '</span>';

        $out .= '<div class="discussion-item inbound-thread">
            <div class="inbound-thread--title">
                <h3>
                    <a href="' . get_permalink( $post->ID ) . '">' . $icon_pinned_discussion . $post->post_title . '</a>';

                    if ( get_post_meta( $post->ID, '_discussion_url', true ) !== '' ) {
                        // $out .= '<br><span class="has-cyan-bluish-gray-color has-text-color has-small-font-size" style="font-weight:400">' . inbound_sanitize_url( get_post_meta( $post->ID, '_discussion_url', true ) ) . '</span>';
                    }

                $out .= '</h3>';

                $out .= '<div class="" style="font-size:12px;margin:0.5em 0">';

                    $out .= $user_info->display_name . ' &middot; ' . $quote_cat;

                $out .= '</div>';

                $out .= '<div class="has-cyan-bluish-gray-color has-text-color has-small-font-size">';

                    $out .= ' ' . $badge_pinned_discussion;
                    $out .= ' ' . $badge_discussion_url;
                    //$out .= ' ' . $badge_topic;

                $out .= '</div>';

            $out .= '</div>
            <div class="inbound-thread--comments">' . get_comment_count( $post->ID )['approved'] . '</div>
            <div class="inbound-thread--likes">' . ( get_post_meta( $post->ID, '_liked', true ) !== '' ? get_post_meta( $post->ID, '_liked', true ) : '0' ) . '</div>
            <div class="inbound-thread--views">' . ghGetDiscussionViewCount( $post->ID ) . '</div>
            <div class="inbound-thread--activity">' .
                $avatar .

                '<span>' .
                    esc_html( $latest_comment_user_name ) .
                    '<br>' . human_time_diff( strtotime( $post->post_date ), current_time( 'timestamp' ) ) .
                '</span>
            </div>
        </div>';
    }

    $out .= '</div>

    <h3>' . __( 'Start New Thread', 'mingle' ) . '</h3>';

    //
    $mingle_status_id = (int) get_term_meta( $term->term_id, 'mingle_status_id', true );

    if ( $mingle_status_id !== 1 ) {
        $out .= mingle_thread_form();
    } else {
        $out .= '<p>' . __( 'This forum is currently locked.', 'mingle' ) . '</p>';
    }

    return $out;
}

add_shortcode( 'mingle', 'inbound_get_community' );
add_shortcode( 'threads', 'inbound_get_community' );
add_shortcode( 'mingle-forum', 'inbound_get_community' );



/**
 * Property brochure template selector
 *
 * @param  string $single_template  Native WordPress single template
 * @return string                   Overridden single template
 */
function inbound_single_discussion( $single_template ) {
    global $post;

    if ( (string) $post->post_type === MINGLE_THREAD_SLUG ) {
        $single_template = MINGLE_PLUGIN_PATH . 'templates/single-thread.php';
    }

    return $single_template;
}



function inbound_single_content( $content ) {
    global $post;

    if ( is_single() && is_main_query() && (string) $post->post_type === MINGLE_THREAD_SLUG ) {
        $out = '<div class="mingle-content">';

            // Get user details
            $user_info = get_userdata( $post->post_author );

            $quote_cats = get_the_terms( $post->ID, MINGLE_FORUM_SLUG );

            if ( $quote_cats && ! is_wp_error( $quote_cats ) ) {
                $term_names = [];

                foreach ( $quote_cats as $term ) {
                    $term_names[] = '<a href="' . esc_url( add_query_arg( 'forum', $term->slug, get_permalink( (int) get_option( 'mingle_page_forums' ) ) ) ) . '">' . $term->name . '</a>';
                }

                $linked_terms = implode( ', ', $term_names );

                // Now $linked_terms contains the linked taxonomy term names as a comma-separated string.
                $quote_cat = $linked_terms;
            }
            //

            // Set post views
            ghSetDiscussionViewCount( $post->ID );

            $out .= '<div id="post-' . get_the_ID() . '" ' . get_post_class() . '>

                <p>
                    <small>' .
                        $user_info->display_name . ' | ' . human_time_diff( strtotime( $post->post_date ), current_time( 'timestamp' ) ) . ' | ' . $quote_cat . '<br>' .
                        ghGetDiscussionViewCount( $post->ID ) . ' ' . __( 'views', 'mingle' ) . ', ' . get_comment_count( $post->ID )['approved'] . ' ' . __( 'replies', 'mingle' ) . '</small>
                </p>';

                $out .= $content;

                $discussion_url = (string) get_post_meta( $post->ID, '_discussion_url', true );

                $out .= ( $discussion_url !== '' ) ? '<p><a href="' . $discussion_url . '" rel="external noopener">' . __( 'Read more', 'mingle' ) . '</a><br><small class="has-cyan-bluish-gray-color has-text-color">' . $discussion_url . '</small>' : '';

            $out .= '</div>
        </div>';

        return $out;
    }

    return $content;
}



/**
 * Single thread template selector
 *
 * @param  string $single_template  Native WordPress single template
 * @return string                   Overridden single template
 */
function mingle_singular_override( $single_template ) {
    global $wp_query, $post;

    $template_path = get_template_directory() . '/single-thread.php';

    if ( (string) $post->post_type === 'thread' ) {
        if ( file_exists( $template_path ) ) {
            $single_template = $template_path;
        }
    }

    return $single_template;
}



if ( (int) get_option( 'mingle_use_template' ) === 1 ) {
    add_filter( 'the_content', 'inbound_single_content' );
} elseif ( (int) get_option( 'mingle_use_template' ) === 2 ) {
    add_filter( 'single_template', 'inbound_single_discussion', 99 );
} elseif ( (int) get_option( 'mingle_use_template' ) === 3 ) {
    add_filter( 'single_template', 'mingle_singular_override', 99 );
}
