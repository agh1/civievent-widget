<?php
/*
Plugin Name: CiviEvent Widget
Plugin URI: http://www.aghstrategies.com/civievent-widget
Description: The CiviEvent Widget plugin displays public CiviCRM events in a widget.
Version: 1.1
Author: AGH Strategies, LLC
Author URI: http://aghstrategies.com/
*/

/*
		Copyright 2013-2015 AGH Strategies, LLC	(email : info@aghstrategies.com)

		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU Affero General Public License as published by
		the Free Software Foundation; either version 3 of the License, or
		(at your option) any later version.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
		GNU Affero General Public License for more details.

		You should have received a copy of the GNU Affero General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA	02110-1301	USA
*/

add_action( 'widgets_init', function() {
	register_widget( 'civievent_Widget' );
	wp_register_style( 'civievent-widget-Stylesheet', plugins_url( 'civievent-widget.css', __FILE__ ) );
});

/**
 * The widget class.
 */
class civievent_Widget extends WP_Widget {

	/**
	 * Version of CiviCRM (to warn those with old versions).
	 *
	 * @var string $_civiversion Version of CiviCRM
	 */
	private $_civiversion = null;

	/**
	 * CiviCRM basepage for Wordpress
	 *
	 * @var string $_civiBasePage Path of base page
	 */
	private $_civiBasePage = null;

	/**
	 * Construct the basic widget object.
	 */
	public function __construct() {
		// Widget actual processes.
		parent::__construct(
			'civievent-widget', // Base ID
			__( 'CiviEvent Widget', 'civievent-widget' ), // Name
			array( 'description' => __( 'displays public CiviCRM events', 'civievent-widget' ) ) // Args.
		);
		if ( ! function_exists( 'civicrm_initialize' ) ) { return; }
		civicrm_initialize();

		require_once 'CRM/Utils/System.php';
		$this->_civiversion = CRM_Utils_System::version();
		$this->_civiBasePage = CRM_Core_BAO_Setting::getItem( CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'wpBasePage' );
	}

	/**
	 * Build the widget
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Widget instance.
	 */
	public function widget( $args, $instance ) {
		if ( ! function_exists( 'civicrm_initialize' ) ) { return; }

		if ( version_compare( $this->_civiversion, '4.3.alpha1' ) < 0 ) { return; }

		// Outputs the content of the widget.
		$title = apply_filters( 'widget_title', $instance['title'] );
		$content = $title ? "<h3 class=\"title widget-title civievent-widget-title\">$title</h2>" : '';

		$params = array( 'name' => 'date_format' );
		$values = array();
		CRM_Core_DAO::commonRetrieve( 'CRM_Core_DAO_PreferencesDate', $params, $values );
		$date_format = ( $values['date_format'] ) ? $values['date_format'] : '%b %E, %Y';
		$params = array( 'name' => 'time_format' );
		$values = array();
		CRM_Core_DAO::commonRetrieve( 'CRM_Core_DAO_PreferencesDate', $params, $values );
		$time_format = ($values['time_format']) ? $values['time_format'] : '%l:%M %p';

		$cal = CRM_Event_BAO_Event::getCompleteInfo();
		$index = 0;
		$divider = ( isset($instance['divider'] ) ) ? $instance['divider'] : ', ';
		$content .= '<div class="civievent-widget-list">';
		foreach ( $cal as $event ) {
			$start = CRM_Utils_Array::value( 'start_date', $event );
			$end = CRM_Utils_Array::value( 'end_date', $event );
			$url = CRM_Utils_Array::value( 'url', $event );
			$title = CRM_Utils_Array::value( 'title', $event );
			$summary = CRM_Utils_Array::value( 'summary', $event, '' );
			$location = '';
			if ( $instance['city'] || $instance['state'] || $instance['country'] ) {
				$location = $this->locationInfo( $event['event_id'], $instance['city'], $instance['state'], $instance['country'] );
				$lprint = array();
				$prevLvalue = null;
				foreach ( $location as $lfield => $lvalue ) {
					if ( ! empty( $lvalue ) && $prevLvalue !== $lvalue ) {
						$lprint[] = '<span class="civievent-widget-location-' . $lfield . '">' . $lvalue . '</span>';
						$prevLvalue = $lvalue;
					}
				}
				$location = ' <span class="civievent-widget-location">' . implode( $divider, $lprint ) . '</span> ';
			}
			$row = '';
			if ( $start ) {
				$date = '<span class="civievent-widget-event-start-date">' . CRM_Utils_Date::customFormat( $start, $date_format ) . '</span>';
				$date .= ' <span class="civievent-widget-event-start-time">' . CRM_Utils_Date::customFormat( $start, $time_format ) . '</span>';
				if ( $end ) {
					$date .= ' &ndash;';
					if ( CRM_Utils_Date::customFormat( $end, $date_format ) !== CRM_Utils_Date::customFormat( $start, $date_format ) ) {
						$date .= ' <span class="civievent-widget-event-end-date">' . CRM_Utils_Date::customFormat( $end, $date_format ) . '</span>';
					}
					$date .= ' <span class="civievent-widget-event-end-time">' . CRM_Utils_Date::customFormat( $end, $time_format ) . '</span>';
				}
				$row .= "<span class=\"civievent-widget-event-datetime\">$date</span>";
			}
			if ( $title ) {
				$row .= ' <span class="civievent-widget-event-title">';
				$row .= $location;
				$row .= '<span class="civievent-widget-infolink">';
				$row .= ($url) ? "<a href=\"$url\">$title</a>" : $title;
				$row .= '</span>';

				if ( CRM_Utils_Array::value( 'is_online_registration', $event )
						&& (strtotime( CRM_Utils_Array::value( 'registration_start_date', $event ) ) <= time()
							|| ! CRM_Utils_Array::value( 'registration_start_date', $event ) )
						&& ( strtotime( CRM_Utils_Array::value( 'registration_end_date', $event ) ) > time()
							|| ! CRM_Utils_Array::value( 'registration_end_date', $event ) ) ) {
					$reglink = CRM_Utils_System::url( 'civicrm/event/register', "reset=1&id={$event['event_id']}" );
					$row .= '<span class="civievent-widget-reglink">';
					$row .= "<a href=\"$reglink\">" . CRM_Utils_Array::value( 'registration_link_text', $event, ts( 'Register' ) ) . '</a>';
					$row .= '</span>';
				}
				if ( $instance['summary'] ) {
					$row .= "<span class=\"civievent-widget-event-summary\">$summary</span>";
				}
				$row .= '</span>';
			}

			$oe = ($index&1) ? 'odd' : 'even';
			$content .= "<div class=\"civievent-widget-event civievent-widget-event-$oe civievent-widget-event-$index\">$row</div>";
			$index++;
			if ( $index >= $instance['limit'] ) { break; }
		}
		$content .= '</div>';
		if ( $instance['alllink'] ) {
			$viewall = CRM_Utils_System::url( 'civicrm/event/ical', 'reset=1&list=1&html=1' );
			$content .= "<div class=\"civievent-widget-viewall\"><a href=\"$viewall\">" . ts( 'View all' ) . '</a></div>';
		}
		$classes = array(
			'widget',
			'civievent-widget',
		);
		$classes[] = ( strlen( $instance['wtheme'] ) ) ? "civievent-widget-{$instance['wtheme']}" : 'civievent-widget-custom';
		if ( $instance['summary'] ) {
			$classes[] = 'civievent-widget-withsummary';
		}
		foreach ( $classes as &$class ) {
			$class = sanitize_html_class( $class );
		}
		$classes = implode( ' ', $classes );
		echo "<div class=\"$classes\">$content</div>";
		wp_enqueue_style( 'civievent-widget-Stylesheet' );
	}

	/**
	 * Widget config form.
	 *
	 * @param array $instance The widget instance.
	 */
	public function form( $instance ) {
		if ( ! function_exists( 'civicrm_initialize' ) ) { ?>
			<h3><?php _e( 'You must enable and install CiviCRM to use this plugin.', 'civievent-widget' ); ?></h3>
			<?php
			return;
		} elseif ( version_compare( $this->_civiversion, '4.3.alpha1' ) < 0 ) { ?>
			<h3><?php print __( 'You must enable and install CiviCRM 4.3 or higher to use this plugin.	You are currently running CiviCRM ', 'civievent-widget' ) . $this->_civiversion; ?></h3>
			<?php
			return;
		} elseif ( strlen( $this->_civiBasePage ) < 1 ) {
			$adminUrl = CRM_Utils_System::url( 'civicrm/admin/setting/uf', 'reset=1' );
			?><div class="civievent-widget-nobasepage">
				<h3><?php _e( 'No Base Page Set', 'civievent-widget' ); ?></h3>
				<?php
				print '<p>' . __( 'You do not have a WordPress Base Page set in your CiviCRM settings.  This can cause the CiviEvent Widget to display inconsistent links.', 'civievent-widget' );
				print '<a href=' . $adminUrl . '>' . __( 'Please set this', 'civievent-widget' ) . '</a> ' . __( 'before using the widget.', 'civievent-widget' ) . '</p>';
				?>
			</div><?php
		}

		// Outputs the options form on admin.
		$title = isset( $instance['title'] ) ? $instance['title'] : __( 'Upcoming Events', 'civievent-widget' );
		$wtheme = isset( $instance['wtheme'] ) ? $instance['wtheme'] : __( 'stripe', 'civievent-widget' );
		$limit = isset( $instance['limit'] ) ? $instance['limit'] : __( 5, 'civievent-widget' );
		$summary = isset( $instance['summary'] ) ? (bool) $instance['summary'] : false;
		$alllink = isset( $instance['alllink'] ) ? (bool) $instance['alllink'] : false;
		$city = isset( $instance['city'] ) ? (bool) $instance['city'] : false;
		$state = isset($instance['state']) ? $instance['state'] : 'none';
		$country = isset( $instance['country'] ) ? (bool) $instance['country'] : false;
		$divider = isset( $instance['divider'] ) ? $instance['divider'] : ', ';

		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'civievent-widget' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'wtheme' ); ?>"><?php _e( 'Widget theme:', 'civievent-widget' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'wtheme' ); ?>" name="<?php echo $this->get_field_name( 'wtheme' ); ?>" type="text" value="<?php echo esc_attr( $wtheme ); ?>" />
		<?php _e( 'Enter the theme for the widget.	Standard options are "stripe" and "divider", or you can enter your own value, which will be added to the widget class name.', 'civievent-widget' ); ?>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Limit:', 'civievent-widget' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="text" value="<?php echo esc_attr( $limit ); ?>" />
		</p>
		<p><input type="checkbox" <?php checked( $city ); ?> name="<?php echo $this->get_field_name( 'city' ); ?>" id="<?php echo $this->get_field_id( 'city' ); ?>" class="checkbox">
		<label for="<?php echo $this->get_field_id( 'city' ); ?>">Display city?</label>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'state' ); ?>">Display state/province?</label>
			<select name="<?php echo $this->get_field_name( 'state' ); ?>" id="<?php echo $this->get_field_id( 'state' ); ?>">
				<option value="none" <?php selected( $state, 'none' ); ?>><?php _e( 'Hidden', 'civievent-widget' ); ?></option>
				<option value="abbreviate" <?php selected( $state, 'abbreviate' ); ?>><?php _e( 'Abbreviations', 'civievent-widget' ); ?></option>
				<option value="full" <?php selected( $state, 'full' ); ?>><?php _e( 'Full names', 'civievent-widget' ); ?></option>
			</select>
		</p>
		<p><input type="checkbox" <?php checked( $country ); ?> name="<?php echo $this->get_field_name( 'country' ); ?>" id="<?php echo $this->get_field_id( 'country' ); ?>" class="checkbox">
		<label for="<?php echo $this->get_field_id( 'country' ); ?>"><?php _e( 'Display country?', 'civievent-widget' ); ?></label>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'divider' ); ?>"><?php _e( 'City, state, country divider:', 'civievent-widget' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'divider' ); ?>" name="<?php echo $this->get_field_name( 'divider' ); ?>" type="text" value="<?php echo esc_attr( $divider ); ?>" />
		<?php _e( 'Enter the character(s) that should separate the city, state/province, and/or country when displayed.', 'civievent-widget' ); ?>
		</p>
		<p><input type="checkbox" <?php checked( $summary ); ?> name="<?php echo $this->get_field_name( 'summary' ); ?>" id="<?php echo $this->get_field_id( 'summary' ); ?>" class="checkbox">
		<label for="<?php echo $this->get_field_id( 'summary' ); ?>"><?php _e( 'Display summary?', 'civievent-widget' ); ?></label>
		</p>
		<p><input type="checkbox" <?php checked( $alllink ); ?> name="<?php echo $this->get_field_name( 'alllink' ); ?>" id="<?php echo $this->get_field_id( 'alllink' ); ?>" class="checkbox">
		<label for="<?php echo $this->get_field_id( 'alllink' ); ?>"><?php _e( 'Display "view all"?', 'civievent-widget' ); ?></label>
		</p>
		<?php
	}

	/**
	 * Widget update function.
	 *
	 * @param array $new_instance The widget instance to be saved.
	 * @param array $old_instance The widget instance prior to update.
	 */
	public function update( $new_instance, $old_instance ) {
		// Processes widget options to be saved.
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		if ( ! empty( $new_instance['wtheme'] ) ) {
			$instance['wtheme'] = array_shift( explode( ' ', trim( strip_tags( $new_instance['wtheme'] ) ) ) );
		} else {
			$instance['wtheme'] = '';
		}
		$instance['limit'] = ( ! empty( $new_instance['limit'] ) ) ? intval( strip_tags( $new_instance['limit'] ) ) : 5;
		$instance['summary'] = isset( $new_instance['summary'] ) ? (bool) $new_instance['summary'] : false;
		$instance['city'] = isset( $new_instance['city'] ) ? (bool) $new_instance['city'] : false;
		$instance['state'] = ( 'none' === $new_instance['state'] ) ? null : $new_instance['state'];
		$instance['country'] = isset( $new_instance['country'] ) ? (bool) $new_instance['country'] : false;
		$instance['alllink'] = isset( $new_instance['alllink'] ) ? (bool) $new_instance['alllink'] : false;
		if ( isset( $new_instance['divider'] ) ) { $instance['divider'] = $new_instance['divider']; }

		return $instance;
	}

	/**
	 * Retrieve event location information.
	 *
	 * @param integer $eventId The ID of the event.
	 * @param boolean $city Return city.
	 * @param string  $state How to return state (abbreviate or full).
	 * @param boolean $country Return country.
	 */
	public function locationInfo($eventId, $city = true, $state = null, $country = false) {
		$result = civicrm_api('Event', 'getsingle', array(
			'version' => 3,
			'id' => $eventId,
			'is_show_location' => 1,
			'return' => 'loc_block_id',
			'api.LocBlock.getsingle' => array(
				'id' => '$value.loc_block_id',
				'api.Address.getsingle' => array( 'id' => '$value.address_id' ),
			),
		));

		if ($result['is_error']) {
			return array();
		}

		$return = array();
		$loc = CRM_Utils_Array::value( 'api.Address.getsingle', CRM_Utils_Array::value( 'api.LocBlock.getsingle', $result, array() ), array() );
		if ( $city ) {
			$return['city'] = CRM_Utils_Array::value( 'city', $loc );
		}
		if ( $state ) {
			$abbreviate = ( 'abbreviate' === $state ) ? 'abbreviate' : null;
			$states = CRM_Core_BAO_Address::buildOptions( 'state_province_id', $abbreviate, array( 'country_id' => CRM_Utils_Array::value( 'country_id', $loc ) ) );
			$return['state'] = CRM_Utils_Array::value( CRM_Utils_Array::value( 'state_province_id', $loc ), $states );
		}
		if ( $country ) {
			$countries = CRM_Core_BAO_Address::buildOptions( 'country_id', 'get' );
			$return['country'] = CRM_Utils_Array::value( CRM_Utils_Array::value( 'country_id', $loc ), $countries );
		}
		return $return;
	}
}
