<?php
/**
 * Listing submission form template.
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$can_post = is_user_logged_in() || ! mylisting_get_setting( 'submission_requires_account' );
?>

<div class="i-section submit-claim-form">
	<div class="container">
		<div class="row section-title">
			<h2 class="case27-primary-text"><?php _ex( 'Claim listing form', 'Claim listing form', 'my-listing' ) ?></h2>
		</div>
		<form action="<?php echo esc_url( $action ); ?>" method="post" id="submit-claim-form" class="job-manager-form light-forms c27-submit-listing-form" enctype="multipart/form-data">

			<div class="form-section-wrapper">
				<div class="element form-section">
					<div class="pf-body">
						<?php foreach ( $fields as $key => $field ) : ?>
							<div class="fieldset-<?php echo esc_attr( $key ) ?> <?php echo esc_attr( 'field-type-'.$field->get_type() ) ?> form-group">
								<div class="field-head">
									<label for="<?php echo esc_attr( $key ) ?>">
										<?php
											echo $field->get_label();
											echo apply_filters(
												'mylisting/submission/required-field-label',
												! $field->is_required()
													? ' <small>'._x( '(optional)', 'Add listing form', 'my-listing' ).'</small>'
													: '',
												$field
											);
										?>
									</label>
									<?php if ( ! empty( $field->get_description() ) ): ?>
										<small class="description"><?php echo $field->get_description() ?></small>
									<?php endif ?>
								</div>
								<div class="field <?php echo $field->is_required() ? 'required-field' : ''; ?>">
									<?php mylisting_locate_template( 'templates/add-listing/form-fields/'.$field->get_type().'-field.php', [ 'key' => $key, 'field' => $field ] ); ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			<div class="form-section-wrapper form-footer" id="form-section-submit">
				<div class="form-section">
					<div class="pf-body">
						<div class="hidden">
							<input type="hidden" name="job_manager_form" value="<?php echo esc_attr( $form ) ?>">
							<input type="hidden" name="job_id" value="<?php echo esc_attr( $job_id ) ?>">
							<input type="hidden" name="step" value="<?php echo esc_attr( $step ) ?>">
							<?php if ( ! empty( $_REQUEST['listing_type'] ) ): ?>
								<input type="hidden" name="listing_type" value="<?php echo esc_attr( $_REQUEST['listing_type'] ) ?>">
							<?php endif ?>
							<?php if ( ! empty( $_REQUEST['listing_package'] ) ): ?>
								<input type="hidden" name="listing_package" value="<?php echo esc_attr( $_REQUEST['listing_package'] ) ?>">
							<?php endif ?>
						</div>

						<div class="listing-form-submit-btn">
							<button type="submit" name="submit_job" class="skip-preview-btn buttons button-2" value="submit--no-preview">
								<?php echo esc_attr( _x( 'Submit claim request', 'Claim listing form', 'my-listing' ) ) ?>
							</button>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>

<div class="loader-bg main-loader add-listing-loader" style="background-color: #fff; display: none;">
	<?php c27()->get_partial( 'spinner', [ 'color' => '#000' ] ) ?>
	<p class="add-listing-loading-message"><?php _ex( 'Please wait while the request is being processed.', 'Add listing form', 'my-listing' ) ?></p>
</div>