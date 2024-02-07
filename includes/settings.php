<?php
add_action( 'admin_menu', 'mingle_menu' );



function mingle_menu() {
    add_menu_page(
        __( 'Mingle Settings', 'mingle' ),
        __( 'Mingle', 'mingle' ),
        'manage_options',
        'mingle',
        'mingle_settings',
        'dashicons-buddicons-forums'
    );
}



function mingle_settings() {
    $tab     = ( filter_has_var( INPUT_GET, 'tab' ) ) ? filter_input( INPUT_GET, 'tab' ) : 'dashboard';
    $section = 'admin.php?page=mingle&amp;tab=';
    ?>
    <div class="wrap">
        <h1>ImagePress Settings</h1>

        <h2 class="nav-tab-wrapper ip-nav-tab-wrapper">
            <a href="<?php echo $section; ?>dashboard" class="nav-tab <?php echo $tab === 'dashboard' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Dashboard', 'imagepress' ); ?></a>
            <a href="<?php echo $section; ?>settings" class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Settings', 'imagepress' ); ?></a>
        </h2>

        <?php
        if ( $tab === 'dashboard' ) {
            global $wpdb;

            // Get the WP built-in version
            $ipdata = get_plugin_data( MINGLE_PLUGIN_PATH );
            ?>

            <div id="gb-ad">
                <h3 class="gb-handle">👋 Thank you for using <b>Mingle Forums &amp; Discussion Boards</b>!</h3>
                <div id="gb-ad-content">
                    <div class="gb-footer">
                        <p>For support, feature requests and bug reporting, please visit the <a href="https://getbutterfly.com/" rel="external">official website</a>.<br>Built by <a href="https://getbutterfly.com/" rel="external"><strong>getButterfly</strong>.com</a> &middot; <a href="https://getbutterfly.com/wordpress-plugins/mingle-forum/">Documentation</a> &middot; <small>Code wrangling since 2005</small></p>
                    </div>
                </div>
            </div>

            <p>
                <small>You are using <b>Mingle Forums &amp; Discussion Boards</b> plugin version <strong><?php echo trim( $ipdata['Version'] ); ?></strong>.</small><br>
                <small>You are using PHP version <?php echo trim( PHP_VERSION ); ?> and MySQL server version <?php echo $wpdb->db_version(); ?>.</small>
            </p>

            <h3>Shortcodes</h3>
            <p>
                <code>[mingle-forum count="72" view="threads|forums"]</code> - show the forums.
            </p>
            <p>Use the <code>count</code> parameter to limit the number of forums displayed.</p>
            <p>Use the <code>view</code> parameter to switch between threads and forums.</p>
            <?php
        } elseif ( $tab === 'settings' ) {
            if ( isset( $_POST['mingle_save'] ) ) {
                update_option( 'mingle_page_forums', (int) sanitize_text_field( $_POST['mingle_page_forums'] ) );

                update_option( 'mingle_page_login', sanitize_url( $_POST['mingle_page_login'] ) );
                update_option( 'mingle_page_signup', sanitize_url( $_POST['mingle_page_signup'] ) );

                update_option( 'mingle_use_template', (int) sanitize_text_field( $_POST['mingle_use_template'] ) );
                update_option( 'mingle_moderate', (int) sanitize_text_field( $_POST['mingle_moderate'] ) );

                echo '<div class="updated notice is-dismissible"><p>Settings updated successfully!</p></div>';
            }
            ?>
            <form method="post" action="">
                <h2>General Settings</h2>

                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="">Pages &amp; Content</label></th>
                            <td>
                                <p>
                                    <?php
                                    wp_dropdown_pages(
                                        [
                                            'name'              => 'mingle_page_forums',
                                            'echo'              => 1,
                                            'show_option_none'  => __( 'Select forums page...', 'mingle' ),
                                            'option_none_value' => 0,
                                            'selected'          => get_option( 'mingle_page_forums' ),
                                        ]
                                    );
                                    ?>
                                    <br><small>Make sure you add the <code>[mingle-forum]</code> shortcode on this page.</small>
                                </p>
                                <p>
                                    <input type="url" placeholder="https://" class="regular-text" value="<?php echo get_option( 'mingle_page_login' ); ?>" name="mingle_page_login">
                                    <label>Login Page</label>
                                </p>
                                <p>
                                    <input type="url" placeholder="https://" class="regular-text" value="<?php echo get_option( 'mingle_page_signup' ); ?>" name="mingle_page_signup">
                                    <label>Signup/Registration Page</label>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="mingle_use_template">Single Thread Template</label></th>
                            <td>
                                <select name="mingle_use_template" id="mingle_use_template">
                                    <option value="0" <?php selected( 0, (int) get_option( 'mingle_use_template' ) ); ?>>None (allow theme customization)</option>
                                    <option value="1" <?php selected( 1, (int) get_option( 'mingle_use_template' ) ); ?>>Use the_content() filter (default, recommended)</option>
                                    <option value="2" <?php selected( 2, (int) get_option( 'mingle_use_template' ) ); ?>>Use included template</option>
                                    <option value="3" <?php selected( 3, (int) get_option( 'mingle_use_template' ) ); ?>>Use theme template</option>
                                </select>
                                <br><small>Using no filter will the theme to create a native single template or allow for furtherm customization in theme's options.</small>
                                <br><small>Using <code>the_content()</code> filter will display the thread in the content area, using the theme's <code>single.php</code> template. This is the default behaviour.</small>
                                <br><small>Using the included template will display the image using the <code>mingle/templates/single-thread.php</code> template. The layout may not match your theme's layout.</small>
                                <br><small>Using the theme template will display the thread using a custom <code>single-thread.php</code> template in your theme's folder. This is recommended if the default behaviour is broken.</small>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="mingle_moderate">Thread Moderation</label></th>
                            <td>
                                <select name="mingle_moderate" id="mingle_moderate">
                                    <option value="0"<?php selected( 0, (int) get_option( 'mingle_moderate' ) ); ?>>Moderate all threads</option>
                                    <option value="1"<?php selected( 1, (int) get_option( 'mingle_moderate' ) ); ?>>Do not moderate threads</option>
                                </select>
                                <br><small>Moderate all submitted threads.</small>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <hr>
                <p><input type="submit" name="mingle_save" value="Save Changes" class="button-primary"></p>
            </form>
        <?php } ?>
    </div>
    <?php
}
