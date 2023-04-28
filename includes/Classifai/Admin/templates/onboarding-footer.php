<?php
/**
 * Footer template for ClassifAI Onboarding.
 *
 * @package ClassifAI
 */

?>
				<div class="classifai-setup-footer">
					<span class="classifai-setup-footer__left">
						<a href="<?php echo esc_url( $args['left_link']['url'] ?? admin_url() ); ?>" class="classifai-setup-skip-link">
							<?php echo esc_html( $args['left_link']['text'] ?? __( 'Skip for now', 'classifai' ) ); ?>
						</a>
					</span>
					<span class="classifai-setup-footer__right">
						<input name="classifai-setup-step" type="hidden" value="<?php echo esc_attr( $args['step'] ); ?>" />
						<?php
						if ( ! empty( $args['right_link']['submit'] ) ) {
							wp_nonce_field( 'classifai-setup-step-action', 'classifai-setup-step-nonce' );
							?>
							<input class="classifai-button" type="submit" value="<?php echo esc_attr( $args['right_link']['text'] ?? __( 'Submit', 'classifai' ) ); ?>" />
							<?php
						} else {
							?>
							<a class="classifai-button" href="<?php echo esc_url( $args['right_link']['url'] ?? admin_url() ); ?>">
								<?php echo esc_html( $args['right_link']['text'] ?? __( 'Submit', 'classifai' ) ); ?>
							</a>
							<?php
						}
						?>
					</span>
				</div>
			</form>
		</div>
	</div>
</div>
