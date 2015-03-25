<?php
namespace TH\WPAtomic;

abstract class Taxonomy {
   
   /**
    * Define which post types to apply the taxonomy.
    * 
    * (default value: array())
    * 
    * @var array
    * @access private
    * @static
    */
   protected static $_post_types = array();
   
   /**
    * Define the machine name of the taxonomy.
    * 
    * (default value: '')
    * 
    * @var string
    * @access private
    * @static
    */
   protected static $_taxonomy_type = '';
   
   /**
    * Define the language terms for the taxonomy.
    * 
    * @var array
    * @access private
    * @static
    */
   protected static $_taxonomy_labels = array(
		'name'                       => 'Custom Terms',
		'singular_name'              => 'Custom Term',
		'search_items'               => 'Search Terms',
		'popular_items'              => 'Popular Terms',
		'all_items'                  => 'All Custom Terms',
		'parent_item'                => null,
		'parent_item_colon'          => null,
		'edit_item'                  => 'Edit Term',
		'update_item'                => 'Update Term',
		'add_new_item'               => 'Add New Custom Term',
		'new_item_name'              => 'New Term Name',
		'separate_items_with_commas' => 'Separate terms with commas',
		'add_or_remove_items'        => 'Add or remove terms',
		'choose_from_most_used'      => 'Choose from the most used terms',
		'not_found'                  => 'No terms found.',
		'menu_name'                  => 'Custom Terms',
   );
   
   /**
    * Define the arguemnts for the taxonomy.
    * 
    * @var array
    * @access private
    * @static
    */
   protected static $_taxonomy_args = array(
		'hierarchical'          => false,
		'labels'                => array(),
		'show_ui'               => true,
		'show_admin_column'     => true,
		'update_count_callback' => '_update_post_term_count',
		'query_var'             => true,
		'rewrite'               => false
	);
   
   /**
    * Register the custom taxonomy.
    * 
    * @access public
    * @static
    * @return void
    */
   public static function register_taxonomy() {
      static::$_taxonomy_args['labels'] = static::$_taxonomy_labels;
      register_taxonomy( static::$_taxonomy_type, static::$_post_types, static::$_taxonomy_args );
   }
   
   public static function get_all_terms() {
      
   }
   
   public static function get_all_posts() {
      
   }
   
}