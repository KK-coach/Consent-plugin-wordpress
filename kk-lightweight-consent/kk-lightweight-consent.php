<?php
/**
 * Plugin Name: Lightweight Consent Mode
 * Plugin URI: https://example.com
 * Description: Lightweight consent banner for WordPress with Google Tag Manager and Google Consent Mode v2 support.
 * Version: 0.3.6
 * Author: Consent Plugin
 * Author URI: https://example.com
 * Text Domain: lightweight-consent-mode
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

class Lightweight_Consent_Mode {
	const LCM_VERSION   = '0.3.6';
	const OPTION_KEY    = 'lcm_options';
	const LEGACY_OPTION = 'kk_lwc_options';

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
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
	}

	public function plugin_action_links( $links ) {
		$url = admin_url( 'options-general.php?page=lightweight-consent-mode' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">Settings</a>' );
		return $links;
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
			'banner_enabled'            => 1,
			'language_mode'             => 'browser',
			'banner_preset'             => 'universal',
			'banner_order'              => 'accept_all,reject_all,settings',
			'panel_order'               => 'save_choices',
			'consent_version'           => 'v1',
			'cookie_days'               => 180,
			'policy_url'                => home_url( '/' ),
			'logo_url'                  => '',
			'reopen_icon_only'          => 1,
			'default_analytics'         => 0,
			'default_marketing'         => 0,
			'default_personalization'   => 0,
			'desktop_position'          => 'center',
			'desktop_layout'            => 'box',
			'mobile_layout'             => 'sheet',
			'design_bg_color'           => '#e8e2d8',
			'design_text_color'         => '#111111',
			'design_header_text_color'  => '#111111',
			'design_border_color'       => '#d4d4d4',
			'design_border_width'       => 1,
			'design_border_radius'      => 14,
			'design_max_width'          => 860,
			'banner_padding'            => 10,
			'button_padding_y'          => 10,
			'button_padding_x'          => 18,
			'button_radius'             => 0,
			'header_font_preset'        => 'inherit',
			'header_custom_font'        => '',
			'header_font_size'          => 22,
			'header_font_weight'        => 700,
			'body_font_preset'          => 'inherit',
			'body_custom_font'          => '',
			'button_font_preset'        => 'inherit',
			'button_custom_font'        => '',
			'btn_accept_bg'             => '#ffffff', 'btn_accept_text' => '#111111', 'btn_accept_border' => '#111111',
			'btn_reject_bg'             => '#111111', 'btn_reject_text' => '#ffffff', 'btn_reject_border' => '#111111',
			'btn_settings_bg'           => '#ffffff', 'btn_settings_text' => '#111111', 'btn_settings_border' => '#111111',
			'btn_save_bg'               => '#ffffff', 'btn_save_text' => '#111111', 'btn_save_border' => '#111111',

			'btn_accept_hover_bg'       => '', 'btn_accept_hover_text' => '', 'btn_accept_hover_border' => '',
			'btn_reject_hover_bg'       => '', 'btn_reject_hover_text' => '', 'btn_reject_hover_border' => '',
			'btn_settings_hover_bg'     => '', 'btn_settings_hover_text' => '', 'btn_settings_hover_border' => '',
			'btn_save_hover_bg'         => '', 'btn_save_hover_text' => '', 'btn_save_hover_border' => '',

			'banner_title_en'           => 'Your privacy choices',
			'banner_title_hu'           => '',
			'banner_text_en'            => 'We use cookies and similar technologies to operate this website, analytics, marketing measurement and personalization.',
			'banner_text_hu'            => '',
			'panel_intro_en'            => 'Manage your consent preferences below.',
			'panel_intro_hu'            => '',
			'necessary_label_en'        => 'Necessary cookies',
			'necessary_label_hu'        => '',
			'analytics_label_en'        => 'Analytics',
			'analytics_label_hu'        => '',
			'marketing_label_en'        => 'Marketing',
			'marketing_label_hu'        => '',
			'personalization_label_en'  => 'Personalization',
			'personalization_label_hu'  => '',
			'necessary_desc_en'         => 'Necessary cookies are always active.',
			'necessary_desc_hu'         => '',
			'analytics_desc_en'         => 'Allow website analytics measurement.',
			'analytics_desc_hu'         => '',
			'marketing_desc_en'         => 'Allow marketing and ad measurement tags.',
			'marketing_desc_hu'         => '',
			'personalization_desc_en'   => 'Allow personalized content and experiences.',
			'personalization_desc_hu'   => '',
			'policy_link_text_en'       => 'More information',
			'policy_link_text_hu'       => '',
			'label_accept_all_en'       => 'Accept all', 'label_accept_all_hu' => '',
			'label_reject_all_en'       => 'Reject all', 'label_reject_all_hu' => '',
			'label_customize_en'        => 'Customize',  'label_customize_hu' => '',
			'label_save_choices_en'     => 'Save choices','label_save_choices_hu' => '',
			'label_reopen_en'           => 'Cookie settings', 'label_reopen_hu' => '',
			'dialog_label_en'           => 'Cookie consent',
			'dialog_label_hu'           => '',
			'gtm_container_id'          => '',
			'gtm_inject'                => 0,
			'debug_mode'                => 0,
		);
	}

	private function get_options() { return wp_parse_args( get_option( self::OPTION_KEY, array() ), $this->defaults() ); }
	private function allowed_html_text() { return array( 'strong'=>array(), 'b'=>array(), 'em'=>array(), 'br'=>array(), 'a'=>array('href'=>true,'target'=>true,'rel'=>true) ); }
	private function allowed_html_button() { return array( 'strong'=>array(), 'b'=>array(), 'em'=>array() ); }
	private function sanitize_formatted_text( $v ) { return wp_kses( (string) $v, $this->allowed_html_text() ); }
	private function sanitize_button_html( $v ) { return wp_kses( (string) $v, $this->allowed_html_button() ); }
	private function sanitize_font_preset( $v ) { return in_array( $v, array( 'inherit','system','arial','georgia','custom' ), true ) ? $v : 'inherit'; }

	private function sanitize_font_family( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value || ! preg_match( '/^[A-Za-z0-9 ,_\-"\']+$/', $value ) ) {
			return '';
		}
		return $value;
	}

	private function font_family_from( $preset, $custom ) {
		$stacks = array(
			'inherit' => 'inherit',
			'system'  => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
			'arial'   => 'Arial, Helvetica, sans-serif',
			'georgia' => 'Georgia, serif',
		);
		if ( isset( $stacks[ $preset ] ) ) {
			return $stacks[ $preset ];
		}
		if ( 'custom' === $preset ) {
			$custom = $this->sanitize_font_family( $custom );
			return '' === $custom ? 'inherit' : $custom;
		}
		return 'inherit';
	}

	private function get_presets() {
		return array(
			'kk' => array(
				'banner_buttons' => array(
					array( 'action' => 'accept_all', 'label_key' => 'accept_all', 'style' => 'primary' ),
					array( 'action' => 'settings', 'label_key' => 'customize', 'style' => 'secondary' ),
				),
				'panel_buttons' => array(
					array( 'action' => 'save_choices', 'label_key' => 'save_choices', 'style' => 'primary' ),
				),
			),
			'universal' => array(
				'banner_buttons' => array(
					array( 'action' => 'accept_all', 'label_key' => 'accept_all', 'style' => 'primary' ),
					array( 'action' => 'reject_all', 'label_key' => 'reject_all', 'style' => 'outline' ),
					array( 'action' => 'settings', 'label_key' => 'customize', 'style' => 'secondary' ),
				),
				'panel_buttons' => array(
					array( 'action' => 'save_choices', 'label_key' => 'save_choices', 'style' => 'primary' ),
				),
			),
		);
	}

	private function ordered_banner_buttons( $buttons, $order_string ) {
		$allowed = array( 'accept_all', 'reject_all', 'settings' );
		$parts = array_map( 'trim', explode( ',', (string) $order_string ) );
		if ( 3 !== count( $parts ) || count( array_unique( $parts ) ) !== 3 || array_diff( $parts, $allowed ) ) {
			$parts = array( 'accept_all', 'reject_all', 'settings' );
		}
		$map = array();
		foreach ( $buttons as $b ) { $map[ $b['action'] ] = $b; }
		$out = array();
		foreach ( $parts as $a ) { if ( isset( $map[ $a ] ) ) { $out[] = $map[ $a ]; } }
		foreach ( $buttons as $b ) { if ( ! in_array( $b['action'], $parts, true ) ) { $out[] = $b; } }
		return $out;
	}


	private function ordered_panel_buttons( $buttons, $order_string ) {
		$allowed = array( 'save_choices', 'reject_all' );
		$parts = array_filter( array_map( 'trim', explode( ',', (string) $order_string ) ) );
		if ( empty( $parts ) || array_diff( $parts, $allowed ) ) {
			$parts = array( 'save_choices' );
		}
		$map = array();
		foreach ( $buttons as $b ) { $map[ $b['action'] ] = $b; }
		$out = array();
		foreach ( $parts as $a ) { if ( isset( $map[ $a ] ) ) { $out[] = $map[ $a ]; } }
		if ( empty( $out ) ) { return array( array( 'action' => 'save_choices', 'label_key' => 'save_choices', 'style' => 'primary' ) ); }
		return $out;
	}

	private function render_buttons( $buttons ) {
		foreach ( $buttons as $button ) {
			echo '<button type="button" class="lcm-btn lcm-btn--' . esc_attr( sanitize_html_class( $button['style'] ) ) . '" data-consent-action="' . esc_attr( $button['action'] ) . '" data-label-key="' . esc_attr( $button['label_key'] ) . '"></button>';
		}
	}

	private function admin_text_input( $label, $key, $value, $description = '' ) {
		echo '<tr><th>' . esc_html( $label ) . '</th><td><input class="regular-text" name="' . esc_attr( self::OPTION_KEY ) . '[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '">';
		if ( '' !== $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
		echo '</td></tr>';
	}

	private function admin_textarea( $label, $key, $value, $description = '' ) {
		echo '<tr><th>' . esc_html( $label ) . '</th><td><textarea class="large-text" rows="3" name="' . esc_attr( self::OPTION_KEY ) . '[' . esc_attr( $key ) . ']">' . esc_textarea( $value ) . '</textarea>';
		if ( '' !== $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
		echo '</td></tr>';
	}

	public function enqueue_assets() {
		if ( is_admin() ) { return; }
		$options = $this->get_options();
		if ( empty( $options['banner_enabled'] ) ) { return; }

		wp_enqueue_style( 'lightweight-consent-mode', plugin_dir_url( __FILE__ ) . 'assets/kk-consent.css', array(), self::LCM_VERSION );
		wp_enqueue_script( 'lightweight-consent-mode', plugin_dir_url( __FILE__ ) . 'assets/kk-consent.js', array(), self::LCM_VERSION, true );

		$inline_css = ':root{' .
			'--lcm-bg:' . esc_attr( $options['design_bg_color'] ) . ';' .
			'--lcm-text:' . esc_attr( $options['design_text_color'] ) . ';' .
			'--lcm-header-text:' . esc_attr( $options['design_header_text_color'] ) . ';' .
			'--lcm-border-color:' . esc_attr( $options['design_border_color'] ) . ';' .
			'--lcm-border-width:' . absint( $options['design_border_width'] ) . 'px;' .
			'--lcm-radius:' . absint( $options['design_border_radius'] ) . 'px;' .
			'--lcm-max-width:' . absint( $options['design_max_width'] ) . 'px;' .
			'--lcm-banner-padding:' . absint( $options['banner_padding'] ) . 'px;' .
			'--lcm-button-padding-y:' . absint( $options['button_padding_y'] ) . 'px;' .
			'--lcm-button-padding-x:' . absint( $options['button_padding_x'] ) . 'px;' .
			'--lcm-button-radius:' . absint( $options['button_radius'] ) . 'px;' .
			'--lcm-header-font:' . $this->font_family_from( $options['header_font_preset'], $options['header_custom_font'] ) . ';' .
			'--lcm-body-font:' . $this->font_family_from( $options['body_font_preset'], $options['body_custom_font'] ) . ';' .
			'--lcm-button-font:' . $this->font_family_from( $options['button_font_preset'], $options['button_custom_font'] ) . ';' .
			'--lcm-header-size:' . absint( $options['header_font_size'] ) . 'px;' .
			'--lcm-header-weight:' . absint( $options['header_font_weight'] ) . ';' .
			'--lcm-accept-bg:' . esc_attr( $options['btn_accept_bg'] ) . ';--lcm-accept-text:' . esc_attr( $options['btn_accept_text'] ) . ';--lcm-accept-border:' . esc_attr( $options['btn_accept_border'] ) . ';' .
			'--lcm-reject-bg:' . esc_attr( $options['btn_reject_bg'] ) . ';--lcm-reject-text:' . esc_attr( $options['btn_reject_text'] ) . ';--lcm-reject-border:' . esc_attr( $options['btn_reject_border'] ) . ';' .
			'--lcm-settings-bg:' . esc_attr( $options['btn_settings_bg'] ) . ';--lcm-settings-text:' . esc_attr( $options['btn_settings_text'] ) . ';--lcm-settings-border:' . esc_attr( $options['btn_settings_border'] ) . ';' .
			'--lcm-save-bg:' . esc_attr( $options['btn_save_bg'] ) . ';--lcm-save-text:' . esc_attr( $options['btn_save_text'] ) . ';--lcm-save-border:' . esc_attr( $options['btn_save_border'] ) . ';' .
			'--lcm-accept-hover-bg:' . esc_attr( $options['btn_accept_hover_bg'] ?: $options['btn_accept_bg'] ) . ';--lcm-accept-hover-text:' . esc_attr( $options['btn_accept_hover_text'] ?: $options['btn_accept_text'] ) . ';--lcm-accept-hover-border:' . esc_attr( $options['btn_accept_hover_border'] ?: $options['btn_accept_border'] ) . ';' .
			'--lcm-reject-hover-bg:' . esc_attr( $options['btn_reject_hover_bg'] ?: $options['btn_reject_bg'] ) . ';--lcm-reject-hover-text:' . esc_attr( $options['btn_reject_hover_text'] ?: $options['btn_reject_text'] ) . ';--lcm-reject-hover-border:' . esc_attr( $options['btn_reject_hover_border'] ?: $options['btn_reject_border'] ) . ';' .
			'--lcm-settings-hover-bg:' . esc_attr( $options['btn_settings_hover_bg'] ?: $options['btn_settings_bg'] ) . ';--lcm-settings-hover-text:' . esc_attr( $options['btn_settings_hover_text'] ?: $options['btn_settings_text'] ) . ';--lcm-settings-hover-border:' . esc_attr( $options['btn_settings_hover_border'] ?: $options['btn_settings_border'] ) . ';' .
			'--lcm-save-hover-bg:' . esc_attr( $options['btn_save_hover_bg'] ?: $options['btn_save_bg'] ) . ';--lcm-save-hover-text:' . esc_attr( $options['btn_save_hover_text'] ?: $options['btn_save_text'] ) . ';--lcm-save-hover-border:' . esc_attr( $options['btn_save_hover_border'] ?: $options['btn_save_border'] ) . ';' .
		'}';
		wp_add_inline_style( 'lightweight-consent-mode', $inline_css );

		$presets   = $this->get_presets();
		$preset    = isset( $presets[ $options['banner_preset'] ] ) ? $options['banner_preset'] : 'universal';
		$config = array(
			'debug'                  => ! empty( $options['debug_mode'] ),
			'storageKey'             => 'kk_consent_' . sanitize_key( $options['consent_version'] ),
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
					'banner_title' => $this->sanitize_formatted_text( $options['banner_title_en'] ), 'banner_text' => $this->sanitize_formatted_text( $options['banner_text_en'] ), 'panel_intro' => $this->sanitize_formatted_text( $options['panel_intro_en'] ),
					'accept_all' => $this->sanitize_button_html( $options['label_accept_all_en'] ), 'reject_all' => $this->sanitize_button_html( $options['label_reject_all_en'] ), 'customize' => $this->sanitize_button_html( $options['label_customize_en'] ), 'save_choices' => $this->sanitize_button_html( $options['label_save_choices_en'] ), 'reopen_html' => $this->sanitize_button_html( $options['label_reopen_en'] ), 'reopen' => sanitize_text_field( wp_strip_all_tags( $options['label_reopen_en'] ) ), 'dialog_label' => sanitize_text_field( wp_strip_all_tags( $options['dialog_label_en'] ) ),
					'necessary' => sanitize_text_field( wp_strip_all_tags( $options['necessary_label_en'] ) ), 'analytics' => sanitize_text_field( wp_strip_all_tags( $options['analytics_label_en'] ) ), 'marketing' => sanitize_text_field( wp_strip_all_tags( $options['marketing_label_en'] ) ), 'personalization' => sanitize_text_field( wp_strip_all_tags( $options['personalization_label_en'] ) ),
					'necessary_desc' => $this->sanitize_formatted_text( $options['necessary_desc_en'] ), 'analytics_desc' => $this->sanitize_formatted_text( $options['analytics_desc_en'] ), 'marketing_desc' => $this->sanitize_formatted_text( $options['marketing_desc_en'] ), 'personalization_desc' => $this->sanitize_formatted_text( $options['personalization_desc_en'] ),
					'more_info' => $this->sanitize_formatted_text( $options['policy_link_text_en'] ),
				),
				'hu' => array(
					'banner_title' => $this->sanitize_formatted_text( $options['banner_title_hu'] ), 'banner_text' => $this->sanitize_formatted_text( $options['banner_text_hu'] ), 'panel_intro' => $this->sanitize_formatted_text( $options['panel_intro_hu'] ),
					'accept_all' => $this->sanitize_button_html( $options['label_accept_all_hu'] ), 'reject_all' => $this->sanitize_button_html( $options['label_reject_all_hu'] ), 'customize' => $this->sanitize_button_html( $options['label_customize_hu'] ), 'save_choices' => $this->sanitize_button_html( $options['label_save_choices_hu'] ), 'reopen_html' => $this->sanitize_button_html( $options['label_reopen_hu'] ), 'reopen' => sanitize_text_field( wp_strip_all_tags( $options['label_reopen_hu'] ) ), 'dialog_label' => sanitize_text_field( wp_strip_all_tags( $options['dialog_label_hu'] ) ),
					'necessary' => sanitize_text_field( wp_strip_all_tags( $options['necessary_label_hu'] ) ), 'analytics' => sanitize_text_field( wp_strip_all_tags( $options['analytics_label_hu'] ) ), 'marketing' => sanitize_text_field( wp_strip_all_tags( $options['marketing_label_hu'] ) ), 'personalization' => sanitize_text_field( wp_strip_all_tags( $options['personalization_label_hu'] ) ),
					'necessary_desc' => $this->sanitize_formatted_text( $options['necessary_desc_hu'] ), 'analytics_desc' => $this->sanitize_formatted_text( $options['analytics_desc_hu'] ), 'marketing_desc' => $this->sanitize_formatted_text( $options['marketing_desc_hu'] ), 'personalization_desc' => $this->sanitize_formatted_text( $options['personalization_desc_hu'] ), 'more_info' => $this->sanitize_formatted_text( $options['policy_link_text_hu'] ),
				),
			),
		);
		wp_add_inline_script( 'lightweight-consent-mode', 'window.kkConsentConfig = ' . wp_json_encode( $config ) . ';', 'before' );
	}

	public function print_consent_defaults() {
		if ( is_admin() ) { return; }
		$options = $this->get_options();
		if ( empty( $options['banner_enabled'] ) ) { return; }
		$store_key = 'kk_consent_' . sanitize_key( $options['consent_version'] );
		$debug = ! empty( $options['debug_mode'] ) ? 'true' : 'false';
		?>
		<script id="lcm-consent-default">window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);} (function(){var storageKey=<?php echo wp_json_encode( $store_key ); ?>;var debug=<?php echo esc_js( $debug ); ?>;var saved=null;try{saved=localStorage.getItem(storageKey);}catch(e){} if(!saved){var match=document.cookie.match(new RegExp('(^| )'+storageKey+'=([^;]+)'));if(match&&match[2]){saved=decodeURIComponent(match[2]);}} var parsed=null;if(saved){try{parsed=JSON.parse(saved);}catch(e){}} var analytics=!!(parsed&&parsed.choices&&parsed.choices.analytics);var marketing=!!(parsed&&parsed.choices&&parsed.choices.marketing);var personalization=!!(parsed&&parsed.choices&&parsed.choices.personalization);var analyticsState=analytics?'granted':'denied';var marketingState=marketing?'granted':'denied';var personalizationState=personalization?'granted':'denied';var payload={analytics_storage:analyticsState,ad_storage:marketingState,ad_user_data:marketingState,ad_personalization:marketingState,functionality_storage:'granted',security_storage:'granted',personalization_storage:personalizationState,wait_for_update:500};gtag('consent','default',payload);var defaultEvent={event:'kk_consent_default',kk_consent_status:parsed?'saved':'unset',kk_consent_analytics:analyticsState,kk_consent_marketing:marketingState,kk_consent_personalization:personalizationState};dataLayer.push(defaultEvent);if(parsed){dataLayer.push({event:'kk_consent_ready',kk_consent_status:'saved',kk_consent_analytics:analyticsState,kk_consent_marketing:marketingState,kk_consent_personalization:personalizationState});window.lcmConsentReadyPushed=true;}if(debug){console.log('[LCM] default payload',payload);console.log('[LCM] default event',defaultEvent);}})();</script>
		<?php
	}

	public function maybe_print_gtm_head() { $o = $this->get_options(); if ( is_admin() || empty( $o['gtm_inject'] ) || empty( $o['gtm_container_id'] ) ) { return; } $id = preg_replace( '/[^A-Z0-9\-]/', '', strtoupper( $o['gtm_container_id'] ) ); ?><script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo esc_js( $id ); ?>');</script><?php }
	public function maybe_print_gtm_noscript() { $o = $this->get_options(); if ( empty( $o['gtm_inject'] ) || empty( $o['gtm_container_id'] ) ) { return; } $id = preg_replace( '/[^A-Z0-9\-]/', '', strtoupper( $o['gtm_container_id'] ) ); echo '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . esc_attr( $id ) . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>'; }

	public function render_banner_markup() {
		if ( is_admin() ) { return; }
		$o = $this->get_options();
		if ( empty( $o['banner_enabled'] ) ) { return; }
		$presets = $this->get_presets();
		$preset = isset( $presets[ $o['banner_preset'] ] ) ? $o['banner_preset'] : 'universal';
		$banner_buttons = $this->ordered_banner_buttons( $presets[ $preset ]['banner_buttons'], $o['banner_order'] );
		$panel_buttons  = $this->ordered_panel_buttons( $presets[ $preset ]['panel_buttons'], $o['panel_order'] );
		?>
		<div id="lcm-consent-root" class="lcm-consent-root" data-desktop-position="<?php echo esc_attr( $o['desktop_position'] ); ?>" data-mobile-layout="<?php echo esc_attr( $o['mobile_layout'] ); ?>" data-desktop-layout="<?php echo esc_attr( $o['desktop_layout'] ); ?>">
			<div class="lcm-consent-banner" role="dialog" aria-live="polite" aria-label="" hidden>
				<img class="lcm-consent-logo" src="" alt="" hidden>
				<h3 class="lcm-consent-title"></h3>
				<p class="lcm-consent-text"></p>
				<a class="lcm-consent-policy" href="<?php echo esc_url( $o['policy_url'] ); ?>" target="_blank" rel="noopener noreferrer"></a>
				<div class="lcm-consent-actions"><?php $this->render_buttons( $banner_buttons ); ?></div>
				<div class="lcm-consent-panel" hidden>
					<p class="lcm-panel-intro"></p>
					<label><input type="checkbox" checked disabled> <span class="lcm-necessary-label"></span> <small class="lcm-necessary-desc"></small></label>
					<label><input type="checkbox" class="lcm-analytics"> <span class="lcm-analytics-label"></span> <small class="lcm-analytics-desc"></small></label>
					<label><input type="checkbox" class="lcm-marketing"> <span class="lcm-marketing-label"></span> <small class="lcm-marketing-desc"></small></label>
					<label><input type="checkbox" class="lcm-personalization"> <span class="lcm-personalization-label"></span> <small class="lcm-personalization-desc"></small></label>
					<div class="lcm-consent-panel-actions"><?php $this->render_buttons( $panel_buttons ); ?></div>
				</div>
			</div>
			<button type="button" class="lcm-reopen-button lcm-consent-reopen" hidden aria-label="Cookie settings" title="Cookie settings">
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<path d="M10.5 2.5a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1l.2 1.5a8.8 8.8 0 0 1 2 .8l1.2-.9a1 1 0 0 1 1.4.1l.7.7a1 1 0 0 1 .1 1.4l-.9 1.2c.3.6.6 1.3.8 2l1.5.2a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1l-1.5.2a8.8 8.8 0 0 1-.8 2l.9 1.2a1 1 0 0 1-.1 1.4l-.7.7a1 1 0 0 1-1.4.1l-1.2-.9c-.6.3-1.3.6-2 .8l-.2 1.5a1 1 0 0 1-1 1h-1a1 1 0 0 1-1-1l-.2-1.5a8.8 8.8 0 0 1-2-.8l-1.2.9a1 1 0 0 1-1.4-.1l-.7-.7a1 1 0 0 1-.1-1.4l.9-1.2a8.8 8.8 0 0 1-.8-2L1.5 12.5a1 1 0 0 1-1-1v-1a1 1 0 0 1 1-1l1.5-.2a8.8 8.8 0 0 1 .8-2l-.9-1.2a1 1 0 0 1 .1-1.4l.7-.7a1 1 0 0 1 1.4-.1l1.2.9c.6-.3 1.3-.6 2-.8zm1.5 6a4 4 0 1 0 0 8 4 4 0 0 0 0-8z"/>
				</svg>
			</button>
		</div>
		<?php
	}

	public function admin_menu() { add_options_page( 'Lightweight Consent Mode', 'Lightweight Consent Mode', 'manage_options', 'lightweight-consent-mode', array( $this, 'render_admin_page' ) ); }
	public function register_settings() { register_setting( 'lcm_settings_group', self::OPTION_KEY, array( $this, 'sanitize_options' ) ); }

	public function sanitize_options( $input ) {
		$d = $this->defaults(); $o = array(); foreach ( $d as $k=>$v ) { $o[$k] = isset($input[$k]) ? $input[$k] : $v; }
		foreach ( array('banner_enabled','gtm_inject','debug_mode','reopen_icon_only','default_analytics','default_marketing','default_personalization') as $k ) { $o[$k] = empty($input[$k]) ? 0 : 1; }
		$o['language_mode'] = in_array( $o['language_mode'], array('browser','en','hu'), true ) ? $o['language_mode'] : 'browser';
		$o['banner_preset'] = in_array( $o['banner_preset'], array('universal','kk'), true ) ? $o['banner_preset'] : 'universal';
		$o['desktop_position'] = in_array( $o['desktop_position'], array('center','bottom_center','bottom_left','bottom_right'), true ) ? $o['desktop_position'] : 'center';
		$o['desktop_layout'] = in_array( $o['desktop_layout'], array('box','sheet'), true ) ? $o['desktop_layout'] : 'box';
		$o['mobile_layout'] = in_array( $o['mobile_layout'], array('sheet','box'), true ) ? $o['mobile_layout'] : 'sheet';
		$o['consent_version'] = sanitize_key( $o['consent_version'] );
		$o['banner_order'] = sanitize_text_field( $o['banner_order'] );
		$o['panel_order'] = sanitize_text_field( $o['panel_order'] );
		$o['gtm_container_id'] = preg_replace('/[^A-Z0-9\-]/','',strtoupper(sanitize_text_field($o['gtm_container_id'])));
		$o['policy_url'] = esc_url_raw( $o['policy_url'] ); $o['logo_url'] = esc_url_raw( $o['logo_url'] );
		$o['cookie_days'] = max(1,min(730,absint($o['cookie_days'])));
		foreach ( array('design_border_radius'=>array(0,40),'design_max_width'=>array(320,1400),'banner_padding'=>array(0,64),'button_padding_y'=>array(0,32),'button_padding_x'=>array(0,64),'button_radius'=>array(0,40),'design_border_width'=>array(0,10),'header_font_size'=>array(10,64),'header_font_weight'=>array(100,900)) as $k=>$rng ) { $o[$k]=max($rng[0],min($rng[1],absint($o[$k]))); }
		$fontFields=array('header_font_preset','body_font_preset','button_font_preset'); foreach($fontFields as $k){$o[$k]=$this->sanitize_font_preset($o[$k]);}
		foreach(array('header_custom_font','body_custom_font','button_custom_font') as $k){$o[$k]=$this->sanitize_font_family($o[$k]);}
		foreach(array('design_bg_color','design_text_color','design_header_text_color','design_border_color','btn_accept_bg','btn_accept_text','btn_accept_border','btn_reject_bg','btn_reject_text','btn_reject_border','btn_settings_bg','btn_settings_text','btn_settings_border','btn_save_bg','btn_save_text','btn_save_border') as $k){$o[$k]=sanitize_hex_color($o[$k])?:$d[$k];}
		foreach(array('btn_accept_hover_bg','btn_accept_hover_text','btn_accept_hover_border','btn_reject_hover_bg','btn_reject_hover_text','btn_reject_hover_border','btn_settings_hover_bg','btn_settings_hover_text','btn_settings_hover_border','btn_save_hover_bg','btn_save_hover_text','btn_save_hover_border') as $k){$v=sanitize_hex_color($o[$k]);$o[$k]=$v?:'';}
		foreach(array('banner_title_en','banner_title_hu','banner_text_en','banner_text_hu','panel_intro_en','panel_intro_hu','necessary_desc_en','necessary_desc_hu','analytics_desc_en','analytics_desc_hu','marketing_desc_en','marketing_desc_hu','personalization_desc_en','personalization_desc_hu','policy_link_text_en','policy_link_text_hu') as $k){$o[$k]=$this->sanitize_formatted_text($o[$k]);}
		foreach(array('label_accept_all_en','label_accept_all_hu','label_reject_all_en','label_reject_all_hu','label_customize_en','label_customize_hu','label_save_choices_en','label_save_choices_hu','label_reopen_en','label_reopen_hu') as $k){$o[$k]=$this->sanitize_button_html($o[$k]);}
		foreach(array('necessary_label_en','necessary_label_hu','analytics_label_en','analytics_label_hu','marketing_label_en','marketing_label_hu','personalization_label_en','personalization_label_hu','dialog_label_en','dialog_label_hu') as $k){$o[$k]=sanitize_text_field(wp_strip_all_tags($o[$k]));}
		return $o;
	}

	public function render_admin_page() {
		$o = $this->get_options();
		?><div class="wrap"><h1>Lightweight Consent Mode</h1><p><strong>Version:</strong> <?php echo esc_html( self::LCM_VERSION ); ?></p><form method="post" action="options.php"><?php settings_fields('lcm_settings_group'); ?>
<details style="margin:12px 0;padding:8px 12px;background:#fff;border:1px solid #ccd0d4;">
			<summary><strong>Google Tag Manager setup guide</strong></summary>
			<h3>A. What this plugin does</h3>
			<p>This plugin sends Google Consent Mode v2 signals from the WordPress frontend.</p>
			<ul><li>It sends a default consent state before the visitor makes a choice.</li><li>It sends an updated consent state after the visitor accepts, rejects, or saves custom choices.</li><li>It pushes dataLayer events: <code>kk_consent_default</code>, <code>kk_consent_update</code>, and <code>kk_consent_ready</code>.</li><li>The plugin can optionally inject the GTM container snippet.</li></ul>
			<h3>B. Plugin-side setup</h3>
			<ol><li>Enter the GTM Container ID (for example <code>GTM-XXXXXXX</code>).</li><li>Enable GTM injection only if the same GTM container is not already installed by theme/plugin/custom code.</li><li>If GTM is already installed elsewhere, keep GTM injection disabled to avoid duplicate containers.</li><li>Save plugin settings.</li><li>Clear page cache if caching is active.</li><li>Test in an incognito browser session.</li></ol>
			<h3>C. Important loading order note</h3>
			<p>Consent defaults must be available before tags that depend on consent run.</p>
			<p>If this plugin injects GTM, consent default is output before or together with the GTM container so Google tags receive the correct initial consent state.</p>
			<p>If GTM is installed by another plugin or theme, ensure this consent plugin still loads early enough. If Tag Assistant shows late consent setup, enable this plugin GTM injection or adjust the other GTM installation.</p>
			<h3>D. Which GTM triggers should I use?</h3>
			<ol><li><strong>Do not use Consent Initialization</strong> for normal GA4/Ads/Meta/remarketing tags. Consent Initialization is for tags that set/update consent.</li><li><strong>GA4 / Google tag</strong>: use <em>All Pages</em> (or <em>Initialization - All Pages</em> if needed). Do not use <em>Consent Initialization - All Pages</em> for normal GA4 firing. Ensure <code>analytics_storage</code> is respected (and ad consent types when needed).</li><li><strong>Google Ads conversion tags</strong>: use the real conversion trigger (purchase/lead/form submit/thank you page). Require <code>ad_storage</code>, <code>ad_user_data</code>, and <code>ad_personalization</code> where applicable.</li><li><strong>Remarketing / marketing tags</strong>: use normal triggers plus consent settings, or stricter custom-event setups using <code>kk_consent_update</code>.</li><li><strong>Custom Event trigger for consent updates</strong>: GTM → Triggers → New → Trigger Configuration → Custom Event → Event name: <code>kk_consent_update</code> → Fires on: All Custom Events → Save. Use this only for tags that should run immediately after consent change.</li><li><strong>Pageview tags after consent</strong>: common setup is GA4 All Pages + consent checks, marketing tags with normal trigger + consent checks, optional stricter <code>kk_consent_update</code> only when intentionally needed.</li></ol>
			<h3>E. GTM Consent Overview setup</h3>
			<ol><li>Open GTM.</li><li>Go to Admin.</li><li>Open Container Settings.</li><li>Enable Consent Overview under Additional Settings.</li><li>Go back to Tags.</li><li>Open the Consent Overview icon.</li><li>Review each tag consent setting.</li></ol>
			<h3>F. Recommended consent settings by tag type</h3>
			<ul><li><strong>GA4 / Google Analytics</strong>: Trigger All Pages; Consent <code>analytics_storage</code>.</li><li><strong>Google Ads conversion</strong>: Trigger on real conversion; Consent <code>ad_storage</code>, <code>ad_user_data</code>, <code>ad_personalization</code> where applicable.</li><li><strong>Google Ads remarketing</strong>: Trigger on relevant page/event; Consent <code>ad_storage</code>, <code>ad_user_data</code>, <code>ad_personalization</code>.</li><li><strong>Meta Pixel / non-Google marketing tags</strong>: normal trigger or <code>kk_consent_update</code> stricter setup; block until marketing consent is granted.</li><li><strong>Strictly necessary tags</strong>: normal trigger; no analytics/marketing consent requirement.</li></ul>
			<h3>G. Testing in GTM Preview</h3>
			<ol><li>Open GTM Preview.</li><li>Open website in incognito.</li><li>Before banner interaction, verify <code>kk_consent_default</code> exists and consent state is as expected.</li><li>Click Accept all and verify <code>kk_consent_update</code> with granted analytics/marketing-related states.</li><li>Clear consent storage and retest Reject all.</li><li>Clear storage again and test Customize/Save choices (for example analytics granted, marketing denied).</li></ol>
			<h3>H. Browser console testing</h3>
			<p>Run this in DevTools Console:</p><pre>window.dataLayer</pre>
			<p>Check for <code>kk_consent_default</code>, <code>kk_consent_update</code>, <code>kk_consent_ready</code>, and consent fields such as <code>analytics_storage</code>, <code>ad_storage</code>, <code>ad_user_data</code>, <code>ad_personalization</code>.</p>
			<h3>I. Common mistakes</h3>
			<ul><li>Installing the same GTM container twice.</li><li>Using Consent Initialization triggers for normal GA4 or Ads tags.</li><li>Forgetting to enable Consent Overview.</li><li>Forgetting to publish GTM after changes.</li><li>Testing only banner visuals and not consent state.</li><li>Using <code>kk_consent_update</code> for every tag and then expecting immediate pageview firing.</li><li>Leaving cache active while testing.</li><li>Running multiple consent plugins at the same time.</li></ul>
			<h3>J. Optional debug mode</h3>
			<p>Enable debug mode only while testing. Disable it on production after verification.</p>
		</details>

		<h2>General</h2><table class="form-table"><tr><th>Banner enabled</th><td><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[banner_enabled]" value="1" <?php checked($o['banner_enabled'],1); ?>></td></tr><tr><th>Policy URL</th><td><input type="url" class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[policy_url]" value="<?php echo esc_attr($o['policy_url']); ?>"></td></tr><tr><th>Language mode</th><td><select name="<?php echo esc_attr(self::OPTION_KEY); ?>[language_mode]"><option value="browser" <?php selected($o['language_mode'],'browser'); ?>>browser</option><option value="en" <?php selected($o['language_mode'],'en'); ?>>en</option><option value="hu" <?php selected($o['language_mode'],'hu'); ?>>hu</option></select></td></tr><tr><th>Banner preset</th><td><select name="<?php echo esc_attr(self::OPTION_KEY); ?>[banner_preset]"><option value="universal" <?php selected($o['banner_preset'],'universal'); ?>>universal</option><option value="kk" <?php selected($o['banner_preset'],'kk'); ?>>kk</option></select></td></tr></table>
        <h2>Texts</h2>
        <p>Long text fields support safe limited HTML: <code>&lt;strong&gt;</code>, <code>&lt;b&gt;</code>, <code>&lt;em&gt;</code>, <code>&lt;br&gt;</code>, and links. Button labels support <code>&lt;strong&gt;</code>, <code>&lt;b&gt;</code>, and <code>&lt;em&gt;</code>. Accessibility labels are plain text only.</p>
        <h3>Banner texts</h3>
        <table class="form-table">
            <?php
            $this->admin_text_input( 'Banner title (EN)', 'banner_title_en', $o['banner_title_en'] );
            $this->admin_text_input( 'Banner title (HU)', 'banner_title_hu', $o['banner_title_hu'] );
            $this->admin_textarea( 'Banner text (EN)', 'banner_text_en', $o['banner_text_en'] );
            $this->admin_textarea( 'Banner text (HU)', 'banner_text_hu', $o['banner_text_hu'] );
            $this->admin_text_input( 'Policy link text (EN)', 'policy_link_text_en', $o['policy_link_text_en'] );
            $this->admin_text_input( 'Policy link text (HU)', 'policy_link_text_hu', $o['policy_link_text_hu'] );
            ?>
        </table>
        <h3>Button labels</h3>
        <table class="form-table">
            <?php
            $button_html_note = 'Supports simple inline emphasis only.';
            $this->admin_text_input( 'Accept all label (EN)', 'label_accept_all_en', $o['label_accept_all_en'], $button_html_note );
            $this->admin_text_input( 'Accept all label (HU)', 'label_accept_all_hu', $o['label_accept_all_hu'], $button_html_note );
            $this->admin_text_input( 'Reject all label (EN)', 'label_reject_all_en', $o['label_reject_all_en'], $button_html_note );
            $this->admin_text_input( 'Reject all label (HU)', 'label_reject_all_hu', $o['label_reject_all_hu'], $button_html_note );
            $this->admin_text_input( 'Customize label (EN)', 'label_customize_en', $o['label_customize_en'], $button_html_note );
            $this->admin_text_input( 'Customize label (HU)', 'label_customize_hu', $o['label_customize_hu'], $button_html_note );
            $this->admin_text_input( 'Save choices label (EN)', 'label_save_choices_en', $o['label_save_choices_en'], $button_html_note );
            $this->admin_text_input( 'Save choices label (HU)', 'label_save_choices_hu', $o['label_save_choices_hu'], $button_html_note );
            $this->admin_text_input( 'Reopen / Cookie settings label (EN)', 'label_reopen_en', $o['label_reopen_en'], $button_html_note );
            $this->admin_text_input( 'Reopen / Cookie settings label (HU)', 'label_reopen_hu', $o['label_reopen_hu'], $button_html_note );
            ?>
        </table>
        <h3>Customize panel texts</h3>
        <table class="form-table">
            <?php
            $this->admin_textarea( 'Panel intro (EN)', 'panel_intro_en', $o['panel_intro_en'] );
            $this->admin_textarea( 'Panel intro (HU)', 'panel_intro_hu', $o['panel_intro_hu'] );
            $this->admin_text_input( 'Necessary cookies label (EN)', 'necessary_label_en', $o['necessary_label_en'], 'Plain text only.' );
            $this->admin_text_input( 'Necessary cookies label (HU)', 'necessary_label_hu', $o['necessary_label_hu'], 'Plain text only.' );
            $this->admin_textarea( 'Necessary cookies description (EN)', 'necessary_desc_en', $o['necessary_desc_en'] );
            $this->admin_textarea( 'Necessary cookies description (HU)', 'necessary_desc_hu', $o['necessary_desc_hu'] );
            $this->admin_text_input( 'Analytics label (EN)', 'analytics_label_en', $o['analytics_label_en'], 'Plain text only.' );
            $this->admin_text_input( 'Analytics label (HU)', 'analytics_label_hu', $o['analytics_label_hu'], 'Plain text only.' );
            $this->admin_textarea( 'Analytics description (EN)', 'analytics_desc_en', $o['analytics_desc_en'] );
            $this->admin_textarea( 'Analytics description (HU)', 'analytics_desc_hu', $o['analytics_desc_hu'] );
            $this->admin_text_input( 'Marketing label (EN)', 'marketing_label_en', $o['marketing_label_en'], 'Plain text only.' );
            $this->admin_text_input( 'Marketing label (HU)', 'marketing_label_hu', $o['marketing_label_hu'], 'Plain text only.' );
            $this->admin_textarea( 'Marketing description (EN)', 'marketing_desc_en', $o['marketing_desc_en'] );
            $this->admin_textarea( 'Marketing description (HU)', 'marketing_desc_hu', $o['marketing_desc_hu'] );
            $this->admin_text_input( 'Personalization label (EN)', 'personalization_label_en', $o['personalization_label_en'], 'Plain text only.' );
            $this->admin_text_input( 'Personalization label (HU)', 'personalization_label_hu', $o['personalization_label_hu'], 'Plain text only.' );
            $this->admin_textarea( 'Personalization description (EN)', 'personalization_desc_en', $o['personalization_desc_en'] );
            $this->admin_textarea( 'Personalization description (HU)', 'personalization_desc_hu', $o['personalization_desc_hu'] );
            ?>
        </table>
        <h3>Accessibility labels</h3>
        <table class="form-table">
            <?php
            $this->admin_text_input( 'Dialog aria-label (EN)', 'dialog_label_en', $o['dialog_label_en'], 'Plain text only.' );
            $this->admin_text_input( 'Dialog aria-label (HU)', 'dialog_label_hu', $o['dialog_label_hu'], 'Plain text only.' );
            ?>
        </table>
		<h2>Design</h2><table class="form-table"><tr><th>Header text color</th><td><input name="<?php echo esc_attr(self::OPTION_KEY); ?>[design_header_text_color]" value="<?php echo esc_attr($o['design_header_text_color']); ?>"></td></tr><tr><th>Header font preset</th><td><select name="<?php echo esc_attr(self::OPTION_KEY); ?>[header_font_preset]"><option value="inherit" <?php selected($o['header_font_preset'],'inherit'); ?>>inherit</option><option value="system" <?php selected($o['header_font_preset'],'system'); ?>>system</option><option value="arial" <?php selected($o['header_font_preset'],'arial'); ?>>arial</option><option value="georgia" <?php selected($o['header_font_preset'],'georgia'); ?>>georgia</option><option value="custom" <?php selected($o['header_font_preset'],'custom'); ?>>custom</option></select></td></tr><tr><th>Header custom font family</th><td><input class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[header_custom_font]" value="<?php echo esc_attr($o['header_custom_font']); ?>"></td></tr><tr><th>Header font size (px)</th><td><input type="number" min="10" max="64" name="<?php echo esc_attr(self::OPTION_KEY); ?>[header_font_size]" value="<?php echo esc_attr($o['header_font_size']); ?>"></td></tr><tr><th>Header font weight</th><td><input type="number" min="100" max="900" step="100" name="<?php echo esc_attr(self::OPTION_KEY); ?>[header_font_weight]" value="<?php echo esc_attr($o['header_font_weight']); ?>"></td></tr><tr><th>Body font preset</th><td><select name="<?php echo esc_attr(self::OPTION_KEY); ?>[body_font_preset]"><option value="inherit" <?php selected($o['body_font_preset'],'inherit'); ?>>inherit</option><option value="system" <?php selected($o['body_font_preset'],'system'); ?>>system</option><option value="arial" <?php selected($o['body_font_preset'],'arial'); ?>>arial</option><option value="georgia" <?php selected($o['body_font_preset'],'georgia'); ?>>georgia</option><option value="custom" <?php selected($o['body_font_preset'],'custom'); ?>>custom</option></select></td></tr><tr><th>Body custom font family</th><td><input class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[body_custom_font]" value="<?php echo esc_attr($o['body_custom_font']); ?>"></td></tr><tr><th>Background color</th><td><input name="<?php echo esc_attr(self::OPTION_KEY); ?>[design_bg_color]" value="<?php echo esc_attr($o['design_bg_color']); ?>"></td></tr><tr><th>Text color</th><td><input name="<?php echo esc_attr(self::OPTION_KEY); ?>[design_text_color]" value="<?php echo esc_attr($o['design_text_color']); ?>"></td></tr><tr><th>Border color</th><td><input name="<?php echo esc_attr(self::OPTION_KEY); ?>[design_border_color]" value="<?php echo esc_attr($o['design_border_color']); ?>"></td></tr><tr><th>Border width (px)</th><td><input type="number" min="0" max="10" name="<?php echo esc_attr(self::OPTION_KEY); ?>[design_border_width]" value="<?php echo esc_attr($o['design_border_width']); ?>"></td></tr><tr><th>Border radius (px)</th><td><input type="number" min="0" max="40" name="<?php echo esc_attr(self::OPTION_KEY); ?>[design_border_radius]" value="<?php echo esc_attr($o['design_border_radius']); ?>"></td></tr><tr><th>Banner max width (px)</th><td><input type="number" min="320" max="1400" name="<?php echo esc_attr(self::OPTION_KEY); ?>[design_max_width]" value="<?php echo esc_attr($o['design_max_width']); ?>"></td></tr><tr><th>Banner padding (px)</th><td><input type="number" min="0" max="64" name="<?php echo esc_attr(self::OPTION_KEY); ?>[banner_padding]" value="<?php echo esc_attr($o['banner_padding']); ?>"></td></tr></table>
		<h2>Buttons</h2><table class="form-table"><tr><th>Banner button order</th><td><input class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[banner_order]" value="<?php echo esc_attr($o['banner_order']); ?>"><p><small>Allowed actions: accept_all,reject_all,settings</small></p></td></tr><tr><th>Panel button order</th><td><input class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[panel_order]" value="<?php echo esc_attr($o['panel_order']); ?>"><p><small>Allowed actions: save_choices,reject_all. Default: save_choices</small></p></td></tr><tr><th>Button border radius (px)</th><td><input type="number" min="0" max="40" name="<?php echo esc_attr(self::OPTION_KEY); ?>[button_radius]" value="<?php echo esc_attr($o['button_radius']); ?>"></td></tr><tr><th>Button padding Y (px)</th><td><input type="number" min="0" max="32" name="<?php echo esc_attr(self::OPTION_KEY); ?>[button_padding_y]" value="<?php echo esc_attr($o['button_padding_y']); ?>"></td></tr><tr><th>Button padding X (px)</th><td><input type="number" min="0" max="64" name="<?php echo esc_attr(self::OPTION_KEY); ?>[button_padding_x]" value="<?php echo esc_attr($o['button_padding_x']); ?>"></td></tr><tr><th>Button font preset</th><td><select name="<?php echo esc_attr(self::OPTION_KEY); ?>[button_font_preset]"><option value="inherit" <?php selected($o['button_font_preset'],'inherit'); ?>>inherit</option><option value="system" <?php selected($o['button_font_preset'],'system'); ?>>system</option><option value="arial" <?php selected($o['button_font_preset'],'arial'); ?>>arial</option><option value="georgia" <?php selected($o['button_font_preset'],'georgia'); ?>>georgia</option><option value="custom" <?php selected($o['button_font_preset'],'custom'); ?>>custom</option></select></td></tr><tr><th>Button custom font family</th><td><input class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[button_custom_font]" value="<?php echo esc_attr($o['button_custom_font']); ?>"></td></tr><tr><th>Accept all button</th><td>Background color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_accept_bg]" value="<?php echo esc_attr($o['btn_accept_bg']); ?>"> Text color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_accept_text]" value="<?php echo esc_attr($o['btn_accept_text']); ?>"> Border color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_accept_border]" value="<?php echo esc_attr($o['btn_accept_border']); ?>"><br>Hover background color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_accept_hover_bg]" value="<?php echo esc_attr($o['btn_accept_hover_bg']); ?>"> Hover text color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_accept_hover_text]" value="<?php echo esc_attr($o['btn_accept_hover_text']); ?>"> Hover border color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_accept_hover_border]" value="<?php echo esc_attr($o['btn_accept_hover_border']); ?>"></td></tr>
<tr><th>Reject all button</th><td>Background color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_reject_bg]" value="<?php echo esc_attr($o['btn_reject_bg']); ?>"> Text color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_reject_text]" value="<?php echo esc_attr($o['btn_reject_text']); ?>"> Border color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_reject_border]" value="<?php echo esc_attr($o['btn_reject_border']); ?>"><br>Hover background color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_reject_hover_bg]" value="<?php echo esc_attr($o['btn_reject_hover_bg']); ?>"> Hover text color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_reject_hover_text]" value="<?php echo esc_attr($o['btn_reject_hover_text']); ?>"> Hover border color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_reject_hover_border]" value="<?php echo esc_attr($o['btn_reject_hover_border']); ?>"></td></tr>
<tr><th>Customize button</th><td>Background color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_settings_bg]" value="<?php echo esc_attr($o['btn_settings_bg']); ?>"> Text color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_settings_text]" value="<?php echo esc_attr($o['btn_settings_text']); ?>"> Border color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_settings_border]" value="<?php echo esc_attr($o['btn_settings_border']); ?>"><br>Hover background color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_settings_hover_bg]" value="<?php echo esc_attr($o['btn_settings_hover_bg']); ?>"> Hover text color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_settings_hover_text]" value="<?php echo esc_attr($o['btn_settings_hover_text']); ?>"> Hover border color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_settings_hover_border]" value="<?php echo esc_attr($o['btn_settings_hover_border']); ?>"></td></tr>
<tr><th>Save choices button</th><td>Background color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_save_bg]" value="<?php echo esc_attr($o['btn_save_bg']); ?>"> Text color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_save_text]" value="<?php echo esc_attr($o['btn_save_text']); ?>"> Border color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_save_border]" value="<?php echo esc_attr($o['btn_save_border']); ?>"><br>Hover background color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_save_hover_bg]" value="<?php echo esc_attr($o['btn_save_hover_bg']); ?>"> Hover text color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_save_hover_text]" value="<?php echo esc_attr($o['btn_save_hover_text']); ?>"> Hover border color <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[btn_save_hover_border]" value="<?php echo esc_attr($o['btn_save_hover_border']); ?>"></td></tr></table>
		<h2>Layout</h2><table class="form-table"><tr><th>Desktop position</th><td><select name="<?php echo esc_attr(self::OPTION_KEY); ?>[desktop_position]"><option value="center" <?php selected($o['desktop_position'],'center'); ?>>center</option><option value="bottom_center" <?php selected($o['desktop_position'],'bottom_center'); ?>>bottom_center</option><option value="bottom_left" <?php selected($o['desktop_position'],'bottom_left'); ?>>bottom_left</option><option value="bottom_right" <?php selected($o['desktop_position'],'bottom_right'); ?>>bottom_right</option></select></td></tr><tr><th>Desktop layout</th><td><select name="<?php echo esc_attr(self::OPTION_KEY); ?>[desktop_layout]"><option value="box" <?php selected($o['desktop_layout'],'box'); ?>>box</option><option value="sheet" <?php selected($o['desktop_layout'],'sheet'); ?>>sheet</option></select></td></tr><tr><th>Mobile layout</th><td><select name="<?php echo esc_attr(self::OPTION_KEY); ?>[mobile_layout]"><option value="sheet" <?php selected($o['mobile_layout'],'sheet'); ?>>sheet</option><option value="box" <?php selected($o['mobile_layout'],'box'); ?>>box</option></select></td></tr></table>
		<h2>GTM / Consent Mode</h2><table class="form-table"><tr><th>GTM Container ID</th><td><input name="<?php echo esc_attr(self::OPTION_KEY); ?>[gtm_container_id]" value="<?php echo esc_attr($o['gtm_container_id']); ?>"></td></tr><tr><th>Inject GTM snippet</th><td><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[gtm_inject]" value="1" <?php checked($o['gtm_inject'],1); ?>></td></tr><tr><th>Default analytics checked</th><td><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[default_analytics]" value="1" <?php checked($o['default_analytics'],1); ?>></td></tr><tr><th>Default marketing checked</th><td><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[default_marketing]" value="1" <?php checked($o['default_marketing'],1); ?>></td></tr><tr><th>Default personalization checked</th><td><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[default_personalization]" value="1" <?php checked($o['default_personalization'],1); ?>></td></tr></table><?php submit_button(); ?></form></div><?php
	}
}

new Lightweight_Consent_Mode();
