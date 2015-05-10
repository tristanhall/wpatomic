<?php
namespace TH\WPAtomic;

class Template
{
   
    /**
     * Set the directory for the template files.
     * 
     * (default value: '/../views/')
     * 
     * @var string
     * @access private
     * @static
     */
    private static $views_dir = '/views/';
   
    /**
     * Set the extension for the template files.
     * 
     * (default value: '.php')
     * 
     * @var string
     * @access private
     * @static
     */
    private static $views_ext = '.php';
   
    /**
     * Extract the given variables from $data and include the template.
     * Echoing the output is optional.
     * @param string $template_name
     * @param array $context
     * @param boolean $echo
     * @return mixed
     */
    public static function make($template_name, $context = array(), $echo = true)
    {
        $base_dir = $GLOBALS['wpatomic_base_dir:'.__DIR__];
        $file_safe_name = strtolower(str_replace(' ', '-', $template_name));
        $template_path = sprintf(
            '%s%s%s%s',
            $base_dir,
            self::$views_dir,
            $file_safe_name,
            self::$views_ext
        );
        if (!file_exists($template_path)) {
            throw new \Exception(sprintf('Template file "%s" does not exist.', $template_path));
            return;
        }
        if (is_object($context)) {
            $context = (array) $context;
        }
        extract( $context );
        ob_start();
        include($template_path);
        $output = ob_get_clean();
        if ($echo === false) {
            return $output;
        } else {
            echo $output;
        }
   }
   
    /**
     * Takes a string, replaces the "shortcodes" (e.g. [variable1]) with the real values from $data
     * and echos the string.
     * Echoing the string is optional.
     * @param string $template_string
     * @param array $context
     * @param boolean $echo
     * @return mixed
     */
    public static function makeString($template_string, $context = array(), $echo = true)
    {
        foreach ($context as $k => $v) {
            $template_string = str_replace(sprintf('[%s]', $k), $v, $template_string);
        }
        if ($echo === false) {
            return $template_string;
        } else {
            echo $template_string;
        }
    }
   
    /**
     * Return a JSON response, typically for AJAX requests.
     * 
     * @access public
     * @static
     * @param array $context (default: array())
     * @param bool $echo (default: true)
     * @return string
     */
    public static function makeJson($context = array(), $echo = true)
    {
        if ($echo) {
            if (!headers_sent()) {
                header('Content-type: application/json');
            }
            echo json_encode($context);
            exit();
        } else {
            return json_encode($context);
        }
    }
   
}