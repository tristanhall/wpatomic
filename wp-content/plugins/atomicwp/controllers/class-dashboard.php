<?php

namespace Atomic;

class Dashboard extends \AtomicWP\BaseController {
   
   public static function get_index() {
      echo 'Coming Soon...<br>';
   }
   
   public static function post_index() {
      echo 'You just posted!';
   }
   
}