<?php

namespace AtomicWP;

use \Dust\Dust;

class Template extends BaseController {
   
   private static $views_dir = '/../views/';
   private static $views_ext = '.dust';
   
   /**
    * Extract the given variables from $data and include the template.
    * Echoing the output is optional.
    * @param string $template_name
    * @param array $context
    * @param boolean $echo
    * @return mixed
    */
   public static function render( $template_name, $context = array(), $echo = true ) {
      $file_safe_name = strtolower( str_replace( ' ', '-', $template_name ) );
      $dust = new Dust();
      $template = $dust->compileFile( __DIR__.self::$views_dir.$file_safe_name.self::$views_ext );
      $output = $dust->renderTemplate( $template, $context );
      if( $echo === false ) {
         return $output;
      } else {
         echo $output;
      }
   }
   
   /**
    * Takes a string, replaces the "shortcodes" (e.g. [variable1]) with the real values from $data
    * and echos the string.
    * Echoing the string is optional.
    * @param string $template_string
    * @param array $context
    * @param boolean $echo
    * @return mixed
    */
   public static function render_string( $template_string, $context = array(), $echo = true ) {
      foreach( $context as $k => $v ) {
         $template_string = str_replace( '['.$k.']', $v, $template_string );
      }
      if( $echo === false ) {
         return $template_string;
      } else {
         echo $template_string;
      }
   }
   
   /**
    * The function will look for pages using the following format: <page_suffix>-<action_reference>.
    * So if you are on admin.php?page=cmq_menu&action=edit&id=n,
    * then the help file is /views/help/menu-form.php.
    * A blank action will look for <page_suffix>-index.
    */
   public static function render_help_text( $contextual_help, $screen_id, $screen ) { 
      if ( !method_exists( $screen, 'add_help_tab' ) ) {
         return $contextual_help;
      }
      $page = filter_input( INPUT_GET, 'page' );
      $page_prefix = substr( $page, 0, strlen( self::$ns ) );
      if( $page_prefix !== self::$ns ) {
         return $contextual_help;
      }
      $template = strtolower( self::get_controller() ).'-'.strtolower( self::get_method() );
      if( file_exists( __DIR__.self::$views_dir.'help/'.$template.self::$views_ext ) ) {
         $help_content = self::render( 'help/'.$template, [], false );
         $screen->add_help_tab( array(
            'id'      => $screen_id,
            'title'   => self::$plugin_name.' '.__( 'Help', self::$domain ),
            'content' => $help_content,
         ) );
      } else {
         echo 'nofile - '.$template;
      }
      return $contextual_help;
   }
   
}