#AtomicWP
A Composer package for drastically speeding up WordPress plugin development.

##Installation
 1. Install from Composer: `composer require tristanhall/wpatomic`
	 - Note: Run this from inside the plugin folder where you want to use WPAtomic.
 2. Setup your directory structure:
	 - `/wp-content`
		 - `/plugins`
			 - `/my-plugin`
				 - `/controllers`
				 - `/models`
				 - `/views`
 3. Add the following tidbit to your main plugin file:
    `require_once( __DIR__.'/vendor/autoload.php' );`
    `\TH\WPAtomic\WPAtomic::init( __NAMESPACE__, __DIR__ );`
    Note: The first parameter is the namespace you will use for your controllers and models. The second parameter is optional, it is the path to your plugin. WPAtomic attempts to discover the path on its own, but will throw a `RuntimeException` if it can't find the path.

##Controllers
Controller class names must end with `Controller` for WPAtomic to register them properly. Additionally, the controller filenames must match the class names.
###A basic controller:
    <?php
    namespace TH\Stashbox;
    
    use TH\WPAtomic\Template;
    
    class CoreController {
    
	    public function action_init() {
			//This is the same as "add_action( 'init', ... );"
		}
		
		public function filter_the_content() {
			//This is the same as "add_filter( 'the_content', ... );"
		}
    
    }

 - Starting a function with `action_{wp_action_name}` will tell WPAtomic to add that function to the WordPress action you name.
 - Starting a function with `filter_{wp_action_name}` will tell WPAtomic to add that function to the WordPress filter you name.

##Models

##Views

##Roadmap
 - More documentation (models, views, templating, etc.)
 - Improved Taxonomy models
 - Term models
 - Role Models
 - User models
 - Performance Improvements