<?php

namespace Eventin\Interfaces;

/**
 * Interface CustomPostTypeInterface
 *
 * @package Eventin\Interfaces
 */
interface CustomPostTypeInterface {

	/**
	 * Register the custom post type.
	 *
	 * @return void
	 */
	public function register_post_type(): void;
}