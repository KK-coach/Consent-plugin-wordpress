<?php
/**
 * Plugin Name: Lightweight Consent Mode
 * Plugin URI: https://example.com
 * Description: Lightweight consent banner for WordPress with Google Tag Manager and Google Consent Mode v2 support.
 * Version: 0.2.0
 * Author: Consent Plugin
 * Author URI: https://example.com
 * Text Domain: lightweight-consent-mode
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

class Lightweight_Consent_Mode {
	const OPTION_KEY     = 'lcm_options';
	const LEGACY_OPTION  = 'kk_lwc_options';

	public function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'maybe_migrate_legacy_options' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_head', array( $this, 'print_consent_defaults' ), 0 );
		add_action( 'wp_footer', array( $this, 'render_banner_markup' ) );
		add_action( 'wp_head', array( $this, 'maybe_print_gtm_head' ), 1 );
		add_action( 'wp_body_open', array( $this, 'maybe_print_gtm_noscript' ) );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'lightweight-consent-mode', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public function maybe_migrate_legacy_options() {
		$current = get_option( self::OPTION_KEY, null );
		$legacy  = get_option( self::LEGACY_OPTION, null );
		if ( null === $current && is_array( $legacy ) ) {
			update_option( self::OPTION_KEY, $legacy );
		}
	}

	private function defaults() {
		return array(
			'banner_enabled'               => 1,
			'consent_version'              => 'v1',
			'gtm_container_id'             => '',
			'gtm_inject'                   => 0,
			'banner_preset'                => 'universal',
			'banner_text_hu'               => '',
			'banner_text_en'               => 'We use cookies and similar technologies to operate this website, analytics, marketing measurement and personalization. Necessary cookies are always active. Analytics, marketing and personalization measurement starts only after consent.',
			'policy_url'                   => home_url( '/' ),
			'panel_intro_hu'               => '',
			'panel_intro_en'               => 'Manage your consent preferences below.',
			'analytics_desc_hu'            => '',
			'analytics_desc_en'            => 'Allow website analytics measurement.',
			'marketing_desc_hu'            => '',
			'marketing_desc_en'            => 'Allow marketing and ad measurement tags.',
			'personalization_desc_hu'      => '',
			'personalization_desc_en'      => 'Allow personalized content and experiences.',
			'policy_link_text_hu'          => '',
			'policy_link_text_en'          => 'More information',
			'cookie_days'                  => 180,
			'logo_url'                     => '',
			'reopen_icon_only'             => 1,
			'default_analytics'            => 1,
			'default_marketing'            => 1,
			'default_personalization'      => 1,
			'design_bg_color'              => '#e8e2d8',
			'design_text_color'            => '#111111',
			'design_primary_bg'            => '#ffffff',
			'design_primary_text'          => '#111111',
			'design_secondary_bg'          => '#ffffff',
			'design_secondary_text'        => '#111111',
			'design_border_color'          => '#d4d4d4',
			'design_border_radius'         => 14,
			'design_max_width'             => 860,
			'font_preset'                  => 'system',
			'font_custom'                  => '',
			'desktop_position'             => 'center',
			'desktop_layout'               => 'box',
			'mobile_layout'                => 'sheet',
			'language_mode'                 => 'browser',
			'label_accept_all_hu'          => '',
			'label_reject_all_hu'          => '',
			'label_customize_hu'           => '',
			'label_save_choices_hu'        => '',
			'label_reopen_hu'              => '',
			'label_accept_all_en'          => 'Accept all',
			'label_reject_all_en'          => 'Reject all',
			'label_customize_en'           => 'Customize',
			'label_save_choices_en'        => 'Save choices',
			'label_reopen_en'              => 'Cookie settings',
			'debug_mode'                   => 0,
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
				'panel_buttons' => array(
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
				'panel_buttons' => array(
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


	private function allowed_html_text() {
		return array(
			'strong' => array(),
			'b'      => array(),
			'em'     => array(),
			'br'     => array(),
			'a'      => array(
				'href'   => true,
				'target' => true,
				'rel'    => true,
			),
		);
	}

	private function allowed_html_button() {
		return array(
			'strong' => array(),
			'b'      => array(),
			'em'     => array(),
		);
	}

	private function sanitize_formatted_text( $value ) {
		$value = wp_kses( (string) $value, $this->allowed_html_text() );
		return preg_replace_callback("/<a\\s+([^>]+)>/i", function ( $m ) {
			$attrs = $m[1];
			preg_match( "/href\\s*=\\s*(['\"])(.*?)\\1/i", $attrs, $hrefm );
			$href = isset( $hrefm[2] ) ? esc_url( $hrefm[2] ) : '';
			preg_match( "/target\\s*=\\s*(['\"])(.*?)\\1/i", $attrs, $tm );
			$target = isset( $tm[2] ) && '_blank' === strtolower( $tm[2] ) ? '_blank' : '';
			preg_match( "/rel\\s*=\\s*(['\"])(.*?)\\1/i", $attrs, $rm );
			$rel = isset( $rm[2] ) ? strtolower( $rm[2] ) : '';
			if ( '_blank' === $target ) {
				$rel = trim( $rel . ' noopener noreferrer' );
			}
			$out = '<a';
			if ( $href ) { $out .= ' href="' . esc_url( $href ) . '"'; }
			if ( $target ) { $out .= ' target="_blank"'; }
			if ( $rel ) { $out .= ' rel="' . esc_attr( trim( $rel ) ) . '"'; }
			$out .= '>';
			return $out;
		}, $value );
	}

	private function sanitize_button_html( $value ) {
		return wp_kses( (string) $value, $this->allowed_html_button() );
	}

	public function enqueue_assets() {
		if ( is_admin() ) {
			return;
		}
		$options = $this->get_options();
		if ( empty( $options['banner_enabled'] ) ) {
			return;
		}

		wp_enqueue_style( 'lightweight-consent-mode', plugin_dir_url( __FILE__ ) . 'assets/kk-consent.css', array(), '0.2.0' );
		wp_enqueue_script( 'lightweight-consent-mode', plugin_dir_url( __FILE__ ) . 'assets/kk-consent.js', array(), '0.2.0', true );

		$font_family = '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif';
		if ( 'inherit' === $options['font_preset'] ) {
			$font_family = 'inherit';
		} elseif ( 'arial' === $options['font_preset'] ) {
			$font_family = 'Arial, sans-serif';
		} elseif ( 'georgia' === $options['font_preset'] ) {
			$font_family = 'Georgia, serif';
		} elseif ( 'custom' === $options['font_preset'] && ! empty( $options['font_custom'] ) ) {
			$font_family = $options['font_custom'];
		}

		$inline_css = ':root{' .
			'--lcm-bg:' . esc_attr( $options['design_bg_color'] ) . ';' .
			'--lcm-text:' . esc_attr( $options['design_text_color'] ) . ';' .
			'--lcm-primary-bg:' . esc_attr( $options['design_primary_bg'] ) . ';' .
			'--lcm-primary-text:' . esc_attr( $options['design_primary_text'] ) . ';' .
			'--lcm-secondary-bg:' . esc_attr( $options['design_secondary_bg'] ) . ';' .
			'--lcm-secondary-text:' . esc_attr( $options['design_secondary_text'] ) . ';' .
			'--lcm-border:' . esc_attr( $options['design_border_color'] ) . ';' .
			'--lcm-radius:' . absint( $options['design_border_radius'] ) . 'px;' .
			'--lcm-max-width:' . absint( $options['design_max_width'] ) . 'px;' .
			'--lcm-font:' . esc_attr( $font_family ) . ';' .
		'}';
		wp_add_inline_style( 'lightweight-consent-mode', $inline_css );

		$lang      = $this->current_lang();
		$version   = sanitize_key( $options['consent_version'] );
		$store_key = 'kk_consent_' . $version;
		$presets   = $this->get_presets();
		$preset    = isset( $presets[ $options['banner_preset'] ] ) ? $options['banner_preset'] : 'universal';

		$config = array(
			'debug'                  => ! empty( $options['debug_mode'] ),
			'storageKey'             => $store_key,
			'cookieDays'             => max( 1, absint( $options['cookie_days'] ) ),
			'policyUrl'              => esc_url_raw( $options['policy_url'] ),
			'logoUrl'                => esc_url_raw( $options['logo_url'] ),
			'reopenIconOnly'         => ! empty( $options['reopen_icon_only'] ),
			'defaultAnalytics'       => ! empty( $options['default_analytics'] ),
			'defaultMarketing'       => ! empty( $options['default_marketing'] ),
			'defaultPersonalization' => ! empty( $options['default_personalization'] ),
			'languageMode'           => $options['language_mode'],
			'translations'           => array(
				'en' => array(
					'banner_text'      => $this->sanitize_formatted_text( $options['banner_text_en'] ),
					'accept_all'       => $this->sanitize_button_html( $options['label_accept_all_en'] ),
					'reject_all'       => $this->sanitize_button_html( $options['label_reject_all_en'] ),
					'customize'        => $this->sanitize_button_html( $options['label_customize_en'] ),
					'save_choices'     => $this->sanitize_button_html( $options['label_save_choices_en'] ),
					'reopen'           => wp_strip_all_tags( $options['label_reopen_en'] ),
					'more_info'        => $this->sanitize_formatted_text( $options['policy_link_text_en'] ),
					'necessary'        => 'Necessary cookies',
					'analytics'        => 'Analytics',
					'marketing'        => 'Marketing measurement',
					'personalization'  => 'Personalization',
					'panel_intro'      => $this->sanitize_formatted_text( $options['panel_intro_en'] ),
					'analytics_desc'   => $this->sanitize_formatted_text( $options['analytics_desc_en'] ),
					'marketing_desc'   => $this->sanitize_formatted_text( $options['marketing_desc_en'] ),
					'personalization_desc' => $this->sanitize_formatted_text( $options['personalization_desc_en'] ),
				),
				'hu' => array(
					'banner_text'      => $this->sanitize_formatted_text( $options['banner_text_hu'] ),
					'accept_all'       => $this->sanitize_button_html( $options['label_accept_all_hu'] ),
					'reject_all'       => $this->sanitize_button_html( $options['label_reject_all_hu'] ),
					'customize'        => $this->sanitize_button_html( $options['label_customize_hu'] ),
					'save_choices'     => $this->sanitize_button_html( $options['label_save_choices_hu'] ),
					'reopen'           => wp_strip_all_tags( $options['label_reopen_hu'] ),
					'more_info'        => $this->sanitize_formatted_text( $options['policy_link_text_hu'] ),
					'necessary'        => '',
					'analytics'        => '',
					'marketing'        => '',
					'personalization'  => '',
					'panel_intro'      => $this->sanitize_formatted_text( $options['panel_intro_hu'] ),
					'analytics_desc'   => $this->sanitize_formatted_text( $options['analytics_desc_hu'] ),
					'marketing_desc'   => $this->sanitize_formatted_text( $options['marketing_desc_hu'] ),
					'personalization_desc' => $this->sanitize_formatted_text( $options['personalization_desc_hu'] ),
				),
			),
			'preset'                 => $preset,
		);

		wp_add_inline_script( 'lightweight-consent-mode', 'window.kkConsentConfig = ' . wp_json_encode( $config ) . ';', 'before' );
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
		<script id="lcm-consent-default">window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);} (function(){var storageKey=<?php echo wp_json_encode( $store_key ); ?>;var debug=<?php echo esc_js( $debug ); ?>;var saved=null;try{saved=localStorage.getItem(storageKey);}catch(e){} if(!saved){var match=document.cookie.match(new RegExp('(^| )'+storageKey+'=([^;]+)'));if(match&&match[2]){saved=decodeURIComponent(match[2]);}} var parsed=null;if(saved){try{parsed=JSON.parse(saved);}catch(e){}} var analytics=!!(parsed&&parsed.choices&&parsed.choices.analytics);var marketing=!!(parsed&&parsed.choices&&parsed.choices.marketing);var personalization=!!(parsed&&parsed.choices&&parsed.choices.personalization);var payload={analytics_storage:analytics?'granted':'denied',ad_storage:marketing?'granted':'denied',ad_user_data:marketing?'granted':'denied',ad_personalization:marketing?'granted':'denied',functionality_storage:'granted',security_storage:'granted',personalization_storage:personalization?'granted':'denied',wait_for_update:500};gtag('consent','default',payload);var defaultEvent={event:'kk_consent_default',kk_consent_status:parsed?'saved':'unset'};dataLayer.push(defaultEvent);if(debug){console.log('[LCM] default payload',payload);console.log('[LCM] default event',defaultEvent);}})();</script>
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
		<div id="lcm-consent-root" class="lcm-consent-root" data-lang="<?php echo esc_attr( $lang ); ?>" data-desktop-position="<?php echo esc_attr( $options['desktop_position'] ); ?>" data-mobile-layout="<?php echo esc_attr( $options['mobile_layout'] ); ?>" data-desktop-layout="<?php echo esc_attr( $options['desktop_layout'] ); ?>">
			<div class="lcm-consent-banner" role="dialog" aria-live="polite" aria-label="Cookie consent" hidden>
				<img class="lcm-consent-logo" src="" alt="" hidden>
				<p class="lcm-consent-text"><?php echo wp_kses_post( $options[ 'banner_text_' . $lang ] ); ?></p>
				<a class="lcm-consent-policy" href="<?php echo esc_url( $options['policy_url'] ); ?>" target="_blank" rel="noopener noreferrer"></a>
				<div class="lcm-consent-actions"><?php $this->render_buttons( $presets[ $preset ]['banner_buttons'] ); ?></div>
				<div class="lcm-consent-panel" hidden>
					<p class="lcm-panel-intro"></p>
					<label><input type="checkbox" checked disabled> <span class="lcm-necessary-label"></span></label>
					<label><input type="checkbox" class="lcm-analytics"> <span class="lcm-analytics-label"></span> <small class="lcm-analytics-desc"></small></label>
					<label><input type="checkbox" class="lcm-marketing"> <span class="lcm-marketing-label"></span> <small class="lcm-marketing-desc"></small></label>
					<label><input type="checkbox" class="lcm-personalization"> <span class="lcm-personalization-label"></span> <small class="lcm-personalization-desc"></small></label>
					<div class="lcm-consent-panel-actions"><?php $this->render_buttons( $presets[ $preset ]['panel_buttons'] ); ?></div>
				</div>
			</div>
			<button type="button" class="lcm-consent-reopen" hidden aria-label="Cookie settings">⚙</button>
		</div>
		<?php
	}

	public function admin_menu() {
		add_options_page( esc_html__( 'Lightweight Consent Mode', 'lightweight-consent-mode' ), esc_html__( 'Lightweight Consent Mode', 'lightweight-consent-mode' ), 'manage_options', 'lightweight-consent-mode', array( $this, 'render_admin_page' ) );
	}

	public function register_settings() {
		register_setting( 'lcm_settings_group', self::OPTION_KEY, array( $this, 'sanitize_options' ) );
	}

	public function sanitize_options( $input ) {
		$defaults = $this->defaults();
		$output   = array();
		foreach ( $defaults as $key => $value ) {
			$output[ $key ] = isset( $input[ $key ] ) ? $input[ $key ] : $value;
		}
		$output['banner_enabled']          = empty( $input['banner_enabled'] ) ? 0 : 1;
		$output['gtm_inject']              = empty( $input['gtm_inject'] ) ? 0 : 1;
		$output['debug_mode']              = empty( $input['debug_mode'] ) ? 0 : 1;
		$output['reopen_icon_only']        = empty( $input['reopen_icon_only'] ) ? 0 : 1;
		$output['default_analytics']       = empty( $input['default_analytics'] ) ? 0 : 1;
		$output['default_marketing']       = empty( $input['default_marketing'] ) ? 0 : 1;
		$output['default_personalization'] = empty( $input['default_personalization'] ) ? 0 : 1;
		$output['consent_version']         = sanitize_key( $output['consent_version'] );
		$output['banner_preset']           = in_array( $output['banner_preset'], array( 'universal', 'kk' ), true ) ? $output['banner_preset'] : 'universal';
		$output['desktop_position']        = in_array( $output['desktop_position'], array( 'center', 'bottom_center', 'bottom_left', 'bottom_right' ), true ) ? $output['desktop_position'] : 'center';
		$output['desktop_layout']          = in_array( $output['desktop_layout'], array( 'box', 'sheet' ), true ) ? $output['desktop_layout'] : 'box';
		$output['mobile_layout']           = in_array( $output['mobile_layout'], array( 'sheet', 'box' ), true ) ? $output['mobile_layout'] : 'sheet';
		$output['font_preset']             = in_array( $output['font_preset'], array( 'inherit', 'system', 'arial', 'georgia', 'custom' ), true ) ? $output['font_preset'] : 'system';
		$output['language_mode']            = in_array( $output['language_mode'], array( 'browser', 'en', 'hu' ), true ) ? $output['language_mode'] : 'browser';
		$output['font_custom']             = sanitize_text_field( $output['font_custom'] );
		$output['banner_text_en']           = $this->sanitize_formatted_text( $output['banner_text_en'] );
		$output['banner_text_hu']           = $this->sanitize_formatted_text( $output['banner_text_hu'] );
		$output['panel_intro_en']           = $this->sanitize_formatted_text( $output['panel_intro_en'] );
		$output['panel_intro_hu']           = $this->sanitize_formatted_text( $output['panel_intro_hu'] );
		$output['analytics_desc_en']        = $this->sanitize_formatted_text( $output['analytics_desc_en'] );
		$output['analytics_desc_hu']        = $this->sanitize_formatted_text( $output['analytics_desc_hu'] );
		$output['marketing_desc_en']        = $this->sanitize_formatted_text( $output['marketing_desc_en'] );
		$output['marketing_desc_hu']        = $this->sanitize_formatted_text( $output['marketing_desc_hu'] );
		$output['personalization_desc_en']  = $this->sanitize_formatted_text( $output['personalization_desc_en'] );
		$output['personalization_desc_hu']  = $this->sanitize_formatted_text( $output['personalization_desc_hu'] );
		$output['policy_link_text_en']      = $this->sanitize_formatted_text( $output['policy_link_text_en'] );
		$output['policy_link_text_hu']      = $this->sanitize_formatted_text( $output['policy_link_text_hu'] );
		$output['label_accept_all_en']      = $this->sanitize_button_html( $output['label_accept_all_en'] );
		$output['label_accept_all_hu']      = $this->sanitize_button_html( $output['label_accept_all_hu'] );
		$output['label_reject_all_en']      = $this->sanitize_button_html( $output['label_reject_all_en'] );
		$output['label_reject_all_hu']      = $this->sanitize_button_html( $output['label_reject_all_hu'] );
		$output['label_customize_en']       = $this->sanitize_button_html( $output['label_customize_en'] );
		$output['label_customize_hu']       = $this->sanitize_button_html( $output['label_customize_hu'] );
		$output['label_save_choices_en']    = $this->sanitize_button_html( $output['label_save_choices_en'] );
		$output['label_save_choices_hu']    = $this->sanitize_button_html( $output['label_save_choices_hu'] );
		$output['label_reopen_en']          = sanitize_text_field( wp_strip_all_tags( $output['label_reopen_en'] ) );
		$output['label_reopen_hu']          = sanitize_text_field( wp_strip_all_tags( $output['label_reopen_hu'] ) );
		$output['gtm_container_id']        = preg_replace( '/[^A-Z0-9\-]/', '', strtoupper( sanitize_text_field( $output['gtm_container_id'] ) ) );
		$output['policy_url']              = esc_url_raw( $output['policy_url'] );
		$output['logo_url']                = esc_url_raw( $output['logo_url'] );
		$output['cookie_days']             = max( 1, min( 730, absint( $output['cookie_days'] ) ) );
		$output['design_bg_color']         = sanitize_hex_color( $output['design_bg_color'] ) ?: $defaults['design_bg_color'];
		$output['design_text_color']       = sanitize_hex_color( $output['design_text_color'] ) ?: $defaults['design_text_color'];
		$output['design_primary_bg']       = sanitize_hex_color( $output['design_primary_bg'] ) ?: $defaults['design_primary_bg'];
		$output['design_primary_text']     = sanitize_hex_color( $output['design_primary_text'] ) ?: $defaults['design_primary_text'];
		$output['design_secondary_bg']     = sanitize_hex_color( $output['design_secondary_bg'] ) ?: $defaults['design_secondary_bg'];
		$output['design_secondary_text']   = sanitize_hex_color( $output['design_secondary_text'] ) ?: $defaults['design_secondary_text'];
		$output['design_border_color']     = sanitize_hex_color( $output['design_border_color'] ) ?: $defaults['design_border_color'];
		$output['design_border_radius']    = max( 0, min( 40, absint( $output['design_border_radius'] ) ) );
		$output['design_max_width']        = max( 320, min( 1400, absint( $output['design_max_width'] ) ) );
		return $output;
	}

	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$options = $this->get_options();
		?>
		<div class="wrap"><h1><?php echo esc_html__( 'Lightweight Consent Mode', 'lightweight-consent-mode' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'lcm_settings_group' ); ?>

			<h2><?php echo esc_html__( 'General', 'lightweight-consent-mode' ); ?></h2>
			<table class="form-table"><tr><th>Banner enabled</th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner_enabled]" value="1" <?php checked( $options['banner_enabled'], 1 ); ?>> Yes</label></td></tr>
			<tr><th>Banner preset</th><td><select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner_preset]"><option value="universal" <?php selected( $options['banner_preset'], 'universal' ); ?>>universal</option><option value="kk" <?php selected( $options['banner_preset'], 'kk' ); ?>>kk</option></select></td></tr>
			<tr><th>Consent version</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[consent_version]" value="<?php echo esc_attr( $options['consent_version'] ); ?>"></td></tr>
			<tr><th>Cookie days</th><td><input type="number" min="1" max="730" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[cookie_days]" value="<?php echo esc_attr( $options['cookie_days'] ); ?>"></td></tr>
			<tr><th>Policy URL</th><td><input type="url" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[policy_url]" value="<?php echo esc_attr( $options['policy_url'] ); ?>"></td></tr>
			<tr><th>Logo URL</th><td><input type="url" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[logo_url]" value="<?php echo esc_attr( $options['logo_url'] ); ?>"></td></tr>
			<tr><th>Reopen icon only</th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[reopen_icon_only]" value="1" <?php checked( $options['reopen_icon_only'], 1 ); ?>> Yes</label></td></tr>\n			<tr><th>Frontend language mode</th><td><select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[language_mode]"><option value="browser" <?php selected( $options['language_mode'], 'browser' ); ?>>browser</option><option value="en" <?php selected( $options['language_mode'], 'en' ); ?>>en</option><option value="hu" <?php selected( $options['language_mode'], 'hu' ); ?>>hu</option></select></td></tr></table>

			<h2><?php echo esc_html__( 'Texts', 'lightweight-consent-mode' ); ?></h2>
			<table class="form-table"><tr><th>Banner text (HU)</th><td><p><small>You can use: <strong>bold</strong>, <em>emphasis</em>, <br>, and links.</small></p><textarea class="large-text" rows="3" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner_text_hu]"><?php echo esc_textarea( $options['banner_text_hu'] ); ?></textarea></td></tr>
			<tr><th>Panel intro (HU)</th><td><textarea class="large-text" rows="2" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[panel_intro_hu]"><?php echo esc_textarea( $options['panel_intro_hu'] ); ?></textarea></td></tr>
			<tr><th>Analytics description (HU)</th><td><textarea class="large-text" rows="2" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[analytics_desc_hu]"><?php echo esc_textarea( $options['analytics_desc_hu'] ); ?></textarea></td></tr>
			<tr><th>Marketing description (HU)</th><td><textarea class="large-text" rows="2" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[marketing_desc_hu]"><?php echo esc_textarea( $options['marketing_desc_hu'] ); ?></textarea></td></tr>
			<tr><th>Personalization description (HU)</th><td><textarea class="large-text" rows="2" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[personalization_desc_hu]"><?php echo esc_textarea( $options['personalization_desc_hu'] ); ?></textarea></td></tr>
			<tr><th>Policy link text (HU)</th><td><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[policy_link_text_hu]" value="<?php echo esc_attr( $options['policy_link_text_hu'] ); ?>"></td></tr>
			<tr><th>Banner text (HU)</th><td><textarea class="large-text" rows="3" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner_text_hu]"><?php echo esc_textarea( $options['banner_text_hu'] ); ?></textarea></td></tr>
			<tr><th>Banner text (EN)</th><td><textarea class="large-text" rows="3" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[banner_text_en]"><?php echo esc_textarea( $options['banner_text_en'] ); ?></textarea></td></tr>
			<tr><th>Panel intro (EN)</th><td><textarea class="large-text" rows="2" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[panel_intro_en]"><?php echo esc_textarea( $options['panel_intro_en'] ); ?></textarea></td></tr>
			<tr><th>Analytics description (EN)</th><td><textarea class="large-text" rows="2" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[analytics_desc_en]"><?php echo esc_textarea( $options['analytics_desc_en'] ); ?></textarea></td></tr>
			<tr><th>Marketing description (EN)</th><td><textarea class="large-text" rows="2" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[marketing_desc_en]"><?php echo esc_textarea( $options['marketing_desc_en'] ); ?></textarea></td></tr>
			<tr><th>Personalization description (EN)</th><td><textarea class="large-text" rows="2" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[personalization_desc_en]"><?php echo esc_textarea( $options['personalization_desc_en'] ); ?></textarea></td></tr>
			<tr><th>Policy link text (EN)</th><td><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[policy_link_text_en]" value="<?php echo esc_attr( $options['policy_link_text_en'] ); ?>"></td></tr>
			<tr><th>HU accept_all</th><td><p><small>Button labels allow: <strong>bold</strong>, <b>bold</b>, <em>emphasis</em>.</small></p><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[label_accept_all_hu]" value="<?php echo esc_attr( $options['label_accept_all_hu'] ); ?>"></td></tr>
			<tr><th>HU reject_all</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[label_accept_all_hu]" value="<?php echo esc_attr( $options['label_accept_all_hu'] ); ?>"></td></tr>
			<tr><th>HU reject_all</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[label_reject_all_hu]" value="<?php echo esc_attr( $options['label_reject_all_hu'] ); ?>"></td></tr>
			<tr><th>HU customize</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[label_customize_hu]" value="<?php echo esc_attr( $options['label_customize_hu'] ); ?>"></td></tr>
			<tr><th>HU save_choices</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[label_save_choices_hu]" value="<?php echo esc_attr( $options['label_save_choices_hu'] ); ?>"></td></tr>
			<tr><th>EN accept_all</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[label_accept_all_en]" value="<?php echo esc_attr( $options['label_accept_all_en'] ); ?>"></td></tr>
			<tr><th>EN reject_all</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[label_reject_all_en]" value="<?php echo esc_attr( $options['label_reject_all_en'] ); ?>"></td></tr>
			<tr><th>EN customize</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[label_customize_en]" value="<?php echo esc_attr( $options['label_customize_en'] ); ?>"></td></tr>
			<tr><th>EN save_choices</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[label_save_choices_en]" value="<?php echo esc_attr( $options['label_save_choices_en'] ); ?>"></td></tr></table>

			<h2><?php echo esc_html__( 'Design', 'lightweight-consent-mode' ); ?></h2>
			<table class="form-table"><tr><th>Background color</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[design_bg_color]" value="<?php echo esc_attr( $options['design_bg_color'] ); ?>"></td></tr>
			<tr><th>Text color</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[design_text_color]" value="<?php echo esc_attr( $options['design_text_color'] ); ?>"></td></tr>
			<tr><th>Primary button background</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[design_primary_bg]" value="<?php echo esc_attr( $options['design_primary_bg'] ); ?>"></td></tr>
			<tr><th>Primary button text</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[design_primary_text]" value="<?php echo esc_attr( $options['design_primary_text'] ); ?>"></td></tr>
			<tr><th>Secondary button background</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[design_secondary_bg]" value="<?php echo esc_attr( $options['design_secondary_bg'] ); ?>"></td></tr>
			<tr><th>Secondary button text</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[design_secondary_text]" value="<?php echo esc_attr( $options['design_secondary_text'] ); ?>"></td></tr>
			<tr><th>Border color</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[design_border_color]" value="<?php echo esc_attr( $options['design_border_color'] ); ?>"></td></tr>
			<tr><th>Border radius</th><td><input type="number" min="0" max="40" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[design_border_radius]" value="<?php echo esc_attr( $options['design_border_radius'] ); ?>"></td></tr>
			<tr><th>Max width</th><td><input type="number" min="320" max="1400" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[design_max_width]" value="<?php echo esc_attr( $options['design_max_width'] ); ?>"></td></tr>
			<tr><th>Font preset</th><td><select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[font_preset]"><option value="inherit" <?php selected( $options['font_preset'], 'inherit' ); ?>>inherit</option><option value="system" <?php selected( $options['font_preset'], 'system' ); ?>>system</option><option value="arial" <?php selected( $options['font_preset'], 'arial' ); ?>>arial</option><option value="georgia" <?php selected( $options['font_preset'], 'georgia' ); ?>>georgia</option><option value="custom" <?php selected( $options['font_preset'], 'custom' ); ?>>custom</option></select></td></tr>
			<tr><th>Custom font family</th><td><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[font_custom]" value="<?php echo esc_attr( $options['font_custom'] ); ?>"></td></tr></table>

			<h2><?php echo esc_html__( 'Layout', 'lightweight-consent-mode' ); ?></h2>
			<table class="form-table"><tr><th>Desktop position</th><td><select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[desktop_position]"><option value="center" <?php selected( $options['desktop_position'], 'center' ); ?>>center</option><option value="bottom_center" <?php selected( $options['desktop_position'], 'bottom_center' ); ?>>bottom_center</option><option value="bottom_left" <?php selected( $options['desktop_position'], 'bottom_left' ); ?>>bottom_left</option><option value="bottom_right" <?php selected( $options['desktop_position'], 'bottom_right' ); ?>>bottom_right</option></select></td></tr>
			<tr><th>Desktop layout</th><td><select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[desktop_layout]"><option value="box" <?php selected( $options['desktop_layout'], 'box' ); ?>>box</option><option value="sheet" <?php selected( $options['desktop_layout'], 'sheet' ); ?>>sheet</option></select></td></tr>
			<tr><th>Mobile layout</th><td><select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[mobile_layout]"><option value="sheet" <?php selected( $options['mobile_layout'], 'sheet' ); ?>>sheet</option><option value="box" <?php selected( $options['mobile_layout'], 'box' ); ?>>box</option></select></td></tr></table>

			<h2><?php echo esc_html__( 'GTM / Consent Mode', 'lightweight-consent-mode' ); ?></h2>
			<table class="form-table"><tr><th>GTM Container ID</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gtm_container_id]" value="<?php echo esc_attr( $options['gtm_container_id'] ); ?>"></td></tr>
			<tr><th>Inject GTM snippet</th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[gtm_inject]" value="1" <?php checked( $options['gtm_inject'], 1 ); ?>> Yes</label></td></tr>
			<tr><th>Default analytics checked</th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_analytics]" value="1" <?php checked( $options['default_analytics'], 1 ); ?>> Yes</label></td></tr>
			<tr><th>Default marketing checked</th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_marketing]" value="1" <?php checked( $options['default_marketing'], 1 ); ?>> Yes</label></td></tr>
			<tr><th>Default personalization checked</th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_personalization]" value="1" <?php checked( $options['default_personalization'], 1 ); ?>> Yes</label></td></tr>
			<tr><th>Debug mode</th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[debug_mode]" value="1" <?php checked( $options['debug_mode'], 1 ); ?>> Yes</label></td></tr></table>

			<?php submit_button(); ?>
		</form>
		</div>
		<?php
	}
}

new Lightweight_Consent_Mode();
