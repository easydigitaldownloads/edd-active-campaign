<?php
/**
 * Plugin Name: Easy Digital Downloads - ActiveCampaign
 * Plugin URL: http://easydigitaldownloads.com/extension/activecampaign
 * Description: Include a ActiveCampaign signup option with your Easy Digital Downloads checkout.
 * Author: Easy Digital Downloads
 * Author URI: https://easydigitaldownloads.com
 * Version: 1.0
 * Text Domain: edd-activecampaign
 * Domain Path: languages
 *
 * @package EDD_ActiveCampaign
 * @version 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'EDD_ActiveCampaign' ) ) :

/**
 * EDD_ActiveCampaign Class
 *
 * @since 1.1
 */
final class EDD_ActiveCampaign {

	/**
	 * Holds the instance.
	 *
	 * Ensures that only one instance of EDD_ActiveCampaign exists in memory at any one
	 * time and it also prevents needing to define globals all over the place.
	 *
	 * TL;DR This is a static property property that holds the singleton instance.
	 *
	 * @var object
	 * @static
	 * @since 1.1
	 */
	private static $instance;

	/**
	 * EDD ActiveCampaign uses many variables, several of which can be filtered to
	 * customize the way it operates. Most of these variables are stored in a
	 * private array that gets updated with the help of PHP magic methods.
	 *
	 * @var   array
	 * @see   EDD_ActiveCampaign::setup_globals()
	 * @since 1.1
	 */
	private $data;

	/**
	 * Get active object instance.
	 *
	 * @since  1.1
	 *
	 * @access public
	 * @static
	 * @return object
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof EDD_ActiveCampaign ) ) {
			self::$instance = new EDD_ActiveCampaign();
			self::$instance->setup_globals();
			self::$instance->load_classes();
			self::$instance->hooks();
			self::$instance->updater();
		}

		return self::$instance;
	}

	/**
	 * Class constructor. Includes constants, includes and init method.
	 *
	 * @access private
	 * @since  1.1
	 */
	private function __construct() {
		if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
			return;
		}

		self::$instance = $this;

		add_action( 'init', array( $this, 'init' ) );
		register_activation_hook( __FILE__, array( $this, 'activation' ) );
	}

	/**
	 * Sets up the constants/globals used.
	 *
	 * @access public
	 * @since  1.1
	 */
	private function setup_globals() {
		// File Path and URL Information
		$this->file        = __FILE__;
		$this->basename    = apply_filters( 'edd_activecampaign_plugin_basenname', plugin_basename( $this->file ) );
		$this->plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_path = plugin_dir_path( __FILE__ );
		$this->lang_dir    = apply_filters( 'edd_activecampaign_lang_dir', trailingslashit( $this->plugin_path . 'languages' ) );

		// Classes
		$this->classes_dir = apply_filters( 'edd_activecampaign_classes_dir', trailingslashit( $this->plugin_path . 'classes' ) );
		$this->classes_url = apply_filters( 'edd_activecampaign_classes_url', trailingslashit( $this->plugin_url . 'classes' ) );
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since  1.1
	 * @access protected
	 * @return void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'edd-activecampaign' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @access protected
	 * @since  1.1
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'edd-activecampaign' ), '1.0' );
	}

	/**
	 * Magic method for checking if custom variables have been set.
	 *
	 * @access protected
	 * @since  1.0
	 *
	 * @param string $key Variable name.
	 *
	 * @return void
	 */
	public function __isset( $key ) {
		return isset( $this->data[ $key ] );
	}

	/**
	 * Magic method for getting variables.
	 *
	 * @access protected
	 * @since  1.1
	 *
	 * @param string $key Variable name.
	 *
	 * @return void
	 */
	public function __get( $key ) {
		return isset( $this->data[ $key ] ) ? $this->data[ $key ] : null;
	}

	/**
	 * Magic method for setting variables.
	 *
	 * @since  1.1
	 * @access protected
	 *
	 * @param string $key   Variable name.
	 * @param string $value Variable value.
	 *
	 * @return void
	 */
	public function __set( $key, $value ) {
		$this->data[ $key ] = $value;
	}

	/**
	 * Magic method for unsetting variables
	 *
	 * @access protected
	 * @since  1.1
	 *
	 * @param string $key Variable name.
	 *
	 * @return void
	 */
	public function __unset( $key ) {
		if ( isset( $this->data[ $key ] ) ) {
			unset( $this->data[ $key ] );
		}
	}

	/**
	 * Magic method to prevent notices and errors from invalid method calls.
	 *
	 * @access public
	 * @since  1.1
	 *
	 * @param string $name
	 * @param array  $args
	 *
	 * @return void
	 */
	public function __call( $name = '', $args = array() ) {
		unset( $name, $args );

		return null;
	}

	/**
	 * Reset the instance of the class.
	 *
	 * @access public
	 * @since  1.1
	 * @static
	 */
	public static function reset() {
		self::$instance = null;
	}

	/**
	 * Function fired on `init`.
	 *
	 * This function is called on WordPress `init`. It's triggered from the
	 * constructor function.
	 *
	 * @access public
	 * @since  1.1
	 * @return void
	 */
	public function init() {
		do_action( 'edd_activecampaign_before_init' );


		do_action( 'edd_activecampaign_after_init' );
	}

	/**
	 * Loads classes.
	 *
	 * @access private
	 * @since  1.1
	 * @return void
	 */
	private function load_classes() {
	}

	/**
	 * Activation function fires when the plugin is activated.
	 *
	 * This function is fired when the activation hook is called by WordPress,
	 * it disables the plugin if EDD isn't active and throws an error.
	 *
	 * @access public
	 * @since  1.1
	 * @return void
	 */
	public function activation() {
		global $wpdb;

		edd_activecampaign();

		if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
			if ( is_plugin_active( $this->basename ) ) {
				deactivate_plugins( $this->basename );
				unset( $_GET['activate'] );
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			}
		}
	}

	/**
	 * Adds all the hooks/filters.
	 *
	 * The plugin relies heavily on the use of hooks and filters and modifies
	 * default WordPress behavior by the use of actions and filters which are
	 * provided by WordPress.
	 *
	 * Actions are provided to hook on this function, before the hooks and filters
	 * are added and after they are added. The class object is passed via the action.
	 *
	 * @access public
	 * @since  1.1
	 * @return void
	 */
	public function hooks() {
		do_action_ref_array( 'edd_activecampaign_before_setup_actions', array( &$this ) );

		/* Actions */
		add_action( 'edd_checkout_before_gateway', array( $this, 'check_for_email_signup' ), 10, 2 );
		add_action( 'edd_purchase_form_before_submit', array( $this, 'display_checkout_fields' ), 100 );

		/* Filters */
		add_filter( 'edd_settings_sections_extensions', array( $this, 'settings_section' ) );
		add_filter( 'edd_settings_extensions', array( $this, 'register_settings' ) );

		do_action_ref_array( 'edd_activecampaign_after_setup_actions', array( &$this ) );
	}

	/**
	 * Handles the displaying of any notices in the admin area.
	 *
	 * @access public
	 * @since  1.1
	 * @return void
	 */
	public function admin_notices() {
		echo '<div class="error"><p>' . sprintf( __( 'You must install %sEasy Digital Downloads%s for the ActiveCampaign Add-On to work.', 'edd-activecampaign' ), '<a href="http://easydigitaldownloads.com" title="Easy Digital Downloads">', '</a>' ) . '</p></div>';
	}

	/**
	 * Checks whether a user should be signed up for the ActiveCampaign list.
	 *
	 * @access public
	 * @since  1.0
	 *
	 * @param array $data      Checkout data.
	 * @param array $user_info User details.
	 *
	 * @return void
	 */
	public function check_for_email_signup( $data, $user_info ) {
		if ( $data['eddactivecampaign_activecampaign_signup'] ) {
			$email = $user_info['email'];
			$this->subscribe_email( $email, $user_info['first_name'], $user_info['last_name'] );
		}
	}

	/**
	 * Add an email address to the ActiveCampaign list.
	 *
	 * @access public
	 * @since  1.0
	 *
	 * @param string $email      Email address.
	 * @param string $first_name First name.
	 * @param string $last_name  Last name.
	 *
	 * @return bool
	 */
	public function subscribe_email( $email, $first_name = '', $last_name = '' ) {
		global $edd_options;

		if ( isset( $edd_options['eddactivecampaign_api'] ) && strlen( trim( $edd_options['eddactivecampaign_api'] ) ) > 0 ) {

			if ( ! isset( $edd_options['eddactivecampaign_list'] ) || strlen( trim( $edd_options['eddactivecampaign_list'] ) ) <= 0 ) {
				return false;
			}

			require_once( 'includes/ActiveCampaign.class.php' );

			$ac = new ActiveCampaign( $edd_options['eddactivecampaign_apiurl'], $edd_options['eddactivecampaign_api'] );

			$subscriber = array(
				"email"              => "$email",
				"first_name"         => "$first_name",
				"last_name"          => "$last_name",
				"p[{$list_id}]"      => $edd_options['eddactivecampaign_list'],
				"status[{$list_id}]" => 1,
			);

			$subscriber_add = $ac->api( "subscriber/add", $subscriber );

		}

		return false;
	}

	/**
	 * Display checkout fields.
	 *
	 * @access public
	 * @since  1.0
	 */
	public function display_checkout_fields() {
		global $edd_options;
		ob_start();
		if ( isset( $edd_options['eddactivecampaign_api'] ) && strlen( trim( $edd_options['eddactivecampaign_api'] ) ) > 0 ) { ?>
			<p>
				<input name="eddactivecampaign_activecampaign_signup" id="eddactivecampaign_activecampaign_signup" type="checkbox" checked="checked" />
				<label for="eddactivecampaign_activecampaign_signup"><?php echo isset( $edd_options['eddactivecampaign_label'] ) ? $edd_options['eddactivecampaign_label'] : __( 'Sign up for our mailing list', 'edd-activecampaign' ); ?></label>
			</p>
			<?php
		}
		echo ob_get_clean();
	}

	/**
	 * Registers the subsection for EDD Settings.
	 *
	 * @access public
	 * @since  1.1
	 *
	 * @param  array $sections Settings Sections.
	 *
	 * @return array Sections with ActiveCampaign added.
	 */
	public function settings_section( $sections ) {
		$sections['activecampaign'] = __( 'ActiveCampaign', 'edd-activecampaign' );

		return $sections;
	}

	/**
	 * Register settings.
	 *
	 * @access public
	 * @since  1.0
	 *
	 * @param array $settings Settings.
	 *
	 * @return array $settings Updated settings.
	 */
	public function register_settings( $settings ) {
		$activecampaign_settings = array(
			array(
				'id'   => 'eddactivecampaign_settings',
				'name' => '<strong>' . __( 'ActiveCampaign Settings', 'edd-activecampaign' ) . '</strong>',
				'desc' => '',
				'type' => 'header',
			),
			array(
				'id'   => 'eddactivecampaign_apiurl',
				'name' => __( 'API URL', 'edd-activecampaign' ),
				'desc' => __( 'Enter your ActiveCampaign API URL. It is located in the Settings --> API area of your ActiveCampaign account.', 'edd-activecampaign' ),
				'type' => 'text',
				'size' => 'regular',
			),
			array(
				'id'   => 'eddactivecampaign_api',
				'name' => __( 'API Key', 'edd-activecampaign' ),
				'desc' => __( 'Enter your ActiveCampaign API Key. It is located in the Settings --> API area of your ActiveCampaign account.', 'edd-activecampaign' ),
				'type' => 'text',
				'size' => 'regular',
			),
			array(
				'id'   => 'eddactivecampaign_list',
				'name' => __( 'List ID', 'edd-activecampaign' ),
				'desc' => __( 'Enter your List ID. It will be in the form of a number.', 'edd-activecampaign' ),
				'type' => 'text',
				'size' => 'regular',
			),
			array(
				'id'   => 'eddactivecampaign_label',
				'name' => __( 'Checkout Label', 'edd-activecampaign' ),
				'desc' => __( 'This is the text shown next to the signup option', 'edd-activecampaign' ),
				'type' => 'text',
				'size' => 'regular',
			),
		);

		if ( version_compare( EDD_VERSION, 2.5, '>=' ) ) {
			$activecampaign_settings = array( 'activecampaign' => $activecampaign_settings );
		}

		return array_merge( $settings, $activecampaign_settings );
	}
}

endif;

/**
 * The main function responsible for returning the one true EDD_ActiveCampaign
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $edd_activecampaign = edd_activecampaign(); ?>
 *
 * @since  1.1
 * @return object|null The one true EDD_ActiveCampaign Instance.
 */
function edd_activecampaign() {
	if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
		return null;
	}

	return EDD_ActiveCampaign::get_instance();
}
add_action( 'plugins_loaded', 'edd_activecampaign', 10 );