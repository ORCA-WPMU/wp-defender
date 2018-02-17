<div class="wrap">
	<div class="wpmud">
		<div class="wp-defender">
			<div class="wd-settings">
				<section id="header">
					<h1 class="tl"><?php esc_html_e( "Settings", wp_defender()->domain ) ?></h1>
				</section>
				<?php if ( $controller->has_flash( 'updated' ) ): ?>
					<div class="wd-success wd-left">
						<a href="#" class="wd-dismiss">
							&times;
						</a>
						<i class="dev-icon dev-icon-tick"></i>
						<?php echo esc_html( $controller->get_flash( 'updated' ) ) ?>
					</div>
				<?php endif; ?>
				<section class="dev-box">
					<div class="box-title">
						<h3><?php esc_html_e( "General Settings", wp_defender()->domain ) ?></h3>
					</div>
					<div class="box-content">
						<form method="post">
							<div class="row setting-field">
								<div class="col-left">
									<label><?php esc_html_e( "Scan types", wp_defender()->domain ) ?></label>

									<div class="setting-description">
										<?php esc_html_e( "By default we recommend running all scans but you can turn these off if you choose", wp_defender()->domain ) ?>
										<div class="wd-clearfix"></div>
										<br/>
									</div>
								</div>
								<div class="col-right">
									<div class="group">
										<?php
										$key     = 'use_' . WD_Scan_Api::SCAN_CORE_INTEGRITY . '_scan';
										$tooltip = WD_Utils::get_setting( $key ) == 1 ? esc_html__( "Disable This Scan", wp_defender()->domain ) : esc_html__( "Enable This Scan", wp_defender()->domain );
										?>
										<div class="col span_4_of_12">
											<label><?php esc_html_e( "WP Core Integrity", wp_defender()->domain ) ?></label>
										</div>
										<div class="col span_8_of_12">
											<div class="group">
												<div class="col span_1_of_12">
													<span class="toggle"
													      tooltip="<?php echo esc_attr( $tooltip ) ?>">
											<input type="checkbox" class="toggle-checkbox"
											       id="<?php echo esc_html( $key ) ?>"
												<?php checked( 1, WD_Utils::get_setting( $key ) ) ?>/>
											<label class="toggle-label" for="<?php echo esc_attr( $key ) ?>"></label>
										</span>
												</div>
												<div class="col span_11_of_12">
													<small class="">
														<?php esc_html_e( "Defender checks for any modifications or additions to WP core files.", wp_defender()->domain ) ?>
													</small>
												</div>
											</div>

										</div>
										<div class="wd-clear"></div>
									</div>
									<div class="group wd-relative-position">
										<div class="col span_4_of_12">
											<label><?php esc_html_e( "Plugin & Theme Vulnerabilities", wp_defender()->domain ) ?></label>
										</div>
										<div class="col span_8_of_12">
											<div class="group">
												<div class="col span_1_of_12">
													<?php
													$key     = 'use_' . WD_Scan_Api::SCAN_VULN_DB . '_scan';
													$tooltip = WD_Utils::get_setting( 'use_' . WD_Scan_Api::SCAN_VULN_DB . '_scan' ) == 1 ? esc_html__( "Disable This Scan", wp_defender()->domain ) : esc_html__( "Enable This Scan", wp_defender()->domain );
													?>
													<span class="toggle"
													      tooltip="<?php echo esc_attr( $tooltip ) ?>">
											<input type="checkbox" class="toggle-checkbox"
											       id="<?php echo esc_attr( $key ) ?>"
												<?php checked( 1, WD_Utils::get_setting( $key ) ) ?>/>
											<label class="toggle-label" for="<?php echo esc_attr( $key ) ?>"></label>
										</span>
												</div>
												<div class="col span_11_of_12">
													<small>
														<?php esc_html_e( "Defender looks for published vulnerabilities in your installed plugins and themes.", wp_defender()->domain ) ?>
													</small>
												</div>
											</div>
										</div>
										<?php if ( WD_Utils::get_dev_api() == false ): ?>
											<div
												tooltip="<?php esc_attr_e( "WPMU DEV Dashboard is required for this scan", wp_defender()->domain ) ?>"
												class="wd-overlay"></div>
										<?php endif; ?>
										<div class="wd-clear"></div>
									</div>
									<div class="group wd-relative-position">
										<div class="col span_4_of_12">
											<label><?php esc_html_e( "Suspicious Code", wp_defender()->domain ) ?></label>
										</div>
										<div class="col span_8_of_12">
											<div class="group">
												<div class="col span_1_of_12">
													<?php
													$key     = 'use_' . WD_Scan_Api::SCAN_SUSPICIOUS_FILE . '_scan';
													$tooltip = WD_Utils::get_setting( $key ) == 1 ? esc_html__( "Disable This Scan", wp_defender()->domain ) : esc_html__( "Enable This Scan", wp_defender()->domain );
													?>
													<span class="toggle"
													      tooltip="<?php echo esc_attr( $tooltip ) ?>">
											<input type="checkbox" class="toggle-checkbox"
											       id="<?php echo esc_attr( $key ) ?>"
												<?php checked( 1, WD_Utils::get_setting( 'use_' . WD_Scan_Api::SCAN_SUSPICIOUS_FILE . '_scan' ) ) ?>/>
											<label class="toggle-label" for="<?php echo esc_attr( $key ) ?>"></label>
										</span>
												</div>
												<div class="col span_11_of_12">
													<small>
														<?php esc_html_e( "Defender looks inside all of your files for suspicious and potentially harmful code.", wp_defender()->domain ) ?>
													</small>
												</div>
											</div>
										</div>
										<?php if ( WD_Utils::get_dev_api() == false ): ?>
											<div
												tooltip="<?php esc_attr_e( "WPMU DEV Dashboard is required for this scan", wp_defender()->domain ) ?>"
												class="wd-overlay"></div>
										<?php endif; ?>
										<div class="wd-clearfix"></div>
									</div>
								</div>
								<div class="wd-clearfix"></div>
							</div>
							<div class="row setting-field">
								<div class="col-left">
									<label><?php esc_html_e( "Max included file size (MB)", wp_defender()->domain ) ?></label>

									<div class="setting-description">
										<?php esc_html_e( "Defender will skip any files larger than this size. The smaller this number is the faster Defender can scan through your system.", wp_defender()->domain ) ?>
										<div class="wd-clearfix"></div>
										<br/>
									</div>
								</div>
								<div class="col-right">
									<div class="group">
										<div class="col span_4_of_12">
											<input type="text" name="max_file_size"
											       value="<?php echo esc_attr( WD_Utils::get_setting( 'max_file_size' ) ) ?>">
										</div>
									</div>
								</div>
								<div class="wd-clearfix"></div>
							</div>
							<div class="row setting-field">
								<div class="col-left">
									<label><?php esc_html_e( "Enable all email reports", wp_defender()->domain ) ?></label>

									<div class="setting-description">
										<?php esc_html_e( "By default, Defender will email you when it runs into trouble on your site. Enabling this option will ensure you are always kept up-to-date, even when your site is running smoothly.", wp_defender()->domain ) ?>
										<div class="wd-clearfix"></div>
										<br/>
									</div>
								</div>
								<div class="col-right">
									<div class="group">
										<div class="col span_4_of_12">
											<?php
											$key = 'always_notify';
											//$tooltip = WD_Utils::get_setting( $key, 0 ) == 1 ? esc_html__( "Send only problem", wp_defender()->domain ) : esc_html__( "Always send", wp_defender()->domain );
											?>
											<span class="toggle">
											<input type="checkbox" class="toggle-checkbox"
											       id="<?php echo esc_attr( $key ) ?>"
												<?php checked( 1, WD_Utils::get_setting( $key, 0 ) ) ?>/>
											<label class="toggle-label" for="<?php echo esc_attr( $key ) ?>"></label>
										</span>
										</div>
									</div>
								</div>
								<div class="wd-clearfix"></div>
							</div>
							<?php wp_nonce_field( 'wd_settings', 'wd_settings_nonce' ) ?>
							<input type="hidden" name="action" value="wd_settings_save"/>
							<br/>

							<div class="wd-clearfix"></div>
							<div class="wd-right">
								<button type="submit" class="button wd-button">
									<?php esc_html_e( "Save Settings", wp_defender()->domain ) ?>
								</button>
							</div>
						</form>
						<br/>
					</div>
				</section>
				<section class="dev-box">
					<div class="box-title">
						<h3><?php esc_html_e( "Email Recipients", wp_defender()->domain ) ?></h3>
					</div>
					<div class="box-content">
						<form id="email-recipients-frm">
							<p>
								<?php esc_html_e( "Choose which of your website’s users will receive scan report results to their email inboxes.", wp_defender()->domain ) ?>
							</p>
							<div class="wd-error wd-hide"></div>
							<div class="wd-clear"></div>
							<br/>
							<?php echo $controller->display_recipients() ?>
							<input name="username" id="email-recipient" class="user-search"
							       data-empty-msg="<?php esc_attr_e( "We did not find an admin user with this name...", wp_defender()->domain ) ?>"
							       placeholder="<?php esc_attr_e( "Type a user’s name", wp_defender()->domain ) ?>"
							       type="search"/>
							<button type="submit" disabled="disabled"
							        class="button wd-button"><?php esc_html_e( "Add", wp_defender()->domain ) ?></button>
							<div class="clearfix"></div>
							<input type="hidden" name="action" value="wd_add_recipient">
							<?php wp_nonce_field( 'wd_add_recipient', 'wd_settings_nonce' ) ?>
						</form>
					</div>
				</section>
				<section class="dev-box">
					<div class="box-title">
						<h3><?php esc_html_e( "Email Templates", wp_defender()->domain ) ?></h3>
					</div>
					<div class="box-content">
						<p>
							<?php esc_html_e( "When Defender scans this website it will generate a report of any issues. You can choose to email those notifications to a particular email address and change the copy below.", wp_defender()->domain ) ?>
						</p>

						<p>
							<?php esc_html_e( "Available variables", wp_defender()->domain ) ?>
						</p>

						<div class="wd-well">
							<div class="group">
								<div class="col span_4_of_12">
									<p>{USER_NAME}</p>
								</div>
								<div class="col span_8_of_12">
									<?php esc_html_e( "We’ll grab the users first name, or display name is first name isn’t available", wp_defender()->domain ) ?>
								</div>
							</div>
							<div class="wd-clearfix"></div>
							<div class="group">
								<div class="col span_4_of_12">
									<p>{ISSUES_COUNT}</p>
								</div>
								<div class="col span_8_of_12">
									<?php esc_html_e( "The number of issues Defender found", wp_defender()->domain ) ?>
								</div>
							</div>
							<div class="wd-clearfix"></div>
							<div class="group">
								<div class="col span_4_of_12">
									<p>{ISSUES_LIST}</p>
								</div>
								<div class="col span_8_of_12">
									<?php esc_html_e( "The list of issues", wp_defender()->domain ) ?><br/>
								</div>
							</div>
							<div class="wd-clearfix"></div>
							<div class="group">
								<div class="col span_4_of_12">
									<p>{SCAN_PAGE_LINK}</p>
								</div>
								<div class="col span_8_of_12">
									<?php esc_html_e( "A link back to the Scans tab of this website", wp_defender()->domain ) ?>
								</div>
							</div>
						</div>
						<br/>

						<form method="post">
							<div class="setting-field">
								<div class="col-left">
									<label
										for="completed_scan_email_subject"><?php esc_html_e( "Subject", wp_defender()->domain ) ?></label>
								</div>
								<div class="col-right">
									<input type="text" id="completed_scan_email_subject"
									       name="completed_scan_email_subject"
									       value="<?php esc_attr_e( WD_Utils::get_setting( 'completed_scan_email_subject' ) ) ?>"/>
								</div>
								<div class="wd-clearfix"></div>
							</div>
							<div class="setting-field">
								<div class="col-left">
									<label
										for="completed_scan_email_content_error"><?php esc_html_e( "Issues found", wp_defender()->domain ) ?></label>

									<div class="setting-description">
										<?php esc_html_e( "When an issue has been found during an automated scan, Defender will send this email to your recipients.", wp_defender()->domain ) ?>
										<div class="wd-clearfix"></div>
										<br/>
									</div>
								</div>
								<div class="col-right">
								<textarea rows="10" id="completed_scan_email_content_error"
								          name="completed_scan_email_content_error"><?php echo esc_textarea( WD_Utils::get_setting( 'completed_scan_email_content_error' ) ) ?></textarea>
								</div>
								<div class="wd-clearfix"></div>
							</div>
							<div class="setting-field">
								<div class="col-left">
									<label for="completed_scan_email_content_success">
										<?php esc_html_e( "All OK", wp_defender()->domain ) ?></label>

									<div class="setting-description">
										<?php esc_html_e( "When there are no issues detected by the scan your recipients will receive this email.", wp_defender()->domain ) ?>
										<div class="wd-clearfix"></div>
										<br/>
									</div>
								</div>
								<div class="col-right">
								<textarea rows="10" id="completed_scan_email_content_success"
								          name="completed_scan_email_content_success"><?php echo esc_textarea( WD_Utils::get_setting( 'completed_scan_email_content_success' ) ) ?></textarea>
								</div>
								<div class="wd-clearfix"></div>
							</div>
							<?php wp_nonce_field( 'wd_settings', 'wd_settings_nonce' ) ?>
							<br/>

							<div class="wd-clearfix"></div>
							<input type="hidden" name="action" value="wd_settings_save"/>

							<div class="wd-right">
								<button type="submit" class="button wd-button">
									<?php esc_html_e( "Save Settings", wp_defender()->domain ) ?>
								</button>
							</div>
						</form>
					</div>
				</section>
			</div>
		</div>
	</div>
</div>