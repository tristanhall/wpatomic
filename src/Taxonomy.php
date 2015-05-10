<?php
namespace TH\WPAtomic;

abstract class Taxonomy
{
   
    /**
     * Define which post types to apply the taxonomy.
     * 
     * (default value: array())
     * 
     * @var array
     * @access private
     * @static
     */
    protected static $post_types = array();
   
    /**
     * Define the machine name of the taxonomy.
     * 
     * (default value: '')
     * 
     * @var string
     * @access private
     * @static
     */
    protected static $taxonomy_type = '';
   
    /**
     * Define the language terms for the taxonomy.
     * 
     * @var array
     * @access private
     * @static
     */
    protected static $taxonomy_labels = array(
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
    protected static $taxonomy_args = array(
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
     * @return null
     */
    public static function registerTaxonomy()
    {
        static::$taxonomy_args['labels'] = static::$taxonomy_labels;
        $create = register_taxonomy(
            static::$taxonomy_type,
            static::$post_types,
            static::$taxonomy_args
        );
        return $created;
    }
    
    /**
     * Load the current taxonomy object.
     * 
     * @access public
     * @return void
     */
    public function __construct()
    {
        $tax = get_taxonomy(static::$taxonomy_type);
        $vars = get_object_vars($tax);
        foreach ($vars as $k => $v) {
            $this->$k = $v;
        }
    }
    
    /**
     * Get all of the terms belonging to the specified taxonomy.
     * 
     * @access public
     * @param array $args
     * @return array
     */
    public function getTerms($args = array())
    {
        $default_args = array(
            'orderby'           => 'name', 
            'order'             => 'ASC',
            'hide_empty'        => true, 
            'exclude'           => array(), 
            'exclude_tree'      => array(), 
            'include'           => array(),
            'number'            => '', 
            'fields'            => 'all', 
            'slug'              => '',
            'parent'            => '',
            'hierarchical'      => true, 
            'child_of'          => 0,
            'childless'         => false,
            'get'               => '', 
            'name__like'        => '',
            'description__like' => '',
            'pad_counts'        => false, 
            'offset'            => '', 
            'search'            => '', 
            'cache_domain'      => 'core'
        );
        $term_args = array_merge($default_args, $args);
        $taxonomies = array(static::$taxonomy_type);
        $terms = get_terms($taxonomies, $term_args);
        if (is_array($terms)) {
            return $terms;
        } else {
            return array();
        }
    }
    
    /**
     * Search for a term by ID, slug, name or taxonomy term ID.
     * Returns the term in the specified output type, default is an object.
     * Returns false if term is not found.
     * 
     * @access public
     * @param mixed $value
     * @param string $field (default: 'id')
     * @param string $output (default: OBJECT)
     * @param string $filter (default: 'raw')
     * @return mixed
     */
    public function getTerm(
        $value,
        $field = 'id',
        $output = OBJECT,
        $filter = 'raw'
    ) {
        $term = get_term_by(
            $field, 
            $value, 
            static::$taxonomy_type,
            $output, 
            $filter
        );
        return $term;
    }
    
    /**
     * Get all of the posts that are assigned any one of the terms
     * that belong to the current taxonomy.
     * 
     * @access public
     * @param array $args
     * @return array
     */
    public function getPosts($args)
    {
        $term_ids = array();
        $terms = $this->getTerms();
        foreach ($terms as $t) {
            $term_ids[] = $t->term_id;
        }
        if (empty($term_ids)) {
            return array();
        }
        $default_args = array(
            'posts_per_page'   => -1,
            'offset'           => 0,
            'category'         => '',
            'category_name'    => '',
            'orderby'          => 'post_date',
            'order'            => 'DESC',
            'include'          => '',
            'exclude'          => '',
            'meta_key'         => '',
            'meta_value'       => '',
            'post_type'        => null,
            'post_mime_type'   => '',
            'post_parent'      => '',
            'post_status'      => 'publish',
            'suppress_filters' => true ,
            'tax_query' => array(
                array(
                    'taxonomy' => $this->name,
                    'field'    => 'id',
                    'terms'    => $term_ids,
                    'operator' => 'IN'
                )
            )
        );
        $post_args = array_merge($default_args, $args);
        $posts = get_posts($post_args);
        if (is_array($posts)) {
            return $posts;
        } else {
            return array();
        }
    }
   
}