<?php

if(!deifined('ABSPATH'))
	die();

class _WPF_CustomPostType {

	protected $post_type = '';

	function __construct( $post_type ) {
		if(did_action('init'))
			trigger_error('init action has already fired.  You need to add custom post types during or before the "init" action.');

	}
}