<?php

class WPF_Core {
	var $textdomain = 'wpf';
	function __construct() {
		require_once( WPF_INCLUDES.'wp-less/class.wp_less.php' );
		add_action('wp_enqueue_scripts', array(&$this, '_wp_enqueue_scripts'));
		add_action('after_setup_theme', array(&$this, '_after_setup_theme'));
		$this->setup();
	}
	function setup() {

	}
	function _wp_enqueue_scripts($hook) {
		if(method_exists($this, 'wp_enqueue_scripts'))
			$this->wp_enqueue_scripts($hook);
	}
	function _after_setup_theme() {
		if(method_exists($this, 'after_setup_theme'))
			$this->after_setup_theme();
	}
}