<div class="rule closed" id="disable-file-editor">
    <div class="rule-title">
		<?php if ( $controller->check() == false ): ?>
            <i class="def-icon icon-warning" aria-hidden="true"></i>
		<?php else: ?>
            <i class="def-icon icon-tick" aria-hidden="true"></i>
		<?php endif; ?>
		<?php _e( "Prevent Information Disclosure", wp_defender()->domain ) ?>
    </div>
    <div class="rule-content">
        <h3><?php _e( "Overview", wp_defender()->domain ) ?></h3>
        <div class="line end">
			<?php _e( "Often servers are incorrectly configured, and can allow an attacker to get access to sensitive information that can be used in attacks. WP Defender can help you prevent that disclosure.", wp_defender()->domain ) ?>
        </div>
        <h3>
			<?php _e( "How to fix", wp_defender()->domain ) ?>
        </h3>
        <div class="well">
			<?php if ( $controller->check() ): ?>
                <p class="line"><?php _e( "Your WordPress is protected.", wp_defender()->domain ) ?></p>
                <form method="post" class="hardener-frm rule-process">
					<?php $controller->createNonceField(); ?>
                    <input type="hidden" name="action" value="processRevert"/>
                    <input type="hidden" name="slug" value="<?php echo $controller::$slug ?>"/>
                    <button class="button button-small button-grey"
                            type="submit"><?php _e( "Revert", wp_defender()->domain ) ?></button>
                </form>
			<?php else:
                $servers        = \WP_Defender\Behavior\Utils::instance()->serverTypes();
                $setting        = \WP_Defender\Module\Hardener\Model\Settings::instance();
				$setting->active_server = \WP_Defender\Behavior\Utils::instance()->determineServer( true );
            ?>
                <div class="columns">
                    <div class="column is-one-third">
                        <?php _e( 'Server Type:', wp_defender()->domain ); ?>
                    </div>
                    <div class="column is-one-third">
                        <select class="mline hardener-server-list information" name="server">
                            <?php foreach ( $servers as $server => $server_name ): ?>
                                <option value="<?php echo esc_attr( $server ); ?>" <?php selected( $server, $setting->active_server ); ?>><?php echo esc_html( $server_name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="<?php echo ( $setting->active_server != 'apache' ) ? 'wd-hide' : ''; ?> hardener-information hardener-information-apache">
                    <div class="line">
                        <p><?php _e( "We will place <strong>.htaccess</strong> file into the root folder to lock down the files and folders inside.", wp_defender()->domain ) ?></p>
                    </div>
                    <form method="post" class="hardener-frm rule-process">
                        <?php $controller->createNonceField(); ?>
                        <input type="hidden" name="action" value="processHardener"/>
                        <input type="hidden" name="slug" value="<?php echo $controller::$slug ?>"/>
                        <button class="button float-r"
                                type="submit"><?php _e( "Add .htaccess file", wp_defender()->domain ) ?></button>
                    </form>
                </div>
                <div class="<?php echo ( $setting->active_server != 'litespeed' ) ? 'wd-hide' : ''; ?> hardener-information hardener-information-litespeed">
                    <div class="line">
                        <p><?php _e( "We will place <strong>.htaccess</strong> file into the root folder to lock down the files and folders inside.", wp_defender()->domain ) ?></p>
                    </div>
                    <form method="post" class="hardener-frm rule-process">
                        <?php $controller->createNonceField(); ?>
                        <input type="hidden" name="action" value="processHardener"/>
                        <input type="hidden" name="slug" value="<?php echo $controller::$slug ?>"/>
                        <button class="button float-r"
                                type="submit"><?php _e( "Add .htaccess file", wp_defender()->domain ) ?></button>
                    </form>
                </div>
                <div class="<?php echo ( $setting->active_server != 'nginx' ) ? 'wd-hide' : ''; ?> hardener-information hardener-information-nginx">
                    <?php
					if ( DIRECTORY_SEPARATOR == '\\' ) {
						//Windows
						$wp_content     = str_replace( ABSPATH, '', WP_CONTENT_DIR );
					} else {
						$wp_content     = str_replace( $_SERVER['DOCUMENT_ROOT'], '', WP_CONTENT_DIR );
					}

                    $rules = "# Turn off directory indexing
autoindex off;

# Deny access to htaccess and other hidden files
location ~ /\. {
  deny  all;
}

# Deny access to wp-config.php file
location = /wp-config.php {
  deny all;
}

# Deny access to revealing or potentially dangerous files in the /wp-content/ directory (including sub-folders)
location ~* ^$wp_content/.*\.(txt|md|exe|sh|bak|inc|pot|po|mo|log|sql)$ {
  deny all;
}
";
                    ?>
                    <div class="">
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
                    <pre>## WP Defender - Prevent information disclosure ##<?php echo esc_html( $rules ); ?>## WP Defender - End ##</pre>
                    </div>
                </div>
                <div class="<?php echo ( $setting->active_server != 'iis' ) ? 'wd-hide' : ''; ?> hardener-information hardener-information-iis">
                    <div class="line">
                        <p><?php printf( __( 'For IIS servers, <a href="%s">visit Microsoft TechNet</a>', wp_defender()->domain ), 'https://technet.microsoft.com/en-us/library/cc754807(v=ws.10).aspx' ); ?></p>
                    </div>
                </div>
                <div class="<?php echo ( $setting->active_server != 'iis-7' ) ? 'wd-hide' : ''; ?> hardener-information hardener-information-iis-7">
                    <div class="line">
                        <p><?php printf( __( 'For IIS 7 servers, <a href="%s">visit Microsoft TechNet</a>', wp_defender()->domain ), 'https://technet.microsoft.com/en-us/library/cc754807(v=ws.10).aspx' ); ?></p>
                    </div>
                </div>
                <?php $controller->showIgnoreForm() ?>
			<?php endif; ?>
        </div>
    </div>
</div>