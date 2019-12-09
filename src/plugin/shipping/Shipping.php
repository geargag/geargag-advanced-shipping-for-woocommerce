<?php

namespace vnh_namespace\shipping;

use vnh_namespace\tools\contracts\Bootable;

class Shipping implements Bootable {
	public function boot() {
		add_action('woocommerce_shipping_init', [$this, 'load_shipping_method']);
	}

	public function load_shipping_method() {
		add_filter('woocommerce_shipping_methods', [$this, 'register_shipping_method']);
	}

	public function register_shipping_method($methods) {
		$methods[Advanced_Shipping::METHOD_ID] = Advanced_Shipping::class;

		return $methods;
	}
}
