<?php

namespace Atomic;

class AdminActions extends \AtomicWP\BaseController {
   
   /**
    * This method is called when the application is initialized.
    * This sets a default action in the event that no controller class is specified in the URL.
    * You can also add any other actions, filters, hooks, etc. in this method.
    * Go crazy!
    */
   public static function main() {
      add_action( static::$ns.'default_action', array( 'CaspersCatering\Dashboard', 'get_index' ) );
   }
   
}