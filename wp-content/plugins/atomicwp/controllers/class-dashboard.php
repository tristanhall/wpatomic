<?php

namespace Atomic;

class Dashboard extends \AtomicWP\BaseController {
   
   public static function get_index() {
      echo 'Coming Soon...<br>';
      Menu::render_data_table();
   }
   
   public static function post_index() {
      echo 'You just posted!';
   }
   
}