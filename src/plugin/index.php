<?php
/**
 * Plugin Name: vnh_name
 * Description: vnh_description
 * Version: vnh_version
 * Tags: vnh_tags
 * Author: vnh_author
 * Author URI: vnh_author_uri
 * License: vnh_license
 * License URI: vnh_license_uri
 * Document URI: vnh_document_uri
 * Text Domain: vnh_textdomain
 * Tested up to: WordPress vnh_tested_up_to
 * WC requires at least: vnh_wc_requires
 * WC tested up to: vnh_wc_tested_up_to
 */

namespace vnh_namespace;

defined('WPINC') || die();

use vnh_namespace\admin\Notices;
use vnh_namespace\shipping\Shipping;
use vnh_namespace\tools\KSES;
use vnh_namespace\tools\Register_Assets;

const PLUGIN_FILE = __FILE__;
const PLUGIN_DIR = __DIR__;

final class Plugin {
	public $admin_notices;
	public $backend_assets;
	public $shipping;

	public function __construct() {
		$this->load();
		$this->init();
		$this->core();
		$this->register_assets();
		$this->boot();
	}

	public function load() {
		require_once PLUGIN_DIR . '/vendor/autoload.php';
		require_once PLUGIN_DIR . '/constants.php';
		require_once PLUGIN_DIR . '/helpers.php';
	}

	public function init() {
		new KSES();

		if (is_admin()) {
			$this->admin_notices = new Notices();
			$this->admin_notices->boot();
		}
	}

	public function core() {
		if (!is_woocommerce_active()) {
			return;
		}

		$this->shipping = new Shipping();
		$this->shipping->boot();
	}

	public function register_assets() {
		$this->backend_assets = new Register_Assets($this->register_backend_assets(), 'backend');
		$this->backend_assets->boot();
	}

	public function register_backend_assets() {
		return [
			'styles' => [
				PLUGIN_SLUG . '-settings-page' => [
					'src' => get_plugin_url('assets/css/settings_page.css'),
				],
			],
			'scripts' => [
				PLUGIN_SLUG . '-settings-page' => [
					'src' => get_plugin_url('assets/js/dist/settings_page.js'),
					'deps' => ['jquery', 'jquery-form'],
					'localize_script' => [
						'settingsPage' => [
							'saveMessage' => esc_html__('Settings Saved Successfully', 'vnh_textdomain'),
						],
					],
				],
			],
		];
	}

	public function boot() {
		add_action('plugin_loaded', [$this, 'load_plugin_textdomain']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_backend_assets']);
	}

	public function load_plugin_textdomain() {
		load_plugin_textdomain('vnh_textdomain');
	}

	public function enqueue_backend_assets() {
		if (is_admin()) {
			wp_enqueue_style(PLUGIN_SLUG . '-settings-page');
			wp_enqueue_script(PLUGIN_SLUG . '-settings-page');
		}
	}
}

new Plugin();
