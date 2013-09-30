<?php
/*
Plugin Name: CiviEvent Widget
Plugin URI: http://www.aghstrategies.com/civievent-widget
Description: The CiviEvent Widget plugin displays public CiviCRM events in a widget.
Version: 0.3
Author: AGH Strategies, LLC
Author URI: http://aghstrategies.com/
*/
/*  Copyright 2013 AGH Strategies, LLC  (email : info@aghstrategies.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_action( 'widgets_init', function(){
     register_widget( 'civievent_Widget' );
     wp_register_style( 'civievent-widget-Stylesheet', plugins_url('civievent-widget.css', __FILE__) );
});


class civievent_Widget extends WP_Widget {

	public function __construct() {
		// widget actual processes
		parent::__construct(
			'civievent-widget', // Base ID
			__('CiviEvent Widget', 'text_domain'), // Name
			array( 'description' => __( 'displays public CiviCRM events', 'text_domain' ), ) // Args
		);
		if (!function_exists('civicrm_initialize')) { return; }
		civicrm_initialize();
	}

	public function widget( $args, $instance ) {
		if (!function_exists('civicrm_initialize')) { return; }
		// outputs the content of the widget
		$title = apply_filters( 'widget_title', $instance['title'] );
    $content = $title ? "<h3 class=\"title widget-title civievent-widget-title\">$title</h2>" : '';

    $params = array('name' => 'date_format');
    $values = array();
    CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_PreferencesDate', $params, $values);
    $date_format = ($values['date_format']) ? $values['date_format'] : "%b %E, %Y";
    $params = array('name' => 'time_format');
    $values = array();
    CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_PreferencesDate', $params, $values);
    $time_format = ($values['time_format']) ? $values['time_format'] : "%l:%M %p";

    $cal = CRM_Event_BAO_Event::getCompleteInfo();
    $index = 0;
    $content .= '<div class="civievent-widget-list">';
    foreach ($cal as $event) {
      $start = CRM_Utils_Array::value('start_date', $event);
      $end = CRM_Utils_Array::value('end_date', $event);
      $url = CRM_Utils_Array::value('url', $event);
      $title = CRM_Utils_Array::value('title', $event);
      $summary = CRM_Utils_Array::value('summary', $event, '');
      $row = '';
      if ($start) {
        $date = '<span class="civievent-widget-event-start-date">' . CRM_Utils_Date::customFormat($start, $date_format) . '</span>';
        $date .= ' <span class="civievent-widget-event-start-time">' . CRM_Utils_Date::customFormat($start, $time_format) . '</span>';
        if ($end) {
          $date .= ' &ndash;';
          if (CRM_Utils_Date::customFormat($end, $date_format) != CRM_Utils_Date::customFormat($start, $date_format)) {
            $date .= ' <span class="civievent-widget-event-end-date">' . CRM_Utils_Date::customFormat($end, $date_format) . '</span>';
          }
          $date .= ' <span class="civievent-widget-event-end-time">' . CRM_Utils_Date::customFormat($end, $time_format) . '</span>';
        }
        $row .= "<span class=\"civievent-widget-event-datetime\">$date</span>";
      }
      if ($title) {
        $row .= ' <span class="civievent-widget-event-title">';
        $row .= '<span class="civievent-widget-infolink">';
        $row .= ($url) ? "<a href=\"$url\">$title</a>" : $title;
        $row .= '</span>';

        if (CRM_Utils_Array::value('is_online_registration', $event)
            && (strtotime(CRM_Utils_Array::value('registration_start_date', $event)) <= time()
              || !CRM_Utils_Array::value('registration_start_date', $event))
            && (strtotime(CRM_Utils_Array::value('registration_end_date', $event)) > time()
              || !CRM_Utils_Array::value('registration_end_date', $event))) {
          $reglink = CRM_Utils_System::url('civicrm/event/register', "reset=1&id={$event['event_id']}");
          $row .= '<span class="civievent-widget-reglink">';
          $row .= "<a href=\"$reglink\">" . CRM_Utils_Array::value('registration_link_text', $event, ts('Register')) . '</a>';
          $row .= '</span>';
        }
        if ($instance['summary']) {
          $row .= "<span class=\"civievent-widget-event-summary\">$summary</span>";
        }
        $row .= '</span>';
      }

      $oe = ($index&1) ? 'odd' : 'even';
      $content .= "<div class=\"civievent-widget-event civievent-widget-event-$oe civievent-widget-event-$index\">$row</div>";
      $index++;
      if ($index >= $instance['limit']) { break; }
    }
    $content .= '</div>';
    if ($instance['alllink']) {
      $viewall = CRM_Utils_System::url('civicrm/event/ical', "reset=1&list=1&html=1");
      $content .= "<div class=\"civievent-widget-viewall\"><a href=\"$viewall\">" . ts('View all') . '</a></div>';
    }
    $classes = array(
      'widget',
      'civievent-widget',
    );
    $classes[] = (strlen($instance['wtheme'])) ? "civievent-widget-{$instance['wtheme']}" : "civievent-widget-custom";
    if ($instance['summary']) {
      $classes[] = 'civievent-widget-withsummary';
    }
    $classes = implode(' ', $classes);
		echo "<div class=\"$classes\">$content</div>";
    wp_enqueue_style( 'civievent-widget-Stylesheet' );
	}

 	public function form( $instance ) {
		if (!function_exists('civicrm_initialize')) { ?>
      <h3>You must enable and install CiviCRM to use this plugin.</h3>
  	  <?php
	    return;
		}
		// outputs the options form on admin
		$title = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : __( 'Upcoming Events', 'text_domain' );
		$wtheme = isset( $instance[ 'wtheme' ] ) ? $instance[ 'wtheme' ] : __( 'stripe', 'text_domain' );
		$limit = isset( $instance[ 'limit' ] ) ? $instance[ 'limit' ] : __( 5, 'text_domain' );
		$summary = isset( $instance[ 'summary' ] ) ? (bool) $instance[ 'summary' ] : false;
		$alllink = isset( $instance[ 'alllink' ] ) ? (bool) $instance[ 'alllink' ] : false;

		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'wtheme' ); ?>"><?php _e( 'Widget theme:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'wtheme' ); ?>" name="<?php echo $this->get_field_name( 'wtheme' ); ?>" type="text" value="<?php echo esc_attr( $wtheme ); ?>" />
		Enter the theme for the widget.  Standard options are "stripe" and "divider", or you can enter your own value, which will be added to the widget class name.
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Limit:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="text" value="<?php echo esc_attr( $limit ); ?>" />
		</p>
    <p><input type="checkbox" <?php checked( $summary ); ?> name="<?php echo $this->get_field_name( 'summary' ); ?>" id="<?php echo $this->get_field_id( 'summary' ); ?>" class="checkbox">
		<label for="<?php echo $this->get_field_id( 'summary' ); ?>">Display summary?</label>
		</p>
    <p><input type="checkbox" <?php checked( $alllink ); ?> name="<?php echo $this->get_field_name( 'alllink' ); ?>" id="<?php echo $this->get_field_id( 'alllink' ); ?>" class="checkbox">
		<label for="<?php echo $this->get_field_id( 'alllink' ); ?>">Display "view all"?</label>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		if ( ! empty( $new_instance['wtheme'] ) ) {
  		$instance['wtheme'] = array_shift(explode(' ', trim(strip_tags( $new_instance['wtheme'] ))));
    }
    else {
      $instance['wtheme'] = '';
    }
		$instance['limit'] = ( ! empty( $new_instance['limit'] ) ) ? intval(strip_tags( $new_instance['limit'] )) : 5;
		$instance['summary'] = isset( $new_instance['summary'] ) ? (bool) $new_instance['summary'] : false;
		$instance['alllink'] = isset( $new_instance['alllink'] ) ? (bool) $new_instance['alllink'] : false;

		return $instance;
	}
}
