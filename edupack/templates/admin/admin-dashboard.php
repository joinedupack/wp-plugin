<div class="welcome-panel-content">
    <?php
    $network_data = get_network();
    ?>
    <h2><?php _e( 'Welcome to ' . $network_data->site_name ); ?></h2>
    <p class="about-description"><?php _e( 'Here are some links to get you started:' ); ?></p>
    <div class="welcome-panel-column-container">
    
        <div class="welcome-panel-column">
            <h3>Edit your site</h3>
            <ul>		
                <?php if ( 'page' === get_option( 'show_on_front' ) && ! get_option( 'page_for_posts' ) ) : ?>
                    <li><?php printf( '<a href="%s" class="welcome-icon welcome-edit-page">' . __( 'Edit your front page' ) . '</a>', get_edit_post_link( get_option( 'page_on_front' ) ) ); ?></li>
                    <li><?php printf( '<a href="%s" class="welcome-icon welcome-add-page">' . __( 'Add additional pages' ) . '</a>', admin_url( 'post-new.php?post_type=page' ) ); ?></li>
                <?php elseif ( 'page' === get_option( 'show_on_front' ) ) : ?>
                    <li><?php printf( '<a href="%s" class="welcome-icon welcome-edit-page">' . __( 'Edit your front page' ) . '</a>', get_edit_post_link( get_option( 'page_on_front' ) ) ); ?></li>
                    <li><?php printf( '<a href="%s" class="welcome-icon welcome-add-page">' . __( 'Add additional pages' ) . '</a>', admin_url( 'post-new.php?post_type=page' ) ); ?></li>
                    <li><?php printf( '<a href="%s" class="welcome-icon welcome-write-blog">' . __( 'Add a blog post' ) . '</a>', admin_url( 'post-new.php' ) ); ?></li>
                <?php else : ?>
                    <li><?php printf( '<a href="%s" class="welcome-icon welcome-write-blog">' . __( 'Write a blog post' ) . '</a>', admin_url( 'post-new.php' ) ); ?></li>
                    <li><?php printf( '<a href="%s" class="welcome-icon welcome-add-page">' . __( 'Add a page' ) . '</a>', admin_url( 'post-new.php?post_type=page' ) ); ?></li>
                    <li><?php printf( '<a href="%s" class="welcome-icon welcome-setup-home">' . __( 'Set up your homepage' ) . '</a>', current_user_can( 'customize' ) ? add_query_arg( 'autofocus[section]', 'static_front_page', admin_url( 'customize.php' ) ) : admin_url( 'options-reading.php' ) ); ?></li>
                <?php endif; ?>
                <?php if ( current_theme_supports( 'menus' ) ) : ?>
                    <li><?php printf( '<a href="%s" class="welcome-icon welcome-menus">' . __( 'Manage menus' ) . '</a>', admin_url( 'nav-menus.php' ) ); ?></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="welcome-panel-column">
            <h3>Preview</h3>
            <ul>
                <li><?php printf( '<a href="%s" class="welcome-icon welcome-setup-home">' . __( 'View this site' ) . '</a>', home_url( '/' ) ); ?></li>
                <li><?php printf( '<a href="%s" class="welcome-icon welcome-view-site">' . __( 'View all your sites' ) . '</a>', admin_url( 'my-sites.php' ) ); ?></li>
            </ul>
        </div>

        <div class="welcome-panel-column">
            <h3>More</h3>
            <ul>
                <li><?php printf( '<a href="%s" class="welcome-icon welcome-learn-more">' . __( 'Edupack.dev' ) . '</a>', 'https://edupack.dev/' ); ?></li>
                <li><?php printf( '<a href="%s" class="welcome-icon welcome-widgets-menus">' . __( 'Edupack Knowledgebase' ) . '</a>', 'https://help.edupack.dev/' ); ?></li>
            </ul>
        </div>
    </div>
</div>