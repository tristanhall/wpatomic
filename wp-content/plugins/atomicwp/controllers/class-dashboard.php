<?php

namespace Atomic;

use AtomicWP\Template;

class Dashboard extends \AtomicWP\BaseController {
   
   public static function get_index() {
      $user = wp_get_current_user();
      $context = [
         'username' => $user->user_login
      ];
      Template::render( 'main', $context );
   }
   
   public static function post_index() {
      self::$help_template = 'main-index';
      $user = wp_get_current_user;
      $context = [
         'username' => $user->user_login
      ];
      Template::render( 'main', $context );
   }
   
}