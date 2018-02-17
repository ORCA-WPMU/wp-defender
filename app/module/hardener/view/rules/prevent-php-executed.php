<div class="rule closed" id="disable-file-editor">
    <div class="rule-title">
		<?php if ( $controller->check() == false ): ?>
            <i class="def-icon icon-warning" aria-hidden="true"></i>
		<?php else: ?>
            <i class="def-icon icon-tick" aria-hidden="true"></i>
		<?php endif; ?>
		<?php _e( "Prevent PHP execution", wp_defender()->domain ) ?>
    </div>
    <div class="rule-content">
        <h3><?php _e( "Overview", wp_defender()->domain ) ?></h3>
        <div class="line end">
			<?php _e( "By default, a plugin/theme vulnerability could allow a PHP file to get uploaded into your site's directories and in turn execute harmful scripts that can wreak havoc on your website. Prevent this altogether by disabling direct PHP execution in directories that don't require it.", wp_defender()->domain ) ?>
        </div>
        <h3>
			<?php _e( "How to fix", wp_defender()->domain ) ?>
        </h3>
        <div class="well">
			<?php
            $setting = \WP_Defender\Module\Hardener\Model\Settings::instance();

            if ( $controller->check() ): ?>
                <p class="line"><?php _e( "PHP execution is locked down.", wp_defender()->domain ) ?>
                <?php
                if ( in_array( $setting->active_server, array( 'apache', 'litespeed' ) ) ) {
                    $file_paths = $setting->getExcludedFilePaths();
                    if ( !empty( $file_paths ) && is_array( $file_paths ) && count( $file_paths ) > 0 ) {
                        _e(" The following file paths have been allowed in the /wp-content directory :", wp_defender()->domain );
						?>
                        <div class="hardener-instructions hardener-instructions-apache-litespeed">
                            <textarea class="hardener-php-excuted-ignore"><?php echo implode( "\n", $file_paths ); ?></textarea>
                            <form method="post" class="hardener-frm hardener-update-frm rule-process">
                                <?php $controller->createNonceField(); ?>
                                <input type="hidden" name="action" value="updateHardener"/>
                                <input type="hidden" name="file_paths" value="<?php echo implode( "\n", $file_paths ); ?>"/>
                                <input type="hidden" name="current_server" value="<?php echo $setting->active_server; ?>"/>
                                <input type="hidden" name="slug" value="<?php echo $controller::$slug ?>"/>
                                <button class="button button-small float-r"
                                        type="submit"><?php _e( "Update .htaccess file", wp_defender()->domain ) ?></button>
                            </form>
                        </div>
						<?php
                    }
                }
                ?>
                </p>
                <form method="post" class="hardener-frm rule-process">
					<?php $controller->createNonceField(); ?>
                    <input type="hidden" name="action" value="processRevert"/>
                    <input type="hidden" name="slug" value="<?php echo $controller::$slug ?>"/>
                    <button class="button button-small button-grey"
                            type="submit"><?php _e( "Revert", wp_defender()->domain ) ?></button>
                </form>
			<?php else:
                $servers = \WP_Defender\Behavior\Utils::instance()->serverTypes();

                if ( DIRECTORY_SEPARATOR == '\\' ) {
                    //Windows
                    $wp_includes    = str_replace( ABSPATH, '', WPINC );
				    $wp_content     = str_replace( ABSPATH, '', WP_CONTENT_DIR );
                } else {
                    $wp_includes    = str_replace( $_SERVER['DOCUMENT_ROOT'], '', ABSPATH . WPINC );
				    $wp_content     = str_replace( $_SERVER['DOCUMENT_ROOT'], '', WP_CONTENT_DIR );
                }
                global $is_nginx, $is_IIS, $is_iis7;
                if ( $is_nginx ) {
                    $setting->active_server = 'nginx';
                } else if ( $is_IIS ) {
                    $setting->active_server = 'iis';
                } else if ( $is_iis7 ) {
                    $setting->active_server = 'iis-7';
                }

            ?>
                <div class="columns">
                    <div class="column is-one-third">
                        <?php _e( 'Server Type:', wp_defender()->domain ); ?>
                    </div>
                    <div class="column is-one-third">
                        <select class="mline hardener-server-list" name="server">
                            <?php foreach ( $servers as $server => $server_name ): ?>
                                <option value="<?php echo esc_attr( $server ); ?>" <?php selected( $server, $setting->active_server ); ?>><?php echo esc_html( $server_name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <input type="hidden" class="hardener-wp-content-dir" value="<?php echo $wp_content; ?>" />
                <input type="hidden" class="hardener-wp-includes-dir" value="<?php echo $wp_includes; ?>" />
                <div class="<?php echo ( $setting->active_server != 'apache' ) ? 'wd-hide' : ''; ?> hardener-instructions hardener-instructions-apache">
                    <div class="line">
                        <p><?php _e( "We will place <strong>.htaccess</strong> file into the root folder to lock down the files and folders inside.", wp_defender()->domain ) ?></p>
                    </div>
                    <form method="post" class="hardener-frm hardener-apache-frm rule-process">
                        <?php $controller->createNonceField(); ?>
                        <input type="hidden" name="action" value="processHardener"/>
                        <input type="hidden" name="file_paths" value=""/>
                        <input type="hidden" name="current_server" value="apache"/>
                        <input type="hidden" name="slug" value="<?php echo $controller::$slug ?>"/>
                        <button class="button float-r"
                                type="submit"><?php _e( "Add .htaccess file", wp_defender()->domain ) ?></button>
                    </form>
                </div>
                <div class="<?php echo ( $setting->active_server != 'litespeed' ) ? 'wd-hide' : ''; ?> hardener-instructions hardener-instructions-litespeed">
                    <div class="line">
                        <p><?php _e( "We will place <strong>.htaccess</strong> file into the root folder to lock down the files and folders inside.", wp_defender()->domain ) ?></p>
                    </div>
                    <form method="post" class="hardener-frm hardener-litespeed-frm rule-process">
                        <?php $controller->createNonceField(); ?>
                        <input type="hidden" name="action" value="processHardener"/>
                        <input type="hidden" name="file_paths" value=""/>
                        <input type="hidden" name="current_server" value="litespeed"/>
                        <input type="hidden" name="slug" value="<?php echo $controller::$slug ?>"/>
                        <button class="button float-r"
                                type="submit" ><?php _e( "Add .htaccess file", wp_defender()->domain ) ?></button>
                    </form>
                </div>
                <div class="<?php echo ( $setting->active_server != 'nginx' ) ? 'wd-hide' : ''; ?> hardener-instructions hardener-instructions-nginx">
                    <?php

                        $rules = "# Stop php access except to needed files in wp-includes
location ~* ^$wp_includes/.*(?<!(js/tinymce/wp-tinymce))\.php$ {
  internal; #internal allows ms-files.php rewrite in multisite to work
}

# Specifically locks down upload directories in case full wp-content rule below is skipped
location ~* /(?:uploads|files)/.*\.php$ {
  deny all;
}

# Deny direct access to .php files in the /wp-content/ directory (including sub-folders).
#  Note this can break some poorly coded plugins/themes, replace the plugin or remove this block if it causes trouble
location ~* ^$wp_content/.*\.php$ {
  deny all;
}
";
                    ?>

                    <p><?php esc_html_e( "For NGINX servers:", wp_defender()->domain ) ?></p>
                    <ol>
                        <li>
                            <?php esc_html_e( "Copy the generated code into your site specific .conf file usually located in a subdirectory under /etc/nginx/... or /usr/local/nginx/conf/...", wp_defender()->domain ) ?>
                        </li>
                        <li>
                            <?php _e( "Add the code above inside the <strong>server</strong> section in the file, right before the php location block. Looks something like:", wp_defender()->domain ) ?>
                            <pre>location ~ \.php$ {</pre>
                        </li>
                        <li>
                            <?php esc_html_e( "Reload NGINX.", wp_defender()->domain ) ?>
                        </li>
                    </ol>
                    <p><?php echo sprintf( __( "Still having trouble? <a target='_blank' href=\"%s\">Open a support ticket</a>.", wp_defender()->domain ), 'https://premium.wpmudev.org/forums/forum/support#question' ) ?></p>
                    <pre>
## WP Defender - Prevent PHP Execution ##
                        <?php echo esc_html( $rules ); ?>
                        <span class="hardener-nginx-extra-instructions"></span>
                        ## WP Defender - End ##
                    </pre>
                </div>
                <div class="<?php echo ( $setting->active_server != 'iis' ) ? 'wd-hide' : ''; ?> hardener-instructions hardener-instructions-iis">
                    <div class="line">
                        <p><?php printf( __( 'For IIS servers, <a href="%s">visit Microsoft TechNet</a>', wp_defender()->domain ), 'https://technet.microsoft.com/en-us/library/cc725855(v=ws.10).aspx' ); ?></p>
                    </div>
                </div>
                <div class="<?php echo ( $setting->active_server != 'iis-7' ) ? 'wd-hide' : ''; ?> hardener-instructions hardener-instructions-iis-7">
                    <div class="line">
                        <p><?php _e( "We will place <strong>web.config</strong> file into the uploads folder to lock down the files and folders inside.", wp_defender()->domain ) ?></p>
                    </div>
                    <div class="line">
                        <p><?php printf( __( 'For more information, please <a href="%s">visit Microsoft TechNet</a>', wp_defender()->domain ), 'https://technet.microsoft.com/en-us/library/cc725855(v=ws.10).aspx' ); ?></p>
                    </div>
                    <form method="post" class="hardener-frm hardener-litespeed-frm rule-process">
                        <?php $controller->createNonceField(); ?>
                        <input type="hidden" name="action" value="processHardener"/>
                        <input type="hidden" name="current_server" value="iis-7"/>
                        <input type="hidden" name="slug" value="<?php echo $controller::$slug ?>"/>
                        <button class="button float-r"
                                type="submit" ><?php _e( "Add web.config file", wp_defender()->domain ) ?></button>
                    </form>

                </div>
                <?php $controller->showIgnoreForm();
                $prevent_php_style = "style='display:none'";
                if ( in_array( $setting->active_server, array( 'apache', 'litespeed', 'nginx' ) ) ) {
                    $prevent_php_style = "style='display:block'";
                }
                ?>
                <div <?php echo $prevent_php_style; ?> class="hardener-instructions hardener-instructions-extra-exceptions">
                    <h3>
                        <?php _e( "Exceptions", wp_defender()->domain ) ?>
                    </h3>
                    <div class="line">
                        <p><?php _e( "By default Defender will lock down directories WordPress doesn't need to allow PHP execution for. However, if you have specific files you need to allow PHP execution for you can add exceptions. Add file name one per line", wp_defender()->domain ) ?></p>
                        <button class="button button-grey hardener-php-excuted-execption" type="button"><?php _e( "Add Exception", wp_defender()->domain ) ?></button>
                    </div>
                    <div class="line">
                        <textarea class="hardener-php-excuted-ignore" style='display:none'></textarea>
                    </div>
                </div>
                <?php endif; ?>
        </div>
    </div>
</div>