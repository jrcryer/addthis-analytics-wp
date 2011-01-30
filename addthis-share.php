<?php

/*
Plugin Name: AddThis Analytics API
Version: 1.0
Plugin URI: http://www.twitter.com/jrcryer
Description: Display AddThis.com analytics to site users.
Author: James Cryer
Author URI: http://www.twitter.com/jrcryer
*/

/*  Copyright 2010 James Cryer (jrcryer@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
require_once(__DIR__.'/lib/bin/Service.class.php');
require_once(__DIR__.'/lib/bin/Request.class.php');
require_once(__DIR__.'/lib/bin/Authentication.class.php');
require_once(__DIR__.'/lib/bin/Metric.class.php');
require_once(__DIR__.'/lib/bin/Dimension.class.php');
require_once(__DIR__.'/lib/bin/QueryParameter.class.php');
require_once(__DIR__.'/lib/bin/Cache.interface.php');
require_once(__DIR__.'/lib/bin/FileCache.class.php');

class AddThisAnalyticsWidget extends WP_Widget {

    /**
     * Constructor
     */
    public function AddThisAnalyticsWidget() {
        parent::WP_Widget(false, $name = 'AddThisAnalytics');
    }

    /**
     * Display widget form
     * 
     * @param array $instance
     */
    public function form($instance) {
        $title       = esc_attr($instance['title']);
        $username    = esc_attr($instance['username']);
        $password    = esc_attr($instance['password']);
        $metric      = esc_attr($instance['metric']);
        $dimension   = esc_attr($instance['dimension']);
        $period      = esc_attr($instance['period']);
        $service     = esc_attr($instance['service']);


        ?>
            <p>
                <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?>
                    <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('username'); ?>"><?php _e('Username:'); ?>
                    <input class="widefat" id="<?php echo $this->get_field_id('username'); ?>" name="<?php echo $this->get_field_name('username'); ?>" type="text" value="<?php echo $username; ?>" />
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('password'); ?>"><?php _e('Password:'); ?>
                    <input class="widefat" id="<?php echo $this->get_field_id('password'); ?>" name="<?php echo $this->get_field_name('password'); ?>" type="text" value="<?php echo $password; ?>" />
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('metric'); ?>"><?php _e('Metric:'); ?>
                    <select name="<?php echo $this->get_field_name('metric'); ?>" id="<?php echo $this->get_field_id('metric'); ?>">
                        <option value="clickbacks" <?php if($metric == 'clickbacks') echo 'selected="selected"' ?>>Click backs</option>
                        <option value="shares" <?php if($metric == 'shares') echo 'selected="selected"' ?>>Shares</option>
                        <option value="subscriptions" <?php if($metric == 'subscriptions') echo 'selected="selected"' ?>>Subscriptions</option>
                    </select>
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('dimension'); ?>"><?php _e('Dimension:'); ?>
                    <select name="<?php echo $this->get_field_name('dimension'); ?>" id="<?php echo $this->get_field_id('dimension'); ?>">
                        <option value="">All</option>
                        <option value="content" <?php if($dimension == 'content') echo 'selected="selected"' ?>>Content</option>
                        <option value="continent" <?php if($dimension == 'contient') echo 'selected="selected"' ?>>Continent</option>
                        <option value="country" <?php if($dimension == 'country') echo 'selected="selected"' ?>>Country</option>
                        <option value="domain" <?php if($dimension == 'domain') echo 'selected="selected"' ?>>Domain</option>
                        <option value="service" <?php if($dimension == 'service') echo 'selected="selected"' ?>>Service</option>
                        <option value="url" <?php if($dimension == 'url') echo 'selected="selected"' ?>>URL</option>
                    </select>
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('period'); ?>"><?php _e('Period:'); ?>
                    <select name="<?php echo $this->get_field_name('period'); ?>" id="<?php echo $this->get_field_id('period'); ?>">
                        <option value="day" <?php if($period == 'day') echo 'selected="selected"' ?>>Day</option>
                        <option value="week" <?php if($period == 'week') echo 'selected="selected"' ?>>Week</option>
                        <option value="month" <?php if($period == 'month') echo 'selected="selected"' ?>>Month</option>
                    </select>
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('service'); ?>"><?php _e('Service:'); ?>
                    <input class="widefat" id="<?php echo $this->get_field_id('service'); ?>" name="<?php echo $this->get_field_name('service'); ?>" type="text" value="<?php echo $service; ?>" />
                </label>
            </p>
        <?php
    }

    /**
     * Update name instance of the widget
     * 
     * @param array $new_instance
     * @param array $old_instance
     * @return array
     */
    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title']       = strip_tags($new_instance['title']);
        $instance['username']    = strip_tags($new_instance['username']);
        $instance['password']    = strip_tags($new_instance['password']);
        $instance['metric']      = strip_tags($new_instance['metric']);
        $instance['dimension']   = strip_tags($new_instance['dimension']);
        $instance['period']      = strip_tags($new_instance['period']);
        $instance['service']     = strip_tags($new_instance['service']);
        return $instance;
    }

    /**
     * Main process of widget
     * 
     * @param array $args
     * @param array $instance
     */
    public function widget($args, $instance) {
        extract( $args );
        extract( $instance );

        $oRequest = new Request(
            new Authentication($username, $password),
            new Metric($metric),
            new Dimension($dimension),
            $this->getServiceQuery()
        );
        $oService  = new Service($oRequest, new FileCache());
        $response  = $oService->getData();
        
        echo sprintf(
            "%s%s%s%s%s",
            $before_widget,
                $before_title,
                    $this->getTitle(),
                $after_title,
                $this->getContent($response),
            $after_widget
        );
    }

    /**
     * Return the widget title from the widget settigns
     * 
     * @return string
     */
    public function getTitle() {
        $aSetting = $this->getSettings();
        return $aSetting['title'];
    }

    /**
     * Return the widgets content for given data
     * 
     * @param array $response
     * @return string
     */
    public function getContent($data) {
        if(empty($data)) {
            return "<p>There is currently no data to share.</p>";
        }
        $content .= '<table><thead><tr>';
        $content .= $this->getDataHeaders($data);
        $content .= '</tr></thead><tbody>';
        $content .= $this->getDataContent($data);
        $content .= '</tbody></table>';

        return $content;
    }

    /**
     * Returns the headers for the given analytics data
     * 
     * @param array $data
     * @return string
     */
    protected function getDataHeaders($data) {
        $oData    = current($data);
        $aHeader = array();
        
        foreach($oData as $key => $value) {
            $aHeader[] = sprintf('<th class="%s">%s</th>', strtolower($key), ucfirst($key));
        }
        return join('', $aHeader);
    }

    /**
     * Returns the content for the given analytics data
     * 
     * @param array $data
     * @return string
     */
    protected function getDataContent($data) {
        $content = '';
        
        foreach($data as $oData) {
            $content .= '<tr>';
            
            foreach($oData as $key => $value) {
                $content .= sprintf('<td class="%s">%s</td>', strtolower($key), ucfirst($value));
            }
            $content .= '</tr>';
        }
        return $content;
    }

    /**
     * Creates parameters to send to the API based on current
     * widget settings
     * 
     * @return array
     */
    protected function getServiceQuery() {
        $aQuery   = array();
        $aSetting = $this->getSettings();
        
        if(isset($aSetting['service']) && $aSetting['service']) {
            $aQuery[] = new QueryParameter('service', $aSetting['service']);
        }

        if(isset($aSetting['period']) && $aSetting['period']) {
            $aQuery[] = new QueryParameter('period', $aSetting['period']);
        }
        return $aQuery;
    }

    /**
     * Returns settings for the current widget
     * 
     * @return array
     */
    protected function getSettings() {
        $aSetting = $this->get_settings();
        $aSetting = $aSetting[$this->number];
        return $aSetting;
    }
}
add_action('widgets_init', create_function('', 'return register_widget("AddThisAnalyticsWidget");'));