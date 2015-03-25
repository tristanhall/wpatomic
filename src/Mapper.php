<?php
namespace TH\WPAtomic;

class Mapper {
   
   /**
    * Take a WP_Post object's public properties and re-assign them to a WPAtomic\Post.
    * 
    * @access public
    * @static
    * @param object|WP_Post $_post
    * @return object|TH\WPAtomic\Post
    */
   public static function post( &$_post, $key ) {
      $props = get_object_vars( $_post );
      $p = new \TH\WPAtomic\Post;
      foreach( $props as $k => $v ) {
         $p->$k = $v;
      }
      $_post = $p;
   }
   
}