<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// DISPLAY FORM FIELDS

/**
 * Display the form field 'calendar'
 * @since 1.5.0
 * @version 1.7.0
 * @param array $field
 * @param string $instance_id
 * @param string $context
 */
function bookacti_display_form_field_calendar( $field, $instance_id, $context ) {
	// Do not keep ID and class (already used for the container)
	$field[ 'id' ] = $instance_id; 
	$field[ 'class' ] = '';
	
	// Check if an event / group of events is picked by default
	if( isset( $_REQUEST[ 'bookacti_group_id' ] )	&& $_REQUEST[ 'bookacti_group_id' ] !== 'single' )	{ $field[ 'picked_events' ][ 'group_id' ] = intval( $_REQUEST[ 'bookacti_group_id' ] ); }
	if( isset( $_REQUEST[ 'event_group_id' ] )		&& $_REQUEST[ 'event_group_id' ] !== 'single' )		{ $field[ 'picked_events' ][ 'group_id' ] = intval( $_REQUEST[ 'event_group_id' ] ); }
	if( isset( $_REQUEST[ 'bookacti_event_id' ] ) )		{ $field[ 'picked_events' ][ 'event_id' ]		= intval( $_REQUEST[ 'bookacti_event_id' ] ); }
	if( isset( $_REQUEST[ 'event_id' ] ) )				{ $field[ 'picked_events' ][ 'event_id' ]		= intval( $_REQUEST[ 'event_id' ] ); }
	if( isset( $_REQUEST[ 'bookacti_event_start' ] ) )	{ $field[ 'picked_events' ][ 'event_start' ]	= bookacti_sanitize_datetime( $_REQUEST[ 'bookacti_event_start' ] ); }
	if( isset( $_REQUEST[ 'event_start' ] ) )			{ $field[ 'picked_events' ][ 'event_start' ]	= bookacti_sanitize_datetime( $_REQUEST[ 'event_start' ] ); }
	if( isset( $_REQUEST[ 'bookacti_event_end' ] ) )	{ $field[ 'picked_events' ][ 'event_end' ]		= bookacti_sanitize_datetime( $_REQUEST[ 'bookacti_event_end' ] ); }
	if( isset( $_REQUEST[ 'event_end' ] ) )				{ $field[ 'picked_events' ][ 'event_end' ]		= bookacti_sanitize_datetime( $_REQUEST[ 'event_end' ] ); }
	
	// Do not auto load on form editor
	// So that if a JS error occurs, you can still change the calendar settings and try to fix it
	if( $context === 'edit' ) { $field[ 'auto_load' ] = 0; }
	
	$field = apply_filters( 'bookacti_form_field_calendar_attributes', $field, $instance_id, $context );
	
	// Display the booking system
	bookacti_get_booking_system( $field, true );
}
add_action( 'bookacti_display_form_field_calendar', 'bookacti_display_form_field_calendar', 10, 3 );


/**
 * Display the form field 'login'
 * @since 1.5.0
 * @version 1.7.0
 * @param string $html
 * @param array $field
 * @param string $instance_id
 * @param string $context
 * @return string
 */
function bookacti_display_form_field_login( $html, $field, $instance_id, $context ) {
	
	$field_id		= ! empty( $field[ 'id' ] ) ? esc_attr( $field[ 'id' ] ) : esc_attr( 'bookacti-form-field-' . $field[ 'type' ] . '-' . $field[ 'field_id' ] . '-' . $instance_id );
	$field_class	= 'bookacti-form-field-container';
	if( ! empty( $field[ 'name' ] ) )		{ $field_class .= ' bookacti-form-field-name-' . sanitize_title_with_dashes( esc_attr( $field[ 'name' ] ) ); } 
	if( ! empty( $field[ 'type' ] ) )		{ $field_class .= ' bookacti-form-field-type-' . sanitize_title_with_dashes( esc_attr( $field[ 'type' ] ) ); } 
	if( ! empty( $field[ 'field_id' ] ) )	{ $field_class .= ' bookacti-form-field-id-' . esc_attr( $field[ 'field_id' ] ); }
	if( ! empty( $field[ 'class' ] ) )		{ $field_class .= ' ' . esc_attr( $field[ 'class' ] ); }
	ob_start();
	?>
	<div class='<?php echo $field_class; ?> bookacti-user-is-not-logged-in' id='<?php echo $field_id; ?>' >
	<?php
		// Display login types
		$login_types = bookacti_get_login_type_field_default_options();
		if( $login_types ) {
			foreach( $login_types as $login_type_name => $login_type ) {
				if( empty( $field[ 'displayed_fields' ][ $login_type_name ] ) ) { unset( $login_types[ $login_type_name ] ); }
			}
		}
		if( $login_types ) {
			$login_types_class = count( $login_types ) === 1 ? 'bookacti-login-types-hidden' : '';
		?>
			<div class='bookacti-form-field-login-field-container bookacti-login-field-login-type bookacti-custom-radio-button-container <?php echo $login_types_class; ?>' >
		<?php
			// Set the default login type on the first available by default
			reset( $login_types );
			$first_login_type = key( $login_types );
			$default_login_type = apply_filters( 'bookacti_default_login_type', ! empty( $_REQUEST[ 'login_type' ] ) ? sanitize_title_with_dashes( $_REQUEST[ 'login_type' ] ) : $first_login_type, $field, $instance_id, $context );
			if( ! in_array( $default_login_type, array_keys( $login_types ), true ) ) { $default_login_type = $first_login_type; }

			foreach( $login_types as $login_type_name => $login_type ) {
				if( empty( $field[ 'displayed_fields' ][ $login_type_name ] ) ) { continue; }
				?>
				<div class='bookacti-login-type-container bookacti-custom-radio-button bookacti-login-type-<?php echo $login_type_name; ?>' data-separator='<?php echo esc_html_x( 'or', 'separator between different options', BOOKACTI_PLUGIN_NAME ); ?>'>
					<input type='radio' name='login_type' value='<?php echo $login_type_name; ?>' id='bookacti-<?php echo $instance_id; ?>-login-type-<?php echo $login_type_name; ?>' required <?php checked( $default_login_type, $login_type_name, true ); ?>/>
					<label for='bookacti-<?php echo $instance_id; ?>-login-type-<?php echo $login_type_name; ?>' 
						<?php
							if( ! empty( $field[ 'tip' ][ $login_type_name ] ) ) {
								echo 'class="bookacti-tip" data-tip="' . esc_attr( apply_filters( 'bookacti_translate_text', $field[ 'tip' ][ $login_type_name ] ) ) . '"';
							}
						?>   
					>
					<?php 
						echo apply_filters( 'bookacti_translate_text', $field[ 'label' ][ $login_type_name ] );
					?>
					</label>
				</div>
				<?php
			} ?>
			</div>
		<?php
		}
	?>
		<div class='bookacti-user-data-fields'>
			<div class='bookacti-log-in-fields'>
				<div class='bookacti-form-field-login-field-container bookacti-login-field-email' id='<?php echo $field_id; ?>-email-container'>
					<div class='bookacti-form-field-label' >
						<label for='<?php echo $field_id . '-email'; ?>' >
						<?php 
							echo esc_html( apply_filters( 'bookacti_translate_text', $field[ 'label' ][ 'email' ] ) ); 
							if( $field[ 'required_fields' ][ 'email' ] ) {
								echo '<span class="bookacti-required-field-indicator" title="' . esc_attr__( 'Required field', BOOKACTI_PLUGIN_NAME ) . '"></span>';
							}
						?>
						</label>
					<?php if( ! empty( $field[ 'tip' ][ 'email' ] ) ) { bookacti_help_tip( esc_html( apply_filters( 'bookacti_translate_text', $field[ 'tip' ][ 'email' ] ) ) ); } ?>
					</div>
					<div class='bookacti-form-field-content' >
					<?php 
						$args = array(
							'type'			=> 'email',
							'name'			=> 'email',
							'value'			=> ! empty( $_REQUEST[ 'email' ] ) ? esc_attr( $_REQUEST[ 'email' ] ) : '',
							'id'			=> $field_id . '-email',
							'class'			=> 'bookacti-form-field bookacti-email',
							'placeholder'	=> esc_attr( apply_filters( 'bookacti_translate_text', $field[ 'placeholder' ][ 'email' ] ) ),
							'required'		=> $field[ 'required_fields' ][ 'email' ] ? 1 : 0
						);
						bookacti_display_field( $args );
					?>
					</div>
				</div>
				<div class='bookacti-form-field-login-field-container bookacti-login-field-password <?php if( ! empty( $field[ 'generate_password' ] ) ) { echo 'bookacti-generated-password '; } if( ! $field[ 'required_fields' ][ 'password' ] ) { echo 'bookacti-password-not-required'; } ?>' id='<?php echo $field_id; ?>-password-container' >
					<div class='bookacti-form-field-label' >
						<label for='<?php echo $field_id . '-password'; ?>' >
						<?php 
							echo esc_html( apply_filters( 'bookacti_translate_text', $field[ 'label' ][ 'password' ] ) ); 
							if( $field[ 'required_fields' ][ 'password' ] || empty( $field[ 'generate_password' ] ) ) {
								echo '<span class="bookacti-required-field-indicator" title="' . __( 'Required field', BOOKACTI_PLUGIN_NAME ) . '"></span>';
							}
						?>
						</label>
					<?php if( ! empty( $field[ 'tip' ][ 'password' ] ) ) { bookacti_help_tip( esc_html( apply_filters( 'bookacti_translate_text', $field[ 'tip' ][ 'password' ] ) ) ); } ?>
					</div>
					<div class='bookacti-form-field-content' >
					<?php 
						$args = array(
							'type'			=> 'password',
							'name'			=> 'password',
							'value'			=> ! empty( $_REQUEST[ 'password' ] ) ? esc_attr( $_REQUEST[ 'password' ] ) : '',
							'id'			=> $field_id . '-password',
							'class'			=> 'bookacti-form-field bookacti-password',
							'placeholder'	=> esc_attr( apply_filters( 'bookacti_translate_text', $field[ 'placeholder' ][ 'password' ] ) ),
							'required'		=> $field[ 'required_fields' ][ 'password' ] ? 1 : 0
						);
						bookacti_display_field( $args );
						?>
						<div class='bookacti-password-strength' style='display:none;'>
							<span class='bookacti-password-strength-meter'></span>
							<input type='hidden' name='password_strength' class='bookacti-password_strength' value='0' min='<?php echo $field[ 'min_password_strength' ]; ?>' />
						</div>
						<?php
						if( ! empty( $field[ 'displayed_fields' ][ 'forgotten_password' ] ) ) { 
						?>
							<div class='bookacti-forgotten-password' >
								<a href='#' class='bookacti-forgotten-password-link' data-field-id='<?php echo $field_id; ?>' ><?php echo esc_html( apply_filters( 'bookacti_translate_text', $field[ 'label' ][ 'forgotten_password' ] ) ) ?></a>
							<?php
								if( ! empty( $field[ 'tip' ][ 'forgotten_password' ] ) ) {
									bookacti_help_tip( esc_html( apply_filters( 'bookacti_translate_text', $field[ 'tip' ][ 'forgotten_password' ] ) ) );
								}
							?>
							</div>
							<div data-field-id='<?php echo $field_id; ?>' class='bookacti-forgotten-password-dialog bookacti-form-dialog' title='<?php esc_html_e( 'Forgotten password', BOOKACTI_PLUGIN_NAME ); ?>' style='display:none;' >
								<div class='bookacti-forgotten-password-dialog-description' >
									<p>
									<?php
										echo apply_filters( 'bookacti_forgotten_password_description', esc_html__( 'Please enter your email address. You will receive a link to create a new password via email.', BOOKACTI_PLUGIN_NAME ), $field, $instance_id, $context );
									?>
									</p>
								</div>
								<div class='bookacti-forgotten-password-dialog-fields' >
									<input type='hidden' class='bookacti-nonce-forgotten-password' name='nonce_forgotten_password' value='<?php echo wp_create_nonce( 'bookacti_forgotten_password' ); ?>' />
									<?php
										$forgotten_pw_fields = apply_filters( 'bookacti_forgotten_password_fields', array(
											'forgotten_password_email' => array(
												'type'			=> 'email',
												'name'			=> 'forgotten_password_email',
												'id'			=> 'bookacti-forgotten-password-email-' . $field_id,
												'class'			=> 'bookacti-forgotten-password-email',
												'placeholder'	=> esc_html__( 'Your email address', BOOKACTI_PLUGIN_NAME ),
											)
										), $field, $instance_id, $context );

										bookacti_display_fields( $forgotten_pw_fields );
									?>
								</div>
							</div>
						<?php 
						}
						?>
					</div>
				</div>
			</div>
			<?php 
			if( ! empty( $field[ 'displayed_fields' ][ 'new_account' ] ) || ! empty( $field[ 'displayed_fields' ][ 'no_account' ] ) ) { 
				// Display registration fields if any
				$register_fields = bookacti_get_register_fields_default_data();
				if( in_array( 1, array_values( array_intersect_key( $field[ 'displayed_fields' ], $register_fields ) ) ) ) { ?>
					<div class='bookacti-register-fields' id='<?php echo $field_id; ?>-register-fields' style='<?php if( $context !== 'edit' ) { echo 'display:none;'; } ?>' >
					<?php foreach( $register_fields as $register_field_name => $register_field ) {
						if( ! empty( $field[ 'displayed_fields' ][ $register_field_name ] ) ) { 
						?>
						<div class='bookacti-form-field-login-field-container bookacti-login-field-<?php echo $register_field_name; ?>' id='<?php echo esc_attr( $field_id . '-' . $register_field_name ); ?>-container' >
							<?php if( $register_field[ 'type' ] !== 'checkbox' ) { ?>
								<div class='bookacti-form-field-label' >
									<label for='<?php echo esc_attr( $field_id . '-' . $register_field_name ); ?>' >
									<?php 
										echo esc_html( apply_filters( 'bookacti_translate_text', $field[ 'label' ][ $register_field_name ] ) ); 
										if( $field[ 'required_fields' ][ $register_field_name ] ) {
											echo '<span class="bookacti-required-field-indicator" title="' . esc_attr__( 'Required field', BOOKACTI_PLUGIN_NAME ) . '"></span>';
										}
									?>
									</label>
								<?php if( ! empty( $field[ 'tip' ][ $register_field_name ] ) ) { bookacti_help_tip( esc_html( apply_filters( 'bookacti_translate_text', $field[ 'tip' ][ $register_field_name ] ) ) ); } ?>
								</div>
							<?php } ?>
							<div class='bookacti-form-field-content' >
							<?php 
								$args = array(
									'type'			=> $register_field[ 'type' ],
									'name'			=> esc_attr( $register_field_name ),
									'value'			=> ! empty( $_REQUEST[ $register_field_name ] ) ? esc_attr( $_REQUEST[ $register_field_name ] ) : ( isset( $register_field[ 'value' ] ) ? esc_attr( $register_field[ 'value' ] ) : '' ),
									'id'			=> esc_attr( $field_id . '-' . $register_field_name ),
									'class'			=> esc_attr( 'bookacti-form-field bookacti-' . $register_field_name ),
									'required'		=> esc_attr( $field[ 'required_fields' ][ $register_field_name ] ),
									'placeholder'	=> esc_attr( apply_filters( 'bookacti_translate_text', $field[ 'placeholder' ][ $register_field_name ] ) ),
									'required'		=> $field[ 'required_fields' ][ $register_field_name ] ? 1 : 0
								);
								if( $register_field[ 'type' ] === 'checkbox' ) { 
									$args[ 'label' ]= esc_html( apply_filters( 'bookacti_translate_text', $field[ 'label' ][ $register_field_name ] ) ); 
									$args[ 'tip' ]	= esc_html( apply_filters( 'bookacti_translate_text', $field[ 'tip' ][ $register_field_name ] ) ); 
								}
								bookacti_display_field( $args );
							?>
							</div>
						</div>
					<?php 
						}
					}
					?>
					</div>
				<?php 
				}
			}
		?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_filter( 'bookacti_html_form_field_login', 'bookacti_display_form_field_login', 20, 4 );


/**
 * Display the form field "Login / Registration" when the user is already logged in
 * @since 1.5.0
 * @version 1.5.4
 * @param string $html
 * @param array $field
 * @param string $instance_id
 * @param string $context
 * @return string
 */
function bookacti_display_form_field_login_when_logged_in( $html, $field, $instance_id, $context ) {
	// Display this only if user is already logged in
	if( $context === 'edit' || ! is_user_logged_in() ) { return $html; }
	
	// Do not display the Login / Registration fields
	remove_filter( 'bookacti_html_form_field_login', 'bookacti_display_form_field_login', 20 );
	
	$user			= get_user_by( 'id', get_current_user_id() );
	$field_id		= ! empty( $field[ 'id' ] ) ? esc_attr( $field[ 'id' ] ) : esc_attr( 'bookacti-form-field-' . $field[ 'type' ] . '-' . $field[ 'field_id' ] . '-' . $instance_id );
	$field_class	= 'bookacti-form-field-container';
	if( ! empty( $field[ 'name' ] ) )		{ $field_class .= ' bookacti-form-field-name-' . sanitize_title_with_dashes( esc_attr( $field[ 'name' ] ) ); } 
	if( ! empty( $field[ 'type' ] ) )		{ $field_class .= ' bookacti-form-field-type-' . sanitize_title_with_dashes( esc_attr( $field[ 'type' ] ) ); } 
	if( ! empty( $field[ 'field_id' ] ) )	{ $field_class .= ' bookacti-form-field-id-' . esc_attr( $field[ 'field_id' ] ); }
	if( ! empty( $field[ 'class' ] ) )		{ $field_class .= ' ' . esc_attr( $field[ 'class' ] ); }
	ob_start();
	?>
	<div class='<?php echo $field_class; ?> bookacti-user-is-logged-in' id='<?php echo $field_id; ?>' >
		<div class='bookacti-form-field-login-field-container bookacti-login-field-log-out' id='<?php echo $field_id; ?>-email-container' >
				<div class='bookacti-logout-form-field-container'>
					<span>
						<?php echo sprintf( esc_html__( 'You are not %s?', BOOKACTI_PLUGIN_NAME ), $user->display_name . ' (' . $user->user_email . ')' ); ?>
					</span>
					<a href='<?php echo wp_logout_url( get_permalink() ); ?>' class='bookacti-logout-link'>
						<?php esc_html_e( 'Click here to log out.', BOOKACTI_PLUGIN_NAME ); ?>
					</a>
				</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_filter( 'bookacti_html_form_field_login', 'bookacti_display_form_field_login_when_logged_in', 10, 4 );


/**
 * Display the form field 'quantity'
 * @since 1.5.0
 * @version 1.7.0
 * @param array $field
 * @param string $instance_id
 * @param string $context
 */
function bookacti_display_form_field_quantity( $field, $instance_id, $context ) {
	$args = array(
		'type'			=> 'number',
		'name'			=> 'quantity',
		'class'			=> 'bookacti-form-field bookacti-quantity',
		'placeholder'	=> ! empty( $field[ 'placeholder' ] ) ? esc_attr( apply_filters( 'bookacti_translate_text', $field[ 'placeholder' ] ) ) : '',
		'options'		=> array( 'min' => 1 ),
		'value'			=> ! empty( $_REQUEST[ 'quantity' ] ) && is_numeric( $_REQUEST[ 'quantity' ] ) ? intval( $_REQUEST[ 'quantity' ] ) : 1
	);
	bookacti_display_field( $args );
}
add_action( 'bookacti_display_form_field_quantity', 'bookacti_display_form_field_quantity', 10, 3 );


/**
 * Display the form field 'checkbox'
 * @since 1.5.2
 * @version 1.7.0
 * @param string $html
 * @param array $field
 * @param string $instance_id
 * @param string $context
 * @return string
 */
function bookacti_display_form_field_checkbox( $html, $field, $instance_id, $context ) {
	
	$field_id		= ! empty( $field[ 'id' ] ) ? esc_attr( $field[ 'id' ] ) : esc_attr( 'bookacti-form-field-' . $field[ 'type' ] . '-' . $field[ 'field_id' ] . '-' . $instance_id );
	$field_class	= 'bookacti-form-field-container';
	if( ! empty( $field[ 'name' ] ) )		{ $field_class .= ' bookacti-form-field-name-' . sanitize_title_with_dashes( esc_attr( $field[ 'name' ] ) ); } 
	if( ! empty( $field[ 'type' ] ) )		{ $field_class .= ' bookacti-form-field-type-' . sanitize_title_with_dashes( esc_attr( $field[ 'type' ] ) ); } 
	if( ! empty( $field[ 'field_id' ] ) )	{ $field_class .= ' bookacti-form-field-id-' . esc_attr( $field[ 'field_id' ] ); }
	if( ! empty( $field[ 'class' ] ) )		{ $field_class .= ' ' . esc_attr( $field[ 'class' ] ); }
	$is_checked		= ! empty( $_REQUEST[ $field[ 'name' ] ] ) ? 1 : $field[ 'value' ];
	
	ob_start();
	?>
	<div class='<?php echo $field_class; ?>' id='<?php echo $field_id; ?>' >
		<div class='bookacti-form-field-checkbox-field-container' >
			<div class='bookacti-form-field-checkbox-input' >
				<input type='hidden' name='<?php echo esc_attr( $field[ 'name' ] ); ?>' value='0'/>
				<input type='checkbox' 
					   name='<?php echo esc_attr( $field[ 'name' ] ); ?>'
					   id='<?php echo $field_id . '-input'; ?>'
					   class='bookacti-form-field'
					   value='1'
					   <?php if( $field[ 'required' ] ) { echo 'required'; } ?>
					   <?php if( $is_checked ) { echo 'checked'; } ?> />
			</div>
			<div class='bookacti-form-field-checkbox-label' >
				<label for='<?php echo $field_id . '-input'; ?>' >
				<?php 
					echo apply_filters( 'bookacti_translate_text', $field[ 'label' ] ); 
					if( $field[ 'required' ] ) {
						echo '<span class="bookacti-required-field-indicator" title="' . esc_attr__( 'Required field', BOOKACTI_PLUGIN_NAME ) . '"></span>';
					}
				?>
				</label>
			<?php if( ! empty( $field[ 'tip' ] ) ) { bookacti_help_tip( esc_html( apply_filters( 'bookacti_translate_text', $field[ 'tip' ] ) ) ); } ?>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_filter( 'bookacti_html_form_field_checkbox', 'bookacti_display_form_field_checkbox', 10, 4 );


/**
 * Display the form field 'submit'
 * @since 1.5.0
 * @version 1.5.4
 * @param string $html
 * @param array $field
 * @param string $instance_id
 * @param string $context
 * @return string
 */
function bookacti_display_form_field_submit( $html, $field, $instance_id, $context ) {
	$field_id		= ! empty( $field[ 'id' ] ) ? esc_attr( $field[ 'id' ] ) : esc_attr( 'bookacti-form-field-' . $field[ 'type' ] . '-' . $field[ 'field_id' ] . '-' . $instance_id );
	$field_class	= 'bookacti-form-field-container';
	if( ! empty( $field[ 'name' ] ) )		{ $field_class .= ' bookacti-form-field-name-' . sanitize_title_with_dashes( esc_attr( $field[ 'name' ] ) ); } 
	if( ! empty( $field[ 'type' ] ) )		{ $field_class .= ' bookacti-form-field-type-' . sanitize_title_with_dashes( esc_attr( $field[ 'type' ] ) ); } 
	if( ! empty( $field[ 'field_id' ] ) )	{ $field_class .= ' bookacti-form-field-id-' . esc_attr( $field[ 'field_id' ] ); }
	if( ! empty( $field[ 'class' ] ) )		{ $field_class .= ' ' . esc_attr( $field[ 'class' ] ); }
	ob_start();
	?>
	<div class='<?php echo $field_class; ?>' id='<?php echo $field_id; ?>' >
		<input	type='<?php echo $context === 'edit' ? 'button' : 'submit'; ?>'
				class='bookacti-submit-form button' 
				value='<?php echo esc_attr( apply_filters( 'bookacti_translate_text', $field[ 'value' ] ) ); ?>' />
	</div>
	<?php
	return ob_get_clean();
}
add_filter( 'bookacti_html_form_field_submit', 'bookacti_display_form_field_submit', 10, 4 );


/**
 * Display the form field 'free_text'
 * @since 1.5.0
 * @version 1.5.2
 * @param array $field
 * @param string $instance_id
 * @param string $context
 */
function bookacti_display_form_field_free_text( $field, $instance_id, $context ) {
	$field_id = ! empty( $field[ 'id' ] ) ? esc_attr( $field[ 'id' ] ) : esc_attr( 'bookacti-form-field-' . $field[ 'type' ] . '-' . $field[ 'field_id' ] . '-' . $instance_id );
	?>
	<div id='<?php echo $field_id; ?>' class='bookacti-form-free-text <?php echo esc_attr( $field[ 'class' ] ); ?>' >
	<?php 
		$html = apply_filters( 'bookacti_translate_text', $field[ 'value' ] );
		echo $context === 'edit' ? $html : do_shortcode( $html ); 
	?>
	</div>
	<?php
}
add_action( 'bookacti_display_form_field_free_text', 'bookacti_display_form_field_free_text', 10, 3 );


/**
 * Add a compulsory quantity input for correct booking form functionning
 * @since 1.5.0
 * @version 1.7.0
 * @param array $fields
 * @param array $form
 * @param string $instance_id
 * @param string $context
 * @return array
 */
function bookacti_display_compulsory_quantity_form_field( $fields, $form, $instance_id, $context ) {
	if( $context !== 'display' ) { return $fields; }
	
	// If there is no "quantity" input, add a default hidden quantity input
	$fields_types = array();
	foreach( $fields as $field ) { if( ! empty( $field[ 'type' ] ) ) { $fields_types[] = $field[ 'type' ]; } }
	if( in_array( 'submit', $fields_types, true ) && ! in_array( 'quantity', $fields_types, true ) ) {
		$field = bookacti_get_default_form_fields_data( 'quantity' );
		$field[ 'id' ]		= 'bookacti-compulsory-quantity-field';
		$field[ 'class' ]	.= ' bookacti-hidden-field';
		$field[ 'value' ]	= ! empty( $_REQUEST[ 'quantity' ] ) && is_numeric( $_REQUEST[ 'quantity' ] ) ? intval( $_REQUEST[ 'quantity' ] ) : 1;
		$fields[] = $field;
	}
	
	return $fields;
}
add_filter( 'bookacti_displayed_form_fields', 'bookacti_display_compulsory_quantity_form_field', 100, 4 );




// FORM

/**
 * AJAX Controller - Get a booking form
 * @since 1.5.0
 * @version 1.5.2
 */
function bookacti_controller_get_form() {
	// Sanitize values
	$form_id		= intval( $_POST[ 'form_id' ] );
	$instance_id	= ! empty( $_POST[ 'instance_id' ] ) ? sanitize_title_with_dashes( $_POST[ 'instance_id' ] ) : '';
	$context		= ! empty( $_POST[ 'context' ] ) ? sanitize_title_with_dashes( $_POST[ 'context' ] ) : 'display';
	
	// Get the form
	$form_html		= bookacti_display_form( $form_id, $instance_id, $context, false );
	
	// Feedback error
	if( ! $form_html ) { wp_send_json( array( 'status' => 'failed', 'error' => 'no_form' ) ); }
	
	wp_send_json( array( 'status' => 'success', 'form_html' => $form_html ) );
}
add_action( 'wp_ajax_bookactiGetForm', 'bookacti_controller_get_form' );
add_action( 'wp_ajax_nopriv_bookactiGetForm', 'bookacti_controller_get_form' );


/**
 * AJAX Controller - Send the forgotten password email
 * @since 1.5.0
 * @version 1.5.8
 */
function bookacti_controller_forgotten_password() {
	// Check nonce
	$is_nonce_valid	= check_ajax_referer( 'bookacti_forgotten_password', 'nonce', false );

	if( ! $is_nonce_valid ) { wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) ); }
	
	$email = sanitize_email( $_POST[ 'email' ] );
	
	if( ! is_email( $email ) ) { wp_send_json( array( 'status' => 'failed', 'error' => 'invalid_email', 'message' => esc_html__( 'Invalid email address.', BOOKACTI_PLUGIN_NAME ) ) ); }
	
	$user = get_user_by( 'email', $email );
	
	if( ! $user ) { wp_send_json( array( 'status' => 'failed', 'error' => 'user_not_found', 'message' => esc_html__( 'This email address doesn\'t match any account.', BOOKACTI_PLUGIN_NAME ) ) ); }
	
	// WordPress 4.4.0 backward compatibility
	if( function_exists( 'wp_send_new_user_notifications' ) ) {
		wp_send_new_user_notifications( $user->ID, apply_filters( 'bookacti_forgotten_password_notify', 'user', $user ) );
	} else {
		wp_new_user_notification( $user->ID, null, apply_filters( 'bookacti_forgotten_password_notify', 'user', $user ) );
	}
	
	$message = sprintf( esc_html__( 'An email has been sent to %s, please check your inbox.', BOOKACTI_PLUGIN_NAME ), $email );
	
	wp_send_json( array( 'status' => 'success', 'message' => $message ) );
}
add_action( 'wp_ajax_bookactiForgottenPassword', 'bookacti_controller_forgotten_password' );
add_action( 'wp_ajax_nopriv_bookactiForgottenPassword', 'bookacti_controller_forgotten_password' );


/**
 * Check if booking form is correct and then book the event, or send the error message
 * @since 1.5.0
 * @version 1.7.0
 */
function bookacti_controller_validate_booking_form() {
	
	// Check nonce
	if( ! check_ajax_referer( 'bookacti_booking_form', 'nonce_booking_form', false ) ) {
		bookacti_send_json_invalid_nonce( 'submit_booking_form' );
	}
	
	$return_array = array(
		'has_logged_in'		=> false,
		'has_registered'	=> false,
		'user_id'			=> '',
		'status'			=> '',
		'error'				=> array(),
		'message'			=> array(),
		'booking_id'		=> 0,
		'booking_group_id'	=> 0,
	);
	
	// Check form id
	$form_id = intval( $_POST[ 'form_id' ] );
	if( ! $form_id ) {
		$return_array[ 'error' ]	= 'invalid_form_id';
		$return_array[ 'message' ]	= esc_html__( 'Invalid form ID.', BOOKACTI_PLUGIN_NAME ) . ' ' . esc_html__( 'Please reload the page and try again.', BOOKACTI_PLUGIN_NAME );
		bookacti_send_json( $return_array, 'submit_booking_form' );
	}
	
	// Retrieve form field data 
	$form_fields_data = bookacti_get_form_fields_data( $form_id );
	
	// Validate the booking form fields
	$form_fields_validated = bookacti_validate_form_fields( $form_id, $form_fields_data );
	if( $form_fields_validated[ 'status' ] !== 'success' ) {
		$form_fields_validated[ 'message' ] = is_array( $form_fields_validated[ 'message' ] ) ? implode( '</li><li>', $form_fields_validated[ 'message' ] ) : $form_fields_validated[ 'message' ];
		bookacti_send_json( $form_fields_validated, 'submit_booking_form' );
	}
	
	// Let third party plugins validate their own part of the form
	$return_array = apply_filters( 'bookacti_validate_booking_form_submission', $return_array, $form_id, $form_fields_data );
	if( $return_array[ 'status' ] === 'failed' || $return_array[ 'error' ] ) {
		$return_array[ 'message' ] = implode( '</li><li>', $return_array[ 'message' ] );
		bookacti_send_json( $return_array, 'submit_booking_form' );
	}
	
	$require_authentication = true;
	
	if( is_user_logged_in() ) {
		$user_id = get_current_user_id();
	} else {
		// Retrieve login data
		$login_values	= bookacti_sanitize_form_field_values( $_POST, 'login' );
		$login_data		= array();
		foreach( $form_fields_data as $form_field_data ) {
			if( $form_field_data[ 'type' ] === 'login' ) { 
				$login_data = $form_field_data;
				break;
			}
		}
		if( empty( $login_data[ 'required_fields' ][ 'password' ] ) ) { $require_authentication = false; }
		
		// Check login data and input values
		if( ! $login_data || empty( $login_values[ 'login_type' ] ) ) {
			$return_array[ 'error' ]	= 'not_logged_in';
			$return_array[ 'message' ]	= esc_html__( 'You are not logged in. Please create an account and log in first.', BOOKACTI_PLUGIN_NAME );
			bookacti_send_json( $return_array, 'submit_booking_form' );
		}
		
		// Check email address
		if( ( $login_data[ 'required_fields' ][ 'email' ] || $login_values[ 'login_type' ] === 'no_account' ) 
		&&  ( empty( $login_values[ 'email' ] ) || strlen( $login_values[ 'email' ] ) > 64 ) ) {
			$return_array[ 'error' ]	= 'invalid_email';
			$return_array[ 'message' ]	= esc_html__( 'Your email address is not valid.', BOOKACTI_PLUGIN_NAME );
			bookacti_send_json( $return_array, 'submit_booking_form' );
		}
		
		// Register
		if( $login_values[ 'login_type' ] === 'new_account' ) {
			
			// Register the new user
			$user = bookacti_register_a_new_user( $login_values, $login_data );
			if( ! is_a( $user, 'WP_User' ) ) {
				bookacti_send_json( $user, 'submit_booking_form' );
			}
			
			$return_array[ 'has_registered' ]	= true;
			$return_array[ 'message' ][]		= esc_html__( 'Your account has been successfully created.', BOOKACTI_PLUGIN_NAME );
			
			
		// Login
		} else if( $login_values[ 'login_type' ] === 'my_account' ) {
			
			// Validate login fields
			$user = bookacti_validate_login( $login_values, $require_authentication );
			if( ! is_a( $user, 'WP_User' ) ) {
				bookacti_send_json( $user, 'submit_booking_form' );
			}
			
			
		// Book without account
		} else if( $login_values[ 'login_type' ] === 'no_account' ) {
			
			$user = new stdClass();
			$user->ID = ! empty( $login_values[ 'email' ] ) ? $login_values[ 'email' ] : esc_attr( apply_filters( 'bookacti_unknown_user_id', 'unknown_user' ) );
			
			// Check that required register fields are filled
			$register_fields_errors = array();
			foreach( $login_data[ 'required_fields' ] as $field_name => $is_required ) {
				if( $is_required && empty( $login_values[ $field_name ] ) ) {
					if( $field_name === 'password' ) { continue; }
					$field_label = ! empty( $login_data[ 'label' ][ $field_name ] ) ? $login_data[ 'label' ][ $field_name ] : $field_name;
					/* translators: %s is the field name. */
					$register_fields_errors[] = sprintf( esc_html__( 'The field "%s" is required.', BOOKACTI_PLUGIN_NAME ), $field_label );
				}
			}
			
			if( ! empty( $register_fields_errors ) ) {
				$return_array[ 'error' ] = 'invalid_register_field';
				$return_array[ 'message' ] = implode( '</li><li>', $register_fields_errors );
				bookacti_send_json( $return_array, 'submit_booking_form' );
			}
		}
		
		$user		= apply_filters( 'bookacti_user_according_to_login_type', $user, $login_values, $login_data, $return_array );
		$user_id	= isset( $user->ID ) ? $user->ID : '';
		$return_array[ 'user_id' ] = $user_id;
		
		// Log the user in programmatically
		if( $login_data[ 'automatic_login' ] && is_a( $user, 'WP_User' ) ) {
			do_action( 'bookacti_before_log_in_user', $user );

			$is_logged_in = bookacti_log_user_in( $user->user_login );
			if( ! $is_logged_in ) { 
				$return_array[ 'error' ]	= 'cannot_log_in';
				$return_array[ 'message' ]	= esc_html__( 'An error occured while trying to log you in.', BOOKACTI_PLUGIN_NAME );
				bookacti_send_json( $return_array, 'submit_booking_form' );
			}

			$return_array[ 'has_logged_in' ]= true;
			$return_array[ 'message' ][]	= esc_html__( 'You are now logged into your account.', BOOKACTI_PLUGIN_NAME );
		}
	}
	
	// Gether the form variables
	$booking_form_values = apply_filters( 'bookacti_booking_form_values', array(
		'user_id'			=> $user_id,
		'group_id'			=> is_numeric( $_POST[ 'bookacti_group_id' ] ) ? intval( $_POST[ 'bookacti_group_id' ] ) : 'single',
		'event_id'			=> intval( $_POST[ 'bookacti_event_id' ] ),
		'event_start'		=> bookacti_sanitize_datetime( $_POST[ 'bookacti_event_start' ] ),
		'event_end'			=> bookacti_sanitize_datetime( $_POST[ 'bookacti_event_end' ] ),
		'quantity'			=> intval( $_POST[ 'quantity' ] ),
		'default_state'		=> bookacti_get_setting_value( 'bookacti_general_settings', 'default_booking_state' ), 
		'payment_status'	=> bookacti_get_setting_value( 'bookacti_general_settings', 'default_payment_status' ),
		'form_id'			=> $form_id
	), $form_id, $return_array );

	// Check if the booking is correct
	$response = bookacti_validate_booking_form( $booking_form_values[ 'group_id' ], $booking_form_values[ 'event_id' ], $booking_form_values[ 'event_start' ], $booking_form_values[ 'event_end' ], $booking_form_values[ 'quantity' ], $booking_form_values[ 'form_id' ] );
	if( $response[ 'status' ] !== 'success' ) {
		$return_array[ 'status' ]	= $response[ 'status' ];
		$return_array[ 'error' ]	= $response[ 'error' ];
		$return_array[ 'message' ]	= $response[ 'message' ];
		bookacti_send_json( $return_array, 'submit_booking_form' );
	}
	
	// Let third party plugins change form values before booking
	$booking_form_values = apply_filters( 'bookacti_booking_form_values_before_booking', $booking_form_values, $form_id, $return_array );
	
	// Let third party plugins do their stuff before booking
	do_action( 'bookacti_booking_form_before_booking', $form_id, $booking_form_values, $return_array );
	
	// Single Booking
	if( $booking_form_values[ 'group_id' ] === 'single' ) {
		$booking_id = bookacti_insert_booking(	$booking_form_values[ 'user_id' ], 
												$booking_form_values[ 'event_id' ], 
												$booking_form_values[ 'event_start' ],
												$booking_form_values[ 'event_end' ], 
												$booking_form_values[ 'quantity' ], 
												$booking_form_values[ 'default_state' ],
												$booking_form_values[ 'payment_status' ],
												null,
												null,
												$booking_form_values[ 'form_id' ] );
		if( ! empty( $booking_id ) ) {
			do_action( 'bookacti_booking_form_validated', $booking_id, $booking_form_values, 'single', $form_id );

			$return_array[ 'status' ]		= 'success';
			$return_array[ 'message' ][]	= bookacti_get_message( 'booking_success' );
			$return_array[ 'booking_id' ]	= $booking_id;
			
			$return_array = apply_filters( 'bookacti_booking_form_validated_response', $return_array, $booking_id, $booking_form_values, 'single', $form_id );
			
			$return_array[ 'message' ]		= implode( '</li><li>', $return_array[ 'message' ] );
				
			bookacti_send_json( $return_array, 'submit_booking_form' );
		}

	// Booking group
	} else if( $booking_form_values[ 'group_id' ] ) {
		// Book all events of the group
		$booking_group_id = bookacti_book_group_of_events(	$booking_form_values[ 'user_id' ], 
															$booking_form_values[ 'group_id' ], 
															$booking_form_values[ 'quantity' ], 
															$booking_form_values[ 'default_state' ], 
															$booking_form_values[ 'payment_status' ], 
															null,
															$booking_form_values[ 'form_id' ] );
		if( ! empty( $booking_group_id ) ) {
			do_action( 'bookacti_booking_form_validated', $booking_group_id, $booking_form_values, 'group', $form_id );
			
			$return_array[ 'status' ]			= 'success';
			$return_array[ 'message' ][]		= bookacti_get_message( 'booking_success' );
			$return_array[ 'booking_group_id' ]	= $booking_group_id;
			
			$return_array = apply_filters( 'bookacti_booking_form_validated_response', $return_array, $booking_group_id, $booking_form_values, 'group', $form_id );
			
			$return_array[ 'message' ]			= implode( '</li><li>', $return_array[ 'message' ] );
			
			bookacti_send_json( $return_array, 'submit_booking_form' );
		}
	}
	
	// Unknown error
	$return_array[ 'error' ]		= 'unknown';
	$return_array[ 'message' ][]	= esc_html__( 'An error occurred, please try again.', BOOKACTI_PLUGIN_NAME );
	$return_array[ 'message' ]		= implode( '</li><li>', $return_array[ 'message' ] );
	bookacti_send_json( $return_array, 'submit_booking_form' );
}
add_action( 'wp_ajax_bookactiSubmitBookingForm', 'bookacti_controller_validate_booking_form' );
add_action( 'wp_ajax_nopriv_bookactiSubmitBookingForm', 'bookacti_controller_validate_booking_form' );


/**
 * Save the user data when the customer do not want to create an account, and attach them to the booking
 * @since 1.6.0
 * @version 1.7.0
 * @param int $booking_id
 * @param array $booking_form_values
 * @param string $booking_type
 * @param int $form_id
 */
function bookacti_save_no_account_user_data( $booking_id, $booking_form_values, $booking_type, $form_id ) {
	if( empty( $_POST[ 'login_type' ] ) || $_POST[ 'login_type' ] !== 'no_account' ) { return; }
	
	// Retrieve login data
	$login_values		= bookacti_sanitize_form_field_values( $_POST, 'login' );
	$register_fields	= bookacti_get_register_fields_default_data();
	
	// Separate user data
	$user_data = apply_filters( 'bookacti_no_account_user_data', array_merge( array( 'email' => $login_values[ 'email' ] ), array_intersect_key( $login_values, $register_fields ) ), $login_values, $booking_id, $booking_type, $booking_form_values, $form_id );
	if( ! $user_data || ! is_array( $user_data ) ) { return; }
	
	// Do not save empty values
	$user_data = array_filter( $user_data, function( $value ) { return $value !== '' && $value !== array(); } );
	if( ! $user_data ) { return; }
	
	// Prefix array keys with 'user_'
	$user_data = array_combine( array_map( function( $key ) { return 'user_' . $key; }, array_keys( $user_data ) ), $user_data );
	
	$object_type = $booking_type === 'group' ? 'booking_group' : 'booking';
	bookacti_update_metadata( $object_type, $booking_id, $user_data );
}
add_action( 'bookacti_booking_form_validated', 'bookacti_save_no_account_user_data', 10, 4 );




// FORM EDITOR PAGE

/**
 * Add form editor meta boxes
 * @since 1.5.0
 */
function bookacti_form_editor_meta_boxes() {
	if( empty( $_REQUEST[ 'action' ] ) || ! in_array( $_REQUEST[ 'action' ], array( 'edit', 'new' ), true ) ) { return; }
	
	// Sidebar
	add_meta_box( 'bookacti_form_publish', __( 'Publish', BOOKACTI_PLUGIN_NAME ), 'bookacti_display_form_publish_meta_box', 'booking-activities_page_bookacti_forms', 'side', 'high' );
	add_meta_box( 'bookacti_form_managers', __( 'Managers', BOOKACTI_PLUGIN_NAME ), 'bookacti_display_form_managers_meta_box', 'booking-activities_page_bookacti_forms', 'side', 'default' );

	add_meta_box( 'bookacti_form_integration_tuto', __( 'How to integrate this form', BOOKACTI_PLUGIN_NAME ), 'bookacti_display_form_integration_tuto_meta_box', 'booking-activities_page_bookacti_forms', 'side', 'low' );
}
add_action( 'add_meta_boxes_booking-activities_page_bookacti_forms', 'bookacti_form_editor_meta_boxes' );


/*
 * Allow metaboxes on for editor
 * @since 1.5.0
 */
function bookacti_allow_meta_boxes_in_form_editor() {
	
	if( empty( $_REQUEST[ 'action' ] ) || ! in_array( $_REQUEST[ 'action' ], array( 'edit', 'new' ), true ) ) { return; }
	
    /* Trigger the add_meta_boxes hooks to allow meta boxes to be added */
    do_action( 'add_meta_boxes_booking-activities_page_bookacti_forms', null );
    do_action( 'add_meta_boxes', 'booking-activities_page_bookacti_forms', null );
	
	/* Enqueue WordPress' script for handling the meta boxes */
	wp_enqueue_script( 'postbox' );
}
add_action( 'load-booking-activities_page_bookacti_forms', 'bookacti_allow_meta_boxes_in_form_editor' );
 

/**
 * Print metabox script to make it work on non "post" edit pages
 * @since 1.5.7 (was bookacti_print_metabox_script_in_form_editor_footer() since 1.5.0)
 */
function bookacti_print_metabox_script() {
	if( empty( $_REQUEST[ 'action' ] ) || ! in_array( $_REQUEST[ 'action' ], array( 'edit', 'new' ), true ) ) { return; }
	?>
		<script>$j( document ).ready( function(){ postboxes.add_postbox_toggles(pagenow); } );</script>
	<?php
}

/**
 * Print metabox script to make it work on form editor
 * @since 1.5.0
 * @version 1.5.7
 */
add_action( 'admin_footer-booking-activities_page_bookacti_forms', 'bookacti_print_metabox_script' );




// EDITOR - FORMS

/**
 * Create a booking form from REQUEST parameters
 * @since 1.5.0
 * @version 1.6.0
 */
function bookacti_controller_create_form() {
	if( empty( $_REQUEST[ 'action' ] ) || $_REQUEST[ 'action' ] !== 'new' ) { return; }
	
	// Exit if not allowed to create a form
	$can_create_form = current_user_can( 'bookacti_create_forms' );
	if( ! $can_create_form ) { esc_html_e( 'You are not allowed to do that.', BOOKACTI_PLUGIN_NAME ); exit; }
	
	$title = ! empty( $_REQUEST[ 'title' ] ) ? sanitize_text_field( stripslashes( $_REQUEST[ 'title' ] ) ) : '';
	
	$form_id = bookacti_create_form( $title, 'publish', 1 );
	if( ! $form_id ) { esc_html_e( 'Error occurs when trying to create the form.', BOOKACTI_PLUGIN_NAME ); exit; }
	
	// Insert calendar data (if any)
	if( ! empty( $_REQUEST[ 'calendar_field' ] ) ) {
		$default_calendar_meta = array();
		if( isset( $_REQUEST[ 'calendar_field' ][ 'calendars' ] ) ) {
			$template_data = bookacti_get_mixed_template_data( $_REQUEST[ 'calendar_field' ][ 'calendars' ], false );
			$default_calendar_meta				= $template_data[ 'settings' ];
			$default_calendar_meta[ 'start' ]	= $template_data[ 'start' ];
			$default_calendar_meta[ 'end' ]		= $template_data[ 'end' ];
		}
		$raw_calendar_meta		= array_merge( array( 'start' => 'default', 'end' => 'default' ), $default_calendar_meta, $_REQUEST[ 'calendar_field' ] );
		bookacti_update_form_field_meta( $raw_calendar_meta, 'calendar', $form_id );
	}
	
	// Change current url to the edit url
	$form_url = is_multisite() ? network_admin_url( 'admin.php?page=bookacti_forms&action=edit&form_id=' . $form_id ) : admin_url( 'admin.php?page=bookacti_forms&action=edit&form_id=' . $form_id );
	header( 'Location: ' . $form_url );
}
add_action( 'load-booking-activities_page_bookacti_forms', 'bookacti_controller_create_form', 5 );


/**
 * AJAX Controller - Update a booking form
 * @since 1.5.0
 */
function bookacti_controller_update_form() {
	// Check nonce and capabilities
	$form_id		= intval( $_REQUEST['form_id'] );
	$is_nonce_valid	= check_ajax_referer( 'bookacti_update_form', 'nonce_update_form', false );
	$is_allowed		= current_user_can( 'bookacti_edit_forms' ) && bookacti_user_can_manage_form( $form_id );

	if( ! $is_nonce_valid || ! $is_allowed ) {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
	
	$was_active		= intval( $_REQUEST['is_active'] );
	$form_title		= sanitize_text_field( stripslashes( $_REQUEST['form_title'] ) );
	$managers_array	= isset( $_REQUEST['form-managers'] ) ? bookacti_ids_to_array( $_REQUEST['form-managers'] ) : array();
	$form_managers	= bookacti_format_form_managers( $managers_array );
	
	// Create the form
	$updated = bookacti_update_form( $form_id, $form_title, -1, '', 'publish', 1 );

	// Feedback error
	if( $updated === false ) {
		wp_send_json( array( 'status' => 'failed', 'error' => 'query_failed' ) );
	}

	// Insert Managers
	bookacti_update_managers( 'form', $form_id, $form_managers );
	
	do_action( 'bookacti_form_updated', $form_id );

	wp_send_json( array( 'status' => 'success', 'message' => ! $was_active ? esc_html__( 'The booking form is published.', BOOKACTI_PLUGIN_NAME ) : esc_html__( 'The booking form has been updated.', BOOKACTI_PLUGIN_NAME ) ) );
}
add_action( 'wp_ajax_bookactiUpdateForm', 'bookacti_controller_update_form' );


/**
 * Trash / Remove / Restore a booking form according to URL parameters and display an admin notice to feedback
 * @since 1.5.0
 */
function bookacti_controller_remove_form() {
	if( empty( $_REQUEST[ 'form_id' ] ) || empty( $_REQUEST[ 'action' ] ) || empty( $_REQUEST[ 'page' ] ) 
		|| $_REQUEST[ 'page' ] !== 'bookacti_forms' 
		|| ! is_numeric( $_REQUEST[ 'form_id' ] )
		|| ! in_array( $_REQUEST[ 'action' ], array( 'trash', 'restore', 'delete' ), true ) ) { return; }
	
	$form_id = intval( $_REQUEST[ 'form_id' ] );
	
	// Check nonces
	if( ! wp_verify_nonce( $_REQUEST[ '_wpnonce' ], $_REQUEST[ 'action' ] . '-form_' . $form_id ) ) {
	?>
		<div class='notice notice-error is-dismissible bookacti-form-notice' >
			<p>
				<?php _e( 'You are not allowed to do that.', BOOKACTI_PLUGIN_NAME ); ?>
			</p>
		</div>
	<?php
		return;
	}
	
	// Remove a booking form
	if( $_REQUEST[ 'action' ] === 'trash' || $_REQUEST[ 'action' ] === 'delete' ) {
		
		// Check if current user is allowed to remove the booking form
		$can_delete_form = current_user_can( 'bookacti_delete_forms' ) && bookacti_user_can_manage_form( $form_id );
		if( ! $can_delete_form ) {
		?>
			<div class='notice notice-error is-dismissible bookacti-form-notice' >
				<p>
					<?php _e( 'You are not allowed to remove a booking form.', BOOKACTI_PLUGIN_NAME ); ?>
				</p>
			</div>
		<?php
			return;
		}
		
		// Whether to trash or remove permanently
		$removed = false;
		if( $_REQUEST[ 'action' ] === 'trash' && $form_id ) {
			$removed = bookacti_deactivate_form( $form_id );
		}
		else if( $_REQUEST[ 'action' ] === 'delete' && $form_id ) {
			$removed = bookacti_delete_form( $form_id );
		}
		
		// Feedback success
		if( $removed ) {
			do_action( 'bookacti_form_removed', $form_id );
		?>
			<div class='notice notice-success is-dismissible bookacti-form-notice' >
				<p>
					<?php _e( 'The booking form has been removed.', BOOKACTI_PLUGIN_NAME ); ?>
				</p>
			</div>
		<?php
		
		// Feedback failure
		} else {
		?>
			<div class='notice notice-error is-dismissible bookacti-form-notice' >
				<p>
					<?php _e( 'An error occured while trying to delete a booking form.', BOOKACTI_PLUGIN_NAME ); ?>
				</p>
			</div>
		<?php
		}
	}
	
	// Restore a booking form
	else if( $_REQUEST[ 'action' ] === 'restore' ) {
		
		// Check if current user is allowed to restore the booking form
		$can_edit_form = current_user_can( 'bookacti_edit_forms' );
		if( ! $can_edit_form ) {
		?>
			<div class='notice notice-error is-dismissible bookacti-form-notice' >
				<p>
					<?php _e( 'You are not allowed to restore a booking form.', BOOKACTI_PLUGIN_NAME ); ?>
				</p>
			</div>
		<?php
			return;
		}
		
		$restored = bookacti_activate_form( $form_id );
		
		// Feedback success
		if( $restored ) {
			do_action( 'bookacti_form_restored', $form_id );
		?>
			<div class='notice notice-success is-dismissible bookacti-form-notice' >
				<p>
					<?php _e( 'The booking form has been restored.', BOOKACTI_PLUGIN_NAME ); ?>
				</p>
			</div>
		<?php
		
		// Feedback failure
		} else {
		?>
			<div class='notice notice-error is-dismissible bookacti-form-notice' >
				<p>
					<?php _e( 'An error occured while trying to restore a booking form.', BOOKACTI_PLUGIN_NAME ); ?>
				</p>
			</div>
		<?php
		}
	}
}
add_action( 'all_admin_notices', 'bookacti_controller_remove_form', 10 );


/**
 * AJAX Controller - Update form meta
 * @since 1.5.0
 */
function bookacti_controller_update_form_meta() {
	
	$form_id = intval( $_POST[ 'form_id' ] );
	
	// Check nonce and capabilities
	$is_nonce_valid	= check_ajax_referer( 'bookacti_update_form', 'nonce', false );
	$is_allowed		= current_user_can( 'bookacti_edit_forms' ) && bookacti_user_can_manage_form( $form_id );
	
	if( $is_nonce_valid && $is_allowed && $form_id ) {
		
		// Sanitize data
		$sanitized_data	= bookacti_sanitize_form_data( $_POST );
		
		// Extract metadata only
		$form_meta = array_intersect_key( $sanitized_data, bookacti_get_default_form_meta() );
		
		if( ! $form_meta ) { wp_send_json( array( 'status' => 'failed', 'error' => 'empty_data' ) ); }
		
		// Update form metadata
		$updated = bookacti_update_metadata( 'form', $form_id, $form_meta );
		
		if( $updated !== false ) {
			
			do_action( 'bookacti_form_meta_updated', $form_id );
			
			// Get form data
			$form_data = bookacti_get_form_data( $form_id );
			
			wp_send_json( array( 'status' => 'success', 'form_data' => $form_data ) );
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_updated' ) );
		}
		
	} else {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
}
add_action( 'wp_ajax_bookactiUpdateFormMeta', 'bookacti_controller_update_form_meta', 10 );




// EDITOR - FIELDS

/**
 * AJAX Controller - Insert a form field
 * @since 1.5.0
 * @version 1.5.3
 */
function bookacti_controller_insert_form_field() {
	
	// Check nonce
	if( ! check_ajax_referer( 'bookacti_insert_form_field', 'nonce', false ) ) {
		bookacti_send_json_invalid_nonce( 'insert_form_field' );
	}
	
	$form_id = intval( $_POST[ 'form_id' ] );
	
	// Check form_id
	if( ! $form_id ) {
		bookacti_send_json( array( 
			'status' => 'failed', 
			'error' => 'invalid_form_id', 
			'message' => __( 'Invalid form ID', BOOKACTI_PLUGIN_NAME ) ), 'insert_form_field' );
	}
	
	// Check capabilities
	if( ! current_user_can( 'bookacti_edit_forms' ) || ! bookacti_user_can_manage_form( $form_id ) ) {
		bookacti_send_json_not_allowed( 'insert_form_field' );
	}
	
	$field_name = $_POST[ 'field_name' ];

	// Check if the field is known
	$fields_data = bookacti_get_default_form_fields_data();
	if( ! in_array( $field_name, array_keys( $fields_data ), true ) ) {
		bookacti_send_json( array( 
			'status' => 'failed', 
			'error' => 'unknown_field_name', 
			'message' => __( 'Unknown field', BOOKACTI_PLUGIN_NAME ) ), 'insert_form_field' );
	}

	// Check if the field already exists
	$form_fields = bookacti_get_form_fields_data( $form_id );
	$field_already_added = array();
	foreach( $form_fields as $form_field ) { $field_already_added[] = $form_field[ 'name' ]; }
	if( in_array( $field_name, $field_already_added, true ) && $fields_data[ $field_name ][ 'unique' ] ) {
		bookacti_send_json( array( 
			'status' => 'failed', 
			'error' => 'field_already_added', 
			'message' => __( 'This field has already been added to the form', BOOKACTI_PLUGIN_NAME ) ), 'insert_form_field' );
	}

	// Insert form field
	$field_id = bookacti_insert_form_field( $form_id, $field_name );

	if( $field_id === false ) {
		bookacti_send_json( array( 
			'status' => 'failed', 
			'error' => 'not_inserted', 
			'message' => __( 'An error occured while trying to insert the form field.', BOOKACTI_PLUGIN_NAME ) ), 'insert_form_field' );
	}

	$fields_data[ $field_name ][ 'field_id' ] = $field_id;

	// Update field order
	$field_order	= bookacti_get_metadata( 'form', $form_id, 'field_order', true );
	$field_order[]	= $field_id;
	bookacti_update_metadata( 'form', $form_id, array( 'field_order' => $field_order ) );

	do_action( 'bookacti_form_field_inserted', $field_id );

	// Get field data and HTML for editor
	$field_data	= bookacti_get_form_field_data( $field_id );
	$field_html = bookacti_display_form_field_for_editor( $field_data, false );

	bookacti_send_json( array( 
		'status' => 'success', 
		'field_id' => $field_id, 
		'field_data' => $field_data, 
		'field_html' => $field_html, 
		'field_order' => $field_order ), 'insert_form_field' );
}
add_action( 'wp_ajax_bookactiInsertFormField', 'bookacti_controller_insert_form_field', 10 );


/**
 * AJAX Controller - Remove a form field
 * @since 1.5.0
 */
function bookacti_controller_remove_form_field() {
	
	$field_id	= intval( $_POST[ 'field_id' ] );
	$field		= bookacti_get_form_field( $field_id );
	
	// Check nonce and capabilities
	$is_nonce_valid	= check_ajax_referer( 'bookacti_remove_form_field', 'nonce', false );
	$is_allowed		= current_user_can( 'bookacti_edit_forms' ) && bookacti_user_can_manage_form( $field[ 'form_id' ] );
	
	if( $is_nonce_valid && $is_allowed && $field_id ) {
		
		// Remove the form field and its metadata
		$removed = bookacti_delete_form_field( $field_id );
		
		if( $removed !== false ) {
			
			do_action( 'bookacti_form_field_removed', $field );
			
			// Update field order
			$field_order = bookacti_get_metadata( 'form', $field[ 'form_id' ], 'field_order', true );
			if( $field_order ) {
				$order_index = array_search( $field_id, $field_order );

				if( $order_index !== false ) {
					unset( $field_order[ $order_index ] );
					$field_order = array_values( $field_order );
					bookacti_update_metadata( 'form', $field[ 'form_id' ], array( 'field_order' => $field_order ) );
				}
			} else {
				$field_order = array();
			}
			
			wp_send_json( array( 'status' => 'success', 'field_order' => $field_order ) );
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_updated' ) );
		}
		
	} else {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
}
add_action( 'wp_ajax_bookactiRemoveFormField', 'bookacti_controller_remove_form_field', 10 );


/**
 * AJAX Controller - Save form field order
 * @since 1.5.0
 */
function bookacti_controller_save_form_field_order() {
	$form_id = intval( $_POST[ 'form_id' ] );
	
	// Check nonce and capabilities
	$is_nonce_valid	= check_ajax_referer( 'bookacti_form_field_order', 'nonce', false );
	$is_allowed		= current_user_can( 'bookacti_edit_forms' ) && bookacti_user_can_manage_form( $form_id );
	
	if( $is_nonce_valid && $is_allowed && $form_id ) {
		
		$field_order	= bookacti_sanitize_form_field_order( $form_id, $_POST[ 'field_order' ] );
		$updated		= bookacti_update_metadata( 'form', $form_id, array( 'field_order' => $field_order ) );
		
		do_action( 'bookacti_form_field_order_updated', $form_id, $field_order );
		
		if( $updated !== false ) {
			wp_send_json( array( 'status' => 'success', 'field_order' => $field_order ) );
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_updated' ) );
		}
		
	} else {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
}
add_action( 'wp_ajax_bookactiSaveFormFieldOrder', 'bookacti_controller_save_form_field_order', 10 );


/**
 * AJAX Controller - Update a field
 * @since 1.5.0
 * @version 1.5.4
 */
function bookacti_controller_update_form_field() {
	// Check nonce
	if( ! check_ajax_referer( 'bookacti_update_form_field', 'nonce', false ) ) {
		bookacti_send_json_invalid_nonce( 'update_form_field' );
	}
	
	$field_id	= intval( $_POST[ 'field_id' ] );
	$field		= bookacti_get_form_field( $field_id );
	$form_id	= $field[ 'form_id' ];
	
	// Check nonce and capabilities
	if( ! current_user_can( 'bookacti_edit_forms' ) || ! bookacti_user_can_manage_form( $form_id ) ) {
		bookacti_send_json_not_allowed( 'update_form_field' );
	}
	
	// Sanitize data
	$_POST[ 'name' ] = $field[ 'name' ]; $_POST[ 'type' ] = $field[ 'type' ];
	$sanitized_data	= bookacti_sanitize_form_field_data( $_POST );
	
	// Update form field
	$updated = bookacti_update_form_field( $sanitized_data );

	if( $updated === false ) {
		bookacti_send_json( array( 
				'status' => 'failed', 
				'error' => 'not_updated', 
				'message' => esc_html__( 'An error occurs while trying to update the field.', BOOKACTI_PLUGIN_NAME ) 
			), 'update_form_field' );
	}
	
	// Extract metadata only
	$field_meta = array_intersect_key( $sanitized_data, bookacti_get_default_form_fields_meta( $field[ 'name' ] ) );
	
	if( $field_meta ) {
		// Unserialize values before preocessing through bookacti_update_metadata
		foreach( $field_meta as $key => $value ) { $field_meta[ $key ] = maybe_unserialize( $value ); }
		// Update field metadata
		bookacti_update_metadata( 'form_field', $field_id, $field_meta );
	}

	do_action( 'bookacti_form_field_updated', $field, $sanitized_data );

	// Get field data and HTML for editor
	$field_data	= bookacti_get_form_field_data( $field_id );
	$field_html = bookacti_display_form_field_for_editor( $field_data, false );

	bookacti_send_json( array( 'status' => 'success', 'field_data' => $field_data, 'field_html' => $field_html ), 'update_form_field' );
}
add_action( 'wp_ajax_bookactiUpdateFormField', 'bookacti_controller_update_form_field', 10 );


/**
 * AJAX Controller - Reset form meta
 * @since 1.5.0
 */
function bookacti_controller_reset_form_field() {
	
	$field_id	= intval( $_POST[ 'field_id' ] );
	$field		= bookacti_get_form_field_data( $field_id );
	$form_id	= $field[ 'form_id' ];
	
	// Check nonce and capabilities
	$is_nonce_valid	= check_ajax_referer( 'bookacti_update_form_field', 'nonce', false );
	$is_allowed		= current_user_can( 'bookacti_edit_forms' ) && bookacti_user_can_manage_form( $form_id );
	
	if( $is_nonce_valid && $is_allowed && $form_id ) {
		
		// Update form field with default values
		$defaults_data	= bookacti_sanitize_form_field_data( bookacti_get_default_form_fields_data( $field[ 'name' ] ) );
		$defaults_data[ 'field_id' ] = $field_id;
		$updated		= bookacti_update_form_field( $defaults_data );
		
		if( $updated !== false ) {
			
			// Delete all metadata to apply default
			$defaults_meta = bookacti_get_default_form_fields_meta( $field[ 'name' ] );
			if( $defaults_meta ) {
				$deleted = bookacti_delete_metadata( 'form_field', $field_id, array_keys( $defaults_meta ) );
			}
			
			do_action( 'bookacti_form_field_reset', $field );
			
			// Get field data and HTML for editor
			$field_data	= bookacti_get_form_field_data( $field_id );
			$field_html = bookacti_display_form_field_for_editor( $field_data, false );
			
			wp_send_json( array( 'status' => 'success', 'field_data' => $field_data, 'field_html' => $field_html ) );
		} else {
			wp_send_json( array( 'status' => 'failed', 'error' => 'not_updated' ) );
		}
		
	} else {
		wp_send_json( array( 'status' => 'failed', 'error' => 'not_allowed' ) );
	}
}
add_action( 'wp_ajax_bookactiResetFormField', 'bookacti_controller_reset_form_field', 10 );


/**
 * Keep calendars meta while reseting calendar form field metadata
 * @since 1.5.0
 * @param array $field_data
 * @param array $old_field_data
 */
function bookacti_reset_calendar_form_field_exceptions( $field_data ) {
	// Keep calendars
	if( $field_data[ 'type' ] === 'calendar' && isset( $_POST[ 'calendars' ] ) ) {
		// Sanitize data
		$_POST[ 'name' ]	= $field_data[ 'name' ]; $_POST[ 'type' ] = $field_data[ 'type' ];
		$sanitized_data		= bookacti_format_booking_system_attributes( $_POST );
		// Update calendars
		if( isset( $sanitized_data[ 'calendars' ] ) ) {
			$meta_to_update = array( 'calendars' => $sanitized_data[ 'calendars' ] );
			bookacti_update_metadata( 'form_field', $field_data[ 'field_id' ], $meta_to_update );
		}
	}
}
add_action( 'bookacti_form_field_reset', 'bookacti_reset_calendar_form_field_exceptions', 10, 1 );


/**
 * Add the "Export" action to the "Calendar" field in form editor
 * @version 1.6.0
 * @param array $field
 */
function bookacti_add_export_action_to_calendar_field( $field ) {
	if( $field[ 'name' ] !== 'calendar' ) { return; }
	?>
	<div class='bookacti-form-editor-field-action bookacti-export-events dashicons dashicons-external' title='<?php esc_attr_e( 'Export events', BOOKACTI_PLUGIN_NAME ); ?>'></div>
	<?php
}
add_action( 'bookacti_form_editor_field_actions_after', 'bookacti_add_export_action_to_calendar_field', 10, 1 );


/**
 * AJAX Controller - Reset form export events URL
 * @since 1.6.0
 */
function bookacti_controller_reset_form_export_events_url() {
	
	$form_id = $_POST[ 'form_id' ];
	
	// Check nonce and capabilities
	$is_nonce_valid	= check_ajax_referer( 'bookacti_reset_export_events_url', 'nonce', false );
	$is_allowed		= current_user_can( 'bookacti_edit_forms' ) && bookacti_user_can_manage_form( $form_id );
	
	if( ! $is_nonce_valid || ! $is_allowed || ! $form_id ) {
		bookacti_send_json_not_allowed( 'reset_export_events_url' );
	}
	
	// Update form secret key
	$new_secret_key = md5( microtime().rand() );
	$old_secret_key = bookacti_get_metadata( 'form', $form_id, 'secret_key', true );
	$updated		= bookacti_update_metadata( 'form', $form_id, array( 'secret_key' => $new_secret_key ) );
	
	if( $updated === false ) {
		bookacti_send_json( array( 'status' => 'failed', 'error' => 'not_updated' ), 'reset_export_events_url' );
	}
	
	do_action( 'bookacti_export_events_url_reset', $form_id, $new_secret_key, $old_secret_key );
	
	bookacti_send_json( 
		array( 
			'status' => 'success', 
			'new_secret_key' => $new_secret_key, 
			'old_secret_key' => $old_secret_key,
			'message' => esc_html__( 'The secret key has been changed. The old link won\'t work anymore. Use the new link above to export your events.', BOOKACTI_PLUGIN_NAME ) 
		), 
		'reset_export_events_url' );
}
add_action( 'wp_ajax_bookactiResetExportEventsUrl', 'bookacti_controller_reset_form_export_events_url', 10 );


/**
 * Export events of a specific form
 * @since 1.6.0
 */
function bookacti_export_form_events_page() {
	if( empty( $_REQUEST[ 'action' ] ) || $_REQUEST[ 'action' ] !== 'bookacti_export_form_events' ) { return; }
	
	// Check if the form is valid
	$form_id = ! empty( $_REQUEST[ 'form_id' ] ) ? intval( $_REQUEST[ 'form_id' ] ) : 0;
	if( ! $form_id ) { esc_html_e( 'Invalid form ID.', BOOKACTI_PLUGIN_NAME ); exit; }
	
	// Check the filename
	$parsed_url = parse_url( $_SERVER[ 'REQUEST_URI' ] );
	if( substr( basename( $parsed_url[ 'path' ] ), -4 ) !== '.ics' ) { esc_html_e( 'Invalid filename.', BOOKACTI_PLUGIN_NAME ); exit; }
	
	// Check if the secret key exists
	$key = ! empty( $_REQUEST[ 'key' ] ) ? $_REQUEST[ 'key' ] : '';
	if( ! $key ) { esc_html_e( 'Missing key.', BOOKACTI_PLUGIN_NAME ); exit; }
	
	// Check if the secret key is correct
	$secret_key = bookacti_get_metadata( 'form', $form_id, 'secret_key', true );
	if( $key !== $secret_key ) { esc_html_e( 'Invalid key.', BOOKACTI_PLUGIN_NAME ); exit; }
	
	// Check if the form has a valid 'calendar' field
	$calendar_field = bookacti_get_form_field_data_by_name( $form_id, 'calendar' );
	if( ! $calendar_field ) { esc_html_e( 'Cannot find the calendar field of the requested form.', BOOKACTI_PLUGIN_NAME ); exit; }
	
	$atts = apply_filters( 'bookacti_export_events_attributes', array_merge( bookacti_format_booking_system_url_attributes( $calendar_field ), array(
		'groups_single_events' => 1
	)));
	
	$form = bookacti_get_form( $form_id );
	if( $form ) {
		$calname	= apply_filters( 'bookacti_translate_text', $form[ 'title' ] );
		/* translators: %s is the form title */
		$caldesc	= sprintf( esc_html__( 'Form "%s"', BOOKACTI_PLUGIN_NAME ), apply_filters( 'bookacti_translate_text', $form[ 'title' ] ) );
	} else {
		/* translators: %d is the form ID */
		$calname	= sprintf( esc_html__( 'Form #%d', BOOKACTI_PLUGIN_NAME ), $form_id );
		$caldesc	= $calname;
	}
	
	// Increment the sequence number each time to make sure that the events will be updated
	$sequence = intval( bookacti_get_metadata( 'form', $form_id, 'ical_sequence', true ) ) + 1;
	bookacti_update_metadata( 'form', $form_id, array( 'ical_sequence' => $sequence ) );
	
	bookacti_export_events_page( $atts, $calname, $caldesc, $sequence );
}
add_action( 'init', 'bookacti_export_form_events_page', 10 );
