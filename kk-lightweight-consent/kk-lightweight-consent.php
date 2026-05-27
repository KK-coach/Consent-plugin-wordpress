<?php
/**
 * Plugin Name: KK Lightweight Consent
 * Plugin URI: https://kk.coach
 * Description: Lightweight custom consent banner for WordPress with Google Tag Manager and Google Consent Mode v2 support.
 * Version: 0.1.0
 * Author: KK Coach
 * Author URI: https://kk.coach
 * Text Domain: kk-lightweight-consent
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

class KK_Lightweight_Consent {
	const OPTION_KEY = 'kk_lwc_options';

	public function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_head', array( $this, 'print_consent_defaults' ), 0 );
		add_action( 'wp_footer', array( $this, 'render_banner_markup' ) );
		add_action( 'wp_head', array( $this, 'maybe_print_gtm_head' ), 1 );
		add_action( 'wp_body_open', array( $this, 'maybe_print_gtm_noscript' ) );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'kk-lightweight-consent', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	private function defaults() {
		return array(
			'banner_enabled'       => 1,
			'consent_version'      => 'v1',
			'gtm_container_id'     => '',
			'gtm_inject'           => 0,
			'banner_preset'        => 'universal',
			'banner_text_hu'       => 'Sütiket és hasonló technológiákat használunk az oldal működtetéséhez, analitikához és marketing méréshez. A szükséges sütik mindig aktívak. Az analitikai, marketing és személyre szabási célú mérések csak hozzájárulás után indulnak.',
			'banner_text_en'       => 'We use cookies and similar technologies to operate this website, analytics, marketing measurement and personalization. Necessary cookies are always active. Analytics, marketing and personalization measurement starts only after consent.',
			'policy_url'           => 'https://kk.coach/',
			'cookie_days'          => 180,
			'logo_url'             => '',
			'reopen_icon_only'     => 1,
			'label_accept_all_hu'  => 'Elfogadom',
			'label_reject_all_hu'  => 'Elutasítom',
			'label_customize_hu'   => 'Beállítások',
			'label_save_choices_hu'=> 'Mentés',
			'label_reopen_hu'      => 'Süti beállítások',
			'label_accept_all_en'  => 'Accept all',
			'label_reject_all_en'  => 'Reject all',
			'label_customize_en'   => 'Customize',
			'label_save_choices_en'=> 'Save choices',
			'label_reopen_en'      => 'Cookie settings',
			'debug_mode'           => 0,
		);
	}

	private function get_options() {
		return wp_parse_args( get_option( self::OPTION_KEY, array() ), $this->defaults() );
	}

	private function current_lang() {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		return ( 0 === strpos( strtolower( $locale ), 'hu' ) ) ? 'hu' : 'en';
	}

	private function get_presets() {
		return array(
			'kk' => array(
				'banner_buttons' => array(
					array( 'action' => 'accept_all', 'label_key' => 'accept_all', 'style' => 'primary' ),
					array( 'action' => 'settings', 'label_key' => 'customize', 'style' => 'secondary' ),
				),
				'panel_buttons'  => array(
					array( 'action' => 'reject_all', 'label_key' => 'reject_all', 'style' => 'outline' ),
					array( 'action' => 'save_choices', 'label_key' => 'save_choices', 'style' => 'primary' ),
				),
			),
			'universal' => array(
				'banner_buttons' => array(
					array( 'action' => 'reject_all', 'label_key' => 'reject_all', 'style' => 'outline' ),
					array( 'action' => 'settings', 'label_key' => 'customize', 'style' => 'secondary' ),
					array( 'action' => 'accept_all', 'label_key' => 'accept_all', 'style' => 'primary' ),
				),
				'panel_buttons'  => array(
					array( 'action' => 'reject_all', 'label_key' => 'reject_all', 'style' => 'outline' ),
					array( 'action' => 'save_choices', 'label_key' => 'save_choices', 'style' => 'primary' ),
				),
			),
		);
	}

	private function render_buttons( $buttons ) {
		foreach ( $buttons as $button ) {
			$style = isset( $button['style'] ) ? sanitize_html_class( $button['style'] ) : 'secondary';
			echo '<button type="button" class="lcm-btn lcm-btn--' . esc_attr( $style ) . '" data-consent-action="' . esc_attr( $button['action'] ) . '" data-label-key="' . esc_attr( $button['label_key'] ) . '"></button>';
		}
	}

	public function enqueue_assets() {
		if ( is_admin() ) {
			return;
		}
		$options = $this->get_options();
		if ( empty( $options['banner_enabled'] ) ) {
			return;
		}

		wp_enqueue_style( 'kk-lightweight-consent', plugin_dir_url( __FILE__ ) . 'assets/kk-consent.css', array(), '1.2.0' );
		wp_enqueue_script( 'kk-lightweight-consent', plugin_dir_url( __FILE__ ) . 'assets/kk-consent.js', array(), '1.2.0', true );

		$lang      = $this->current_lang();
		$version   = sanitize_key( $options['consent_version'] );
		$store_key = 'kk_consent_' . $version;
		$presets   = $this->get_presets();
		$preset    = isset( $presets[ $options['banner_preset'] ] ) ? $options['banner_preset'] : 'universal';

		$config = array(
			'debug'          => ! empty( $options['debug_mode'] ),
			'storageKey'     => $store_key,
			'cookieDays'     => max( 1, absint( $options['cookie_days'] ) ),
			'policyUrl'      => esc_url_raw( $options['policy_url'] ),
			'logoUrl'        => esc_url_raw( $options['logo_url'] ),
			'reopenIconOnly' => ! empty( $options['reopen_icon_only'] ),
			'labels'         => array(
				'accept_all'      => $options[ 'label_accept_all_' . $lang ],
				'reject_all'      => $options[ 'label_reject_all_' . $lang ],
				'customize'       => $options[ 'label_customize_' . $lang ],
				'save_choices'    => $options[ 'label_save_choices_' . $lang ],
				'reopen'          => $options[ 'label_reopen_' . $lang ],
				'more_info'       => ( 'hu' === $lang ) ? 'Bővebb információ' : 'More information',
				'necessary'       => ( 'hu' === $lang ) ? 'Szükséges sütik' : 'Necessary cookies',
				'analytics'       => ( 'hu' === $lang ) ? 'Analitika' : 'Analytics',
				'marketing'       => ( 'hu' === $lang ) ? 'Marketing mérés' : 'Marketing measurement',
				'personalization' => ( 'hu' === $lang ) ? 'Személyre szabás' : 'Personalization',
			),
			'preset'         => $preset,
		);

		wp_add_inline_script( 'kk-lightweight-consent', 'window.kkConsentConfig = ' . wp_json_encode( $config ) . ';', 'before' );
	}

	public function print_consent_defaults() {
		if ( is_admin() ) {
			return;
		}
		$options = $this->get_options();
		if ( empty( $options['banner_enabled'] ) ) {
			return;
		}
		$version   = sanitize_key( $options['consent_version'] );
		$store_key = 'kk_consent_' . $version;
		$debug     = ! empty( $options['debug_mode'] ) ? 'true' : 'false';
		?>
		<script id="kk-consent-default">window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);} (function(){var storageKey=<?php echo wp_json_encode( $store_key ); ?>;var debug=<?php echo esc_js( $debug ); ?>;var saved=null;try{saved=localStorage.getItem(storageKey);}catch(e){} if(!saved){var match=document.cookie.match(new RegExp('(^| )'+storageKey+'=([^;]+)'));if(match&&match[2]){saved=decodeURIComponent(match[2]);}} var parsed=null;if(saved){try{parsed=JSON.parse(saved);}catch(e){}} var analytics=!!(parsed&&parsed.choices&&parsed.choices.analytics);var marketing=!!(parsed&&parsed.choices&&parsed.choices.marketing);var personalization=!!(parsed&&parsed.choices&&parsed.choices.personalization);var payload={analytics_storage:analytics?'granted':'denied',ad_storage:marketing?'granted':'denied',ad_user_data:marketing?'granted':'denied',ad_personalization:marketing?'granted':'denied',functionality_storage:'granted',security_storage:'granted',personalization_storage:personalization?'granted':'denied',wait_for_update:500};gtag('consent','default',payload);var defaultEvent={event:'kk_consent_default',kk_consent_status:parsed?'saved':'unset'};dataLayer.push(defaultEvent);if(debug){console.log('[KK Consent] default payload',payload);console.log('[KK Consent] default event',defaultEvent);}})();</script>
		<?php
	}

	public function maybe_print_gtm_head() {
		$options = $this->get_options();
		if ( is_admin() || empty( $options['gtm_inject'] ) || empty( $options['gtm_container_id'] ) ) {
			return;
		}
		$container_id = preg_replace( '/[^A-Z0-9\-]/', '', strtoupper( $options['gtm_container_id'] ) );
		?>
		<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo esc_js( $container_id ); ?>');</script>
		<?php
	}

	public function maybe_print_gtm_noscript() {
		$options = $this->get_options();
		if ( empty( $options['gtm_inject'] ) || empty( $options['gtm_container_id'] ) ) {
			return;
		}
		$container_id = preg_replace( '/[^A-Z0-9\-]/', '', strtoupper( $options['gtm_container_id'] ) );
		echo '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . esc_attr( $container_id ) . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>';
	}

	public function render_banner_markup() {
		if ( is_admin() ) {
			return;
		}
		$options = $this->get_options();
		if ( empty( $options['banner_enabled'] ) ) {
			return;
		}
		$lang    = $this->current_lang();
		$presets = $this->get_presets();
		$preset  = isset( $presets[ $options['banner_preset'] ] ) ? $options['banner_preset'] : 'universal';
		?>
		<div id="kk-consent-root" class="kk-consent-root" data-lang="<?php echo esc_attr( $lang ); ?>">
			<div class="kk-consent-banner" role="dialog" aria-live="polite" aria-label="Cookie consent" hidden>
				<img class="kk-consent-logo" src="" alt="" hidden>
				<p class="kk-consent-text"><?php echo wp_kses_post( $options[ 'banner_text_' . $lang ] ); ?></p>
				<a class="kk-consent-policy" href="<?php echo esc_url( $options['policy_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $options['policy_url'] ); ?></a>
				<div class="kk-consent-actions">
					<?php $this->render_buttons( $presets[ $preset ]['banner_buttons'] ); ?>
				</div>
				<div class="kk-consent-panel" hidden>
					<label><input type="checkbox" checked disabled> <span class="kk-necessary-label"></span></label>
					<label><input type="checkbox" class="kk-analytics"> <span class="kk-analytics-label"></span></label>
					<label><input type="checkbox" class="kk-marketing"> <span class="kk-marketing-label"></span></label>
					<label><input type="checkbox" class="kk-personalization"> <span class="kk-personalization-label"></span></label>
					<div class="kk-consent-panel-actions">
						<?php $this->render_buttons( $presets[ $preset ]['panel_buttons'] ); ?>
					</div>
				</div>
			</div>
			<button type="button" class="kk-consent-reopen" hidden aria-label="Cookie settings">⚙</button>
		</div>
		<?php
	}

	public function admin_menu() {
		add_options_page( esc_html__( 'KK Consent', 'kk-lightweight-consent' ), esc_html__( 'KK Consent', 'kk-lightweight-consent' ), 'manage_options', 'kk-lightweight-consent', array( $this, 'render_admin_page' ) );
	}

	public function register_settings() {
		register_setting( 'kk_lwc_settings_group', self::OPTION_KEY, array( $this, 'sanitize_options' ) );
	}

	public function sanitize_options( $input ) {
		$defaults = $this->defaults();
		$output   = array();
		foreach ( $defaults as $key => $value ) {
			$output[ $key ] = isset( $input[ $key ] ) ? $input[ $key ] : $value;
		}
		$output['banner_enabled']   = empty( $input['banner_enabled'] ) ? 0 : 1;
		$output['gtm_inject']       = empty( $input['gtm_inject'] ) ? 0 : 1;
		$output['debug_mode']       = empty( $input['debug_mode'] ) ? 0 : 1;
		$output['reopen_icon_only'] = empty( $input['reopen_icon_only'] ) ? 0 : 1;
		$output['consent_version']  = sanitize_key( $output['consent_version'] );
		$output['banner_preset']    = in_array( $output['banner_preset'], array( 'universal', 'kk' ), true ) ? $output['banner_preset'] : 'universal';
		$output['gtm_container_id'] = preg_replace( '/[^A-Z0-9\-]/', '', strtoupper( sanitize_text_field( $output['gtm_container_id'] ) ) );
		$output['policy_url']       = esc_url_raw( $output['policy_url'] );
		$output['logo_url']         = esc_url_raw( $output['logo_url'] );
		$output['cookie_days']      = max( 1, absint( $output['cookie_days'] ) );
		return $output;
	}

	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$options = $this->get_options();
		?>
		<div class="wrap"><h1><?php echo esc_html__( 'KK Lightweight Consent Settings', 'kk-lightweight-consent' ); ?></h1>
		<form method="post" action="options.php"><?php settings_fields( 'kk_lwc_settings_group' ); ?><table class="form-table" role="presentation">
		<tr><th scope="row">Banner engedélyezve</th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner_enabled]" value="1" <?php checked( $options['banner_enabled'], 1 ); ?>> Igen</label></td></tr>
		<tr><th scope="row">Banner preset</th><td><select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner_preset]"><option value="universal" <?php selected( $options['banner_preset'], 'universal' ); ?>>universal</option><option value="kk" <?php selected( $options['banner_preset'], 'kk' ); ?>>kk</option></select></td></tr>
		<tr><th scope="row">Consent version</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[consent_version]" value="<?php echo esc_attr( $options['consent_version'] ); ?>" class="regular-text"></td></tr>
		<tr><th scope="row">GTM Container ID</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gtm_container_id]" value="<?php echo esc_attr( $options['gtm_container_id'] ); ?>" class="regular-text"></td></tr>
		<tr><th scope="row">GTM snippet injektálása</th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gtm_inject]" value="1" <?php checked( $options['gtm_inject'], 1 ); ?>> Igen</label></td></tr>
		<tr><th scope="row">Logó URL</th><td><input type="url" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[logo_url]" value="<?php echo esc_attr( $options['logo_url'] ); ?>" class="regular-text"></td></tr>
		<tr><th scope="row">Süti gomb csak ikon (sarok)</th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[reopen_icon_only]" value="1" <?php checked( $options['reopen_icon_only'], 1 ); ?>> Igen</label></td></tr>
		<tr><th scope="row">Label HU - Accept all</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[label_accept_all_hu]" value="<?php echo esc_attr( $options['label_accept_all_hu'] ); ?>" class="regular-text"></td></tr>
		<tr><th scope="row">Label HU - Reject all</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[label_reject_all_hu]" value="<?php echo esc_attr( $options['label_reject_all_hu'] ); ?>" class="regular-text"></td></tr>
		<tr><th scope="row">Label HU - Customize</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[label_customize_hu]" value="<?php echo esc_attr( $options['label_customize_hu'] ); ?>" class="regular-text"></td></tr>
		<tr><th scope="row">Label HU - Save choices</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[label_save_choices_hu]" value="<?php echo esc_attr( $options['label_save_choices_hu'] ); ?>" class="regular-text"></td></tr>
		<tr><th scope="row">Label EN - Accept all</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[label_accept_all_en]" value="<?php echo esc_attr( $options['label_accept_all_en'] ); ?>" class="regular-text"></td></tr>
		<tr><th scope="row">Label EN - Reject all</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[label_reject_all_en]" value="<?php echo esc_attr( $options['label_reject_all_en'] ); ?>" class="regular-text"></td></tr>
		<tr><th scope="row">Label EN - Customize</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[label_customize_en]" value="<?php echo esc_attr( $options['label_customize_en'] ); ?>" class="regular-text"></td></tr>
		<tr><th scope="row">Label EN - Save choices</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[label_save_choices_en]" value="<?php echo esc_attr( $options['label_save_choices_en'] ); ?>" class="regular-text"></td></tr>
		<tr><th scope="row">Banner szöveg (HU)</th><td><textarea name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner_text_hu]" class="large-text" rows="3"><?php echo esc_textarea( $options['banner_text_hu'] ); ?></textarea></td></tr>
		<tr><th scope="row">Banner szöveg (EN)</th><td><textarea name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner_text_en]" class="large-text" rows="3"><?php echo esc_textarea( $options['banner_text_en'] ); ?></textarea></td></tr>
		<tr><th scope="row">Bővebb információ URL</th><td><input type="url" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[policy_url]" value="<?php echo esc_attr( $options['policy_url'] ); ?>" class="regular-text"></td></tr>
		<tr><th scope="row">Cookie élettartam (nap)</th><td><input type="number" min="1" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[cookie_days]" value="<?php echo esc_attr( $options['cookie_days'] ); ?>"></td></tr>
		<tr><th scope="row">Debug mód</th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[debug_mode]" value="1" <?php checked( $options['debug_mode'], 1 ); ?>> Igen</label></td></tr>
		</table><?php submit_button(); ?></form></div><?php
	}
}

new KK_Lightweight_Consent();
