<?php
namespace TH\WPAtomic;

class WpAtomic
{
   
    /**
    * Initialize WPAtomic for a specific plugin directory.
    * 
    * @access public
    * @static
    * @param string $base_dir
    * @return void
    */
    public static function init($namespace = '\\', $base_dir = null)
    {
        global $wp_version;
        $base_dir_key = sprintf('wpatomic_base_dir:%s', __DIR__);
        $ns_key = sprintf('wpatomic_ns:%s', __DIR__);
        if (empty($wp_version)) {
            $message = 'WPAtomic requires WordPress to be initialized.';
            throw new \RuntimeException($message);
        }
        //Attempt to discover the plugin directory
        if( is_null( $base_dir ) ) {
            $GLOBALS[$base_dir_key] = self::discoverBaseDir();
        } else {
            $GLOBALS[$base_dir_key] = $base_dir;
        }
        //Define the namespace for autoloading
        $GLOBALS[$ns_key] = $namespace;
        //Register the autoloader
        self::registerAutoloader();
        //Initialize the controllers
        self::registerControllers();
        //Initialize the models
        self::registerModels();
    }
    
    /**
    * Discover the base directory from the debug backtrace.
    * 
    * @access private
    * @static
    * @return void
    */
    private static function discoverBaseDir()
    {
        $trace = debug_backtrace();
        if (count($trace) < 2 || !isset($trace[1]['file'])) {
            $message = 'Could not discover the plugin directory.';
            $message .= 'Please define the plugin directory as the second argument of WPAtomic::init().';
            throw new \RuntimeException($message);
        }
        $source_file = $trace[1]['file'];
        $file_parts = explode('/', $source_file);
        $base_dir = rtrim(str_replace(end($file_parts), '', $source_file), '/');
        if (empty($base_dir)) {
            $message = 'Could not discover the plugin directory.';
            $message .= 'Please define the plugin directory as the second argument of WPAtomic::init().';
            throw new \RuntimeException($message);
        }
        return $base_dir;
    }
    
    /**
    * Run through the controllers directory and register hooks.
    * 
    * @access private
    * @static
    * @return void
    */
    private static function registerControllers() {
        $base_dir_key = sprintf('wpatomic_base_dir:%s', __DIR__);
        $base_dir = $GLOBALS[$base_dir_key];
        if (!file_exists(sprintf('%s/controllers', $base_dir))) {
            return;
        }
        $controllers = self::getControllerFiles();
        foreach ($controllers as $path) {
            //Extract the short name of the class for each file
            $long_name = self::getClassFromPath($path);
            try {
                //Construct a reflection class
                $ref = new \ReflectionClass($long_name);
                //Register the actions and filters for each controller
                self::registerHooks($long_name);
            } catch (ReflectionException $e) {
                error_log($e->getMessage());
            }
        }
    }
    
    /**
     * Run through the models directory and initialize post types and taxonomies.
     * 
     * @access private
     * @static
     * @return void
     */
    private static function registerModels()
    {
        $base_dir_key = sprintf('wpatomic_base_dir:%s', __DIR__);
        $base_dir = $GLOBALS[$base_dir_key];
        if (!file_exists(sprintf('%s/models', $base_dir))) {
            return;
        }
        $models = self::getModelFiles();
        foreach( $models as $path ) {
            //Extract the short name of the class for each file
            $long_name = self::getClassFromPath($path);
            try {
                //Construct a reflection class
                $ref = new \ReflectionClass($long_name);
                //Get the extension of the current class, if it extends another class.
                $extension = $ref->getParentClass();
                //Add the register_taxonomy() or register_post_type() method to the init hook.
                if (
                    is_object($extension)
                    && $extension->name === 'TH\WPAtomic\Post'
                    && $ref->hasMethod('register_post_type')
                ) {
                    add_action('init', array($long_name, 'registerPostType'));
                } elseif(
                    is_object( $extension )
                    && $extension->name === 'TH\WPAtomic\Taxonomy'
                    && $ref->hasMethod('register_taxonomy')
                ) {
                    add_action('init', array($long_name, 'registerTaxonomy'));
                }
            } catch (ReflectionException $e) {
                error_log($e->getMessage());
            }
        }
    }
    
    /**
     * Register actions and filters for a certain class.
     * 
     * @access private
     * @static
     * @param string $class
     * @return void
     */
    private static function registerHooks($class)
    {
        $ns_key = sprintf('wpatomic_ns:%s', __DIR__);
        $ns = $GLOBALS[$ns_key];
        $ref = new \ReflectionClass($class);
        $methods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
        $long_name = $ref->getName();
        foreach ($methods as $m) {
            $hook_type = substr($m->name, 0, 7);
            if ($hook_type !== 'action_' && $hook_type !== 'filter_') {
                continue;
            }
            if ($hook_type === 'action_') {
                $hook_name = str_replace('action_', '', $m->name);
                $hook_function = 'add_action';
            }
            if ($hook_type === 'filter_') {
                $hook_name = str_replace('filter_', '', $m->name);
                $hook_function = 'add_filter';
            }
            $_method = new \ReflectionMethod($long_name, $m->name );
            if (
                $_method->isPublic()
                && !empty($hook_function)
                && !empty($hook_name)
            ) {
                $params = $_method->getParameters();
                $hook_args = array(
                    $hook_name,
                    array($long_name, $m->getName()),
                    10,
                    ( count( $params ) > 0 ? count( $params ) : 1 )
                );
                call_user_func_array($hook_function, $hook_args);
            }
        }
    }
    
    /**
     * Register an autoloader for controllers and classes.
     * 
     * @access private
     * @static
     * @return void
     */
    private static function registerAutoloader()
    {
        spl_autoload_register(array(__CLASS__, 'autoloader'));
    }
    
    /**
     * Autoloads Stashbox plugin classes as they are called.
     * Only triggers for classes in the \TH\Stashbox namespace.
     *
     * @access public
     * @param string $class
     * @return void
     */
    public static function autoloader($class)
    {
        $base_dir_key = sprintf('wpatomic_base_dir:%s', __DIR__);
        $base_dir = $GLOBALS[$base_dir_key];
        //Break out the call stack into chunks
        $stack = explode('\\', ltrim($class, '\\'));
        //Extract the name of the class and build the file path.
        $short_name = end($stack);
        $controller_path = sprintf(
            '%s/controllers/%s.php',
            $base_dir,
            $short_name
        );
        $model_path = sprintf('%s/models/%s.php', $base_dir, $short_name);
        //Require the model or controller depending on which file exists
        if (file_exists($model_path)) {
            require_once($model_path);
            return;
        }
        //Controller classes will always end with Controller
        if (
            file_exists($controller_path) 
            && substr($short_name, -10) === 'Controller'
        ) {
            require_once($controller_path);
            return;
        }
    }
    
    /**
     * Get the short name of a class from the full path.
     * 
     * @access private
     * @static
     * @param string $path
     * @return string
     */
    public static function getClassFromPath($path)
    {
        $ns_key = sprintf('wpatomic_ns:%s', __DIR__);
        $base_dir_key = sprintf('wpatomic_base_dir:%s', __DIR__);
        $ns = $GLOBALS[$ns_key];
        $base_dir = $GLOBALS[$base_dir_key];
        $short_name = str_replace(
            array(
                sprintf('%s/controllers/', $base_dir),
                sprintf('%s/models/', $base_dir),
                '.php'
            ),
            '',
            $path
        );
        $long_name = rtrim( $ns, '\\' ).'\\'.$short_name;
        return $long_name;
    }
    
    /**
    * Retrieve an array of the files in the /controllers/ directory.
    * 
    * @access private
    * @static
    * @return array
    */
    private static function getControllerFiles()
    {
        $base_dir_key = sprintf('wpatomic_base_dir:%s', __DIR__);
        $base_dir = $GLOBALS[$base_dir_key];
        $models = glob(sprintf('%s/controllers/*.php', $base_dir));
        return $models;
    }
    
    /**
     * Retrieve an array of the files in the /models/ directory.
     * 
     * @access private
     * @static
     * @return array
     */
    private static function getModelFiles()
    {
        $base_dir_key = sprintf('wpatomic_base_dir:%s', __DIR__);
        $base_dir = $GLOBALS[$base_dir_key];
        $models = glob(sprintf('%s/models/*.php', $base_dir));
        return $models;
    }
    
    /**
     * Find the path of a model by searching for the post type.
     * 
     * @access private
     * @static
     * @param string $post_type
     * @return string
     */
    public static function findModelPath($post_type)
    {
        $models = self::getModelFiles();
        foreach ($models as $path) {
            foreach (file($path) as $fli => $fl) {
                if (strpos($fl, $post_type) !== false) {
                    return $path;
                }
            }
        }
    }
   
}