<?php
namespace TH\WPAtomic;

abstract class Post
{
   
    /**
     * The Post ID.
     * 
     * @var integer
     * @access private
     */
    protected $ID = null;
   
    /**
     * The Post Title
     * 
     * (default value: '')
     * 
     * @var string
     * @access protected
     */
    protected $post_title = '';
   
    /**
     * The Post Name/Slug
     * 
     * (default value: '')
     * 
     * @var string
     * @access protected
     */
    protected $post_name = '';
   
    /**
     * Post meta container array.
     * 
     * (default value: array())
     * 
     * @var array
     * @access private
     * @static
     */
    protected $meta = array();
   
    /**
     * Define the machine name of the post type.
     * 
     * (default value: '')
     * 
     * @var string
     * @access private
     * @static
     */
    protected static $post_type = '';
   
    /**
     * Define the language terms for the post type.
     * 
     * @var array
     * @access private
     * @static
     */
    protected static $post_labels = array(
		'name'               => 'Custom Posts',
		'singular_name'      => 'Custom Post',
		'menu_name'          => 'Custom Posts',
		'name_admin_bar'     => 'Custom Post',
		'add_new'            => 'Add New',
		'add_new_item'       => 'Add New Custom Post',
		'new_item'           => 'New Custom Post',
		'edit_item'          => 'Edit Custom Post',
		'view_item'          => 'View Custom Post',
		'all_items'          => 'All Custom Posts',
		'search_items'       => 'Search Custom Posts',
		'parent_item_colon'  => 'Parent Custom Posts:',
		'not_found'          => 'No custom posts found.',
		'not_found_in_trash' => 'No custom posts found in Trash.'
    );
   
    /**
     * Define the arguemnts for the post type.
     * 
     * @var array
     * @access private
     * @static
     */
    protected static $post_args = array(
        'labels'             => array(),
		'public'             => true,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => false,
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => false,
		'menu_position'      => 30,
		'menu_icon'          => '',
		'taxonomies'         => array(),
		'supports'           => array( 'title', 'page-attributes', 'editor', 'author' ),
	);
   
    /**
     * Register the custom post type.
     * 
     * @access public
     * @abstract
     * @static
     * @return object
     */
    abstract public static function registerPostType() {
        static::$post_args['labels'] = static::$post_labels;
        $registered = register_post_type( static::$post_type, static::$post_args );
        return $registered;
    }
   
    /**
     * Retrieve all of the posts of this type.
     * 
     * @access public
     * @static
     * @return array
     */
    public static function getAll($convert_posts = true)
    {
        $post_args = array(
            'posts_per_page' => -1,
            'post_type'      => static::$post_type
        );
        $posts = get_posts($post_args);
        if ($convert_posts) {
            array_walk($posts, array(__CLASS__, 'walk'));
        }
        return is_array($posts) ? $posts : array();
    }
   
    /**
     * Construct a new WPAtomic post object.
     * If an ID is provided, retrieve the post from the DB.
     * 
     * @access public
     * @param integer $post_id (default: null)
     * @return void
     */
    public function __construct($post_id = null)
    {
        if (is_numeric($post_id)) {
            $this->ID = $post_id;
            $p = get_post($post_id);
        }
    }
   
    /**
     * Get the meta value of the post.
     * Check the meta array first. If a Post ID is defined, retrieve the meta value from the DB.
     * 
     * @access public
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (isset($this->_meta[$key])) {
            return $this->_meta[$key];
        }
        if (isset($this->ID)) {
            $this->_meta[$key] = get_post_meta($this->ID, $key, true);
            return $this->_meta[$key];
        }
        return false;
    }
   
    /**
     * Set the meta property for a post, update the DB if a post ID is set.
     * 
     * @access public
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function __set($key, $value)
    {
        $this->_meta[$key] = $value;
        if (isset($this->ID)) {
            update_post_meta($this->ID, $key, $this->_meta[$key]);
        }
        return true;
    }
   
    /**
     * Get the ID of the post.
     * 
     * @access public
     * @return integer
     */
    public function getID()
    {
        return $this->ID;
    }
   
    /**
     * Get the post name/slug.
     * 
     * @access public
     * @return void
     */
    public function getName()
    {
        return $this->post_name;
    }
   
    /**
     * Set the post name/slug.
     * 
     * @access public
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->post_name = sanitize_title($name);
    }
   
    /**
     * Get the post title.
     * 
     * @access public
     * @return string
     */
    public function getTitle()
    {
        return $this->post_title;
    }
   
    /**
     * Set the post title. If a post name is not set, create one.
     * 
     * @access public
     * @param string $title
     * @return void
     */
    public function setTitle($title)
    {
        if (empty($this->post_name)) {
            $this->post_name = sanitize_title($name);
        }
    }
   
    /**
     * Save a post and its meta to the database.
     * 
     * @access public
     * @return bool
     */
    public function save()
    {
        $post_args = array(
            'post_name'      => $this->post_name,
            'post_title'     => $this->post_title,
            'post_status'    => 'publish',
            'post_type'      => self::$_post_type,
            'ping_status'    => 'closed',
            'comment_status' => 'closed'
        );
        if (isset($this->ID)) {
            $post_args['ID'] = $this->ID;
        }
        $post_id = wp_insert_post($post_args, true);
        if (is_wp_error($post_id)) {
            wp_die('Failed to save the post.', 'Error');
        } else {
            $this->ID = $post_id;
        }
        foreach ($this->_meta as $k => $v) {
            update_post_meta($this->ID, $k, $v);
        }
        return $this->ID;
    }
   
    /**
     * Take a WP_Post object's public properties and re-assign them to a WPAtomic\Post.
     * 
     * @access public
     * @static
     * @param object|WP_Post $_post
     * @return object
     */
    public static function walk(&$_post, $key)
    {
        $model_path = WPAtomic::findModelPath($_post->post_type);
        $class = WPAtomic::getClassFromPath($model_path);
        $props = get_object_vars($_post);
        $p = new $class;
        foreach ($props as $k => $v) {
            $p->$k = $v;
        }
        $_post = $p;
    }
   
}