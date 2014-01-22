<?php
/**
 * @package Laposta
 */
/*
Plugin Name: Laposta
Plugin URI: http://laposta.nl/documentatie/wordpress.524.html
Description: Laposta is programma waarmee je gemakkelijk en snel nieuwsbrieven kunt maken en versturen. Met deze plugin plaats je snel een aanmeldformulier op je website.
Version: 0.4
Author: Laposta - Stijn van der Ree
Author URI: http://laposta.nl/contact
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define('LAPOSTA_VERSION', '0.4');
define('LAPOSTA_PLUGIN_URL', plugin_dir_url( __FILE__ ));

if (!class_exists('Laposta_Template')) { 
	class Laposta_Template {

		public function __construct() { 

			// registreer admin acties
			add_action('admin_init', array(&$this, 'admin_init')); 
			add_action('admin_menu', array(&$this, 'add_menu'));
		}

		// bij activatie plugin
		public static function activate() {
			 // Do nothing 
		}

		// bij deactivatie plugin
		public static function deactivate() { 
			// Do nothing 
		}

		// hook into WP's admin_init action hook
		public function admin_init() { 

			// Set up the settings for this plugin 
			$this->init_settings(); 
		}

		// Initialize some custom settings
		public function init_settings() { 

			// register the settings for this plugin
			register_setting('laposta_template-group', 'title'); 
			register_setting('laposta_template-group', 'api_key'); 
			register_setting('laposta_template-group', 'list'); 
		}

		// add a menu
		public function add_menu() { 
			add_options_page('Laposta', 'Laposta', 'manage_options', 'laposta_template', array(&$this, 'plugin_settings_page')); 
		}

		// Menu Callback 
		public function plugin_settings_page() { 

			if (!current_user_can('manage_options')) { 
				wp_die(__('You do not have sufficient permissions to access this page.')); 
			} 

			// Render the settings template 
			include(sprintf("%s/templates/settings.php", dirname(__FILE__))); 
		}
	}
}

if (class_exists('Laposta_Template')) { 

	// Installation and uninstallation hooks 
	register_activation_hook(__FILE__, array('Laposta_Template', 'activate')); 
	register_deactivation_hook(__FILE__, array('Laposta_Template', 'deactivate')); 

	// instantiate the plugin class 
	$laposta_template = new Laposta_Template(); 

	// Add a link to the settings page onto the plugin page 
	if (isset($laposta_template)) { 

		// Add the settings link to the plugins page
		function plugin_settings_link($links) { 
			$settings_link = '<a href="options-general.php?page=laposta_template">Settings</a>'; 
			array_unshift($links, $settings_link); 
			return $links; 
		}

		$plugin = plugin_basename(__FILE__);
		add_filter("plugin_action_links_$plugin", 'plugin_settings_link'); 
	}
}

// widget
if (!class_exists('Laposta_Widget')) { 

	class Laposta_Widget extends WP_Widget {

		public function __construct() {
			parent::__construct(
				'laposta_widget', // Base ID
				__('Laposta', 'text_domain'), // Name
				array( 'description' => __( 'Aanmeldformulier', 'text_domain' ), ) // Args
			);
		}

		public function init() {
			register_widget('Laposta_Widget');
		}

		public function form($instance) {
		// all settings inside plugin; do nothing

			echo '<p>De widget kan ingesteld worden bij het Laposta plugin scherm.</p>';

		}

		public function update($new_instance, $old_instance) {

			return $new_instance;
		}

		public function widget($args, $instance) {

			// before and after widget arguments are defined by themes
			echo $args['before_widget'];

			$title = get_option('title');
			if ($title) {
				echo $args['before_title'] . $title . $args['after_title'];
			}

			// check availability list and api-key
			$api_key = get_option('api_key');
			$list = get_option('list');
			if (!$api_key) {
				echo 'Er is nog geen api-key ingevoerd. Dit kan bij de Settings van deze plugin.';
			} else if (!$list) {
				echo 'Er is nog geen lijst aangevinkt. Dit kan bij de Settings van deze plugin.';
			}

			if ($list && $api_key) {

				// Laposta javascript
				echo '
<div align="center">
<!-- Laposta 1.1 -->
<script type="text/javascript">
var Laposta = {};
Laposta.width = "96%";
</script>
<script type="text/javascript" src="https://wat-een-fantastische.email-provider.nl/a/' . get_option('list') . '/subscribe.js"></script>
<!-- /Laposta -->
</div>';
			}

			echo $args['after_widget'];
		}
	}
}
if (class_exists('Laposta_Widget')) { 
	add_action('widgets_init', 'register_laposta_widget');
	function register_laposta_widget() {
		register_widget('Laposta_Widget');
	}
}
