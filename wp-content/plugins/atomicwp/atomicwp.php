<?php
/**
 * Plugin Name: Atomic WP
 * Plugin URI: http://www.tristanhall.com
 * Description: Simple, dev-friendly WordPress plugin framework.
 * Author: Tristan Hall
 * Author URI: http://tristanhall.com
 * Version: 1.0
 * License: Apache v2
 * Text Domain: atomicwp
 */

require_once( __DIR__.'/controllers/class-base-controller.php' );

\AtomicWP\BaseController::bootstrap();