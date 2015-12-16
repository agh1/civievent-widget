<?php
/**
 *	Widget to show a single event
 *
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
 *
 *	@package civievent-widget
 **/

/**
 * Deliver the widget as a shortcode.
 *
 * @param array $atts The shortcode attributes provided
 *                    Available attributes include:
 *                    - title string The widget title (default automatically fills
 *                      with the event title),
 *                    - wtheme string The widget theme (default: "standard"),
 *                    - divider string The location field delimiter (default comma),
 *                    - city bool 1 = display event city,
 *                    - state string display event state/province:
 *                    	'abbreviate' - abbreviation
 *                    	'full' - full name
 *                    	'none' (default) - display nothing
 *                    - country bool 1 = display event country,
 *                    - offset int The number of events to skip (default: 0).
 *                    All booleans default to false; any value makes them true.
 *
 * @return string The widget to drop into the post body.
 */
function civievent_single_widget_shortcode( $atts ) {
	$widget = new civievent_single_Widget( true );
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

add_shortcode( 'civievent_single_widget', 'civievent_single_widget_shortcode' );

/**
 * The widget class.
 */
class civievent_single_Widget extends civievent_Widget {
	/**
	 * Default parameter values
	 *
	 * @var array $_defaultWidgetParams Default parameters
	 */
	public $_defaultWidgetParams = array(
		'title' => '',
		'wtheme' => 'standard',
		'alllink' => false,
		'city' => false,
		'state' => 'none',
		'country' => false,
		'divider' => ', ',
		'offset' => 0,
	);

	/**
	 * Construct the basic widget object.
	 *
	 * @param bool $shortcode Whether this is actually a shortcode, not a widget.
	 */
	public function __construct( $shortcode = false ) {
		WP_Widget::__construct(
			'civievent-single-widget', // Base ID
			__( 'Single CiviEvent Widget', 'civievent-widget' ), // Name
			array( 'description' => __( 'displays a single CiviCRM event', 'civievent-widget' ) ) // Args.
		);

		if ( $shortcode ) {
			$this->_isShortcode = true;
		}
		$this->commonConstruct();
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

		try {
			$event = civicrm_api3( 'Event', 'getsingle', array(
				'sequential' => 1,
				'is_active' => 1,
				'is_public' => 1,
				'is_template' => 0,
				'options' => array(
					'limit' => 1,
					'sort' => 'start_date ASC',
					'offset' => CRM_Utils_Array::value( 'offset', $instance, 0 ),
				),
				'start_date' => array( '>=' => date( 'Y-m-d' ) ),
			) );
		} catch ( CiviCRM_API3_Exception $e ) {
			error_log( 'CiviCRM API Error: ' . $e->getMessage() );
		}

		if ( isset( $event['title'] ) ) {
			// Outputs the content of the widget.
			$infoLink = CRM_Utils_System::url( 'civicrm/event/info', "reset=1&id={$event['id']}" );
			if ( empty( $instance['title'] ) ) {
				$title = apply_filters( 'widget_title', $event['title'] );
				$title = "<a href=\"$infoLink\">" . apply_filters( 'widget_title', $event['title'] ) . '</a>';
				$content = '';
			} else {
				$title = apply_filters( 'widget_title', $instance['title'] );
				$content = "<div class=\"civievent-widget-single-title\"><a href=\"$infoLink\">{$event['title']}</a></div>";
			}

			if ( ! empty($event['summary'] ) ) {
				$content .= "<div class=\"civievent-widget-single-summary\">{$event['summary']}</div>";
			}
			$content .= $this->dateFix( $event, 'civievent-widget-single' );
			$content .= self::locFix( $event, $event['id'], $instance, 'civievent-widget-single' );
			$content .= self::regFix( $event, $event['id'], 'civievent-widget-single' );

			if ( $instance['alllink'] ) {
				$viewall = CRM_Utils_System::url( 'civicrm/event/ical', 'reset=1&list=1&html=1' );
				$content .= "<div class=\"civievent-widget-single-viewall\"><a href=\"$viewall\">" . ts( 'View all' ) . '</a></div>';
			}
		} else {
			return;
		}
		$classes = array();
		$classes[] = ( strlen( $instance['wtheme'] ) ) ? "civievent-widget-single-{$instance['wtheme']}" : 'civievent-widget-single-custom';

		foreach ( $classes as &$class ) {
			$class = sanitize_html_class( $class );
		}
		$classes = implode( ' ', $classes );

		wp_enqueue_style( 'civievent-widget-Stylesheet' );
		if ( $this->_isShortcode ) {
			$content = "<h3 class=\"title widget-title civievent-single-widget-title\">$title</h3>$content";
			return "<div class=\"civievent-widget $classes\">$content</div>";
		} else {
			echo $args['before_widget'];
			echo $args['before_title'] . $title . $args['after_title'];
			echo "<div class=\"$classes\">$content</div>";
			echo $args['after_widget'];
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

		// Outputs the options form on admin.
		foreach ( $this->_defaultWidgetParams as $param => $val ) {
			if ( false === $val ) {
				$$param = isset( $instance[ $param ] ) ? (bool) $instance[ $param ] : false;
			} else {
				$$param = isset( $instance[ $param ] ) ? $instance[ $param ] : $val;
			}
		}

		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'civievent-widget' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		<?php _e( 'Enter the title for the widget.	You may leave this blank to set the event\'s title as the widget title', 'civievent-widget' ); ?>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'wtheme' ); ?>"><?php _e( 'Widget theme:', 'civievent-widget' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'wtheme' ); ?>" name="<?php echo $this->get_field_name( 'wtheme' ); ?>" type="text" value="<?php echo esc_attr( $wtheme ); ?>" />
		<?php _e( 'Enter the theme for the widget.	The standard option is "standard", or you can enter your own value, which will be added to the widget class name.', 'civievent-widget' ); ?>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'offset' ); ?>"><?php _e( 'Offset:', 'civievent-widget' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'offset' ); ?>" name="<?php echo $this->get_field_name( 'offset' ); ?>" type="text" value="<?php echo esc_attr( $offset ); ?>" />
		<?php _e( 'By default, the widget will show the first upcoming event starting today or in the future.  Enter an offset to skip one or more events: for example, an offset of 1 will skip the first event and display the second.', 'civievent-widget' ); ?>
		</p>
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
		<p><input type="checkbox" <?php checked( $alllink ); ?> name="<?php echo $this->get_field_name( 'alllink' ); ?>" id="<?php echo $this->get_field_id( 'alllink' ); ?>" class="checkbox">
		<label for="<?php echo $this->get_field_id( 'alllink' ); ?>"><?php _e( 'Display "view all"?', 'civievent-widget' ); ?></label>
		</p>
		<?php
	}
}
