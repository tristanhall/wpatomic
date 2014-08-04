<?php

namespace AtomicWP;

class BaseController {
   
   /**
    * Set the name for the plugin's admin menu page title.
    * @var string
    */
   public static $plugin_name = 'Atomic WP';
   
   /**
    * Set the icon variable for the plugin's admin menu page.
    * @var string
    */
   public static $icon = 'dashicons-share';
   
   /**
    * Set this to your plugin's "short namespace".
    * This is for database table and variable prefixes.
    * @var string
    */
   public static $ns = 'awp_';
   
   /**
    * Define the text domain for the plugin.
    * Makes it easy to change the namespace quickly in the future and 
    * enables the core classes to use your namespace. 
    * @var string 
    */
   public static $domain = 'atomicwp';
   
   /**
    * Set to true to log all database queries made from this plugin
    * in the log table in the database.
    * @var boolean
    */
   public static $log_queries = false;
   
   /**
    * Calls the Install function from the Install class if the controller exists when
    * the plugin is activated.
    * Ex: __NAMESPACE__\Install::install();
    * @global object $wpdb
    */
   public static function install() {
      $user_install = __DIR__.'/class-install.php';
      if( file_exists( $user_install ) ) {
         require_once( $user_install );
         $pattern = '/((?:[a-z][a-z]+))(\\\\)(Install)/is';
         $matches = preg_grep( $pattern, get_declared_classes() );
         $install_class = reset( $matches );
         if( is_subclass_of( $install_class, 'AtomicWP\BaseController' ) ) {
            call_user_func( $install_class );
         } else {
            exit( 'AtomicWP is not initialized.' );
         }
      }
      global $wpdb;
      $tables = array();
      $tables['log'] = "CREATE TABLE `".$wpdb->prefix.static::$ns."log` (
         `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
         `class` varchar(255) DEFAULT NULL,
         `message` text,
         `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
         PRIMARY KEY (`id`)
      );";
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      foreach( $tables as $query ) {
         dbDelta( $query );
      }
      update_option( static::$ns.'new_install', 1 );
   }
   
   /**
    * Define an autoloader for the plugin.
    * Looks in the controllers and models directories automatically.
    * @param string $called_class
    */
   public static function autoload( $called_class ) {
      $callable_parts = explode( '\\', $called_class );
      $classname = end( $callable_parts );
      $pieces = preg_split( '/(?=[A-Z])/', $classname );
      $model = __DIR__.'/../models/class'.strtolower( implode( '-', $pieces ) ).'.php';
      $controller = __DIR__.'/class'.strtolower( implode( '-', $pieces ) ).'.php';
      if( file_exists( $model ) ) {
         require_once( $model );
      }
      if( file_exists( $controller ) ) {
         require_once( $controller );
      }
   }
   
   /**
    * Registers the autoloader and activation hook.
    * Sets up the page/action router and runs the Admin & Frontend actions.
    * Looks for __YOUR_NAMESPACE__\AdminActions::main() && __YOUR_NAMESPACE__\Frontend::main();
    */
   public static function bootstrap() {
      spl_autoload_register( array( __CLASS__, 'autoload' ) );
      register_activation_hook( __FILE__, array( __CLASS__, 'install' ) );
      $admin_file = __DIR__.'/class-admin-actions.php';
      $frontend_file = __DIR__.'/class-frontend.php';
      if( is_admin() ) {
         if( file_exists( $admin_file ) ) {
            require_once( $admin_file );
            $pattern = '/((?:[a-z][a-z]+))(\\\\)(AdminActions)/is';
            $matches = preg_grep( $pattern, get_declared_classes() );
            $admin_class = reset( $matches );
            if( is_subclass_of( $admin_class, 'AtomicWP\BaseController' ) ) {
               call_user_func( array( $admin_class, 'main' ) );
            }
         }
         self::main_page();
      }
      if( !is_admin() && file_exists( $frontend_file ) ) {
         $pattern = '/((?:[a-z][a-z]+))(\\\\)(Frontend)/is';
         $matches = preg_grep( $pattern, get_declared_classes() );
         $frontend_class = reset( $matches );
         if( is_subclass_of( $frontend_class, 'AtomicWP\BaseController' ) ) {
            call_user_func( array( $frontend_class, 'main' ) );
         }
      }
   }
   
   /**
    * Registers the main plugin page & sets up listeners for 
    * the 'c' & 'm' variables to call certain functions.
    */
   public static function main_page() {
      add_action( 'admin_menu', function() {
         $hooks = array();
         $hooks[] = add_menu_page( __( static::$plugin_name, static::$domain ), __( static::$plugin_name, static::$domain ), 'edit_pages', static::$ns.'main', array( __CLASS__, 'router' ), static::$icon, '6.24' );
      });
   }
   
   /**
    * Supports RESTful request route validation for GET & POST methods.
    * Runs the static::$ns.'default_action' action if no controller variable is set.
    * Defaults to get_index if no method variable is set.
    * 
    */
   public static function router() {
      $page = filter_input( INPUT_GET, 'page' );
      $class = filter_input( INPUT_GET, 'c' );
      $method = filter_input( INPUT_GET, 'm' );
      $controller_file = __DIR__.'/class-'.strtolower( $class ).'.php';
      if( !file_exists( $controller_file ) || empty( $class ) ) {
         do_action( static::$ns.'default_action' );
      } else {
         require_once( $controller_file );
      }
      $pattern = '/((?:[a-z][a-z]+))(\\\\)('.ucfirst( $class ).')/is';
      $matches = preg_grep( $pattern, get_declared_classes() );
      $method_parts = explode( '\\', reset( $matches ) );
      $class_name = $method_parts[0].'\\'.$method_parts[1];
      $get_callback = !empty( $method ) ? 'get_'.$method : 'get_index';
      $post_callback = !empty( $method ) ? 'post_'.$method : 'post_index';
      if( $page !== static::$ns.'main' || !is_subclass_of( $class_name, 'AtomicWP\BaseController' ) ) {
         return;
      }
      if( $_SERVER['REQUEST_METHOD'] == 'GET' && method_exists( $class_name, $get_callback ) ) {
         call_user_func( $class_name.'::'.$get_callback );
      }
      if( $_SERVER['REQUEST_METHOD'] == 'POST' && method_exists( $class_name, $post_callback ) ) {
         call_user_func( $class_name.'::'.$post_callback );
      }
   }
   
}