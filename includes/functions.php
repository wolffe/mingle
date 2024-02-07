<?php
function inbound_sanitize_url( $url ) {
    // Remove "http://" and "https://" and "www."
    $url = preg_replace( '#^(https?://|www\.)#i', '', $url );

    // Remove the last slash
    $url = rtrim( $url, '/' );

    return $url;
}



function inbound_get_terms() {
    $taxonomy_name = MINGLE_FORUM_SLUG;

    $terms = get_terms(
        [
            'taxonomy'   => $taxonomy_name,
            'hide_empty' => false,
        ]
    );

    $out = '<div class="inbound-threads">
        <div class="inbound-thread inbound-thread--head">
            <div class="inbound-thread--title"></div>
            <div class="inbound-thread--comments"><i class="icofont-speech-comments"></i></div>
            <div class="inbound-thread--likes"><i class="icofont-heart"></i></div>
            <div class="inbound-thread--views"><i class="icofont-eye-alt"></i></div>
            <div class="inbound-thread--activity">' . __( 'Recent Activity', 'mingle' ) . '</div>
        </div>';

        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                // Count the number of threads for each term
                $thread_count = new WP_Query( [
                    'post_type'      => 'thread', // Replace with your actual custom post type
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'tax_query'      => [
                        [
                            'taxonomy' => $taxonomy_name,
                            'field'    => 'slug',
                            'terms'    => $term->slug,
                        ],
                    ],
                ] );

                // Get the cumulated count of all thread views for each term
                $view_count_query = new WP_Query( [
                    'post_type'      => 'thread', // Replace with your actual custom post type
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                    'tax_query'      => [
                        [
                            'taxonomy' => $taxonomy_name,
                            'field'    => 'slug',
                            'terms'    => $term->slug,
                        ],
                    ],
                    'meta_query'     => [
                        [
                            'key'     => '_discussion_view_count',
                            'compare' => 'EXISTS',
                        ],
                    ],
                ] );

                $view_count = 0;

                // Calculate the cumulated thread views for each term
                foreach ( $view_count_query->posts as $post_id ) {
                    $view_count += intval( get_post_meta( $post_id, '_discussion_view_count', true ) );
                }

                // Get the cumulated count of all thread likes for each term
                $like_count_query = new WP_Query( [
                    'post_type'      => 'thread', // Replace with your actual custom post type
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                    'tax_query'      => [
                        [
                            'taxonomy' => $taxonomy_name,
                            'field'    => 'slug',
                            'terms'    => $term->slug,
                        ],
                    ],
                    'meta_query'     => [
                        [
                            'key'     => '_liked',
                            'compare' => 'EXISTS',
                        ],
                    ],
                ] );

                $like_count = 0;

                // Calculate the cumulated thread views for each term
                foreach ( $like_count_query->posts as $post_id ) {
                    $like_count += intval( get_post_meta( $post_id, '_liked', true ) );
                }

                // Get the latest comment user for each term
                $latest_comment_query = new WP_Query( [
                    'post_type'      => 'thread', // Replace with your actual custom post type
                    'post_status'    => 'publish',
                    'posts_per_page' => 1,
                    'tax_query'      => [
                        [
                            'taxonomy' => $taxonomy_name,
                            'field'    => 'slug',
                            'terms'    => $term->slug,
                        ],
                    ],
                    'orderby'        => 'comment_date',
                    'order'          => 'DESC',
                    'fields'         => 'ids',
                ] );

                $latest_comment_user_id   = '';
                $latest_comment_user_name = '';
                $latest_comment_user_email = '';
                $latest_comment_date = '';
                $latest_thread_date = '';

                $avatar = '';

                // Get the user information for the latest comment
                if ( $latest_comment_query->have_posts() ) {
                    $latest_comment_post_id = $latest_comment_query->posts[0];
                    $latest_comment         = get_comments( [
                        'post_id' => $latest_comment_post_id,
                        'number'  => 1,
                        'status'  => 'approve',
                        'order'   => 'DESC',
                    ] );

                    if ( ! empty( $latest_comment ) ) {
                        // Retrieve user information
                        $latest_comment_user_name  = $latest_comment[0]->comment_author;
                        $latest_comment_user_email = $latest_comment[0]->comment_author_email;

                        // Check if the user is registered
                        $user_id = email_exists( $latest_comment_user_email );

                        // Get the latest comment date
                        $latest_comment_date = $latest_comment[0]->comment_date;

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

                // If there are no comments, use the latest thread author in this term
                if ( $latest_comment_user_id === '' ) {
                    $latest_thread_query = new WP_Query( [
                        'post_type'      => 'thread', // Replace with your actual custom post type
                        'post_status'    => 'publish',
                        'posts_per_page' => 1,
                        'tax_query'      => [
                            [
                                'taxonomy' => $taxonomy_name,
                                'field'    => 'slug',
                                'terms'    => $term->slug,
                            ],
                        ],
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                        'fields'         => 'ids',
                    ] );

                    if ( $latest_thread_query->have_posts() ) {
                        $latest_thread_post_id = $latest_thread_query->posts[0];

                        // Get latest thread date
                        $latest_thread_date = get_the_date( 'Y-m-d H:i:s', $latest_thread_post_id );

                        $latest_thread_user_id   = get_post_field( 'post_author', $latest_thread_post_id );
                        $latest_thread_user_name = get_the_author_meta( 'display_name', $latest_thread_user_id );
                        $latest_thread_user_email = get_the_author_meta( 'user_email', $latest_thread_user_id );

                        $latest_comment_user_name = $latest_thread_user_name;

                        $avatar = get_avatar( $latest_thread_user_id, 32 );
                    }
                }

                if ( (string) $latest_comment_date !== '' ) {
                    $latest_activity_date = '<span>' . human_time_diff( strtotime( $latest_comment_date ), current_time( 'timestamp' ) ) . '</span>';
                } elseif ( (string) $latest_thread_date !== '' ) {
                    $latest_activity_date = '<span>' . human_time_diff( strtotime( $latest_thread_date ), current_time( 'timestamp' ) ) . '</span>';
                } else {
                    $latest_activity_date = '';
                }

                $mingle_status_id = (int) get_term_meta( $term->term_id, 'mingle_status_id', true );
                $mingle_status    = ( $mingle_status_id === 1 ) ? '<i class="icofont-lock"></i> ' : '';

                // Output term information along with counts
                $out .= '<div class="inbound-thread">
                    <div class="inbound-thread--title">
                        <h3>
                            <a href="' . esc_url( add_query_arg( MINGLE_FORUM_SLUG, $term->slug, get_permalink() ) ) . '">' . $mingle_status . esc_html( $term->name ) . '</a>
                        </h3>
                        <div style="font-size:13px;">' . $term->description . '</div>
                    </div>
                    <div class="inbound-thread--comments">' . $thread_count->found_posts . '</div>
                    <div class="inbound-thread--likes">' . $like_count . '</div>
                    <div class="inbound-thread--views">' . $view_count . '</div>
                    <div class="inbound-thread--activity">' .
                        $avatar .
                        '<span>' .
                            esc_html( $latest_comment_user_name ) .
                            '<br>' . $latest_activity_date .
                        '</span>
                    </div>
                </div>';
            }
        }

    $out .= '</div>';

    return $out;
}

add_shortcode( 'forums', 'inbound_get_terms' );
