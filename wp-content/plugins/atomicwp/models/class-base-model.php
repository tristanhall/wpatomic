<?php

namespace AtomicWP;

abstract class BaseModel extends BaseController {
   
   /**
    * The table name belonging to the current model.
    * @var string 
    */
   protected static $table_name;
   
   /**
    * The ID field belonging to the current model.
    * @var string
    */
   protected static $id_field = 'id';
   
   /**
    * An array of all the columns that could be shown in a data table.
    * Example:
    * ['name'] => array( 'label' => 'Name', 'callback' => array( __CLASS__, 'callback_function' ) );
    * 'name' is the name of the variable containing the data.
    * 'label' is the title to display in the data table's heading
    * 'callback' defines an optional callback function to run for this field
    * when building a data table instead of simply displaying the data.
    * @var array
    */
   protected static $columns = array();
   
   /**
    * An array of the columns from the list above that should be sortable.
    * @var array
    */
   protected static $sortable_columns = array();
   
   /**
    * An array of the columns from the list above that should be filterable.
    * Columns are filtered based on boolean or boolean-like values being in the column.
    * @var array
    */
   protected static $filter_columns = array();
   
   /**
    * Define whether or not to paginate the data table for the current object.
    * @var boolean
    */
   protected static $paginated = true;
   
   /**
    * Define the default number of items to show per page in a data table.
    * @var integer 
    */
   protected static $items_per_page = 25;
   
   /**
    * Define whether or not to include edit & delete actions as the last column in a data table.
    * @var boolean
    */
   protected static $show_actions = true;
   
   public function __construct() {
      //does nothing yet.
   }
   
   private static function log_query( $query, $result_count ) {
      if( static::$log_queries !== false ) {
         return;
      }
      global $wpdb;
      $log_record = array(
         'class' => get_called_class(),
         'message' => "Query: ".$query."\nResult Count: ".$result_count,
         'created_at' => date( 'Y-m-d H:i:s' )
      );
      $wpdb->insert( $wpdb->prefix.static::$ns.'log', $log_record );
   }
   
   /**
    * Retrieve a single record from the DB.
    * @global object $wpdb
    * @param integer $id
    * @return object
    */
   public static function get_by_id( $id ) {
      global $wpdb;
      $query = $wpdb->prepare(
         "SELECT * FROM `".$wpdb->prefix.static::$ns.static::$table_name."` WHERE `".static::$id_field."` = %d",
         $id
      );
      $record = $wpdb->get_row( $query );
      self::log_query( $query, 1 );
      $class_name = get_called_class();
      $object = new $class_name();
      $properties = get_object_vars( $record );
      foreach( $properties as $name => $value ) {
         $object->$name = $value;
      }
      return $object;
      
   }
   
   /**
    * Save an array of values to the database. If an ID is provided, the record will be updated.
    * @global object $wpdb
    * @param array $values
    * @param integer $id
    * @return integer
    */
   public static function save( $values, $id = null ) {
      global $wpdb;
      if( $id === null ) {
         $wpdb->insert( $wpdb->prefix.static::$ns.static::$table_name, $values );
         self::log_query( $wpdb->last_query, 1 );
         $id = $wpdb->insert_id;
         self::log_revision( $id, get_called_class() );
         return $id;
      } else {
         $wpdb->update( $wpdb->prefix.static::$ns.static::$table_name, $values, array( static::$id_field => $id ) );
         self::log_query( $wpdb->last_query, 1 );
         self::log_revision( $id, get_called_class() );
         return $id;
      }
   }
   
   /**
    * Retrieve a collection of records from the database. Supports WHERE & ORDER clauses and pagination with $offset & $limit.
    * @global object $wpdb
    * @param string $where
    * @param string $order
    * @param integer $offset
    * @param integer $limit
    * @return \TMBooking\class_name
    */
   public static function get( $where = '', $order = '', $offset = null, $limit = 20 ) {
      global $wpdb;
      if( !empty( $where ) ) {
         $where = ' AND '.$where;
      }
      if( !empty( $order ) ) {
         $order = ' ORDER BY '.$order;
      }
      if( !is_null( $offset ) ) {
         $query = 'SELECT * FROM `'.$wpdb->prefix.static::$ns.static::$table_name.'` WHERE 1'.$where.$order.' LIMIT '.intval( $offset * $limit ).', '.intval( $limit );
         $results = $wpdb->get_results( $query );
      } else {
         $results = $wpdb->get_results( 'SELECT * FROM `'.$wpdb->prefix.static::$ns.static::$table_name.'` WHERE 1'.$where.$order );
      }
      array_walk( $results, array( __CLASS__, 'cast_object' ), get_called_class()  );
      self::log_query( $wpdb->last_query, count( $results ) );
      return $results;
   }
   
   /**
    * Count the number of objects are in a table matching the provided conditions.
    * @global object $wpdb
    * @param string $where
    * @return type
    */
   public static function count( $where = '' ) {
      global $wpdb;
      if( !empty( $where ) ) {
         $where = 'AND '.$where;
      }
      $count = $wpdb->get_var( 'SELECT COUNT(*) FROM `'.$wpdb->prefix.static::$ns.static::$table_name.'` WHERE 1 '.$where );
      self::log_query( $wpdb->last_query, 1 );
      return $count;
   }
   
   /**
    * Delete a record from the DB.
    * @global object $wpdb
    * @param integer $id
    */
   public static function delete( $id ) {
      global $wpdb;
      $wpdb->delete( $wpdb->prefix.static::$ns.static::$table_name, array( static::$id_field => $id ) );
      self::log_query( $wpdb->last_query, 1 );
   }
   
   /**
    * Move an object to the trash.
    * @global object $wpdb
    * @param integer $id
    */
   public static function trash( $id ) {
      global $wpdb;
      $wpdb->update( $wpdb->prefix.static::$ns.static::$table_name, array( 'trash' => 1 ), array( static::$id_field => $id ) );
      self::log_query( $wpdb->last_query, 1 );
   }
   
   /**
    * Restore an object from the trash.
    * @global object $wpdb
    * @param integer $id
    */
   public static function restore( $id ) {
      global $wpdb;
      $wpdb->update( $wpdb->prefix.static::$ns.static::$table_name, array( 'trash' => 0 ), array( static::$id_field => $id ) );
      self::log_query( $wpdb->last_query, 1 );
   }
   
   /**
    * Cast an object as the called class.
    * @param mixed $item
    * @param mixed $key
    * @param string prefix
    */
   public static function cast_object( &$item, $key, $called_class ) {
      if( is_object( $item ) ) {
         $object = new $called_class();
         $properties = get_object_vars( $item );
         foreach( $properties as $key => $value ) {
            $object->$key = $value;
         }
         $item = $object;
      }
   }
   
   /**
    * Append an ORDER BY clause if the URL parameters are set and no ORDER BY clause
    * has been already defined.
    * @param object $query
    */
   public static function sort_handler( $query ) {
      $orderby = filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_STRING );
      $order = filter_input( INPUT_GET, 'order', FILTER_SANITIZE_STRING );
      if( empty( $query->order ) ) {
         $query->order = 'ORDER BY `'.( !empty( $orderby ) ? $orderby : static::$id_field ).'` '.( !empty( $order ) ? $order : 'DESC' );
      }
      return $query;
   }
   
   /**
    * Append a WHERE clause if the URL parameters are set for filtering.
    * @param object $query
    */
   public static function filter_handler( $query ) {
      $filter_column = filter_input( INPUT_GET, 'filter', FILTER_SANITIZE_STRING );
      if( !is_array( $query->where ) ) {
         $query->where = array();
      }
      if( in_array( $filter_column, static::$filter_columns ) ) {
         $query->where[] = '`'.$filter_column.'` = 1';
      }
      return $query;
   }
   
   /**
    * Enables pagination for data tables if the URL variables are set.
    * @param object $query
    */
   public static function pagination_handler( $query ) {
      if( filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT ) != 0 ) {
         $offset = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );
      } else {
         $offset = 0;
      }
      if( filter_input( INPUT_POST, 'count', FILTER_SANITIZE_NUMBER_INT ) != 0 ) {
         $limit = filter_input( INPUT_POST, 'count', FILTER_SANITIZE_NUMBER_INT );
      } else {
         $limit = static::$items_per_page;
      }
      if( static::$paginated === true ) {
         $query->limit = 'LIMIT '.intval( $offset * $limit ).', '.intval( $limit );
      }
      return $query;
   }
   
   public static function render_data_table( $echo = true ) {
      global $wpdb;
      $query = new \stdClass();
      $query->select = 'SELECT * FROM `'.$wpdb->prefix.static::$ns.static::$table_name.'` WHERE 1';
      $query->where = array();
      $query->order = '';
      $query->limit = '';
      add_filter( static::$ns.'_query_where', array( __CLASS__, 'filter_handler' ), 10, 1 );
      $query = apply_filters( static::$ns.'_query_where', $query );
      add_filter( static::$ns.'_query_order', array( __CLASS__, 'sort_handler' ), 10, 1 );
      $query = apply_filters( static::$ns.'_query_order', $query );
      add_filter( static::$ns.'_query_limit', array( __CLASS__, 'pagination_handler' ), 10, 1 );
      $query = apply_filters( static::$ns.'_query_limit', $query );
      $where_string = count( $query->where ) > 0 ? ' AND '.implode( ' AND ', $query->where ) : '';
      $query_string = trim( $query->select.' '.$where_string.' '.$query->order.' '.$query->limit );
      $result_set = $wpdb->get_results( $query_string );
      self::log_query( $wpdb->last_query, count( $result_set ) );
      array_walk( $result_set, array( __CLASS__, 'cast_object' ), get_called_class() );
      $table = new \stdClass();
      $table->header = static::build_data_table_header();
      $table->body = static::build_data_table_body( $result_set );
      $table->footer = static::build_data_table_footer();
      $table_class = static::$ns.static::$table_name;
      $html = '<table class="wp-list-table '.static::$ns.'-table widefat fixed '.$table_class.'">'.$table->header.$table->body.$table->footer.'</table>';
      if( $echo === true ) {
         echo $html;
      } else {
         return $html;
      }
   }
   
   private static function build_data_table_header() {
      $page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING );
      $orderby = filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_STRING );
      $order = filter_input( INPUT_GET, 'order', FILTER_SANITIZE_STRING );
      $header_html = '<thead><tr>';
      foreach( static::$columns as $column_name => $settings ) {
         $cell_class = 'manage-column';
         $next_order = $order == 'ASC' ? 'DESC' : 'ASC';
         $cell_class .= $column_name == $orderby ? ' sorted '.$order : ' '.$next_order;
         $header_html .= '<th scope="col" class="'.$cell_class.'">';
         if( in_array( $column_name, static::$sortable_columns ) ) {
            $url = admin_url( 'admin.php?page='.$page.'&orderby='.$column_name.'&order='.$next_order );
            add_filter( static::$ns.'_sort_url', array( __CLASS__, 'build_sort_url' ), 10, 1 );
            $url = apply_filters( static::$ns.'_sort_url', $url );
            $header_html .= '<a href="'.$url.'" title="'.__( 'Sort by '.$settings['label'], static::$domain ).'">';
            $header_html .= '<span>'.__( $settings['label'], static::$domain ).'</span>';
            $header_html .= '</a>';
            $header_html .= '<span class="sorting-indicator"></span>';
         } else {
            $header_html .= '<span>'.__( $settings['label'], static::$domain ).'</span>';
         }
         $header_html .= '</th>';
      }
      if( static::$show_actions === true ) {
         $header_html .= '<th>'.__( 'Actions', static::$domain ).'</th>';
      }
      $header_html .= '</tr></thead>';
      return apply_filters( static::$ns.'_data_table_header_html', $header_html );
   }
   
   private static function build_data_table_footer() {
      $footer_html = '<tfoot><tr>';
      foreach( static::$columns as $settings ) {
         $cell_class = 'manage-column';
         $footer_html .= '<th scope="col" class="'.$cell_class.'">';
         $footer_html .= '<span>'.__( $settings['label'], static::$domain ).'</span>';
         $footer_html .= '</th>';
      }
      if( static::$show_actions === true ) {
         $footer_html .= '<th>'.__( 'Actions', static::$domain ).'</th>';
      }
      $footer_html .= '</tr></tfoot>';
      return apply_filters( static::$ns.'_data_table_footer_html', $footer_html );
   }
   
   public static function build_sort_url( $url ) {
      $action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );
      $filter = filter_input( INPUT_GET, 'filter', FILTER_SANITIZE_STRING );
      if( filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT ) != 0 ) {
         $paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );
      } else {
         $paged = 0;
      }
      if( filter_input( INPUT_GET, 'count', FILTER_SANITIZE_NUMBER_INT ) != 0 ) {
         $count = filter_input( INPUT_GET, 'count', FILTER_SANITIZE_NUMBER_INT );
      } else {
         $count = static::$items_per_page;
      }
      if( !empty( $action ) ) {
         $url .= '&action='.$action;
      }
      if( !empty( $filter ) ) {
         $url .= '&filter='.$filter;
      }
      $url .= '&paged='.$paged.'&count='.$count;
      return $url;
   }
   
   private static function build_data_table_body( $result_set ) {
      $zebra = 0;
      $body_html = '<tbody>';
      foreach( $result_set as $r ) {
         $row_class = $zebra % 2 ? 'alternate odd' : 'even';
         $body_html .= '<tr class="'.$row_class.'">';
         foreach( static::$columns as $column_name => $settings ) {
            $body_html .= '<td>';
            if( !empty( $settings['callback'] ) ) {
               $body_html .= call_user_func( $settings['callback'] );
            } else {
               $body_html .= $r->$column_name;
            }
            $body_html .= '</td>';
         }
         if( static::$show_actions === true ) {
            $body_html .= '<td>';
            if( method_exists( $r, 'edit_link' ) ) {
               $body_html .= $r->edit_link();
            }
            if( method_exists( $r, 'delete_link' ) ) {
               $body_html .= $r->delete_link();
            }
            $body_html .= '</td>';
         }
         $body_html .= '</tr>';
         $zebra++;
      }
      $body_html .= '</tbody>';
      return apply_filters( static::$ns.'_data_table_body_html', $body_html );
   }
   
}