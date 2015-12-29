<?php
/*
Plugin Name: CiviEvent Widget
Plugin URI: http://www.aghstrategies.com/civievent-widget
Description: The CiviEvent Widget plugin displays public CiviCRM events in a widget.
Version: 3.1
Author: AGH Strategies, LLC
Author URI: http://aghstrategies.com/
*/

/*
 *	Copyright 2013-2015 AGH Strategies, LLC	(email : info@aghstrategies.com)
 *
 *	This program is free software; you can redistribute it and/or modify
 *	it under the terms of the GNU Affero General Public License as published by
 *	the Free Software Foundation; either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 *	GNU Affero General Public License for more details.
 *
 *	You should have received a copy of the GNU Affero General Public License
 *	along with this program; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA	02110-1301	USA
 **/

require_once 'civievent-single-widget.php';

add_action( 'widgets_init', function() {
	register_widget( 'civievent_Widget' );
	register_widget( 'civievent_single_Widget' );
	wp_register_style( 'civievent-widget-Stylesheet', plugins_url( 'civievent-widget.css', __FILE__ ) );
});

/**
 * Deliver the widget as a shortcode.
 *
 * @param array $atts The shortcode attributes provided
 *                    Available attributes include:
 *                    - title string The widget title (default: "Upcoming Events"),
 *                    - summary bool 1 = display the summary,
 *                    - limit int The number of events (default: 5),
 *                    - alllink bool 1 = display "view all",
 *                    - wtheme string The widget theme (default: "stripe"),
 *                    - divider string The location field delimiter (default comma),
 *                    - city bool 1 = display event city,
 *                    - state string display event state/province:
 *                    	'abbreviate' - abbreviation
 *                    	'full' - full name
 *                    	'none' (default) - display nothing
 *                    - country bool 1 = display event country,
 *                    - admin_type string display type:
 *                    	'simple' (default) - use settings above for title, summary, etc.
 *                    	'custom' - use custom_display and custom_filter
 *                    - custom_display string JSON of custom display options (see documentation).
 *                    - custom_filter string JSON of custom filter options (see documentation).
 *                    All booleans default to false; any value makes them true.
 *
 * @return string The widget to drop into the post body.
 */
function civievent_widget_shortcode( $atts ) {
	$widget = new civievent_Widget( true );
	$defaults = $widget->_defaultWidgetParams;

	// Taking care of those who take things literally.
	if ( is_array( $atts ) ) {
		foreach ( $atts as $k => $v ) {
			if ( 'false' === $v ) {
				$atts[ $k ] = false;
			}
		}
	}

	foreach ( $defaults as $param => $default ) {
		if ( ! empty( $atts[ $param ] ) ) {
			$defaults[ $param ] = ( false === $default ) ? true : $atts[ $param ];
		}
	}
	$widgetAtts = array();
	return $widget->widget( $widgetAtts, $defaults );
}

add_shortcode( 'civievent_widget', 'civievent_widget_shortcode' );

/**
 * The widget class.
 */
class civievent_Widget extends WP_Widget {

	/**
	 * Version of CiviCRM (to warn those with old versions).
	 *
	 * @var string $_civiversion Version of CiviCRM
	 */
	protected $_civiversion = null;

	/**
	 * CiviCRM basepage for Wordpress
	 *
	 * @var string $_civiBasePage Path of base page
	 */
	protected $_civiBasePage = null;

	/**
	 * CiviCRM date format
	 *
	 * @var string $_dateFormat Date format
	 */
	protected $_dateFormat = null;

	/**
	 * CiviCRM time format
	 *
	 * @var string $_timeFormat Date format
	 */
	protected $_timeFormat = null;

	/**
	 * Default parameter values
	 *
	 * @var array $_defaultWidgetParams Default parameters
	 */
	public $_defaultWidgetParams = array(
		'title' => '',
		'wtheme' => 'stripe',
		'limit' => 5,
		'admin_type' => 'simple',
		'summary' => false,
		'alllink' => false,
		'city' => false,
		'state' => 'none',
		'country' => false,
		'divider' => ', ',
		'custom_display' => '',
		'custom_filter' => '',
	);

	/**
	 * Fields available for events
	 *
	 * @var array $_eventFields Name => Label array.
	 */
	private $_eventFields = array();

	/**
	 * Whether this is actually displaying as a shortcode section, not a real widget
	 *
	 * @var bool $_isShortcode It's a shortcode.
	 */
	protected $_isShortcode = false;

	/**
	 * Construct the basic widget object.
	 *
	 * @param bool $shortcode Whether this is actually a shortcode, not a widget.
	 */
	public function __construct( $shortcode = false ) {
		// Widget actual processes.
		parent::__construct(
			'civievent-widget', // Base ID
			__( 'CiviEvent List Widget', 'civievent-widget' ), // Name
			array( 'description' => __( 'displays public CiviCRM events', 'civievent-widget' ) ) // Args.
		);

		if ( $shortcode ) {
			$this->_isShortcode = true;
		}

		$this->_defaultWidgetParams['title'] = __( 'Upcoming Events', 'civievent-widget' );
		$this->commonConstruct();
	}

	/**
	 * Common features to both widgets.
	 */
	protected function commonConstruct() {
		if ( ! function_exists( 'civicrm_initialize' ) ) { return; }
		civicrm_initialize();

		require_once 'CRM/Utils/System.php';
		$this->_civiversion = CRM_Utils_System::version();
		$this->_civiBasePage = CRM_Core_BAO_Setting::getItem( CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'wpBasePage' );

		// Get date and time formats.
		$params = array( 'name' => 'date_format' );
		$values = array();
		CRM_Core_DAO::commonRetrieve( 'CRM_Core_DAO_PreferencesDate', $params, $values );
		$this->_dateFormat = CRM_Utils_Array::value( 'date_format', $values, '%b %E, %Y' );
		$params = array( 'name' => 'time_format' );
		$values = array();
		CRM_Core_DAO::commonRetrieve( 'CRM_Core_DAO_PreferencesDate', $params, $values );
		$this->_timeFormat = CRM_Utils_Array::value( 'time_format', $values, '%l:%M %p' );
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

		$fields = $this->getFields();

		$standardDisplay = false;
		if ( ! empty( $instance['custom_display'] ) && CRM_Utils_Array::value( 'admin_type', $instance ) === 'custom' ) {
			// Get the custom display params.
			$custom = json_decode( $instance['custom_display'], true );
			foreach ( $custom as $name => $fieldAttrs ) {
				// Make sure only legit fields are sent.
				if ( empty( $fields[ $name ] ) ) {
					unset( $custom[ $name ] );
				}
			}
			if ( empty( $custom ) ) {
				$standardDisplay = true;
			} else {
				// Get custom filters.
				$customFilters = json_decode( CRM_Utils_Array::value( 'custom_filter', $instance, '' ), true );
				$filterParams = array(
					'start_date' => array( '>=' => date( 'Y-m-d' ) ),
					'is_public' => 1,
					'options' => array(
						'sort' => 'start_date ASC',
						'limit' => CRM_Utils_Array::value( 'limit', $instance, 5 ),
					),
				);
				$allCustomDisplayFields = self::getCustomDisplayTitles();
				// Set filter params only if they're legit fields or options.
				if ( is_array( $customFilters ) ) {
					foreach ( $customFilters as $name => $val ) {
						if ( 'custom' === $name ) {
							foreach ( $val as $option => $optionVal ) {
								if ( in_array( $option, $okOptions ) ) {
									switch ( $option ) {
										case 'limit':
										case 'offset':
										case 'sort':
											$filterParams['options'][ $option ] = $optionVal;
									}
								}
							}
						} elseif ( array_key_exists( $name, $fields ) && ! array_key_exists( $name, $allCustomDisplayFields ) ) {
							$filterParams[ $name ] = $val;
						}
					}
				}
				$fieldsToRetrieve = array_keys( $custom );
				$customDisplayFields = array_intersect_key( $allCustomDisplayFields, $custom );
				foreach ( $customDisplayFields as $customDisplayField => $dontcare ) {
					$fieldsToRetrieve = array_merge( $fieldsToRetrieve, self::getCustomDisplayField( $customDisplayField ) );
				}
				// Return fields should be based on the custom_display only.
				$filterParams['return'] = array_unique( $fieldsToRetrieve );
				try {
					$eventsCustom = civicrm_api3( 'Event', 'get', $filterParams );
					if ( ! empty( $eventsCustom['values'] ) ) {
						$content = '<div class="civievent-widget-list civievent-widget-custom-display">';
						$index = 0;
						foreach ( $eventsCustom['values'] as $eventId => $event ) {
							$oe = ($index&1) ? 'odd' : 'even';
							$content .= "<div class=\"civievent-widget-event civievent-widget-event-$oe civievent-widget-event-$index\">";
							$index++;
							foreach ( $custom as $name => $fieldAttrs ) {
								if ( empty( $event[ $name ] ) ) {
									if ( array_key_exists( $name, $customDisplayFields ) ) {
										$fieldVal = self::getCustomDisplayField( $name, $event );
									} else {
										continue;
									}
								} else {
									$fieldVal = $event[ $name ];
								}
								$rowField = empty( $fieldAttrs['prefix'] ) ? '' : wp_filter_kses( $fieldAttrs['prefix'] );
								if ( ! empty( $fieldAttrs['title'] ) ) {
								 	$rowField .= empty( $fieldAttrs['wrapper'] ) ? "{$fields[ $name ]}: " : "<span class=\"civievent-widget-custom-label\">{$fields[ $name ]}: </span>";
								}
								$rowField .= empty( $fieldAttrs['wrapper'] ) ? $fieldVal : "<span class=\"civievent-widget-custom-value\">$fieldVal</span>";
								$rowField .= empty( $fieldAttrs['suffix'] ) ? '' : wp_filter_kses( $fieldAttrs['suffix'] );

								$rowClass = sanitize_html_class( "civievent-widget-custom-display-$name" );
								$content .= empty( $fieldAttrs['wrapper'] ) ? "$rowField\n" : "<span class=\"$rowClass\">$rowField</span>\n";
							}
							$content .= '</div>';
						}
						$content .= '</div>';
					} else {
						$content = '';
					}
				} catch (CiviCRM_API3_Exception $e) {
					// TODO: log the error.
					$error = $e->getMessage();
				}
			}
		} else {
			$standardDisplay = true;
		}

		if ( $standardDisplay ) {
			// Outputs the content of the widget.
			$cal = CRM_Event_BAO_Event::getCompleteInfo();
			$index = 0;
			$content = '<div class="civievent-widget-list">';
			foreach ( $cal as $event ) {
				$url = CRM_Utils_Array::value( 'url', $event );
				$title = CRM_Utils_Array::value( 'title', $event );
				$summary = CRM_Utils_Array::value( 'summary', $event, '' );
				$row = $this->dateFix( $event, 'civievent-widget-event' );
				if ( $title ) {
					$row .= ' <span class="civievent-widget-event-title">';
					$row .= self::locFix( $event, $event['event_id'], $instance, 'civievent-widget-event' );
					$row .= '<span class="civievent-widget-infolink">';
					$row .= ($url) ? "<a href=\"$url\">$title</a>" : $title;
					$row .= '</span>';

					$row .= self::regFix( $event, $event['event_id'], 'civievent-widget' );

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
			$classes = array();

			if ( $instance['summary'] ) {
				$classes[] = 'civievent-widget-withsummary';
			}
		}

		$classes[] = ( strlen( $instance['wtheme'] ) ) ? "civievent-widget-{$instance['wtheme']}" : 'civievent-widget-custom';

		foreach ( $classes as &$class ) {
			$class = sanitize_html_class( $class );
		}
		$classes = implode( ' ', $classes );

		wp_enqueue_style( 'civievent-widget-Stylesheet' );
		$title = apply_filters( 'widget_title', $instance['title'] );
		if ( $this->_isShortcode ) {
			if ( ! empty( $title ) ) {
				$content = "<h3 class=\"title widget-title civievent-widget-title\">$title</h3>$content";
			}
			return "<div class=\"civievent-widget $classes\">$content</div>";
		} else {
			$wTitle = $title ? $args['before_title'] . $title . $args['after_title'] : '';
			$content = "$wTitle<div class=\"$classes\">$content</div>";
			echo $args['before_widget'] . $content . $args['after_widget'];
		}
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

		wp_enqueue_script( 'civievent-widget-form', plugins_url( 'civievent-widget-form.js', __FILE__ ), array( 'jquery', 'underscore' ) );
		wp_enqueue_style( 'civievent-widget-form-css', plugins_url( 'civievent-widget-form.css', __FILE__ ) );

		if ( empty( $instance['admin_type'] ) ) {
			$instance['admin_type'] = 'simple';
		}

		// Outputs the options form on admin.
		foreach ( $this->_defaultWidgetParams as $param => $val ) {
			if ( false === $val ) {
				$$param = isset( $instance[ $param ] ) ? (bool) $instance[ $param ] : false;
			} else {
				$$param = isset( $instance[ $param ] ) ? $instance[ $param ] : $val;
			}
		}

		$fields = array_reverse( array_unique( array_reverse( $this->getFields() ) ) );

		if ( ! empty( $fields ) ) {
			$fieldSelect = '<select name="getfields-' . $this->get_field_name( 'custom-display' ) . '" class="civievent-widget-getfields widefat">';
			$fieldSelect .= '<option value="">' . __( '- Select a field to add -', 'civievent-widget' ) . '</option>';
			foreach ( $fields as $fieldName => $field ) {
				$fieldSelect .= "<option value=\"$fieldName\">$field</option>";
			}
			$fieldSelect .= '</select>';
		} else {
			$fieldSelect = '';
		}
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
		<div class="civievent-widget-admin-sections">
			<input type="radio" id="<?php echo $this->get_field_id( 'admin_type' ); ?>-simple" name="<?php echo $this->get_field_name( 'admin_type' );?>" value="simple" <?php checked( $admin_type, 'simple' ); ?>>
			<input type="radio" id="<?php echo $this->get_field_id( 'admin_type' ); ?>-custom" name="<?php echo $this->get_field_name( 'admin_type' );?>" value="custom" <?php checked( $admin_type, 'custom' ); ?>>
			<label for="<?php echo $this->get_field_id( 'admin_type' ); ?>-simple" class="civievent-widget-admin-type-label">Simple</label>
			<label for="<?php echo $this->get_field_id( 'admin_type' ); ?>-custom" class="civievent-widget-admin-type-label">Custom</label>
			<div class="civievent-widget-admin-simple">
				<p><input type="checkbox" <?php checked( $city ); ?> name="<?php echo $this->get_field_name( 'city' ); ?>" id="<?php echo $this->get_field_id( 'city' ); ?>" class="checkbox">
				<label for="<?php echo $this->get_field_id( 'city' ); ?>"><?php _e( 'Display city?', 'civievent-widget' ); ?></label>
				</p>
				<p>
				<label for="<?php echo $this->get_field_id( 'state' ); ?>"><?php _e( 'Display state/province?', 'civievent-widget' ); ?></label>
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
			</div>
			<div class="civievent-widget-admin-custom">
				<p><?php _e( 'ADVANCED: If you want to display additional or different fields, add their names here using the drop-down.', 'civievent-widget' ); ?></p>
				<div>
					<label for="<?php echo $this->get_field_id( 'custom_display' ); ?>"><?php _e( 'Custom display fields:', 'civievent-widget' ); ?></label>
					<?php echo $fieldSelect; ?>
					<input class="widefat civievent-widget-custom-display-params" id="<?php echo $this->get_field_id( 'custom_display' ); ?>" name="<?php echo $this->get_field_name( 'custom_display' ); ?>" type="text" value="<?php echo esc_attr( $custom_display ); ?>" />
					<a class="show-json" href="#" onclick="return false;">Show JSON</a>
					<p class="civievent-widget-custom-display-ui"></p>
				</div>
				<p>
					<label for="<?php echo $this->get_field_id( 'custom_filter' ); ?>"><?php _e( 'Custom API filter:', 'civievent-widget' ); ?></label>
					<input class="widefat" id="<?php echo $this->get_field_id( 'custom_filter' ); ?>" name="<?php echo $this->get_field_name( 'custom_filter' ); ?>" type="text" value="<?php echo esc_attr( $custom_filter ); ?>" />
			</div>
		</div>
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
		$instance['admin_type'] = ( 'custom' == $new_instance['admin_type'] ) ? 'custom' : 'simple';
		$instance['summary'] = isset( $new_instance['summary'] ) ? (bool) $new_instance['summary'] : false;
		$instance['city'] = isset( $new_instance['city'] ) ? (bool) $new_instance['city'] : false;
		$instance['state'] = ( 'none' === $new_instance['state'] ) ? null : $new_instance['state'];
		$instance['country'] = isset( $new_instance['country'] ) ? (bool) $new_instance['country'] : false;
		$instance['alllink'] = isset( $new_instance['alllink'] ) ? (bool) $new_instance['alllink'] : false;
		if ( isset( $new_instance['divider'] ) ) { $instance['divider'] = $new_instance['divider']; }
		$instance['offset'] = ( ! empty( $new_instance['offset'] ) ) ? intval( strip_tags( $new_instance['offset'] ) ) : 0;
		$instance['custom_display'] = ( ! empty( $new_instance['custom_display'] ) ) ? $new_instance['custom_display'] : '';
		$instance['custom_filter'] = ( ! empty( $new_instance['custom_filter'] ) ) ? $new_instance['custom_filter'] : '';

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
	public static function locationInfo( $eventId, $city = true, $state = null, $country = false ) {
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

		if ( ! empty( $result['is_error'] ) ) {
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

	/**
	 * Prepare event date display.
	 *
	 * @param array  $event The event details.
	 * @param string $classPrefix The beginning of the classname for the elements.
	 */
	public function dateFix( $event, $classPrefix ) {
		$start = CRM_Utils_Array::value( 'start_date', $event );
		$end = CRM_Utils_Array::value( 'end_date', $event );
		if ( $start ) {
			$date = '<span class="' . $classPrefix . '-start-date">' . CRM_Utils_Date::customFormat( $start, $this->_dateFormat ) . '</span>';
			$date .= ' <span class="' . $classPrefix . '-start-time">' . CRM_Utils_Date::customFormat( $start, $this->_timeFormat ) . '</span>';
			if ( $end ) {
				$date .= ' &ndash;';
				if ( CRM_Utils_Date::customFormat( $end, $this->_dateFormat ) !== CRM_Utils_Date::customFormat( $start, $this->_dateFormat ) ) {
					$date .= ' <span class="' . $classPrefix . '-end-date">' . CRM_Utils_Date::customFormat( $end, $this->_dateFormat ) . '</span>';
				}
				$date .= ' <span class="' . $classPrefix . '-end-time">' . CRM_Utils_Date::customFormat( $end, $this->_timeFormat ) . '</span>';
			}
			return "<span class=\"$classPrefix-datetime\">$date</span>";
		}
	}

	/**
	 * Prepare event location display.
	 *
	 * @param array   $event The event details.
	 * @param integer $id The event id.
	 * @param array   $instance The widget instance.
	 * @param string  $classPrefix The beginning of the classname for the elements.
	 */
	public static function locFix( $event, $id, $instance, $classPrefix ) {
		$location = '';
		$divider = ( isset($instance['divider'] ) ) ? $instance['divider'] : ', ';

		if ( $instance['city'] || 'none' !== $instance['state'] || $instance['country'] ) {
			$location = self::locationInfo( $id, $instance['city'], $instance['state'], $instance['country'] );
			$lprint = array();
			$prevLvalue = null;
			foreach ( $location as $lfield => $lvalue ) {
				if ( ! empty( $lvalue ) && $prevLvalue !== $lvalue ) {
					$lprint[] = '<span class="' . $classPrefix . '-location-' . $lfield . '">' . $lvalue . '</span>';
					$prevLvalue = $lvalue;
				}
			}
			$location = ' <span class="' . $classPrefix . '-location">' . implode( $divider, $lprint ) . '</span> ';
		}
		return $location;
	}

	/**
	 * Prepare registration link display.
	 *
	 * @param array   $event The event details.
	 * @param integer $id The event id.
	 * @param string  $classPrefix The beginning of the classname for the elements.
	 */
	public static function regFix( $event, $id, $classPrefix ) {
		$reg = '';
		if ( CRM_Utils_Array::value( 'is_online_registration', $event )
				&& (strtotime( CRM_Utils_Array::value( 'registration_start_date', $event ) ) <= time()
					|| ! CRM_Utils_Array::value( 'registration_start_date', $event ) )
				&& ( strtotime( CRM_Utils_Array::value( 'registration_end_date', $event ) ) > time()
					|| ! CRM_Utils_Array::value( 'registration_end_date', $event ) ) ) {
			$reglink = CRM_Utils_System::url( 'civicrm/event/register', "reset=1&id=$id" );
			$reg = '<span class="' . $classPrefix . '-reglink">';
			$reg .= "<a href=\"$reglink\">" . CRM_Utils_Array::value( 'registration_link_text', $event, ts( 'Register' ) ) . '</a>';
			$reg .= '</span>';
		}
		return $reg;
	}

	/**
	 * Retrieve the fields available for events.
	 *
	 * @return array
	 *   The event fields.
	 */
	public function getFields() {
		if ( empty( $this->_eventFields ) ) {
			$return = array();
			try {
				$fields = civicrm_api3( 'Event', 'getfields', array( 'api_action' => 'get' ) );
				if ( ! empty( $fields['values'] ) ) {
					foreach ( $fields['values'] as $name => $info ) {
						$prefix = empty($info['groupTitle']) ? '' : $info['groupTitle'] . ': ';
						$return[ $name ] = $prefix . CRM_Utils_Array::value( 'title', $info, $name );
						if ( $name != CRM_Utils_Array::value( 'name', $info, $name ) ) {
							$return[ $info['name'] ] = $prefix . CRM_Utils_Array::value( 'title', $info, $name );
						}
					}
				}
			} catch (CiviCRM_API3_Exception $e) {
				// TODO: log the error.
				$error = $e->getMessage();
			}
			$return = array_merge( $return, self::getCustomDisplayTitles() );
			asort( $return );
			$this->_eventFields = $return;
		}
		return $this->_eventFields;
	}

	public static function getCustomDisplayTitles() {
		return array(
			'event_title_infolink' => __( 'Event Title (linked to info page)', 'civievent-widget' ),
			'event_title_reglink' => __( 'Event Title (linked to registration)', 'civievent-widget' ),
			'registration_link_text_reglink' => __( 'Registration Link', 'civievent-widget' ),
		);
	}

	public static function getCustomDisplayField( $field, $event = array() ) {
		$reqs = array(
			'event_title_infolink' => array(
				'title',
				'id',
			),
			'event_title_reglink' => array(
				'title',
				'id',
			),
			'registration_link_text_reglink' => array(
				'registration_link_text',
				'id',
			),
		);

		if ( array_key_exists( $field, $reqs ) ) {
			// Return the required fields.
			if ( empty( $event ) ) {
				return $reqs[ $field ];
			}

			// Make sure required fields are in $event.
			$r = array_flip( $reqs[ $field ] );
			if ( array_intersect_key( $r, $event ) !== $r ) {
				return;
			}
		} else {
			return;
		}

		// What should be returned for each custom display field.
		switch ( $field ) {
			case 'event_title_infolink':
				return CRM_Utils_System::href( $event['event_title'], 'civicrm/event/info', "reset=1&id={$event['id']}", true, null, true, true );

			case 'event_title_reglink':
				return CRM_Utils_System::href( $event['event_title'], 'civicrm/event/register', "reset=1&id={$event['id']}", true, null, true, true );

			case 'registration_link_text_reglink':
				return CRM_Utils_System::href( $event['registration_link_text'], 'civicrm/event/register', "reset=1&id={$event['id']}", true, null, true, true );
		}
	}
}
