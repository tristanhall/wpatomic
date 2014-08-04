<?php

namespace AtomicWP;

class Template extends BaseController {
   
   private static $views_dir = '/../views/';
   private static $views_ext = '.php';
   
   /**
    * Extract the given variables from $data and include the template.
    * Echoing the output is optional.
    * @param string $template_name
    * @param array $data
    * @param boolean $echo
    * @return mixed
    */
   public static function render( $template_name, $data = array(), $echo = true ) {
      if( $echo === false ) {
         ob_end_clean();
         ob_start();
         extract( $data );
         include( __DIR__.self::$views_dir.$template_name.self::$views_ext );
         $html = ob_get_contents();
         ob_end_clean();
         return $html;
      } else {
         extract( $data );
         include( __DIR__.self::$views_dir.$template_name.self::$views_ext );
      }
   }
   
   /**
    * Takes a string, replaces the "shortcodes" (e.g. [variable1]) with the real values from $data
    * and echos the string.
    * Echoing the string is optional.
    * @param string $template_string
    * @param array $data
    * @param boolean $echo
    * @return mixed
    */
   public static function render_string( $template_string, $data = array(), $echo = true ) {
      foreach( $data as $k => $v ) {
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
   public static function render_help_text() {
      $page = filter_input( INPUT_GET, 'page' );
      $action = filter_input( INPUT_GET, 'action' );
      $page_suffix = substr( $page, 4 );
      switch( $action ) {
         case 'add_new':
         case 'edit':
            $template = $page_suffix.'-form';
            break;
         case 'view':
            $template = $page_suffix.'-view';
            break;
         case '':
         default:
            $template = $page_suffix.'-index';
            break;
      }
      self::render( 'help/'.$template );
   }
   
}