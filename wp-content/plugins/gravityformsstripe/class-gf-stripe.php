<?php
/**
 * Gravity Forms Stripe Add-On.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2009 - 2018, Rocketgenius
 */

// Include the payment add-on framework.
GFForms::include_payment_addon_framework();

/**
 * Class GFStripe
 *
 * Primary class to manage the Stripe add-on.
 *
 * @since 1.0
 *
 * @uses GFPaymentAddOn
 */
class GFStripe extends GFPaymentAddOn {

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @access private
	 *
	 * @used-by GFStripe::get_instance()
	 *
	 * @var object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the Stripe Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @used-by GFStripe::scripts()
	 *
	 * @var string $_version Contains the version, defined from stripe.php
	 */
	protected $_version = GF_STRIPE_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '1.9.14.17';

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformsstripe';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformsstripe/stripe.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this Add-On can be found.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_url The URL of the Add-On.
	 */
	protected $_url = 'http://www.gravityforms.com';

	/**
	 * Defines the title of this Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_title The title of the Add-On.
	 */
	protected $_title = 'Gravity Forms Stripe Add-On';

	/**
	 * Defines the short title of the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_short_title The short title.
	 */
	protected $_short_title = 'Stripe';

	/**
	 * Defines if Add-On should use Gravity Forms servers for update data.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var bool $_enable_rg_autoupgrade true
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Defines if user will not be able to create feeds for a form until a credit card field has been added.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var bool $_requires_credit_card true.
	 */
	protected $_requires_credit_card = true;

	/**
	 * Defines if callbacks/webhooks/IPN will be enabled and the appropriate database table will be created.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var bool $_supports_callbacks true
	 */
	protected $_supports_callbacks = true;

	/**
	 * Stripe requires monetary amounts to be formatted as the smallest unit for the currency being used e.g. cents.
	 *
	 * @since  1.10.1
	 * @access protected
	 *
	 * @var bool $_requires_smallest_unit true
	 */
	protected $_requires_smallest_unit = true;

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  1.4.3
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_stripe';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  1.4.3
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_stripe';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  1.4.3
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_stripe_uninstall';

	/**
	 * Defines the capabilities needed for the Stripe Add-On
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array( 'gravityforms_stripe', 'gravityforms_stripe_uninstall' );

	/**
	 * Holds the custom meta key currently being processed. Enables the key to be passed to the gform_stripe_field_value filter.
	 *
	 * @since  2.1.1
	 * @access protected
	 *
	 * @used-by GFStripe::maybe_override_field_value()
	 *
	 * @var string $_current_meta_key The meta key currently being processed.
	 */
	protected $_current_meta_key = '';

	/**
	 * Get an instance of this class.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFStripe
	 * @uses GFStripe::$_instance
	 *
	 * @return object GFStripe
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new GFStripe();
		}

		return self::$_instance;

	}

	/**
	 * Load the Stripe credit card field.
	 *
	 * @since 2.6
	 */
	public function pre_init() {
		parent::pre_init();

		require_once 'includes/class-gf-field-stripe-creditcard.php';
	}

	/**
	 * Return the scripts which should be enqueued.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFPaymentAddOn::scripts()
	 * @uses GFAddOn::get_base_url()
	 * @uses GFAddOn::get_short_title()
	 * @uses GFStripe::$_version
	 * @uses GFCommon::get_base_url()
	 * @uses GFStripe::frontend_script_callback()
	 *
	 * @return array The scripts to be enqueued.
	 */
	public function scripts() {

		$scripts = array(
			array(
				'handle'    => 'stripe.js',
				'src'       => 'https://js.stripe.com/v2/',
				'version'   => $this->_version,
				'deps'      => array(),
				'in_footer' => false,
				'enqueue'   => array(
					array( $this, 'stripe_js_callback' ),
				),
			),
			array(
				'handle'    => 'stripe_elements',
				'src'       => 'https://js.stripe.com/v3/',
				'version'   => $this->_version,
				'deps'      => array(),
				'in_footer' => false,
				'enqueue'   => array(
					array( $this, 'stripe_elements_callback' ),
				),
			),
			array(
				'handle'    => 'stripe_checkout',
				'src'       => 'https://checkout.stripe.com/checkout.js',
				'version'   => $this->_version,
				'deps'      => array(),
				'in_footer' => false,
				'enqueue'   => array(
					array( $this, 'stripe_checkout_callback' ),
				),
			),
			array(
				'handle'    => 'gforms_stripe_frontend',
				'src'       => $this->get_base_url() . '/js/frontend.js',
				'version'   => $this->_version,
				'deps'      => array( 'jquery', 'gform_json', 'gform_gravityforms' ),
				'in_footer' => false,
				'enqueue'   => array(
					array( $this, 'frontend_script_callback' ),
				),
				'strings'   => array(
					'no_active_frontend_feed' => esc_html__( 'The credit card field will initiate once the payment condition is met.', 'gravityformsstripe' ),
				),
			),
			array(
				'handle'    => 'gforms_stripe_admin',
				'src'       => $this->get_base_url() . '/js/admin.js',
				'version'   => $this->_version,
				'deps'      => array( 'jquery', 'stripe.js' ),
				'in_footer' => false,
				'enqueue'   => array(
					array(
						'admin_page' => array( 'plugin_settings' ),
						'tab'        => array( $this->_slug, $this->get_short_title() ),
					),
				),
				'strings'   => array(
					'spinner'          => GFCommon::get_base_url() . '/images/spinner.gif',
					'validation_error' => esc_html__( 'Error validating this key. Please try again later.', 'gravityformsstripe' ),
				),
			),
		);

		return array_merge( parent::scripts(), $scripts );

	}

	/***
	 *  Return the styles that need to be enqueued.
	 * @since 2.6
	 * @access public
	 *
	 * @return array Returns an array of styles and when to enqueue them
	 */
	public function styles() {

		$styles = array(
			array(
				'handle'    => 'gforms_stripe_frontend',
				'src'       => $this->get_base_url() . '/css/frontend.css',
				'version'   => $this->_version,
				'in_footer' => false,
				'enqueue'   => array(
					array( $this, 'frontend_style_callback' ),
				),
			),
		);

		return array_merge( parent::styles(), $styles );

	}


	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Initialize the AJAX hooks.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFPaymentAddOn::init_ajax()
	 *
	 * @return void
	 */
	public function init_ajax() {

		parent::init_ajax();

		add_action( 'wp_ajax_gf_validate_secret_key', array( $this, 'ajax_validate_secret_key' ) );
	}

	/**
	 * Handler for the gf_validate_secret_key AJAX request.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::init_ajax()
	 * @uses    GFStripe::include_stripe_api()
	 * @uses    GFAddOn::log_error()
	 * @uses    \Stripe\Stripe::setApiKey()
	 * @uses    \Stripe\Account::retrieve()
	 * @uses    \Stripe\Error\Authentication::getMessage()
	 *
	 * @return void
	 */
	public function ajax_validate_secret_key() {

		// Get the API key name.
		$key_name = rgpost( 'keyName' );

		// If no cache or if new value provided, do a fresh validation.
		$this->include_stripe_api();
		\Stripe\Stripe::setApiKey( rgpost( 'key' ) );

		// Initialize validatity state.
		$is_valid = true;

		try {

			// Attempt to retrieve account details.
			\Stripe\Account::retrieve();

		} catch ( \Stripe\Error\Authentication $e ) {

			// Set validity state to false.
			$is_valid = false;

			// Log that key validation failed.
			$this->log_error( __METHOD__ . "(): {$key_name}: " . $e->getMessage() );

		}

		// Prepare response.
		$response = $is_valid ? 'valid' : 'invalid';

		// Send API key validation response.
		die( $response );

	}

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFAddOn::maybe_save_plugin_settings()
	 * @used-by GFAddOn::plugin_settings_page()
	 * @uses    GFStripe::api_settings_fields()
	 * @uses    GFStripe::get_webhooks_section_description()
	 *
	 * @return array Plugin settings fields to add.
	 */
	public function plugin_settings_fields() {

		$fields = array(
			array(
				'title'  => esc_html__( 'Stripe API', 'gravityformsstripe' ),
				'fields' => $this->api_settings_fields(),
			),
		);

		if ( version_compare( GFFormsModel::get_database_version(), '2.4-beta-1', '>=' ) ) {
			$fields[] = array(
				'title'       => esc_html__( 'Payment Collection', 'gravityformsstripe' ),
				'description' => $this->get_checkout_method_section_description(),
				'fields'      => array(
					array(
						'name'          => 'checkout_method',
						'label'         => esc_html__( 'Payment Collection Method', 'gravityformsstripe' ),
						'type'          => 'radio',
						/*'horizontal'    => true,*/
						'default_value' => 'credit_card',
						'choices'       => array(
							array(
								'label' => esc_html__( 'Gravity Forms Credit Card Field', 'gravityformsstripe' ),
								'value' => 'credit_card',
								'tooltip' => '<h6>' . esc_html__( 'Gravity Forms Credit Card Field', 'gravityformsstripe' ) . '</h6>' . esc_html__( 'Select this option to use the built-in Gravity Forms Credit Card field to collect payment.', 'gravityformsstripe' ),
							),
							array(
								'label' => esc_html__( 'Stripe Credit Card Field (Elements)', 'gravityformsstripe' ),
								'value' => 'stripe_elements',
								'tooltip' => '<h6>' . esc_html__( 'Stripe Credit Card Field (Elements)', 'gravityformsstripe' ) . '</h6>'.  esc_html__( 'Select this option to use a Credit Card field hosted by Stripe. This option offers the benefit of a streamlined user interface and the security of having the credit card field hosted on Stripe\'s servers. Selecting this option or "Stripe Payment Form" greatly simplifies the PCI compliance application process with Stripe.', 'gravityformsstripe' ),
							),
							array(
								'label' => esc_html__( 'Stripe Payment Form (Stripe Checkout)', 'gravityformsstripe' ),
								'value' => 'stripe_checkout',
								'tooltip' => '<h6>' . esc_html__( 'Stripe Payment Form', 'gravityformsstripe' ) . '</h6>' . esc_html__( 'Select this option to collect all payment information in a separate page (modal window) hosted by Stripe. This option is the simplest to implement since it doesn\'t require a credit card field in your form. Selecting this option or "Stripe Credit Card Field" greatly simplifies the PCI compliance application process with Stripe.', 'gravityformsstripe' ),
							),
						),
					),
				),
			);
		}

		$fields[] = array(
				'title'       => esc_html__( 'Stripe Webhooks', 'gravityformsstripe' ),
				'description' => $this->get_webhooks_section_description(),
				'fields'      => array(
					array(
						'name'       => 'webhooks_enabled',
						'label'      => esc_html__( 'Webhooks Enabled?', 'gravityformsstripe' ),
						'type'       => 'checkbox',
						'horizontal' => true,
						'required'   => 1,
						'choices'    => array(
							array(
								'label' => esc_html__( 'I have enabled the Gravity Forms webhook URL in my Stripe account.', 'gravityformsstripe' ),
								'value' => 1,
								'name'  => 'webhooks_enabled',
							),
						),
					),
					array(
						'name'       => 'test_signing_secret',
						'label'      => esc_html__( 'Test Signing secret', 'gravityformsstripe' ),
						'type'       => 'text',
						'input_type' => 'password',
						'class'      => 'medium',
					),
					array(
						'name'       => 'live_signing_secret',
						'label'      => esc_html__( 'Live Signing secret', 'gravityformsstripe' ),
						'type'       => 'text',
						'input_type' => 'password',
						'class'      => 'medium',
					),
				),
			array(
				'type'     => 'save',
				'messages' => array( 'success' => esc_html__( 'Settings updated successfully', 'gravityformsstripe' ) ),
			),
		);



		return $fields;

	}

	/**
	 * Define the settings which appear in the Stripe API section.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::plugin_settings_fields()
	 *
	 * @return array The API settings fields.
	 */
	public function api_settings_fields() {

		return array(
			array(
				'name'          => 'api_mode',
				'label'         => esc_html__( 'API', 'gravityformsstripe' ),
				'type'          => 'radio',
				'default_value' => 'live',
				'choices'       => array(
					array(
						'label' => esc_html__( 'Live', 'gravityformsstripe' ),
						'value' => 'live',
					),
					array(
						'label'    => esc_html__( 'Test', 'gravityformsstripe' ),
						'value'    => 'test',
						'selected' => true,
					),
				),
				'horizontal'    => true,
			),
			array(
				'name'     => 'test_publishable_key',
				'label'    => esc_html__( 'Test Publishable Key', 'gravityformsstripe' ),
				'type'     => 'text',
				'class'    => 'medium',
				'onchange' => "GFStripeAdmin.validateKey('test_publishable_key', this.value);",
			),
			array(
				'name'       => 'test_secret_key',
				'label'      => esc_html__( 'Test Secret Key', 'gravityformsstripe' ),
				'type'       => 'text',
				'input_type' => 'password',
				'class'      => 'medium',
				'onchange'   => "GFStripeAdmin.validateKey('test_secret_key', this.value);",
			),
			array(
				'name'     => 'live_publishable_key',
				'label'    => esc_html__( 'Live Publishable Key', 'gravityformsstripe' ),
				'type'     => 'text',
				'class'    => 'medium',
				'onchange' => "GFStripeAdmin.validateKey('live_publishable_key', this.value);",
			),
			array(
				'name'       => 'live_secret_key',
				'label'      => esc_html__( 'Live Secret Key', 'gravityformsstripe' ),
				'type'       => 'text',
				'input_type' => 'password',
				'class'      => 'medium',
				'onchange'   => "GFStripeAdmin.validateKey('live_secret_key', this.value);",
			),
			array(
				'label' => 'hidden',
				'name'  => 'live_publishable_key_is_valid',
				'type'  => 'hidden',
			),
			array(
				'label' => 'hidden',
				'name'  => 'live_secret_key_is_valid',
				'type'  => 'hidden',
			),
			array(
				'label' => 'hidden',
				'name'  => 'test_publishable_key_is_valid',
				'type'  => 'hidden',
			),
			array(
				'label' => 'hidden',
				'name'  => 'test_secret_key_is_valid',
				'type'  => 'hidden',
			),
		);

	}

	/**
	 * Define the markup to be displayed for the webhooks section description.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::plugin_settings_fields()
	 * @uses    GFStripe::get_webhook_url()
	 *
	 * @return string HTML formatted webhooks description.
	 */
	public function get_webhooks_section_description() {
		ob_start();
		?>

		<?php esc_html_e( 'Gravity Forms requires the following URL to be added to your Stripe account\'s list of Webhooks for each API mode you will be using.', 'gravityformsstripe' ); ?>
		<a href="javascript:return false;"
		   onclick="jQuery('#stripe-webhooks-instructions').slideToggle();"><?php esc_html_e( 'View Instructions', 'gravityformsstripe' ); ?></a>

		<div id="stripe-webhooks-instructions" style="display:none;">

			<ol>
				<li>
					<?php esc_html_e( 'Click the following link and log in to access your Stripe Webhooks management page:', 'gravityformsstripe' ); ?>
					<br/>
					<a href="https://dashboard.stripe.com/account/webhooks" target="_blank">https://dashboard.stripe.com/account/webhooks</a>
				</li>
				<li><?php esc_html_e( 'Click the "Add Endpoint" button above the list of Webhook URLs.', 'gravityformsstripe' ); ?></li>
				<li>
					<?php esc_html_e( 'Enter the following URL in the "URL to be called" field:', 'gravityformsstripe' ); ?>
					<code><?php echo $this->get_webhook_url(); ?></code>
				</li>
				<li><?php esc_html_e( 'If offered the choice, select the latest webhook version.', 'gravityformsstripe' ); ?></li>
				<li><?php esc_html_e( 'Select "Send all event types".', 'gravityformsstripe' ); ?></li>
				<li><?php esc_html_e( 'Click the "Add Endpoint" button to save the webhook.', 'gravityformsstripe' ); ?></li>
			</ol>

		</div>

		<?php
		return ob_get_clean();
	}

	/**
	 * Define the markup to be displayed for the Stripe Checkout section description.
	 *
	 * @since 2.6
	 */
	public function get_checkout_method_section_description() {
		ob_start();
		?>
        <p><?php esc_html_e( 'Select how payment information will be collected. You can choose the classic "Credit Card" option to use the native Gravity Forms credit card field or select one of the Stripe hosted solutions (Stripe Credit Card or Stripe Checkout) which simplifies the PCI compliance process with Stripe.' ); ?></p>

		<?php
		return ob_get_clean();
	}





	// # FEED SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Remove the add new button from the title if the form requires a credit card field.
	 *
	 * @since 2.6
	 *
	 * @return string
	 */
	public function feed_list_title() {
		$form = $this->get_current_form();

		if ( ( $this->_requires_credit_card && ! $this->has_credit_card_field( $form ) ) || ( $this->get_plugin_setting( 'checkout_method' ) === 'stripe_elements' && ! $this->has_stripe_card_field( $form ) ) ) {
			return $this->form_settings_title();
		}

		return GFFeedAddOn::feed_list_title();
	}

	/**
	 * Get the require credit card message.
	 *
	 * @since 2.6
	 *
	 * @return false|string
	 */
	public function feed_list_message() {
		$form = $this->get_current_form();

		// If form has card field or using Stripe Checkout, allow adding feeds.
		if ( $this->has_credit_card_field( $form ) || $this->has_stripe_card_field( $form ) || $this->get_plugin_setting( 'checkout_method' ) === 'stripe_checkout' ) {
			return GFFeedAddOn::feed_list_message();
		} elseif ( ( $this->_requires_credit_card && ! $this->has_credit_card_field( $form ) ) ) {
			return $this->requires_credit_card_message();
		} elseif ( $this->get_plugin_setting( 'checkout_method' ) === 'stripe_elements' && ! $this->has_stripe_card_field( $form ) ) {
			return $this->requires_stripe_card_message();
		}

		return GFFeedAddOn::feed_list_message();
	}

	/**
	 * Display the requiring Stripe Card field message.
	 *
	 * @since 2.6
	 *
	 * @return string
	 */
	public function requires_stripe_card_message() {
		$url = add_query_arg( array( 'view' => null, 'subview' => null ) );

		return sprintf( esc_html__( "You must add a Stripe Card field to your form before creating a feed. Let's go %sadd one%s!", 'gravityformsstripe' ), "<a href='" . esc_url( $url ) . "'>", '</a>' );
	}

	/**
	 * Configures the settings which should be rendered on the feed edit page.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFPaymentAddOn::feed_settings_fields()
	 * @uses GFAddOn::replace_field()
	 * @uses GFAddOn::get_setting()
	 * @uses GFAddOn::add_field_after()
	 * @uses GFAddOn::remove_field()
	 * @uses GFAddOn::add_field_before()
	 *
	 * @return array The feed settings.
	 */
	public function feed_settings_fields() {

		// Get default payment feed settings fields.
		$default_settings = parent::feed_settings_fields();

		// Prepare customer information fields.
		$customer_info_field = array(
			'name'       => 'customerInformation',
			'label'      => esc_html__( 'Customer Information', 'gravityformsstripe' ),
			'type'       => 'field_map',
			'dependency' => array(
				'field'  => 'transactionType',
				'values' => array( 'subscription' ),
			),
			'field_map'  => array(
				array(
					'name'       => 'email',
					'label'      => esc_html__( 'Email', 'gravityformsstripe' ),
					'required'   => true,
					'field_type' => array( 'email', 'hidden' ),
				),
				array(
					'name'     => 'description',
					'label'    => esc_html__( 'Description', 'gravityformsstripe' ),
					'required' => false,
				),
				array(
					'name'       => 'coupon',
					'label'      => esc_html__( 'Coupon', 'gravityformsstripe' ),
					'required'   => false,
					'field_type' => array( 'coupon', 'text' ),
					'tooltip'    => '<h6>' . esc_html__( 'Coupon', 'gravityformsstripe' ) . '</h6>' . esc_html__( 'Select which field contains the coupon code to be applied to the recurring charge(s). The coupon must also exist in your Stripe Dashboard.', 'gravityformsstripe' ),
				),
			),
		);

		// Replace default billing information fields with customer information fields.
		$default_settings = $this->replace_field( 'billingInformation', $customer_info_field, $default_settings );

		// Define end of Metadata tooltip based on transaction type.
		if ( 'subscription' === $this->get_setting( 'transactionType' ) ) {
			$info = esc_html__( 'You will see this data when viewing a customer page.', 'gravityformsstripe' );
		} else {
			$info = esc_html__( 'You will see this data when viewing a payment page.', 'gravityformsstripe' );
		}

		// Prepare meta data field.
		$custom_meta = array(
			array(
				'name'                => 'metaData',
				'label'               => esc_html__( 'Metadata', 'gravityformsstripe' ),
				'type'                => 'dynamic_field_map',
				'limit'				  => 20,
				'exclude_field_types' => array( 'creditcard', 'stripe_creditcard' ),
				'tooltip'             => '<h6>' . esc_html__( 'Metadata', 'gravityformsstripe' ) . '</h6>' . esc_html__( 'You may send custom meta information to Stripe. A maximum of 20 custom keys may be sent. The key name must be 40 characters or less, and the mapped data will be truncated to 500 characters per requirements by Stripe. ' . $info , 'gravityformsstripe' ),
				'validation_callback' => array( $this, 'validate_custom_meta' ),
			),
		);

		// Add meta data field.
		$default_settings = $this->add_field_after( 'customerInformation', $custom_meta, $default_settings );

		// Remove subscription recurring times setting due to lack of Stripe support.
		$default_settings = $this->remove_field( 'recurringTimes', $default_settings );

		// Prepare trial period field.
		$trial_period_field = array(
			'name'                => 'trialPeriod',
			'label'               => esc_html__( 'Trial Period', 'gravityformsstripe' ),
			'style'               => 'width:40px;text-align:center;',
			'type'                => 'trial_period',
			'after_input'         => '&nbsp;' . esc_html__( 'days', 'gravityformsstripe' ),
			'validation_callback' => array( $this, 'validate_trial_period' ),
		);

		// Add trial period field.
		$default_settings = $this->add_field_after( 'trial', $trial_period_field, $default_settings );

		if ( $this->has_stripe_card_field() ) {
			$section_index  = count( $default_settings ) - 1;
			$other_settings = $default_settings[ $section_index ];

			$default_settings[ $section_index ] = array(
				'title'      => esc_html__( 'Stripe Credit Card Field Settings', 'gravityformsstripe' ),
				'dependency' => array(
					'field'  => 'transactionType',
					'values' => array( 'subscription', 'product', 'donation' ),
				),
				'fields'     => array(
					array(
						'name'      => 'billingInformation',
						'label'     => esc_html__( 'Billing Information', 'gravityformsstripe' ),
						'tooltip'   => '<h6>' . esc_html__( 'Billing Information', 'gravityformsstripe' ) . '</h6>' . esc_html__( 'Map your Form Fields to the available listed fields. The address information will be sent to Stripe.', 'gravityformsstripe' ),
						'type'      => 'field_map',
						'field_map' => $this->billing_info_fields(),
					),
				),
			);

			$default_settings[] = $other_settings;
		} elseif ( $this->is_stripe_checkout_enabled() ) {
			$section_index  = count( $default_settings ) - 1;
			$other_settings = $default_settings[ $section_index ];

			$default_settings[ $section_index ] = array(
				'title'       => esc_html__( 'Stripe Payment Form Settings', 'gravityformsstripe' ),
				'description' => esc_html__( 'The following settings control information displayed on the Stripe hosted payment window that is displayed when the form is submitted.', 'gravityformsstripe' ),
				'dependency'  => array(
					'field'  => 'transactionType',
					'values' => array( 'subscription', 'product', 'donation' ),
				),
				'fields'      => array(
					array(
						'name'       => 'logoUrl',
						'label'      => esc_html__( 'Logo URL', 'gravityformsstripe' ),
						'tooltip'    => esc_html__( sprintf( 'A relative or absolute URL pointing to a square image of your brand or product. The recommended minimum size is 128x128px.The supported image types are: %s.', '<strong>.gif</strong>, <strong>.jpeg</strong>, and <strong>.png</strong>' ), 'gravityformsstripe' ),
						'type'       => 'text',
						'input_type' => 'url',
						'class'      => 'medium',
					),
					array(
						'name'          => 'name',
						'label'         => esc_html__( 'Name', 'gravityformsstripe' ),
						'tooltip'       => esc_html__( 'The name of this checkout form. This will show up in the Stripe Checkout modal.', 'gravityformsstripe' ),
						'type'          => 'text',
						'class'         => 'medium merge-tag-support mt-hide_all_fields mt-position-right mt-exclude-entry_id-entry_url-form_id-form_title',
						'default_value' => get_bloginfo( 'name' ),
					),
					array(
						'name'          => 'description',
						'label'         => esc_html__( 'Description', 'gravityformsstripe' ),
						'tooltip'       => esc_html__( 'A description of the product or service being purchased. This will show up in the Stripe Checkout modal.', 'gravityformsstripe' ),
						'type'          => 'text',
						'class'         => 'medium merge-tag-support mt-hide_all_fields mt-position-right mt-exclude-entry_id-entry_url-form_id-form_title',
						'default_value' => esc_html__( 'Stripe Payment', 'gravityformsstripe' ),
					),
					array(
						'name'          => 'billingAddress',
						'label'         => esc_html__( 'Billing Address', 'gravityformsstripe' ),
						'type'          => 'radio',
						'tooltip'       => '<h6>' . esc_html__( 'Billing Address', 'gravityformsstripe' ) . '</h6>' . esc_html__( 'When enabled, Stripe Checkout will collect the customer\'s billing address for you.', 'gravityformsstripe' ),
						'horizontal'    => true,
						'choices'       => array(
							array(
								'label' => esc_html__( 'Enabled', 'gravityformsstripe' ),
								'value' => 1,
							),
							array(
								'label' => esc_html__( 'Disabled', 'gravityformsstripe' ),
								'value' => 0,
							),
						),
						'default_value' => 0,
					),
				),
			);

			$default_settings[] = $other_settings;
		}

		// Add receipt field if the feed transaction type is a product.
		if ( 'product' === $this->get_setting( 'transactionType' ) ) {

			$receipt_settings = array(
				'name'    => 'receipt',
				'label'   => esc_html__( 'Stripe Receipt', 'gravityformsstripe' ),
				'type'    => 'receipt',
				'tooltip' => '<h6>' . esc_html__( 'Stripe Receipt', 'gravityformsstripe' ) . '</h6>' . esc_html__( 'Stripe can send a receipt via email upon payment. Select an email field to enable this feature.', 'gravityformsstripe' ),
			);

			$default_settings = $this->add_field_before( 'conditionalLogic', $receipt_settings, $default_settings );

		}

		return $default_settings;

	}

	/**
	 * Prevent feeds being listed or created if the API keys aren't valid.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFeedAddOn::feed_edit_page()
	 * @used-by GFFeedAddOn::feed_list_message()
	 * @used-by GFFeedAddOn::feed_list_title()
	 * @uses    GFAddOn::get_plugin_settings()
	 * @uses    GFStripe::get_api_mode()
	 *
	 * @return bool True if feed creation is allowed. False otherwise.
	 */
	public function can_create_feed() {

		// Get plugin settings and API mode.
		$settings = $this->get_plugin_settings();
		$api_mode = $this->get_api_mode( $settings );

		// Return valid key state based on API mode.
		if ( 'live' === $api_mode ) {
			return rgar( $settings, 'live_publishable_key_is_valid' ) && rgar( $settings, 'live_secret_key_is_valid' ) && $this->is_webhook_enabled();
		} else {
			return rgar( $settings, 'test_publishable_key_is_valid' ) && rgar( $settings, 'test_secret_key_is_valid' ) && $this->is_webhook_enabled();
		}

	}

	/**
	 * Enable feed duplication on feed list page and during form duplication.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param int|array $id The ID of the feed to be duplicated or the feed object when duplicating a form.
	 *
	 * @return false
	 */
	public function can_duplicate_feed( $id ) {

		return false;

	}

	/**
	 * Define the markup for the field_map setting table header.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return string The header HTML markup.
	 */
	public function field_map_table_header() {
		return '<thead>
					<tr>
						<th></th>
						<th></th>
					</tr>
				</thead>';
	}

	/**
	 * Define the markup for the receipt type field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFAddOn::get_form_fields_as_choices()
	 * @uses GFAddOn::get_current_form()
	 * @uses GFAddOn::settings_select()
	 *
	 * @param array     $field The field properties. Not used.
	 * @param bool|true $echo  Should the setting markup be echoed. Defaults to true.
	 *
	 * @return string|void The HTML markup if $echo is set to false. Void otherwise.
	 */
	public function settings_receipt( $field, $echo = true ) {

		// Prepare first field choice and get form fields as choices.
		$first_choice = array( 'label' => esc_html__( 'Do not send receipt', 'gravityformsstripe' ), 'value' => '' );
		$fields       = $this->get_form_fields_as_choices( $this->get_current_form(), array( 'input_types' => array( 'email', 'hidden' ) ) );

		// Add first choice to the beginning of the fields array.
		array_unshift( $fields, $first_choice );

		// Prepare select field settings.
		$select = array(
			'name'    => 'receipt_field',
			'choices' => $fields,
		);

		// Get select markup.
		$html = $this->settings_select( $select, false );

		// Echo setting markup, if enabled.
		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Define the markup for the setup_fee type field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFPaymentAddOn::get_payment_choices()
	 * @uses GFAddOn::settings_checkbox()
	 * @uses GFAddOn::get_current_form()
	 * @uses GFAddOn::get_setting()
	 * @uses GFAddOn::settings_select()
	 *
	 * @param array     $field The field properties.
	 * @param bool|true $echo  Should the setting markup be echoed. Defaults to true.
	 *
	 * @return string|void The HTML markup if $echo is set to false. Void otherwise.
	 */
	public function settings_setup_fee( $field, $echo = true ) {

		// Prepare checkbox field settings.
		$enabled_field = array(
			'name'       => $field['name'] . '_checkbox',
			'type'       => 'checkbox',
			'horizontal' => true,
			'choices'    => array(
				array(
					'label'    => esc_html__( 'Enabled', 'gravityformsstripe' ),
					'name'     => $field['name'] . '_enabled',
					'value'    => '1',
					'onchange' => "if(jQuery(this).prop('checked')){
						jQuery('#{$field['name']}_product').show('slow');
						jQuery('#gaddon-setting-row-trial, #gaddon-setting-row-trialPeriod').hide('slow');
						jQuery('#trial_enabled').prop( 'checked', false );
						jQuery('#trialPeriod').val( '' );
					} else {
						jQuery('#{$field['name']}_product').hide('slow');
						jQuery('#gaddon-setting-row-trial').show('slow');
					}",
				),
			),
		);

		// Get checkbox field markup.
		$html = $this->settings_checkbox( $enabled_field, false );

		// Get current form.
		$form = $this->get_current_form();

		// Get enabled state.
		$is_enabled = $this->get_setting( "{$field['name']}_enabled" );

		// Prepare setup fee select field settings.
		$product_field = array(
			'name'    => $field['name'] . '_product',
			'type'    => 'select',
			'class'   => $is_enabled ? '' : 'hidden',
			'choices' => $this->get_payment_choices( $form ),
		);

		// Add select field markup to checkbox field markup.
		$html .= '&nbsp' . $this->settings_select( $product_field, false );

		// Echo setting markup, if enabled.
		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Define the markup for the trial type field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFAddOn::settings_checkbox()
	 *
	 * @param array     $field The field properties.
	 * @param bool|true $echo Should the setting markup be echoed. Defaults to true.
	 *
	 * @return string|void The HTML markup if $echo is set to false. Void otherwise.
	 */
	public function settings_trial( $field, $echo = true ) {

		// Prepare enabled field settings.
		$enabled_field = array(
			'name'       => $field['name'] . '_checkbox',
			'type'       => 'checkbox',
			'horizontal' => true,
			'choices'    => array(
				array(
					'label'    => esc_html__( 'Enabled', 'gravityformsstripe' ),
					'name'     => $field['name'] . '_enabled',
					'value'    => '1',
					'onchange' => "if(jQuery(this).prop('checked')){
						jQuery('#gaddon-setting-row-trialPeriod').show('slow');
					} else {
						jQuery('#gaddon-setting-row-trialPeriod').hide('slow');
						jQuery('#trialPeriod').val( '' );
					}",
				),
			),
		);

		// Get checkbox markup.
		$html = $this->settings_checkbox( $enabled_field, false );

		// Echo setting markup, if enabled.
		if ( $echo ) {
			echo $html;
		}

		return $html;
	}

	/**
	 * Define the markup for the trial_period type field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFAddOn::settings_text()
	 * @uses GFAddOn::field_failed_validation()
	 * @uses GFAddOn::get_error_icon()
	 *
	 * @param array     $field The field properties.
	 * @param bool|true $echo  Should the setting markup be echoed. Defaults to true.
	 *
	 * @return string|void The HTML markup if $echo is set to false. Void otherwise.
	 */
	public function settings_trial_period( $field, $echo = true ) {

		// Get text input markup.
		$html = $this->settings_text( $field, false );

		// Prepare validation placeholder name.
		$validation_placeholder = array( 'name' => 'trialValidationPlaceholder' );

		// Add validation indicator.
		if ( $this->field_failed_validation( $validation_placeholder ) ) {
			$html .= '&nbsp;' . $this->get_error_icon( $validation_placeholder );
		}

		// If trial is not enabled and setup fee is enabled, hide field.
		$html .= '
			<script type="text/javascript">
			if( ! jQuery( "#trial_enabled" ).is( ":checked" ) || jQuery( "#setupFee_enabled" ).is( ":checked" ) ) {
				jQuery( "#trial_enabled" ).prop( "checked", false );
				jQuery( "#gaddon-setting-row-trialPeriod" ).hide();
			}
			</script>';

		// Echo setting markup, if enabled.
		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Validate the trial_period type field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFAddOn::get_posted_settings()
	 * @uses GFAddOn::set_field_error()
	 *
	 * @param array $field The field properties. Not used.
	 *
	 * @return void
	 */
	public function validate_trial_period( $field ) {

		// Get posted settings.
		$settings = $this->get_posted_settings();

		// If trial period is not numeric, set field error.
		if ( $settings['trial_enabled'] && ( empty( $settings['trialPeriod'] ) || ! ctype_digit( $settings['trialPeriod'] ) ) ) {
			$this->set_field_error( array( 'name' => 'trialValidationPlaceholder' ), esc_html__( 'Please enter a valid number of days.', 'gravityformsstripe' ) );
		}

	}

	/**
	 * Validate the custom_meta type field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFAddOn::get_posted_settings()
	 * @uses GFAddOn::set_field_error()
	 *
	 * @param array $field The field properties. Not used.
	 *
	 * @return void
	 */
	public function validate_custom_meta( $field ) {

		/*
		 * Number of keys is limited to 20.
		 * Interface should control this, validating just in case.
		 * Key names have maximum length of 40 characters.
		 */

		// Get metadata from posted settings.
		$settings  = $this->get_posted_settings();
		$meta_data = $settings['metaData'];

		// If metadata is not defined, return.
		if ( empty( $meta_data ) ) {
			return;
		}

		// Get number of metadata items.
		$meta_count = count( $meta_data );

		// If there are more than 20 metadata keys, set field error.
		if ( $meta_count > 20 ) {
			$this->set_field_error( array( esc_html__( 'You may only have 20 custom keys.' ), 'gravityformsstripe' ) );
			return;
		}

		// Loop through metadata and check the key name length (custom_key).
		foreach ( $meta_data as $meta ) {
			if ( empty( $meta['custom_key'] ) && ! empty( $meta['value'] ) ) {
				$this->set_field_error( array( 'name' => 'metaData' ), esc_html__( "A field has been mapped to a custom key without a name. Please enter a name for the custom key, remove the metadata item, or return the corresponding drop down to 'Select a Field'.", 'gravityformsstripe' ) );
				break;
			} else if ( strlen( $meta['custom_key'] ) > 40 ) {
				$this->set_field_error( array( 'name' => 'metaData' ), sprintf( esc_html__( 'The name of custom key %s is too long. Please shorten this to 40 characters or less.', 'gravityformsstripe' ), $meta['custom_key'] ) );
				break;
			}
		}

	}

	/**
	 * Define the choices available in the billing cycle dropdowns.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFPaymentAddOn::settings_billing_cycle()
	 *
	 * @return array Billing intervals that are supported.
	 */
	public function supported_billing_intervals() {

		return array(
			'day'   => array( 'label' => esc_html__( 'day(s)', 'gravityformsstripe' ),   'min' => 1, 'max' => 365 ),
			'week'  => array( 'label' => esc_html__( 'week(s)', 'gravityformsstripe' ),  'min' => 1, 'max' => 12 ),
			'month' => array( 'label' => esc_html__( 'month(s)', 'gravityformsstripe' ), 'min' => 1, 'max' => 12 ),
			'year'  => array( 'label' => esc_html__( 'year(s)', 'gravityformsstripe' ),  'min' => 1, 'max' => 1 ),
		);

	}

	/**
	 * Prevent the 'options' checkboxes setting being included on the feed.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFPaymentAddOn::other_settings_fields()
	 *
	 * @return false
	 */
	public function option_choices() {
		return false;
	}



	// # FORM SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Add supported notification events.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFeedAddOn::notification_events()
	 * @uses    GFFeedAddOn::has_feed()
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return array|false The supported notification events. False if feed cannot be found within $form.
	 */
	public function supported_notification_events( $form ) {

		// If this form does not have a Stripe feed, return false.
		if ( ! $this->has_feed( $form['id'] ) ) {
			return false;
		}

		// Return Stripe notification events.
		return array(
			'complete_payment'          => esc_html__( 'Payment Completed', 'gravityformsstripe' ),
			'refund_payment'            => esc_html__( 'Payment Refunded', 'gravityformsstripe' ),
			'fail_payment'              => esc_html__( 'Payment Failed', 'gravityformsstripe' ),
			'create_subscription'       => esc_html__( 'Subscription Created', 'gravityformsstripe' ),
			'cancel_subscription'       => esc_html__( 'Subscription Canceled', 'gravityformsstripe' ),
			'add_subscription_payment'  => esc_html__( 'Subscription Payment Added', 'gravityformsstripe' ),
			'fail_subscription_payment' => esc_html__( 'Subscription Payment Failed', 'gravityformsstripe' ),
		);

	}





	// # FRONTEND ------------------------------------------------------------------------------------------------------

	/**
	 * Initialize the frontend hooks.
	 *
	 * @since  2.6 Added more filters per Stripe Elements and Stripe Checkout.
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFStripe::register_init_scripts()
	 * @uses GFStripe::add_stripe_inputs()
	 * @uses GFStripe::pre_validation()
	 * @uses GFStripe::populate_credit_card_last_four()
	 * @uses GFPaymentAddOn::init()
	 *
	 * @return void
	 */
	public function init() {

		add_filter( 'gform_register_init_scripts', array( $this, 'register_init_scripts' ), 10, 3 );
		add_filter( 'gform_field_content', array( $this, 'add_stripe_inputs' ), 10, 5 );
		add_filter( 'gform_field_validation', array( $this, 'pre_validation' ), 10, 4 );
		add_filter( 'gform_pre_submission', array( $this, 'populate_credit_card_last_four' ) );
		add_filter( 'gform_field_css_class', array( $this, 'stripe_card_field_css_class' ), 10, 3 );
		add_filter( 'gform_submission_values_pre_save', array( $this, 'stripe_card_submission_value_pre_save' ), 10, 3 );

		// Supports frontend feeds.
		$this->_supports_frontend_feeds = true;

		if ( $this->get_plugin_setting( 'checkout_method' ) !== 'credit_card' ) {
			$this->_requires_credit_card = false;
		}

		if ( $this->is_stripe_checkout_enabled() ) {
			// Stripe Checkout doesn't require a CC field, so we need to validate card types with a separate function.
			add_filter( 'gform_validation', array( $this, 'card_type_validation' ) );
			// Stripe Checkout doesn't require a CC field, so we can't populate the response with populate_credit_card_last_four().
			// hence populate stripe response with another function (this will happen when form validation fails).
			add_filter( 'gform_form_tag', array( $this, 'populate_stripe_response' ) );
		}

		parent::init();

	}

	/**
	 * Register Stripe script when displaying form.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::init()
	 * @uses    GFFeedAddOn::has_feed()
	 * @uses    GFPaymentAddOn::get_credit_card_field()
	 * @uses    GFStripe::get_publishable_api_key()
	 * @uses    GFStripe::get_card_labels()
	 * @uses    GFFormDisplay::add_init_script()
	 * @uses    GFFormDisplay::ON_PAGE_RENDER
	 *
	 * @param array $form         Form object.
	 * @param array $field_values Current field values. Not used.
	 * @param bool  $is_ajax      If form is being submitted via AJAX.
	 *
	 * @return void
	 */
	public function register_init_scripts( $form, $field_values, $is_ajax ) {

		if ( ! $this->frontend_script_callback( $form ) ) {
			return;
		}

		// Prepare Stripe Javascript arguments.
		$args = array(
			'apiKey'         => $this->get_publishable_api_key(),
			'formId'         => $form['id'],
			'isAjax'         => $is_ajax,
			'stripe_payment' => ( $this->has_stripe_card_field( $form ) ) ? 'elements' : ( ( $this->is_stripe_checkout_enabled() && ! $this->has_credit_card_field( $form ) ) ? 'checkout' : 'stripe.js' ),
		);

		if ( $this->has_stripe_card_field( $form ) ) {
			$cc_field = $this->get_stripe_card_field( $form );
		} elseif ( $this->has_credit_card_field( $form ) ) {
			$cc_field = $this->get_credit_card_field( $form );
		}

		// Starts from 2.6, CC field isn't required when Stripe Checkout enabled.
		if ( isset( $cc_field ) ) {
			$args['ccFieldId']  = $cc_field->id;
			$args['ccPage']     = $cc_field->pageNumber;
			$args['cardLabels'] = $this->get_card_labels();
		}

		// getting all Stripe feeds.
		$args['currency'] = gf_apply_filters( array( 'gform_currency_pre_save_entry', $form['id'] ), GFCommon::get_currency(), $form );
		$feeds            = $this->get_feeds_by_slug( $this->_slug, $form['id'] );
		$args['feeds']    = array();
		if ( $this->has_stripe_card_field( $form ) ) {
			// Add options when creating Stripe Elements.
			$args['cardClasses'] = apply_filters( 'gform_stripe_elements_classes', array(), $form['id'] );
			$args['cardStyle']   = apply_filters( 'gform_stripe_elements_style', array(), $form['id'] );
			foreach ( $feeds as $feed ) {
				$args['feeds'][] = array(
					'feedId'          => $feed['id'],
					'address_line1'   => rgars( $feed, 'meta/billingInformation_address_line1' ),
					'address_line2'   => rgars( $feed, 'meta/billingInformation_address_line2' ),
					'address_city'    => rgars( $feed, 'meta/billingInformation_address_city' ),
					'address_state'   => rgars( $feed, 'meta/billingInformation_address_state' ),
					'address_zip'     => rgars( $feed, 'meta/billingInformation_address_zip' ),
					'address_country' => rgars( $feed, 'meta/billingInformation_address_country' ),
				);
			}
		} elseif ( $this->is_stripe_checkout_enabled() ) {
			foreach ( $feeds as $feed ) {
				if ( rgar( $feed, 'is_active' ) === '0' ) {
					continue;
				}

				$transaction_type = rgars( $feed, 'meta/transactionType' );

				$feed_settings = array(
					'feedId'         => $feed['id'],
					'logoUrl'        => rgars( $feed, 'meta/logoUrl' ),
					'name'           => GFCommon::replace_variables_prepopulate( rgars( $feed, 'meta/name' ) ),
					'description'    => GFCommon::replace_variables_prepopulate( rgars( $feed, 'meta/description' ) ),
					'billingAddress' => boolval( rgars( $feed, 'meta/billingAddress' ) ),
				);

				if ( $transaction_type === 'product' ) {
					$feed_settings['paymentAmount'] = rgars( $feed, 'meta/paymentAmount' );
				} else {
					$feed_settings['paymentAmount'] = rgars( $feed, 'meta/recurringAmount' );
					if ( rgars( $feed, 'meta/setupFee_enabled' ) ) {
						$feed_settings['setupFee'] = rgars( $feed, 'meta/setupFee_product' );
					}
				}

				$args['feeds'][] = $feed_settings;
			}
		}

		// Initialize Stripe script.
		$args   = apply_filters( 'gform_stripe_object', $args, $form['id'] );
		$script = 'new GFStripe( ' . json_encode( $args, JSON_FORCE_OBJECT ) . ' );';

		// Add Stripe script to form scripts.
		GFFormDisplay::add_init_script( $form['id'], 'stripe', GFFormDisplay::ON_PAGE_RENDER, $script );
	}

	/**
	 * Check if the form has an active Stripe feed and a credit card field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::scripts()
	 * @uses    GFFeedAddOn::has_feed()
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return bool If the script should be enqueued.
	 */
	public function frontend_script_callback( $form ) {

		// Starts from 2.6, CC field isn't required when Stripe Checkout enabled.
		return $form && $this->has_feed( $form['id'] ) && ( ( ! $this->is_stripe_checkout_enabled() && ( $this->has_stripe_card_field( $form ) || $this->has_credit_card_field( $form ) ) ) || $this->is_stripe_checkout_enabled() );

	}

	/**
	 * Check if we should display the Stripe JS.
	 *
	 * @since  2.6
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return bool If the script should be enqueued.
	 */
	public function stripe_js_callback( $form ) {
		return $form && $this->has_feed( $form['id'] ) && $this->has_credit_card_field( $form ) && ! $this->has_stripe_card_field( $form );
	}

	/**
	 * Check if we should display the Stripe Elements JS.
	 *
	 * @since  2.6
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return bool If the script should be enqueued.
	 */
	public function stripe_elements_callback( $form ) {
		return $form && $this->has_feed( $form['id'] ) && $this->has_stripe_card_field( $form );
	}

	/**
	 * Check if we should display the Stripe Checkout JS.
	 *
	 * @since  2.6
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return bool If the script should be enqueued.
	 */
	public function stripe_checkout_callback( $form ) {
	    // When a form has Stripe feeds but without any CC field, we enqueue the Stripe Checkout script.
		return $form && $this->has_feed( $form['id'] ) && ( ! $this->has_credit_card_field( $form ) && ! $this->has_stripe_card_field( $form ) );
	}

	/**
	 * Check if the form has an active Stripe feed and Stripe Elements is enabled
	 *
	 * @since  2.6
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return bool True if the style should be enqueued, false otherwise.
	 */
	public function frontend_style_callback( $form ) {
		return $form && $this->has_feed( $form['id'] ) && ( $this->has_credit_card_field( $form ) || $this->has_stripe_card_field( $form ) );
	}

	/**
	 * Add required Stripe inputs to form.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::init()
	 * @uses    GFFeedAddOn::has_feed()
	 * @uses    GFStripe::get_stripe_js_response()
	 *
	 * @param string  $content The field content to be filtered.
	 * @param object  $field   The field that this input tag applies to.
	 * @param string  $value   The default/initial value that the field should be pre-populated with.
	 * @param integer $lead_id When executed from the entry detail screen, $lead_id will be populated with the Entry ID.
	 * @param integer $form_id The current Form ID.
	 *
	 * @return string $content HTML formatted content.
	 */
	public function add_stripe_inputs( $content, $field, $value, $lead_id, $form_id ) {

		// If this form does not have a Stripe feed or if this is not a credit card field, return field content.
		if ( ! $this->has_feed( $form_id ) || ( $field->get_input_type() !== 'creditcard' && $field->get_input_type() !== 'stripe_creditcard' ) ) {
			return $content;
		}

		// If a Stripe response exists, populate it to a hidden field.
		if ( $this->get_stripe_js_response() ) {
			$content .= '<input type=\'hidden\' name=\'stripe_response\' id=\'gf_stripe_response\' value=\'' . rgpost( 'stripe_response' ) . '\' />';
		}

		// If the last four credit card digits are provided by Stripe, populate it to a hidden field.
		if ( rgpost( 'stripe_credit_card_last_four' ) ) {
			$content .= '<input type="hidden" name="stripe_credit_card_last_four" id="gf_stripe_credit_card_last_four" value="' . rgpost( 'stripe_credit_card_last_four' ) . '" />';
		}

		// If the  credit card type is provided by Stripe, populate it to a hidden field.
		if ( rgpost( 'stripe_credit_card_type' ) ) {
			$content .= '<input type="hidden" name="stripe_credit_card_type" id="stripe_credit_card_type" value="' . rgpost( 'stripe_credit_card_type' ) . '" />';
		}

		if ( $field->get_input_type() === 'creditcard' && ! $this->has_stripe_card_field( GFAPI::get_form( $form_id ) ) ) {
			// Remove name attribute from credit card field inputs for security.
			// Removes: name='input_2.1', name='input_2.2[]', name='input_2.3', name='input_2.5', where 2 is the credit card field id.
			$content = preg_replace( "/name=\'input_{$field->id}\.([135]|2\[\])\'/", '', $content );
		}

		return $content;

	}

	/**
	 * Validate the card type and prevent the field from failing required validation, Stripe.js will handle the required validation.
	 *
	 * The card field inputs are erased on submit, this will cause two issues:
	 * 1. The field will fail standard validation if marked as required.
	 * 2. The card type validation will not be performed.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::init()
	 * @uses    GF_Field_CreditCard::is_card_supported()
	 * @uses    GFStripe::get_card_slug()
	 *
	 * @param array    $result The field validation result and message.
	 * @param mixed    $value  The field input values; empty for the credit card field as they are cleared by frontend.js.
	 * @param array    $form   The Form currently being processed.
	 * @param GF_Field $field  The field currently being processed.
	 *
	 * @return array $result The results of the validation.
	 */
	public function pre_validation( $result, $value, $form, $field ) {

		// If this is a credit card field and the last four credit card digits are defined, validate.
		if ( $field->type == 'creditcard' && rgpost( 'stripe_credit_card_last_four' ) ) {

			// Get card type.
			$card_type = rgpost( 'stripe_credit_card_type' );
			if ( ! $card_type || $card_type === 'false' ) {
				$card_type = __( 'Unknown', 'gravityformsstripe' );
			}

			// Get card slug.
			$card_slug = $this->get_card_slug( $card_type );

			// If credit card type is not supported, mark field as invalid.
			if ( $field->type == 'creditcard' && ! $field->is_card_supported( $card_slug ) ) {
				$result['is_valid'] = false;
				$result['message']  = sprintf( esc_html__( 'Card type (%s) is not supported. Please enter one of the supported credit cards.', 'gravityformsstripe' ), $card_type );
			} else {
				$result['is_valid'] = true;
				$result['message']  = '';
			}
		}

		return $result;

	}

	/**
	 * Validate if the card type is supported.
	 *
	 * @since 2.6.0
	 *
	 * @param array $validation_result The results of the validation.
	 *
	 * @return array $validation_result The results of the validation.
	 */
	public function card_type_validation( $validation_result ) {

		if ( rgpost( 'stripe_credit_card_last_four' ) ) {

			// Get card type.
			$card_type = rgpost( 'stripe_credit_card_type' );
			if ( ! $card_type || 'false' === $card_type ) {
				$card_type = __( 'Unknown', 'gravityformsstripe' );
			}

			// Get card slug.
			$card_slug = $this->get_card_slug( $card_type );

			// Use a filter `gform_stripe_checkout_supported_cards` to set the supported cards.
			// By default (when it's empty), allows all card types Stripe supports.
			// Possible value could be: array( 'amex', 'discover', 'mastercard', 'visa' );.
			$supported_cards = apply_filters( 'gform_stripe_checkout_supported_cards', array() );
			if ( ! empty( $supported_cards ) && ! in_array( $card_slug, $supported_cards, true ) ) {
				$validation_result['is_valid']               = false;
				$validation_result['failed_validation_page'] = GFFormDisplay::get_max_page_number( $validation_result['form'] );

				add_filter( 'gform_validation_message', array( $this, 'card_type_validation_message' ), 10, 2 );

				$this->log_debug( __METHOD__ . '(): The gform_stripe_checkout_supported_cards filter was used; the card type wasn\'t supported.' );

				// empty Stripe response so we can trigger Stripe Checkout modal again.
				$_POST['stripe_response'] = '';
			}
		}

		return $validation_result;
	}

	/**
	 * Display card type validation error message.
	 *
	 * @since 2.6.0
	 *
	 * @param string $message HTML message string.
	 * @param array  $form Form object.
	 *
	 * @return string
	 */
	public function card_type_validation_message( $message, $form ) {

		$card_type = rgpost( 'stripe_credit_card_type' );
		if ( ! $card_type || 'false' === $card_type ) {
			$card_type = __( 'Unknown', 'gravityformsstripe' );
		}

		$message .= "<div class='validation_error'>" . sprintf( esc_html__( 'Card type (%s) is not supported. Please enter one of the supported credit cards.', 'gravityformsstripe' ), $card_type ) . '</div>';

		return $message;
	}

	/**
	 * Display card type validation error message.
	 *
	 * @since 2.6.1
	 *
	 * @param string $message HTML message string.
	 *
	 * @return string
	 */
	public function stripe_checkout_error_message( $message ) {
		$authorization_result = $this->authorization;

		$message .= "<div class='validation_error'>" . $authorization_result['error_message'] . '</div>';

		return $message;
	}

	// # STRIPE TRANSACTIONS -------------------------------------------------------------------------------------------

	/**
	 * Initialize authorizing the transaction for the product & services type feed or return the Stripe.js error.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFStripe::include_stripe_api()
	 * @uses GFStripe::get_stripe_js_error()
	 * @uses GFStripe::authorization_error()
	 * @uses GFStripe::authorize_product()
	 *
	 * @param array $feed            The feed object currently being processed.
	 * @param array $submission_data The customer and transaction data.
	 * @param array $form            The form object currently being processed.
	 * @param array $entry           The entry object currently being processed.
	 *
	 * @return array Authorization and transaction ID if authorized. Otherwise, exception.
	 */
	public function authorize( $feed, $submission_data, $form, $entry ) {

		// Include Stripe API library.
		$this->include_stripe_api();

		// If there was an error when retrieving the Stripe.js token, return an authorization error.
		if ( $this->get_stripe_js_error() ) {
			return $this->authorization_error( $this->get_stripe_js_error() );
		}

		// Authorize product.
		return $this->authorize_product( $feed, $submission_data, $form, $entry );

	}

	/**
	 * Create the Stripe charge authorization and return any authorization errors which occur.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::authorize()
	 * @uses    GFStripe::get_stripe_js_response()
	 * @uses    GFPaymentAddOn::get_amount_export()
	 * @uses    GFStripe::get_payment_description()
	 * @uses    GFStripe::get_customer()
	 * @uses    GFAddOn::get_field_value()
	 * @uses    GFStripe::get_stripe_meta_data()
	 * @uses    GFAddOn::log_debug()
	 * @uses    \Stripe\Charge::create()
	 * @uses    GFPaymentAddOn::authorization_error()
	 *
	 * @param array $feed            The feed object currently being processed.
	 * @param array $submission_data The customer and transaction data.
	 * @param array $form            The form object currently being processed.
	 * @param array $entry           The entry object currently being processed.
	 *
	 * @return array Authorization and transaction ID if authorized. Otherwise, exception.
	 */
	public function authorize_product( $feed, $submission_data, $form, $entry ) {

		try {

			// Get Stripe.js token.
			$stripe_response = $this->get_stripe_js_response();

			// Prepare Stripe charge meta.
			$charge_meta = array(
				'amount'      => $this->get_amount_export( $submission_data['payment_amount'], rgar( $entry, 'currency' ) ),
				'currency'    => rgar( $entry, 'currency' ),
				'description' => $this->get_payment_description( $entry, $submission_data, $feed ),
				'capture'     => false,
			);

			$customer = $this->get_customer( '', $feed, $entry, $form );

			if ( $customer ) {
				// Update the customer source with the Stripe token.
				$customer->source = $stripe_response->id;
				$customer->save();

				// Add the customer id to the charge meta.
				$charge_meta['customer'] = $customer->id;
			} else {
				// Add the Stripe token to the charge meta.
				$charge_meta['source'] = $stripe_response->id;
			}

			// If receipt field is defined, add receipt email address to charge meta.
			$receipt_field = rgars( $feed, 'meta/receipt_field' );
			if ( ! empty( $receipt_field ) && strtolower( $receipt_field ) !== 'do not send receipt' ) {
				$charge_meta['receipt_email'] = $this->get_field_value( $form, $entry, $receipt_field );
			}

			// Get Stripe metadata for feed.
			$metadata = $this->get_stripe_meta_data( $feed, $entry, $form );

			// If metadata was defined, add it to charge meta.
			if ( ! empty( $metadata ) ) {
				$charge_meta['metadata'] = $metadata;
			}

			/**
			 * Allow the charge properties to be overridden before the charge is created by the Stripe API.
			 *
			 * @since 2.2.2
			 *
			 * @param array $charge_meta     The properties for the charge to be created.
			 * @param array $feed            The feed object currently being processed.
			 * @param array $submission_data The customer and transaction data.
			 * @param array $form            The form object currently being processed.
			 * @param array $entry           The entry object currently being processed.
			 */
			$charge_meta = apply_filters( 'gform_stripe_charge_pre_create', $charge_meta, $feed, $submission_data, $form, $entry );

			// Log the charge we're about to process.
			$this->log_debug( __METHOD__ . '(): Charge meta to be created => ' . print_r( $charge_meta, 1 ) );

			// Charge customer.
			$charge = \Stripe\Charge::create( $charge_meta );

			// Get authorization data from charge.
			$auth = array(
				'is_authorized'  => true,
				'transaction_id' => $charge['id'],
			);

		} catch ( \Exception $e ) {

			// Set authorization data to error.
			$auth = $this->authorization_error( $e->getMessage() );

		}

		return $auth;

	}

	/**
	 * Handle cancelling the subscription from the entry detail page.
	 *
	 * @since 2.8 Updated to use the subscription object instead of the customer object.
	 * @since Unknown
	 *
	 * @param array $entry The entry object currently being processed.
	 * @param array $feed  The feed object currently being processed.
	 *
	 * @return bool True if successful. False if failed.
	 */
	public function cancel( $entry, $feed ) {

		// Include Stripe API library.
		$this->include_stripe_api();

		if ( empty( $entry['transaction_id'] ) ) {
			return false;
		}

		// Get Stripe subscription object.
		$subscription = $this->get_subscription( $entry['transaction_id'] );

		if ( ! $subscription ) {
			return false;
		}

		if ( $subscription->status === 'canceled' ) {
			$this->log_debug( __METHOD__ . '(): Subscription already cancelled.' );

			return true;
		}

		/**
		 * Allow the cancellation of the subscription to be delayed until the end of the current period.
		 *
		 * @since 2.1.0
		 *
		 * @param bool  $at_period_end Defaults to false, subscription will be cancelled immediately.
		 * @param array $entry         The entry from which the subscription was created.
		 * @param array $feed          The feed object which processed the current entry.
		 */
		$at_period_end = apply_filters( 'gform_stripe_subscription_cancel_at_period_end', false, $entry, $feed );

		try {

			if ( $at_period_end ) {

				$this->log_debug( __METHOD__ . '(): The gform_stripe_subscription_cancel_at_period_end filter was used; cancelling subscription at period end.' );
				$subscription->cancel_at_period_end = true;
				$subscription->save();
				$this->log_debug( __METHOD__ . '(): Subscription updated.' );

			} else {

				$subscription->cancel();
				$this->log_debug( __METHOD__ . '(): Subscription cancelled.' );

			}

			return true;

		} catch ( \Exception $e ) {

			$action = $at_period_end ? 'update' : 'cancel';

			// Log error.
			$this->log_error( sprintf( '%s(): Unable to %s subscription; %s', __METHOD__, $action, $e->getMessage() ) );

			return false;

		}

	}

	/**
	 * Gets the payment validation result.
	 *
	 * @since  2.6
	 *
	 * @param array $validation_result    Contains the form validation results.
	 * @param array $authorization_result Contains the form authorization results.
	 *
	 * @return array The validation result for the credit card field.
	 */
	public function get_validation_result( $validation_result, $authorization_result ) {
	    if ( empty( $authorization_result['error_message'] ) ) {
	        return $validation_result;
        }

		$credit_card_page   = 0;
		$has_error_cc_field = false;
		foreach ( $validation_result['form']['fields'] as &$field ) {
			if ( $field->type === 'creditcard' || $field->type === 'stripe_creditcard' ) {
				$has_error_cc_field        = true;
				$field->failed_validation  = true;
				$field->validation_message = $authorization_result['error_message'];
				$credit_card_page          = $field->pageNumber;
				break;
			}
		}

		if ( ! $has_error_cc_field && $this->is_stripe_checkout_enabled() ) {
			$credit_card_page = GFFormDisplay::get_max_page_number( $validation_result['form'] );
			add_filter( 'gform_validation_message', array( $this, 'stripe_checkout_error_message' ) );
		}

		$validation_result['credit_card_page'] = $credit_card_page;
		$validation_result['is_valid']         = false;

		return $validation_result;
	}

	/**
	 * Capture the Stripe charge which was authorized during validation.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFStripe::get_stripe_meta_data()
	 * @uses GFStripe::get_payment_description()
	 * @uses \Stripe\Charge::retrieve()
	 * @uses \Stripe\Charge::save()
	 * @uses GFAddOn::log_debug()
	 * @uses \Stripe\Charge::capture()
	 * @uses GFPaymentAddOn::get_amount_import()
	 * @uses Exception::getMessage()
	 *
	 * @param array $auth            Contains the result of the authorize() function.
	 * @param array $feed            The feed object currently being processed.
	 * @param array $submission_data The customer and transaction data.
	 * @param array $form            The form object currently being processed.
	 * @param array $entry           The entry object currently being processed.
	 *
	 * @return array $payment Contains payment details. If failed, shows failure message.
	 */
	public function capture( $auth, $feed, $submission_data, $form, $entry ) {

		// Get Stripe charge from authorization.
		$charge = \Stripe\Charge::retrieve( $auth['transaction_id'] );

		try {

			// Set charge description and metadata.
			$charge->description = $this->get_payment_description( $entry, $submission_data, $feed );

			$metadata = $this->get_stripe_meta_data( $feed, $entry, $form );
			if ( ! empty( $metadata ) ) {
				$charge->metadata = $metadata;
			}

			// Save charge.
			$charge->save();

			/**
			 * Allow authorization only transactions by preventing the capture request from being made after the entry has been saved.
			 *
			 * @since 2.1.0
			 *
			 * @param bool  false            Defaults to false, return true to prevent payment being captured.
			 * @param array $feed            The feed object currently being processed.
			 * @param array $submission_data The customer and transaction data.
			 * @param array $form            The form object currently being processed.
			 * @param array $entry           The entry object currently being processed.
			 */
			$authorization_only = apply_filters( 'gform_stripe_charge_authorization_only', false, $feed, $submission_data, $form, $entry );

			if ( $authorization_only ) {
				$this->log_debug( __METHOD__ . '(): The gform_stripe_charge_authorization_only filter was used to prevent capture.' );

				return array();
			}

			// Capture the charge.
			$charge = $charge->capture();

			// Prepare payment details.
			$payment = array(
				'is_success'     => true,
				'transaction_id' => $charge->id,
				'amount'         => $this->get_amount_import( $charge->amount, $entry['currency'] ),
				'payment_method' => rgpost( 'stripe_credit_card_type' ),
			);

		} catch ( \Exception $e ) {

			// Log that charge could not be captured.
			$this->log_error( __METHOD__ . '(): Unable to capture charge; ' . $e->getMessage() );

			// Prepare payment details.
			$payment = array(
				'is_success'    => false,
				'error_message' => $e->getMessage(),
			);

		}

		return $payment;
	}

	/**
	 * Update the entry meta with the Stripe Customer ID.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFStripe::get_stripe_meta_data()
	 * @uses GFStripe::get_customer()
	 * @uses GFPaymentAddOn::process_subscription()
	 * @uses \Stripe\Customer::save()
	 * @uses \Exception::getMessage()
	 * @uses GFAddOn::log_error()
	 *
	 * @param array $authorization   Contains the result of the subscribe() function.
	 * @param array $feed            The feed object currently being processed.
	 * @param array $submission_data The customer and transaction data.
	 * @param array $form            The form object currently being processed.
	 * @param array $entry           The entry object currently being processed.
	 *
	 * @return array The entry object.
	 */
	public function process_subscription( $authorization, $feed, $submission_data, $form, $entry ) {

		// Update customer ID for entry.
		gform_update_meta( $entry['id'], 'stripe_customer_id', $authorization['subscription']['customer_id'] );

		$metadata = $this->get_stripe_meta_data( $feed, $entry, $form );
		if ( ! empty( $metadata ) ) {

			// Update to user meta post entry creation so entry ID is available.
			try {

				// Get customer.
				$customer = $this->get_customer( $authorization['subscription']['customer_id'] );

				// Update customer metadata.
				$customer->metadata = $metadata;

				// Save customer.
				$customer->save();

			} catch ( \Exception $e ) {

				// Log that we could not save customer.
				$this->log_error( __METHOD__ . '(): Unable to save customer; ' . $e->getMessage() );

			}

		}

		return parent::process_subscription( $authorization, $feed, $submission_data, $form, $entry );

	}

	/**
	 * Subscribe the user to a Stripe plan. This process works like so:
	 *
	 * 1 - Get existing plan or create new plan (plan ID generated by feed name, id and recurring amount).
	 * 2 - Create new customer.
	 * 3 - Create new subscription by subscribing customer to plan.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFStripe::include_stripe_api()
	 * @uses GFStripe::get_stripe_js_error()
	 * @uses GFPaymentAddOn::authorization_error()
	 * @uses GFStripe::get_subscription_plan_id()
	 * @uses GFStripe::get_plan()
	 * @uses GFStripe::get_stripe_js_response()
	 * @uses GFStripe::create_plan()
	 * @uses GFStripe::get_customer()
	 * @uses GFAddOn::log_debug()
	 * @uses GFPaymentAddOn::get_amount_export()
	 * @uses GFAddOn::get_field_value()
	 * @uses GFStripe::get_stripe_meta_data()
	 * @uses GFAddOn::maybe_override_field_value()
	 * @uses GFStripe::create_customer()
	 * @uses \Stripe\Customer::save()
	 * @uses \Stripe\Customer::updateSubscription()
	 * @uses \Stripe\Customer::addInvoiceItem()
	 *
	 * @param array $feed            The feed object currently being processed.
	 * @param array $submission_data The customer and transaction data.
	 * @param array $form            The form object currently being processed.
	 * @param array $entry           The entry object currently being processed.
	 *
	 * @return array Subscription details if successful. Contains error message if failed.
	 */
	public function subscribe( $feed, $submission_data, $form, $entry ) {

		// Include Stripe API library.
		$this->include_stripe_api();

		// If there was an error when retrieving the Stripe.js token, return an authorization error.
		if ( $this->get_stripe_js_error() ) {
			return $this->authorization_error( $this->get_stripe_js_error() );
		}

		// Prepare payment amount and trial period data.
		$payment_amount        = $submission_data['payment_amount'];
		$single_payment_amount = $submission_data['setup_fee'];
		$trial_period_days     = rgars( $feed, 'meta/trialPeriod' ) ? $submission_data['trial'] : null;
		$currency              = rgar( $entry, 'currency' );

		// Get Stripe plan for feed.
		$plan_id = $this->get_subscription_plan_id( $feed, $payment_amount, $trial_period_days, $currency );
		$plan    = $this->get_plan( $plan_id );

		// If error was returned when retrieving plan, return plan.
		if ( rgar( $plan, 'error_message' ) ) {
			return $plan;
		}

		try {

			// If plan does not exist, create it.
			if ( ! $plan ) {
				$plan = $this->create_plan( $plan_id, $feed, $payment_amount, $trial_period_days, $currency );
			}

			// Get Stripe.js token.
			$stripe_response = $this->get_stripe_js_response();

			$customer = $this->get_customer( '', $feed, $entry, $form );

			if ( $customer ) {

				$this->log_debug( __METHOD__ . '(): Updating existing customer.' );

				// Update the customer source with the Stripe token.
				$customer->source = $stripe_response->id;
				$customer->save();

				// If a setup fee is required, add an invoice item.
				if ( $single_payment_amount ) {
					$setup_fee = array(
						'amount'   => $this->get_amount_export( $single_payment_amount, $currency ),
						'currency' => $currency,
					);
					$customer->addInvoiceItem( $setup_fee );
				}

			} else {

				// Prepare customer metadata.
				$customer_meta = array(
					'description'     => $this->get_field_value( $form, $entry, rgar( $feed['meta'], 'customerInformation_description' ) ),
					'email'           => $this->get_field_value( $form, $entry, rgar( $feed['meta'], 'customerInformation_email' ) ),
					'source'          => $stripe_response->id,
					'account_balance' => $this->get_amount_export( $single_payment_amount, $currency ),
					'metadata'        => $this->get_stripe_meta_data( $feed, $entry, $form ),
				);

				// Get coupon for feed.
				$coupon_field_id = rgar( $feed['meta'], 'customerInformation_coupon' );
				$coupon          = $this->maybe_override_field_value( rgar( $entry, $coupon_field_id ), $form, $entry, $coupon_field_id );

				// If coupon is set, add it to customer metadata.
				if ( $coupon ) {
					$customer_meta['coupon'] = $coupon;
				}

				$customer = $this->create_customer( $customer_meta, $feed, $entry, $form );

			}

			// Add subscription to customer and retrieve the subscription ID.
			$subscription_id = $this->update_subscription( $customer, $plan, $feed, $entry, $form, $trial_period_days );

		} catch ( \Exception $e ) {

			// Return authorization error.
			return $this->authorization_error( $e->getMessage() );

		}

		// Return subscription data.
		return array(
			'is_success'      => true,
			'subscription_id' => $subscription_id,
			'customer_id'     => $customer->id,
			'amount'          => $payment_amount,
		);

	}

	// # STRIPE HELPER FUNCTIONS ---------------------------------------------------------------------------------------

	/**
	 * Retrieve a specific customer from Stripe.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::authorize_product()
	 * @used-by GFStripe::cancel()
	 * @used-by GFStripe::process_subscription()
	 * @used-by GFStripe::subscribe()
	 * @uses    GFAddOn::log_debug()
	 * @uses    \Stripe\Customer::retrieve()
	 *
	 * @param string $customer_id The identifier of the customer to be retrieved.
	 * @param array  $feed        The feed currently being processed.
	 * @param array  $entry       The entry currently being processed.
	 * @param array  $form        The which created the current entry.
	 *
	 * @return bool|\Stripe\Customer Contains customer data if available. Otherwise, false.
	 */
	public function get_customer( $customer_id, $feed = array(), $entry = array(), $form = array() ) {
		if ( empty( $customer_id ) && has_filter( 'gform_stripe_customer_id' ) ) {
			$this->log_debug( __METHOD__ . '(): Executing functions hooked to gform_stripe_customer_id.' );

			/**
			 * Allow an existing customer ID to be specified for use when processing the submission.
			 *
			 * @since  2.1.0
			 * @access public
			 *
			 * @param string $customer_id The identifier of the customer to be retrieved. Default is empty string.
			 * @param array  $feed        The feed currently being processed.
			 * @param array  $entry       The entry currently being processed.
			 * @param array  $form        The form which created the current entry.
			 */
			$customer_id = apply_filters( 'gform_stripe_customer_id', $customer_id, $feed, $entry, $form );
		}

		if ( $customer_id ) {
			$this->log_debug( __METHOD__ . '(): Retrieving customer id => ' . print_r( $customer_id, 1 ) );
			$customer = \Stripe\Customer::retrieve( $customer_id );

			return $customer;
		}

		return false;
	}

	/**
	 * Create and return a Stripe customer with the specified properties.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::subscribe()
	 * @uses    GFAddOn::log_debug()
	 * @uses    \Stripe\Customer::create()
	 *
	 * @param array $customer_meta The customer properties.
	 * @param array $feed          The feed currently being processed.
	 * @param array $entry         The entry currently being processed.
	 * @param array $form          The form which created the current entry.
	 *
	 * @return \Stripe\Customer The Stripe customer object.
	 */
	public function create_customer( $customer_meta, $feed, $entry, $form ) {

		// Log the customer to be created.
		$this->log_debug( __METHOD__ . '(): Customer meta to be created => ' . print_r( $customer_meta, 1 ) );

		// Create customer.
		$customer = \Stripe\Customer::create( $customer_meta );

		if ( has_filter( 'gform_stripe_customer_after_create' ) ) {
			// Log that filter will be executed.
			$this->log_debug( __METHOD__ . '(): Executing functions hooked to gform_stripe_customer_after_create.' );

			/**
			 * Allow custom actions to be performed between the customer being created and subscribed to the plan.
			 *
			 * @since 2.0.1
			 *
			 * @param Stripe\Customer $customer The Stripe customer object.
			 * @param array           $feed     The feed currently being processed.
			 * @param array           $entry    The entry currently being processed.
			 * @param array           $form     The form currently being processed.
			 */
			do_action( 'gform_stripe_customer_after_create', $customer, $feed, $entry, $form );
		}

		return $customer;
	}

	/**
	 * Try and retrieve the plan if a plan with the matching id has previously been created.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::subscribe()
	 * @uses    \Stripe\Plan::retrieve()
	 * @uses    GFPaymentAddOn::authorization_error()
	 *
	 * @param string $plan_id The subscription plan id.
	 *
	 * @return array|bool|string $plan The plan details. False if not found. If invalid request, the error message.
	 */
	public function get_plan( $plan_id ) {

		try {

			// Get Stripe plan.
			$plan = \Stripe\Plan::retrieve( $plan_id );

		} catch ( \Exception $e ) {

			/**
			 * There is no error type specific to failing to retrieve a subscription when an invalid plan ID is passed. We assume here
			 * that any 'invalid_request_error' means that the subscription does not exist even though other errors (like providing
			 * incorrect API keys) will also generate the 'invalid_request_error'. There is no way to differentiate these requests
			 * without relying on the error message which is more likely to change and not reliable.
			 */

			// Get error response.
			$response = $e->getJsonBody();

			// If error is an invalid request error, return error message.
			if ( rgars( $response, 'error/type' ) !== 'invalid_request_error' ) {
				$plan = $this->authorization_error( $e->getMessage() );
			} else {
				$plan = false;
			}

		}

		return $plan;
	}

	/**
	 * Create and return a Stripe plan with the specified properties.
	 *
	 * @since   Unknown
	 * @access  public
	 *
	 * @used-by GFStripe::subscribe()
	 * @uses    GFPaymentAddOn::get_amount_export()
	 * @uses    \Stripe\Plan::create()
	 * @uses    GFAddOn::log_debug()
	 *
	 * @param string    $plan_id           The plan ID.
	 * @param array     $feed              The feed currently being processed.
	 * @param float|int $payment_amount    The recurring amount.
	 * @param int       $trial_period_days The number of days the trial should last.
	 * @param string    $currency          The currency code for the entry being processed.
	 *
	 * @return \Stripe\Plan The plan object.
	 */
	public function create_plan( $plan_id, $feed, $payment_amount, $trial_period_days, $currency ) {
		// Prepare plan metadata.
		$plan_meta = array(
			'interval'          => $feed['meta']['billingCycle_unit'],
			'interval_count'    => $feed['meta']['billingCycle_length'],
			'product'           => array( 'name' => $feed['meta']['feedName'] ),
			'currency'          => $currency,
			'id'                => $plan_id,
			'amount'            => $this->get_amount_export( $payment_amount, $currency ),
			'trial_period_days' => $trial_period_days,
		);

		// Log the plan to be created.
		$this->log_debug( __METHOD__ . '(): Plan to be created => ' . print_r( $plan_meta, 1 ) );

		// Create Stripe plan.
		$plan = \Stripe\Plan::create( $plan_meta );

		return $plan;
	}

	/**
	 * Subscribes the customer to the plan.
	 *
	 * @since 2.5.2 Added the $trial_period_days param.
	 * @since 2.3.4
	 *
	 * @param \Stripe\Customer $customer          The Stripe customer object.
	 * @param \Stripe\Plan     $plan              The Stripe plan object.
	 * @param array            $feed              The feed currently being processed.
	 * @param array            $entry             The entry currently being processed.
	 * @param array            $form              The form which created the current entry.
	 * @param int              $trial_period_days The number of days the trial should last.
	 *
	 * @return string The Stripe subscription ID.
	 */
	public function update_subscription( $customer, $plan, $feed, $entry, $form, $trial_period_days = 0 ) {

		$subscription_params = array( 'plan' => $plan->id );

		if ( $trial_period_days > 0 ) {
			$subscription_params['trial_from_plan'] = true;
		}

		/**
		 * Allow the subscription parameters to be overridden before the customer is subscribed to the plan.
		 *
		 * @since 2.5.2 Added the $trial_period_days param.
		 * @since 2.3.4
		 *
		 * @param array            $subscription_params The subscription parameters.
		 * @param \Stripe\Customer $customer            The Stripe customer object.
		 * @param \Stripe\Plan     $plan                The Stripe plan object.
		 * @param array            $feed                The feed currently being processed.
		 * @param array            $entry               The entry currently being processed.
		 * @param array            $form                The form which created the current entry.
		 * @param int              $trial_period_days   The number of days the trial should last.
		 */
		$subscription_params = apply_filters( 'gform_stripe_subscription_params_pre_update_customer', $subscription_params, $customer, $plan, $feed, $entry, $form, $trial_period_days );

		if ( has_filter( 'gform_stripe_subscription_params_pre_update_customer' ) ) {
			$this->log_debug( __METHOD__ . '(): Subscription parameters => ' . print_r( $subscription_params, 1 ) );
		}

		$subscription = $customer->updateSubscription( $subscription_params );

		return $subscription->id;
	}

	/**
	 * Gets the Stripe subscription object for the given ID.
	 *
	 * @since 2.8
	 *
	 * @param string $subscription_id The subscription ID.
	 *
	 * @return bool|\Stripe\Subscription
	 */
	public function get_subscription( $subscription_id ) {

		$this->log_debug( __METHOD__ . '(): Getting subscription ' . $subscription_id );

		try {

			$subscription = \Stripe\Subscription::retrieve( $subscription_id );

		} catch ( \Exception $e ) {

			$this->log_error( __METHOD__ . '(): Unable to get subscription; ' . $e->getMessage() );
			$subscription = false;
		}

		return $subscription;
	}

	/**
	 * Retrieve the specified Stripe Event.
	 *
	 * @since   Unknown
	 * @access  public
	 *
	 * @used-by GFStripe::callback()
	 * @uses    GFStripe::include_stripe_api()
	 * @uses    \Stripe\Event::retrieve()
	 *
	 * @param string      $event_id Stripe Event ID.
	 * @param null|string $mode     The API mode; live or test.
	 *
	 * @return \Stripe\Event The Stripe event object.
	 */
	public function get_stripe_event( $event_id, $mode = null ) {

		// Include Stripe API library.
		$this->include_stripe_api( $mode );

		// Get Stripe event.
		$event = \Stripe\Event::retrieve( $event_id );

		return $event;

	}

	/**
	 * If custom meta data has been configured on the feed retrieve the mapped field values.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::authorize_product()
	 * @used-by GFStripe::capture()
	 * @used-by GFStripe::process_subscription()
	 * @used-by GFStripe::subscribe()
	 * @uses    GFAddOn::get_field_value()
	 *
	 * @param array $feed  The feed object currently being processed.
	 * @param array $entry The entry object currently being processed.
	 * @param array $form  The form object currently being processed.
	 *
	 * @return array The Stripe meta data.
	 */
	public function get_stripe_meta_data( $feed, $entry, $form ) {

		// Initialize metadata array.
		$metadata = array();

		// Find feed metadata.
		$custom_meta = rgars( $feed, 'meta/metaData' );

		if ( is_array( $custom_meta ) ) {

			// Loop through custom meta and add to metadata for stripe.
			foreach ( $custom_meta as $meta ) {

				// If custom key or value are empty, skip meta.
				if ( empty( $meta['custom_key'] ) || empty( $meta['value'] ) ) {
					continue;
				}

				// Make the key available to the gform_stripe_field_value filter.
				$this->_current_meta_key = $meta['custom_key'];

				// Get field value for meta key.
				$field_value = $this->get_field_value( $form, $entry, $meta['value'] );

				if ( ! empty( $field_value ) ) {

					// Trim to 500 characters, per Stripe requirement.
					$field_value = substr( $field_value, 0, 500 );

					// Add to metadata array.
					$metadata[ $meta['custom_key'] ] = $field_value;

				}

			}

			// Clear the key in case get_field_value() and gform_stripe_field_value are used elsewhere.
			$this->_current_meta_key = '';

		}

		return $metadata;

	}

	/**
	 * Check if a Stripe.js has an error or is missing the ID and then return the appropriate message.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::authorize()
	 * @used-by GFStripe::subscribe()
	 * @uses    GFStripe::get_stripe_js_response()
	 *
	 * @return bool|string The error. False if the error does not exist.
	 */
	public function get_stripe_js_error() {

		// Get Stripe.js response.
		$response = $this->get_stripe_js_response();

		// If an error message is provided, return error message.
		if ( isset( $response->error ) ) {
			return $response->error->message;
		} elseif ( empty( $response->id ) ) {
			return esc_html__( 'Unable to authorize card. No response from Stripe.js.', 'gravityformsstripe' );
		}

		return false;

	}

	/**
	 * Response from Stripe.js is posted to the server as 'stripe_response'.
	 *
	 * @since Unknown
	 * @access public
	 *
	 * @used-by GFStripe::add_stripe_inputs()
	 * @used-by GFStripe::authorize_product()
	 * @used-by GFStripe::get_stripe_js_error()
	 * @used-by GFStripe::subscribe()
	 *
	 * @return \Stripe\Token|null A valid Stripe response object or null
	 */
	public function get_stripe_js_response() {

		$response = json_decode( rgpost( 'stripe_response' ) );

		if ( isset( $response->token ) ) {
			$response->id = $response->token->id;
		}

		return $response;

	}

	/**
	 * Include the Stripe API and set the current API key.
	 *
	 * @since   Unknown
	 * @access  public
	 *
	 * @used-by GFStripe::ajax_validate_secret_key()
	 * @used-by GFStripe::authorize()
	 * @used-by GFStripe::cancel()
	 * @used-by GFStripe::get_stripe_event()
	 * @used-by GFStripe::subscribe()
	 * @uses    GFAddOn::get_base_path()
	 * @uses    \Stripe\Stripe::setApiKey()
	 * @uses    GFStripe::get_secret_api_key()
	 *
	 * @param null|string $mode The API mode; live or test.
	 */
	public function include_stripe_api( $mode = null ) {

		// If Stripe class does not exist, load Stripe API library.
		if ( ! class_exists( '\Stripe\Stripe' ) ) {
			require_once( $this->get_base_path() . '/includes/autoload.php' );
		}

		require_once( $this->get_base_path() . '/includes/deprecated.php' );

		// Set Stripe API key.
		\Stripe\Stripe::setApiKey( $this->get_secret_api_key( $mode ) );

		if ( method_exists( '\Stripe\Stripe', 'setAppInfo' ) ) {
			// Send plugin title, version and site url along with API calls.
			\Stripe\Stripe::setAppInfo( $this->_title, $this->_version, esc_url( site_url() ) );
		}

		/**
		 * Run post Stripe API initialization action.
		 *
		 * @since 2.0.10
		 */
		do_action( 'gform_stripe_post_include_api' );

	}





	// # WEBHOOKS ------------------------------------------------------------------------------------------------------

	/**
	 * If the Stripe webhook belongs to a valid entry process the raw response into a standard Gravity Forms $action.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFAddOn::get_plugin_settings()
	 * @uses GFStripe::get_api_mode()
	 * @uses GFStripe::get_stripe_event()
	 * @uses GFAddOn::log_error()
	 * @uses GFAddOn::log_debug()
	 * @uses GFPaymentAddOn::get_entry_by_transaction_id()
	 * @uses GFPaymentAddOn::get_amount_import()
	 * @uses GFStripe::get_subscription_line_item()
	 * @uses GFStripe::get_captured_payment_note()
	 * @uses GFAPI::get_entry()
	 * @uses GFCommon::to_money()
	 *
	 * @return array|bool|WP_Error Return a valid GF $action or if the webhook can't be processed a WP_Error object or false.
	 */
	public function callback() {

		$event = $this->get_webhook_event();

		if ( ! $event || is_wp_error( $event ) ) {
			return $event;
		}

		// Get event properties.
		$action = $log_details = array( 'id' => $event->id );
		$type   = $event->type;

		$log_details += array(
			'type'        => $type,
			'webhook api_version' => $event->api_version,
		);

		$this->log_debug( __METHOD__ . '() Webhook event details => ' . print_r( $log_details, 1 ) );

		switch ( $type ) {

			case 'charge.expired':
			case 'charge.refunded':

				$action['transaction_id'] = rgars( $event, 'data/object/id' );
				$entry_id                 = $this->get_entry_by_transaction_id( $action['transaction_id'] );
				if ( ! $entry_id ) {
					return $this->get_entry_not_found_wp_error( 'transaction', $action, $event );
				}

				$entry = GFAPI::get_entry( $entry_id );

				$action['entry_id'] = $entry_id;

				if ( $event->data->object->captured ) {
					$action['type']   = 'refund_payment';
					$action['amount'] = $this->get_amount_import( rgars( $event, 'data/object/amount_refunded' ), $entry['currency'] );
				} else {
					$action['type'] = 'void_authorization';
				}

				break;

			case 'customer.subscription.deleted':

				$action['subscription_id'] = rgars( $event, 'data/object/id' );
				$entry_id                  = $this->get_entry_by_transaction_id( $action['subscription_id'] );
				if ( ! $entry_id ) {
					return $this->get_entry_not_found_wp_error( 'subscription', $action, $event );
				}

				$entry = GFAPI::get_entry( $entry_id );

				$action['entry_id'] = $entry_id;
				$action['type']     = 'cancel_subscription';
				$action['amount']   = $this->get_amount_import( rgars( $event, 'data/object/plan/amount' ), $entry['currency'] );

				break;

			case 'invoice.payment_succeeded':

				$subscription = $this->get_subscription_line_item( $event );
				if ( ! $subscription ) {
					return new WP_Error( 'invalid_request', sprintf( __( 'Subscription line item not found in request', 'gravityformsstripe' ) ) );
				}

				$action['subscription_id'] = rgar( $subscription, 'subscription' );
				$entry_id                  = $this->get_entry_by_transaction_id( $action['subscription_id'] );
				if ( ! $entry_id ) {
					return $this->get_entry_not_found_wp_error( 'subscription', $action, $event );
				}

				$entry = GFAPI::get_entry( $entry_id );

				$action['transaction_id'] = rgars( $event, 'data/object/charge' );
				$action['entry_id']       = $entry_id;
				$action['type']           = 'add_subscription_payment';
				$action['amount']         = $this->get_amount_import( rgars( $event, 'data/object/amount_due' ), $entry['currency'] );

				$action['note'] = '';

				// Get starting balance, assume this balance represents a setup fee or trial.
				$starting_balance = $this->get_amount_import( rgars( $event, 'data/object/starting_balance' ), $entry['currency'] );
				if ( $starting_balance > 0 ) {
					$action['note'] = $this->get_captured_payment_note( $action['entry_id'] ) . ' ';
				}

				$amount_formatted = GFCommon::to_money( $action['amount'], $entry['currency'] );
				$action['note']   .= sprintf( __( 'Subscription payment has been paid. Amount: %s. Subscription Id: %s', 'gravityformsstripe' ), $amount_formatted, $action['subscription_id'] );

				break;

			case 'invoice.payment_failed':

				$subscription = $this->get_subscription_line_item( $event );
				if ( ! $subscription ) {
					return new WP_Error( 'invalid_request', sprintf( __( 'Subscription line item not found in request', 'gravityformsstripe' ) ) );
				}

				$action['subscription_id'] = rgar( $subscription, 'subscription' );
				$entry_id                  = $this->get_entry_by_transaction_id( $action['subscription_id'] );
				if ( ! $entry_id ) {
					return $this->get_entry_not_found_wp_error( 'subscription', $action, $event );
				}

				$entry = GFAPI::get_entry( $entry_id );

				$action['type']     = 'fail_subscription_payment';
				$action['amount']   = $this->get_amount_import( rgar( $subscription, 'amount' ), $entry['currency'] );
				$action['entry_id'] = $this->get_entry_by_transaction_id( $action['subscription_id'] );

				break;

		}

		if ( has_filter( 'gform_stripe_webhook' ) ) {
			$this->log_debug( __METHOD__ . '(): Executing functions hooked to gform_stripe_webhook.' );

			/**
			 * Enable support for custom webhook events.
			 *
			 * @since 1.0.0
			 *
			 * @param array         $action An associative array containing the event details.
			 * @param \Stripe\Event $event  The Stripe event object for the webhook which was received.
			 */
			$action = apply_filters( 'gform_stripe_webhook', $action, $event );
		}

		if ( rgempty( 'entry_id', $action ) ) {
			$this->log_debug( __METHOD__ . '() entry_id not set for callback action; no further processing required.' );

			return false;
		}

		return $action;

	}

	/**
	 * Get the WP_Error to be returned when the entry is not found.
	 *
	 * @since 2.5.1
	 *
	 * @param string        $type   The type to be included in the error message and when getting the id: transaction or subscription.
	 * @param array         $action An associative array containing the event details.
	 * @param \Stripe\Event $event  The Stripe event object for the webhook which was received.
	 *
	 * @return WP_Error
	 */
	public function get_entry_not_found_wp_error( $type, $action, $event ) {
		$message     = sprintf( __( 'Entry for %s id: %s was not found. Webhook cannot be processed.', 'gravityformsstripe' ), $type, rgar( $action, $type . '_id' ) );
		$status_code = 200;

		/**
		 * Enables the status code for the entry not found WP_Error to be overridden.
		 *
		 * @since 2.5.1
		 *
		 * @param int           $status_code The status code. Default is 200.
		 * @param array         $action      An associative array containing the event details.
		 * @param \Stripe\Event $event       The Stripe event object for the webhook which was received.
		 */
		$status_code = apply_filters( 'gform_stripe_entry_not_found_status_code', $status_code, $action, $event );

		return new WP_Error( 'entry_not_found', $message, array( 'status_header' => $status_code ) );
	}

	/**
	 * Retrieve the Stripe Event for the received webhook.
	 *
	 * @since 2.3.1
	 *
	 * @return false|WP_Error|\Stripe\Event
	 */
	public function get_webhook_event() {

		$body     = @file_get_contents( 'php://input' );
		$response = json_decode( $body, true );

		if ( empty( $response ) ) {
			return false;
		}

		$mode = rgempty( 'livemode', $response ) ? 'test' : 'live';
		$this->log_debug( __METHOD__ . '(): Processing ' . $mode . ' mode event.' );

		$endpoint_secret = $this->get_webhook_signing_secret( $mode );
		$event           = $error_message = false;

		$event_id      = rgar( $response, 'id' );
		$is_test_event = 'evt_00000000000000' === $event_id;

		try {

			if ( empty( $endpoint_secret ) && ! $is_test_event ) {

				// Use the legacy method for getting the event.
				$event = $this->get_stripe_event( $event_id, $mode );

			} else {

				$this->include_stripe_api( $mode );
				$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
				$event      = \Stripe\Webhook::constructEvent( $body, $sig_header, $endpoint_secret );

			}

		} catch ( \UnexpectedValueException $e ) {

			// Invalid payload.
			$error_message = $e->getMessage();

		} catch ( \Stripe\Error\SignatureVerification $e ) {

			// Invalid signature.
			$error_message = $e->getMessage();

		} catch ( \Exception $e ) {

			// Any other issue.
			$error_message = $e->getMessage();

		}

		if ( $error_message ) {
			$this->log_error( __METHOD__ . '(): Unable to retrieve Stripe Event object. ' . $error_message );
			$message = __( 'Invalid request. Webhook could not be processed.', 'gravityformsstripe' ) . ' ' . $error_message;

			return new WP_Error( 'invalid_request', $message, array( 'status_header' => 400 ) );
		}

		if ( $is_test_event ) {
			return new WP_Error( 'test_webhook_succeeded', __( 'Test webhook succeeded. Your Stripe Account and Stripe Add-On are configured correctly to process webhooks.', 'gravityformsstripe' ), array( 'status_header' => 200 ) );
		}

		return $event;
	}

	/**
	 * Generate the url Stripe webhooks should be sent to.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::get_webhooks_section_description()
	 *
	 * @return string The webhook URL.
	 */
	public function get_webhook_url() {

		return get_bloginfo( 'url' ) . '/?callback=' . $this->_slug;

	}

	/**
	 * Helper to check that webhooks are enabled.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::can_create_feed()
	 * @uses    GFAddOn::get_plugin_setting()
	 *
	 * @return bool True if webhook is enabled. False otherwise.
	 */
	public function is_webhook_enabled() {

		return $this->get_plugin_setting( 'webhooks_enabled' ) == true;

	}

	// # HELPER FUNCTIONS ----------------------------------------------------------------------------------------------

	/**
	 * Retrieve the specified api key.
	 *
	 * @since   Unknown
	 * @access  public
	 *
	 * @used-by GFStripe::get_publishable_api_key()
	 * @used-by GFStripe::get_secret_api_key()
	 * @uses    GFStripe::get_query_string_api_key()
	 * @uses    GFAddOn::get_plugin_settings()
	 * @uses    GFStripe::get_api_mode()
	 * @uses    GFAddOn::get_setting()
	 *
	 * @param string      $type The type of key to retrieve.
	 * @param null|string $mode The API mode; live or test.
	 *
	 * @return string
	 */
	public function get_api_key( $type = 'secret', $mode = null ) {

		// Check for api key in query first; user must be an administrator to use this feature.
		$api_key = $this->get_query_string_api_key( $type );
		if ( $api_key && current_user_can( 'update_core' ) ) {
			return $api_key;
		}

		$settings = $this->get_plugin_settings();

		if ( ! $mode ) {
			// Get API mode.
			$mode = $this->get_api_mode( $settings );
		}

		// Get API key based on current mode and defined type.
		$setting_key = "{$mode}_{$type}_key";
		$api_key     = $this->get_setting( $setting_key, '', $settings );

		return $api_key;

	}

	/**
	 * Helper to implement the gform_stripe_api_mode filter so the api mode can be overridden.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::get_api_key()
	 * @used-by GFStripe::callback()
	 * @used-by GFStripe::can_create_feed()
	 *
	 * @param array $settings The plugin settings.
	 *
	 * @return string $api_mode Either live or test.
	 */
	public function get_api_mode( $settings ) {

		// Get API mode from settings.
		$api_mode = rgar( $settings, 'api_mode' );

		/**
		 * Filters the API mode.
		 *
		 * @since 1.10.1
		 *
		 * @param string $api_mode The API mode.
		 */
		return apply_filters( 'gform_stripe_api_mode', $api_mode );

	}

	/**
	 * Retrieve the specified api key from the query string.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::get_api_key()
	 *
	 * @param string $type The type of key to retrieve. Defaults to 'secret'.
	 *
	 * @return string The result of the query string.
	 */
	public function get_query_string_api_key( $type = 'secret' ) {

		return rgget( $type );

	}

	/**
	 * Retrieve the publishable api key.
	 *
	 * @since   Unknown
	 * @access  public
	 *
	 * @used-by GFStripe::register_init_scripts()
	 * @uses    GFStripe::get_api_key()
	 *
	 * @return string The publishable (public) API key.
	 */
	public function get_publishable_api_key() {

		return $this->get_api_key( 'publishable' );

	}

	/**
	 * Retrieve the secret api key.
	 *
	 * @since   Unknown
	 * @access  public
	 *
	 * @used-by GFStripe::include_stripe_api()
	 * @uses    GFStripe::get_api_key()
	 *
	 * @param null|string $mode    The API mode; live or test.
	 *
	 * @return string The secret API key.
	 */
	public function get_secret_api_key( $mode = null ) {

		return $this->get_api_key( 'secret', $mode );

	}

	/**
	 * Retrieve the webhook signing secret for the specified API mode.
	 *
	 * @since 2.3.1
	 *
	 * @param string $mode The API mode; live or test.
	 *
	 * @return string
	 */
	public function get_webhook_signing_secret( $mode ) {

		$signing_secret = $this->get_plugin_setting( $mode . '_signing_secret' );

		/**
		 * Override the webhook signing secret for the specified API mode.
		 *
		 * @param string   $signing_secret The signing secret to be used when validating received webhooks.
		 * @param string   $mode           The API mode; live or test.
		 * @param GFStripe $gfstripe       GFStripe class object
		 *
		 * @since 2.3.1
		 */
		return apply_filters( 'gform_stripe_webhook_signing_secret', $signing_secret, $mode, $this );

	}

	/**
	 * Helper to check that Stripe Checkout is enabled.
	 *
	 * @since  2.6.0
	 * @access public
	 *
	 * @used-by GFStripe::scripts()
	 * @uses    GFAddOn::get_plugin_setting()
	 *
	 * @return bool True if Stripe Checkout is enabled. False otherwise.
	 */
	public function is_stripe_checkout_enabled() {

		return $this->get_plugin_setting( 'checkout_method' ) === 'stripe_checkout' && version_compare( GFFormsModel::get_database_version(), '2.4-beta-1', '>=' );

	}

	/**
	 * Helper to get logo URL.
	 *
	 * @since  2.6.0
	 * @access public
	 *
	 * @used-by GFStripe::scripts()
	 * @uses    GFAddOn::get_plugin_setting()
	 *
	 * @return string Logo URL.
	 */
	public function get_logo_url() {

		return esc_url( $this->get_plugin_setting( 'logo_url' ) );

	}

	/**
	 * Retrieve the first part of the subscription's entry note.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::callback()
	 * @uses    GFAPI::get_entry()
	 * @uses    GFPaymentAddOn::get_payment_feed()
	 *
	 * @param int $entry_id The ID of the entry currently being processed.
	 *
	 * @return string The payment note. Escaped.
	 */
	public function get_captured_payment_note( $entry_id ) {

		// Get feed for entry.
		$entry = GFAPI::get_entry( $entry_id );
		$feed  = $this->get_payment_feed( $entry );

		// Define note based on if setup fee is enabled.
		if ( rgars( $feed, 'meta/setupFee_enabled' ) ) {
			$note = esc_html__( 'Setup fee has been paid.', 'gravityformsstripe' );
		} else {
			$note = esc_html__( 'Trial has been paid.', 'gravityformsstripe' );
		}

		return $note;
	}

	/**
	 * Retrieve the labels for the various card types.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::register_init_scripts()
	 * @uses    GFCommon::get_card_types()
	 *
	 * @return array The card labels available.
	 */
	public function get_card_labels() {

		// Get credit card types.
		$card_types  = GFCommon::get_card_types();

		// Initialize credit card labels array.
		$card_labels = array();

		// Loop through card types.
		foreach ( $card_types as $card_type ) {

			// Add card label for card type.
			$card_labels[ $card_type['slug'] ] = $card_type['name'];

		}

		return $card_labels;

	}

	/**
	 * Get the slug for the card type returned by Stripe.js
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::pre_validation()
	 * @uses    GFCommon::get_card_types()
	 *
	 * @param string $type The possible types are "Visa", "MasterCard", "American Express", "Discover", "Diners Club", and "JCB" or "Unknown".
	 *
	 * @return string
	 */
	public function get_card_slug( $type ) {

		// If type is defined, attempt to get card slug.
		if ( $type ) {

			// Get card types.
			$card_types = GFCommon::get_card_types();

			// Loop through card types.
			foreach ( $card_types as $card ) {

				// If the requested card type is equal to the current card's name, return the slug.
				if ( rgar( $card, 'name' ) === $type ) {
					return rgar( $card, 'slug' );
				}

			}

		}

		return $type;

	}

	/**
	 * Populate the $_POST with the last four digits of the card number and card type.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::init()
	 * @uses    GFPaymentAddOn::$is_payment_gateway
	 * @uses    GFPaymentAddOn::get_credit_card_field()
	 *
	 * @param array $form Form object.
	 */
	public function populate_credit_card_last_four( $form ) {

		if ( ! $this->is_payment_gateway || ( $this->is_stripe_checkout_enabled() && ! $this->has_credit_card_field( $form ) && ! $this->has_stripe_card_field( $form ) ) ) {
			return;
		}

		if ( $this->has_stripe_card_field( $form ) ) {
			$cc_field = $this->get_stripe_card_field( $form );
		} elseif ( $this->has_credit_card_field( $form ) ) {
			$cc_field = $this->get_credit_card_field( $form );
		}

		$_POST[ 'input_' . $cc_field->id . '_1' ] = 'XXXXXXXXXXXX' . rgpost( 'stripe_credit_card_last_four' );
		$_POST[ 'input_' . $cc_field->id . '_4' ] = rgpost( 'stripe_credit_card_type' );

	}

	/**
     * Add credit card warning CSS class for the Stripe Card field.
     *
     * @since 2.6
     *
	 * @param string   $css_class CSS classes.
	 * @param GF_Field $field Field object.
	 * @param array    $form Form array.
	 *
	 * @return string
	 */
	public function stripe_card_field_css_class( $css_class, $field, $form ) {
	    if ( GFFormsModel::get_input_type( $field ) === 'stripe_creditcard' && ! GFCommon::is_ssl() ) {
            $css_class .= ' gfield_creditcard_warning';
        }

	    return $css_class;
    }

	/**
	 * Allows the modification of submitted values of the Stripe Card field before the draft submission is saved.
	 *
	 * @since 2.6
	 *
	 * @param array $submitted_values The submitted values.
	 * @param array $form             The Form object.
     *
     * @return array
	 */
    public function stripe_card_submission_value_pre_save( $submitted_values, $form ) {
	    foreach ( $form['fields'] as $field ) {
		    if ( $field->type == 'stripe_creditcard' ) {
			    unset( $submitted_values[ $field->id ] );
		    }
	    }

        return $submitted_values;
    }

	/**
	 * Populate Stripe Checkout response in a hidden field.
	 *
	 * @param string $form The form tag.
	 *
	 * @return string
	 */
	public function populate_stripe_response( $form ) {

		// If a Stripe response exists, populate it to a hidden field.
		if ( $this->get_stripe_js_response() ) {
			$form .= '<input type=\'hidden\' name=\'stripe_response\' id=\'gf_stripe_response\' value=\'' . rgpost( 'stripe_response' ) . '\' />';
		}

		return $form;
	}

	/**
	 * Add the value of the trialPeriod property to the order data which is to be included in the $submission_data.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFPaymentAddOn::get_submission_data()
	 * @uses    GFPaymentAddOn::get_order_data()
	 *
	 * @param array $feed  The feed currently being processed.
	 * @param array $form  The form currently being processed.
	 * @param array $entry The entry currently being processed.
	 *
	 * @return array The order data found.
	 */
	public function get_order_data( $feed, $form, $entry ) {

		$order_data          = parent::get_order_data( $feed, $form, $entry );
		$order_data['trial'] = rgars( $feed, 'meta/trialPeriod' );

		return $order_data;

	}

	/**
	 * Return the description to be used with the Stripe charge.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::authorize_product()
	 * @used-by GFStripe::capture()
	 *
	 * @param array $entry           The entry object currently being processed.
	 * @param array $submission_data The customer and transaction data.
	 * @param array $feed            The feed object currently being processed.
	 *
	 * @return string
	 */
	public function get_payment_description( $entry, $submission_data, $feed ) {

		// Charge description format:
		// Entry ID: 123, Products: Product A, Product B, Product C

		$strings = array();

		if ( $entry['id'] ) {
			$strings['entry_id'] = sprintf( esc_html__( 'Entry ID: %d', 'gravityformsstripe' ), $entry['id'] );
		}

		$strings['products'] = sprintf(
			_n( 'Product: %s', 'Products: %s', count( $submission_data['line_items'] ), 'gravityformsstripe' ),
			implode( ', ', wp_list_pluck( $submission_data['line_items'], 'name' ) )
		);

		$description = implode( ', ', $strings );

		/**
		 * Allow the charge description to be overridden.
		 *
		 * @since 1.0.0
		 *
		 * @param string $description     The charge description.
		 * @param array  $strings         Contains the Entry ID and Products. The array which was imploded to create the description.
		 * @param array  $entry           The entry object currently being processed.
		 * @param array  $submission_data The customer and transaction data.
		 * @param array  $feed            The feed object currently being processed.
		 */
		return apply_filters( 'gform_stripe_charge_description', $description, $strings, $entry, $submission_data, $feed );
	}

	/**
	 * Retrieve the subscription line item from from the Stripe response.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFStripe::capture()
	 *
	 * @param \Stripe\Event $response The Stripe webhook response.
	 *
	 * @return bool|array The subscription line items. Returns false if nothing found.
	 */
	public function get_subscription_line_item( $response ) {

		$lines = rgars( $response, 'data/object/lines/data' );

		foreach ( $lines as $line ) {
			if ( 'subscription' === $line['type'] ) {
				return $line;
			}
		}

		return false;
	}

	/**
	 * Generate the subscription plan id.
	 *
	 * @since   2.3.4 Added the $currency param.
	 * @since   Unknown
	 * @access  public
	 *
	 * @used-by GFStripe::subscribe()
	 *
	 * @param array     $feed              The feed object currently being processed.
	 * @param float|int $payment_amount    The recurring amount.
	 * @param int       $trial_period_days The number of days the trial should last.
	 * @param string    $currency          The currency code for the entry being processed.
	 *
	 * @return string The subscription plan ID, if found.
	 */
	public function get_subscription_plan_id( $feed, $payment_amount, $trial_period_days, $currency = '' ) {

		$safe_feed_name      = preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $feed['meta']['feedName'] ) );
		$safe_billing_cycle  = $feed['meta']['billingCycle_length'] . $feed['meta']['billingCycle_unit'];
		$safe_trial_period   = $trial_period_days ? 'trial' . $trial_period_days . 'days' : '';
		$safe_payment_amount = $this->get_amount_export( $payment_amount, $currency );

		/*
		 * Only include the currency code in the plan id when the entry currency does not match the plugin currency.
		 * Ensures the majority of plans created before this change will continue to be used.
		 * https://stripe.com/docs/subscriptions/plans#working-with-local-currencies
		*/
		if ( ! empty( $currency ) && $currency === GFCommon::get_currency() ) {
			$currency = '';
		}

		$plan_id = implode( '_', array_filter( array( $safe_feed_name, $feed['id'], $safe_billing_cycle, $safe_trial_period, $safe_payment_amount, $currency ) ) );

		$this->log_debug( __METHOD__ . '(): ' . $plan_id );

		return $plan_id;

	}

	/**
	 * Enables use of the gform_stripe_field_value filter to override the field value.
	 *
	 * @since 2.1.1 Making the $meta_key parameter available to the gform_stripe_field_value filter.
	 *
	 * @used-by GFAddOn::get_field_value()
	 *
	 * @param string $field_value The field value to be filtered.
	 * @param array  $form        The form currently being processed.
	 * @param array  $entry       The entry currently being processed.
	 * @param string $field_id    The ID of the Field currently being processed.
	 *
	 * @return string
	 */
	public function maybe_override_field_value( $field_value, $form, $entry, $field_id ) {
		$meta_key = $this->_current_meta_key;
		$form_id  = $form['id'];

		/**
		 * Allow the mapped field value to be overridden.
		 *
		 * @since 2.1.1 Added the $meta_key parameter.
		 * @since 1.9.10.11
		 *
		 * @param string $field_value The field value to be filtered.
		 * @param array  $form        The form currently being processed.
		 * @param array  $entry       The entry currently being processed.
		 * @param string $field_id    The ID of the Field currently being processed.
		 * @param string $meta_key    The custom meta key currently being processed.
		 */
		$field_value = apply_filters( 'gform_stripe_field_value', $field_value, $form, $entry, $field_id, $meta_key );
		$field_value = apply_filters( "gform_stripe_field_value_{$form_id}", $field_value, $form, $entry, $field_id, $meta_key );
		$field_value = apply_filters( "gform_stripe_field_value_{$form_id}_{$field_id}", $field_value, $form, $entry, $field_id, $meta_key );

		return $field_value;
	}

	/**
	 * Get Stripe Card field for form.
	 *
	 * @since 2.6
	 *
	 * @param array $form Form object. Defaults to null.
	 *
	 * @return boolean
	 */
	public function has_stripe_card_field( $form = null ) {
	    // Get form
		if ( is_null( $form ) ) {
			$form = $this->get_current_form();
        }

		return $this->get_stripe_card_field( $form ) !== false;
	}

	/**
	 * Gets Stripe credit card field object.
	 *
	 * @since 2.6
	 *
	 * @param array $form The Form Object.
	 *
	 * @return bool|GF_Field_Stripe_CreditCard The Stripe card field object, if found. Otherwise, false.
	 */
	public function get_stripe_card_field( $form ) {
		$fields = GFAPI::get_fields_by_type( $form, array( 'stripe_creditcard' ) );

		return empty( $fields ) ? false : $fields[0];
	}

	/**
	 * Prepare fields for field mapping in feed settings.
	 *
	 * @since 2.6
	 *
	 * @return array $fields
	 */
	public function billing_info_fields() {
	    $fields = array(
			array(
				'name'       => 'address_line1',
				'label'      => __( 'Address', 'gravityformsconstantcontact' ),
				'required'   => false,
				'field_type' => array( 'address' ),
			),
			array(
				'name'       => 'address_line2',
				'label'      => __( 'Address 2', 'gravityformsconstantcontact' ),
				'required'   => false,
				'field_type' => array( 'address' ),
			),
			array(
				'name'       => 'address_city',
				'label'      => __( 'City', 'gravityformsconstantcontact' ),
				'required'   => false,
				'field_type' => array( 'address' ),
			),
			array(
				'name'       => 'address_state',
				'label'      => __( 'State', 'gravityformsconstantcontact' ),
				'required'   => false,
				'field_type' => array( 'address' ),
			),
			array(
				'name'       => 'address_zip',
				'label'      => __( 'Zip', 'gravityformsconstantcontact' ),
				'required'   => false,
				'field_type' => array( 'address' ),
			),
			array(
				'name'       => 'address_country',
				'label'      => __( 'Country', 'gravityformsconstantcontact' ),
				'required'   => false,
				'field_type' => array( 'address' ),
			),
		);

		return $fields;

	}

	/**
	 * Returns the specified plugin setting.
	 *
	 * @since 2.6.0.1
	 *
	 * @param string $setting_name The setting to be returned.
	 *
	 * @return mixed|string
	 */
	public function get_plugin_setting( $setting_name ) {
		$setting = parent::get_plugin_setting( $setting_name );

		if ( ! $setting && $setting_name === 'checkout_method' ) {
			$setting = 'credit_card';
		}

		return $setting;
	}

	/**
	 * Target of gform_before_delete_field hook. Sets relevant payment feeds to inactive when the Stripe Card field is deleted.
	 *
	 * @since 2.6.1
	 *
	 * @param int $form_id ID of the form being edited.
	 * @param int $field_id ID of the field being deleted.
	 */
	public function before_delete_field( $form_id, $field_id ) {
	    parent::before_delete_field( $form_id, $field_id );

	    $form = GFAPI::get_form( $form_id );
		if ( $this->has_stripe_card_field( $form ) ) {
			$field = $this->get_stripe_card_field( $form );

			if ( is_object( $field ) && $field->id == $field_id ) {
				$feeds = $this->get_feeds( $form_id );
				foreach ( $feeds as $feed ) {
					if ( $feed['is_active'] ) {
						$this->update_feed_active( $feed['id'], 0 );
					}
				}
			}
		}
	}

}
