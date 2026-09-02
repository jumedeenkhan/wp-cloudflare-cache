<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function sfcfc_toggle_id( $field_name ) {
	return 'sfcfc-toggle-' . sanitize_html_class( str_replace( array( '[', ']' ), '-', $field_name ) );
}

function sfcfc_render_toggle( $field_name, $current_value, $radio_group = '' ) {
	$is_on = 'on' === $current_value;
	printf(
		'<button type="button" id="%1$s" class="sfcfc-toggle%2$s" data-toggle-group="%3$s" data-toggle-radio="%6$s" role="switch" aria-checked="%4$s"></button>' .
		'<input type="hidden" name="%3$s" value="%5$s">',
		esc_attr( sfcfc_toggle_id( $field_name ) ),
		$is_on ? ' is-on' : '',
		esc_attr( 'sfcfc_options[' . $field_name . ']' ),
		$is_on ? 'true' : 'false',
		$is_on ? 'on' : '',
		esc_attr( $radio_group )
	);
}

function sfcfc_render_toggle_row( $field_name, $current_value, $label, $radio_group = '' ) {
	?>
	<div class="sfcfc-toggle-row">
		<?php sfcfc_render_toggle( $field_name, $current_value, $radio_group ); ?>
		<label class="sfcfc-toggle-row__label" for="<?php echo esc_attr( sfcfc_toggle_id( $field_name ) ); ?>"><?php echo esc_html( $label ); ?></label>
	</div>
	<?php
}

function sfcfc_get_purge_role_order() {
	$known    = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );
	$existing = array_keys( wp_roles()->roles );
	$ordered  = array_intersect( $known, $existing );

	return array_values( array_merge( $ordered, array_diff( $existing, $ordered ) ) );
}

function sfcfc_credential_option_keys() {
	return array( 'cf_auth_method', 'cf_email', 'cf_api_key', 'cf_api_token' );
}
