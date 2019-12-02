<?php
/**
 * Template file for payment form fields.
 *
 * @package Shmart_Payment_Gateway
 */

?>

<p id="edd-contact-wrap">
	<label for="contact_number" class="edd-label">

		<?php esc_html_e( 'Contact Number', 'edd-shmart' ); ?>

		<?php if ( edd_field_is_required( 'contact_number' ) ) { ?>
				<span class="edd-required-indicator">*</span>
		<?php } ?>

	</label>

	<span class="edd-description"><?php esc_html_e( 'Your contact number.', 'edd-shmart' ); ?></span>

	<input id="contact_number" type="text" size="10" name="contact_number" class="contact-number edd-input

	<?php
	if ( edd_field_is_required( 'contact_number' ) ) {
		echo ' required';
	}
	?>

		"placeholder="<?php esc_attr_e( 'Contact Number', 'edd-shmart' ); ?>" value="<?php echo esc_attr( $contact_number ); ?>"/>

</p>
