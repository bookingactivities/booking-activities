<?php
/**
 * Booking Activities landing page
 * @version 1.7.6
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>

<div class='wrap'>
<h1 class='wp-heading-inline'>Booking Activities</h1>
<hr class='wp-header-end' />

<div id='bookacti-landing-container'>
	
	
	<div id='bookacti-add-ons'>	
		<div id='bookacti-add-ons-intro' >
			<h3><?php esc_html_e( 'Make the most of Booking Activities', 'booking-activities' ); ?></h3>
			<p><?php esc_html_e( 'You can extend Booking Activities functionnalities with the following great add-ons. Pick the one you are interested in and just give it a try, you have a 30-day money back guarantee. ', 'booking-activities' ); ?></p>
		</div>
		
		<div id='bookacti-add-ons-container' >
		<?php
			$promo = '';
			$promo_price_29 = '';
			$promo_price_39 = '';
			$promo_price_49 = '';
			$promo_price_59 = '';
			
			$add_ons = array(
				'prices-and-credits' => array( 
					'prefix' => 'bapap',
					'title' => 'Prices and Credits',
					'subtitle' => '',
					'link' => 'https://booking-activities.fr/en/downloads/prices-and-credits/?utm_source=plugin&utm_medium=plugin&utm_campaign=prices-and-credits&utm_content=landing',
					'screenshot' => true,
					'light_color' => '#91d2f2',
					'dark_color' => '#263740',
					'excerpt' => esc_html__( 'Set per event prices, volume discounts and price categories (children, adults...). Sell booking passes and redeem them on your forms.', 'booking-activities' ),
					'price' => '69.00€',
					'promo' => $promo,
					'promo_price' => $promo_price_59
				),
				'advanced-forms' => array( 
					'prefix' => 'baaf',
					'title' => 'Advanced Forms',
					'subtitle' => '',
					'link' => 'https://booking-activities.fr/en/downloads/advanced-forms/?utm_source=plugin&utm_medium=plugin&utm_campaign=advanced-forms&utm_content=landing',
					'screenshot' => true,
					'light_color' => '#f291c2',
					'dark_color' => '#402633',
					'excerpt' => esc_html__( 'Add any kind of fields to your booking forms. Collect data from each participant. View, edit and filter the values in your booking list.', 'booking-activities' ),
					'price' => '59.00€',
					'promo' => $promo,
					'promo_price' => $promo_price_49
				),
				'display-pack' => array( 
					'prefix' => 'badp',
					'title' => 'Display Pack',
					'subtitle' => '',
					'link' => 'https://booking-activities.fr/en/downloads/display-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=display-pack&utm_content=landing',
					'screenshot' => true,
					'light_color' => '#c291f2',
					'dark_color' => '#332640',
					'excerpt' => esc_html__( 'Customize Booking Activities appearance with the alternate views and customization options of this pack.', 'booking-activities' ),
					'price' => '49.00€',
					'promo' => $promo,
					'promo_price' => $promo_price_39
				),
				'notification-pack' => array( 
					'prefix' => 'banp',
					'title' => 'Notification Pack',
					'subtitle' => '',
					'link' => 'https://booking-activities.fr/en/downloads/notification-pack/?utm_source=plugin&utm_medium=plugin&utm_campaign=notification-pack&utm_content=landing',
					'screenshot' => true,
					'light_color' => '#91f2d2',
					'dark_color' => '#264037',
					'excerpt' => esc_html__( 'Send notifications and booking reminders via email, SMS and Push. Set specific messages for each event and use them in your notifications.', 'booking-activities' ),
					'price' => '49.00€',
					'promo' => $promo,
					'promo_price' => $promo_price_39
				),
				'order-for-customers' => array( 
					'prefix' => 'baofc',
					'title' => 'Order for Customers',
					'subtitle' => '',
					'link' => 'https://booking-activities.fr/en/downloads/order-for-customers/?utm_source=plugin&utm_medium=plugin&utm_campaign=order-for-customers&utm_content=landing',
					'screenshot' => true,
					'light_color' => '#f2ed91',
					'dark_color' => '#403f26',
					'excerpt' => esc_html__( 'Order and book for your customers and allow them to pay later on your website. Perfect for your operators and your salespersons.', 'booking-activities' ),
					'price' => '39.00€',
					'promo' => $promo,
					'promo_price' => $promo_price_29
				),
				'points-of-sale' => array( 
					'prefix' => 'bapos',
					'title' => 'Points of Sale',
					'subtitle' => esc_html__( '(requires WooCommerce)', 'booking-activities' ),
					'link' => 'https://booking-activities.fr/en/downloads/points-of-sale/?utm_source=plugin&utm_medium=plugin&utm_campaign=points-of-sale&utm_content=landing',
					'screenshot' => true,
					'light_color' => '#91f2a1',
					'dark_color' => '#26402a',
					'excerpt' => esc_html__( 'You have several points of sale and one website for all. Thanks to this plugin, your points of sale managers will be able to manage independently their own activities, calendars and bookings from this single website.', 'booking-activities' ),
					'price' => '69.00€',
					'promo' => $promo,
					'promo_price' => $promo_price_59
				)
			);


			foreach( $add_ons as $add_on_slug => $add_on ) {
				$license_status = get_option( $add_on[ 'prefix' ] . '_license_status' );
				if( empty( $license_status ) || $license_status !== 'valid' ) {
					$img_url = '';
					if( $add_on[ 'screenshot' ] === true ) {
						$img_url = plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/add-ons/' . $add_on_slug . '.png';
					} else if( is_string( $add_on[ 'screenshot' ] ) ) {
						$img_url = plugins_url() . '/' . BOOKACTI_PLUGIN_NAME . '/img/add-ons/' . $add_on[ 'screenshot' ];
					}
				?>
					<div class='bookacti-add-on-container' >
						<div class='bookacti-add-on-inner' >
							<?php if( $add_on[ 'promo' ] !== '' ) { ?>
							<div class='bookacti-add-on-promo' >
								<span><?php echo esc_html( $add_on[ 'promo' ] ); ?></span>
							</div>
							<?php } ?>

							<?php if( $img_url !== '' ) { 
								$color1 = $add_on[ 'light_color' ];
								$color2 = $add_on[ 'dark_color' ];

								if( $color1 && $color2 ) {
								?>
									<style>
										#bookacti-add-on-image-<?php echo $add_on_slug; ?>:before {
											background: <?php echo $color1; ?>;
											background: -moz-radial-gradient(center, ellipse cover, <?php echo $color1; ?> 35%, <?php echo $color2; ?> 135%);
											background: -webkit-radial-gradient(center, ellipse cover, <?php echo $color1; ?> 35%, <?php echo $color2; ?> 135%);
											background: radial-gradient(ellipse at center, <?php echo $color1; ?> 35%, <?php echo $color2; ?> 135%);
											filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='<?php echo $color1; ?>', endColorstr='<?php echo $color2; ?>',GradientType=1 );
										}
										#bookacti-add-on-image-<?php echo $add_on_slug; ?> {
											background: <?php echo $color2; ?>;
										}
									</style>
								<?php
								}
							?>

							<div id='bookacti-add-on-image-<?php echo esc_attr( $add_on_slug ); ?>' class='bookacti-add-on-image' >
								<a href='<?php echo esc_url( $add_on[ 'link' ] ); ?>' title='<?php echo esc_attr( $add_on[ 'title' ] ); ?>' target='_blank' >
									<img src='<?php echo esc_url( $img_url ); ?>' title='<?php echo esc_attr( $add_on[ 'title' ] ); ?>' />
								</a>
							</div>
							<?php } ?>

							<div class='bookacti-add-on-description' >
								<div class='bookacti-add-on-title' >
									<h4><?php echo esc_html( $add_on[ 'title' ] ); ?></h4>
									<?php if( $add_on[ 'subtitle' ] !== '' ) { ?>
									<em><?php echo esc_html( $add_on[ 'subtitle' ] ); ?></em>
									<?php } ?>
								</div>

								<div class='bookacti-add-on-excerpt' ><p><?php echo esc_html( $add_on[ 'excerpt' ] ); ?></p></div>

								<div class='bookacti-add-on-price' >
									<p>
									<?php 
										echo esc_html_x( 'From', 'Before add-on price', 'booking-activities' ) . ' ';
										$price_class = 'bookacti-add-on-price-value';
										if( $add_on[ 'promo_price' ] !== '' ) { $price_class = 'bookacti-line-through'; } 
									?>
										<span class='<?php echo $price_class ?>' >
											<?php echo esc_html( $add_on[ 'price' ] ); ?>
										</span>
									<?php if( $add_on[ 'promo_price' ] !== '' ) { ?>
										<span class='bookacti-add-on-price-value bookacti-add-on-promo-price-value' >
											<?php echo esc_html( $add_on[ 'promo_price' ] ); ?>
										</span>
									<?php } ?>
									</p>
								</div>

								<div class='bookacti-add-on-button' >
									<a href='<?php echo esc_url( $add_on[ 'link' ] ); ?>' title='<?php echo esc_attr( $add_on[ 'title' ] ); ?>' target='_blank' ><?php esc_html_e( 'More information', 'booking-activities' ); ?></a>
								</div>
							</div>
						</div>
					</div>
				<?php
				}
			}
		?>
		</div>
		
		<div id='bookacti-add-ons-guarantees' >
			<div id='bookacti-add-ons-guarantees-intro' >
				<h3><?php esc_html_e( 'Benefit from the best guarantees', 'booking-activities' ); ?></h3>
				<p><?php esc_html_e( "Our customers satisfaction is what keep us moving in the right direction. We adapt our products according to your feedbacks in order to meet your needs. So just give a try to Booking Activities and its add-ons. If they do not meet your expectations, you will just have to tell us. This is the very reason why Booking Activities is completely free and we offer a 30-day money back guarantee on all our add-ons.", 'booking-activities' ); ?></p>
			</div>
			<div id='bookacti-add-ons-guarantees-container' >
				<div class='bookacti-add-ons-guarantee' >
					<div class='bookacti-add-ons-guarantee-picto' ><span class="dashicons dashicons-lock"></span></div>
					<h4><?php esc_html_e( 'Secure Payments', 'booking-activities' ); ?></h4>
					<div class='bookacti-add-ons-guarantee-description' ><?php esc_html_e( 'Online payments are secured by PayPal', 'booking-activities' ); ?></div>
				</div>
				<div class='bookacti-add-ons-guarantee' >
					<div class='bookacti-add-ons-guarantee-picto' ><span class="dashicons dashicons-money"></span></div>
					<h4><?php esc_html_e( '30-Day money back guarantee', 'booking-activities' ); ?></h4>
					<div class='bookacti-add-ons-guarantee-description' ><?php esc_html_e( 'If you are not satisfied you will be 100% refunded', 'booking-activities' ); ?></div>
				</div>
				<div class='bookacti-add-ons-guarantee' >
					<div class='bookacti-add-ons-guarantee-picto' ><span class="dashicons dashicons-email-alt"></span></div>
					<h4><?php esc_html_e( 'Ready to help', 'booking-activities' ); ?></h4>
					<div class='bookacti-add-ons-guarantee-description' ><?php esc_html_e( 'Contact us at contact@booking‑activities.fr, we answer within 48h', 'booking-activities' ); ?></div>
				</div>
			</div>
		</div>
	</div>
</div>