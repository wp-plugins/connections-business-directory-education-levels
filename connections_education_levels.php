<?php
/**
 * An extension for the Connections plugin which adds a metabox for education levels.
 *
 * @package   Connections Education Levels
 * @category  Extension
 * @author    Steven A. Zahm
 * @license   GPL-2.0+
 * @link      http://connections-pro.com
 * @copyright 2014 Steven A. Zahm
 *
 * @wordpress-plugin
 * Plugin Name:       Connections Education Levels
 * Plugin URI:        http://connections-pro.com
 * Description:       An extension for the Connections plugin which adds a metabox for education levels.
 * Version:           1.0.3
 * Author:            Steven A. Zahm
 * Author URI:        http://connections-pro.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       connections_education_levels
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists('Connections_Education_Levels') ) {

	class Connections_Education_Levels {

		public function __construct() {

			self::defineConstants();
			self::loadDependencies();

			// register_activation_hook( CNIL_BASE_NAME . '/connections_education_levels.php', array( __CLASS__, 'activate' ) );
			// register_deactivation_hook( CNIL_BASE_NAME . '/connections_education_levels.php', array( __CLASS__, 'deactivate' ) );

			/*
			 * Load translation. NOTE: This should be ran on the init action hook because
			 * function calls for translatable strings, like __() or _e(), execute before
			 * the language files are loaded will not be loaded.
			 *
			 * NOTE: Any portion of the plugin w/ translatable strings should be bound to the init action hook or later.
			 */
			add_action( 'init', array( __CLASS__ , 'loadTextdomain' ) );

			// Register the metabox and fields.
			add_action( 'cn_metabox', array( __CLASS__, 'registerMetabox') );

			// Add the business hours option to the admin settings page.
			// This is also required so it'll be rendered by $entry->getContentBlock( 'education_level' ).
			add_filter( 'cn_content_blocks', array( __CLASS__, 'settingsOption') );

			// Add the action that'll be run when calling $entry->getContentBlock( 'education_level' ) from within a template.
			add_action( 'cn_output_meta_field-education_level', array( __CLASS__, 'block' ), 10, 4 );

			// Register the widget.
			add_action( 'widgets_init', create_function( '', 'register_widget( "CN_Education_Levels_Widget" );' ) );
		}

		/**
		 * Define the constants.
		 *
		 * @access  private
		 * @static
		 * @since  1.0
		 * @return void
		 */
		private static function defineConstants() {

			define( 'CNEL_CURRENT_VERSION', '1.0.3' );
			define( 'CNEL_DIR_NAME', plugin_basename( dirname( __FILE__ ) ) );
			define( 'CNEL_BASE_NAME', plugin_basename( __FILE__ ) );
			define( 'CNEL_PATH', plugin_dir_path( __FILE__ ) );
			define( 'CNEL_URL', plugin_dir_url( __FILE__ ) );
		}

		/**
		 * The widget.
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 * @return void
		 */
		private static function loadDependencies() {

			require_once( CNEL_PATH . 'includes/class.widgets.php' );
		}


		public static function activate() {


		}

		public static function deactivate() {

		}

		/**
		 * Load the plugin translation.
		 *
		 * Credit: Adapted from Ninja Forms / Easy Digital Downloads.
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 * @uses   apply_filters()
		 * @uses   get_locale()
		 * @uses   load_textdomain()
		 * @uses   load_plugin_textdomain()
		 * @return void
		 */
		public static function loadTextdomain() {

			// Plugin's unique textdomain string.
			$textdomain = 'connections_education_levels';

			// Filter for the plugin languages folder.
			$languagesDirectory = apply_filters( 'connections_education_level_lang_dir', CNEL_DIR_NAME . '/languages/' );

			// The 'plugin_locale' filter is also used by default in load_plugin_textdomain().
			$locale = apply_filters( 'plugin_locale', get_locale(), $textdomain );

			// Filter for WordPress languages directory.
			$wpLanguagesDirectory = apply_filters(
				'connections_education_level_wp_lang_dir',
				WP_LANG_DIR . '/connections-education-level/' . sprintf( '%1$s-%2$s.mo', $textdomain, $locale )
			);

			// Translations: First, look in WordPress' "languages" folder = custom & update-secure!
			load_textdomain( $textdomain, $wpLanguagesDirectory );

			// Translations: Secondly, look in plugin's "languages" folder = default.
			load_plugin_textdomain( $textdomain, FALSE, $languagesDirectory );
		}

		/**
		 * Defines the education level options.
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 * @uses   apply_filters()
		 * @return array An indexed array containing the education levels.
		 */
		private static function levels() {

			$options = array(
				'-1' => __( 'Choose...', 'connections_education_levels'),
				'1'  => __( '1st - 4th Grade', 'connections_education_levels'),
				'5'  => __( '5th - 6th Grade', 'connections_education_levels'),
				'7'  => __( '7th - 8th Grade', 'connections_education_levels'),
				'9'  => __( '9th Grade', 'connections_education_levels'),
				'10' => __( '10th Grade', 'connections_education_levels'),
				'11' => __( '11th Grade', 'connections_education_levels'),
				'12' => __( '12th Grade No Diploma', 'connections_education_levels'),
				'13' => __( 'High School Graduate', 'connections_education_levels'),
				'15' => __( 'Some College No Degree', 'connections_education_levels'),
				'20' => __( 'Associate\'s Degree, occupational', 'connections_education_levels'),
				'25' => __( 'Associate\'s Degree, academic', 'connections_education_levels'),
				'30' => __( 'Bachelor\'s Degree', 'connections_education_levels'),
				'35' => __( 'Master\'s Degree', 'connections_education_levels'),
				'40' => __( 'Professional Degree', 'connections_education_levels'),
				'45' => __( 'Doctoral Degree', 'connections_education_levels'),
			);

			return apply_filters( 'cn_education_level_options', $options );
		}

		/**
		 * Return the education level based on the supplied key.
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 * @uses   levels()
		 * @param  string $level The key of the education level to return.
		 * @return mixed         bool | string	The education level if found, if not, FALSE.
		 */
		private static function education( $level = '' ) {

			if ( ! is_string( $level ) || empty( $level ) || $level === '-1' ) {

				return FALSE;
			}

			$levels    = self::levels();
			$education = isset( $levels[ $level ] ) ? $levels[ $level ] : FALSE;

			return $education;
		}

		/**
		 * Registered the custom metabox.
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 * @uses   levels()
		 * @uses   cnMetaboxAPI::add()
		 * @return void
		 */
		public static function registerMetabox() {

			$atts = array(
				'name'     => 'Education Level',
				'id'       => 'education-level',
				'title'    => __( 'Education Level', 'connections_education_levels' ),
				'context'  => 'side',
				'priority' => 'core',
				'fields'   => array(
					array(
						'id'      => 'education_level',
						'type'    => 'select',
						'options' => self::levels(),
						'default' => '-1',
						),
					),
				);

			cnMetaboxAPI::add( $atts );
		}

		/**
		 * Add the custom meta as an option in the content block settings in the admin.
		 * This is required for the output to be rendered by $entry->getContentBlock().
		 *
		 * @access private
		 * @since  1.0
		 * @param  array  $blocks An associtive array containing the registered content block settings options.
		 * @return array
		 */
		public static function settingsOption( $blocks ) {

			$blocks['education_level'] = 'Education Level';

			return $blocks;
		}

		/**
		 * Renders the Education Levels content block.
		 *
		 * Called by the cn_meta_output_field-education_level action in cnOutput->getMetaBlock().
		 *
		 * @access  private
		 * @since  1.0
		 * @static
		 * @uses   esc_attr()
		 * @uses   education()
		 * @param  string $id    The field id.
		 * @param  array  $value The education level ID.
		 * @param  array  $atts  The shortcode atts array passed from the calling action.
		 *
		 * @return string
		 */
		public static function block( $id, $value, $object = NULL, $atts ) {

			if ( $education = self::education( $value ) ) {

				printf( '<div class="cn-education-level">%1$s</div>', esc_attr( $education ) );
			}

		}

	}

	/**
	 * Start up the extension.
	 *
	 * @access public
	 * @since 1.0
	 *
	 * @return mixed object | bool
	 */
	function Connections_Education_Levels() {

			if ( class_exists('connectionsLoad') ) {

					return new Connections_Education_Levels();

			} else {

				add_action(
					'admin_notices',
					 create_function(
						 '',
						'echo \'<div id="message" class="error"><p><strong>ERROR:</strong> Connections must be installed and active in order use Connections Income Levels.</p></div>\';'
						)
				);

				return FALSE;
			}
	}

	/**
	 * Since Connections loads at default priority 10, and this extension is dependent on Connections,
	 * we'll load with priority 11 so we know Connections will be loaded and ready first.
	 */
	add_action( 'plugins_loaded', 'Connections_Education_Levels', 11 );

}
