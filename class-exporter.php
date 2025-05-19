<?php

if (! class_exists('Shelterbuddy_Get_Pets_Exporter')):

  class Shelterbuddy_Get_Pets_Exporter
  {

    // Enforce the singleton pattern
    public static $instance = null;

    private static $json_file = "adoptable-pets.json";

    /**
     * Create an instance of this class
     * @since 1.3.0
     */
    private function __construct() {}

    private static function export_json_for_wp_all_import($data)
    {
      // format JSON data to suit WP All Imports
      $data_as_array = json_decode($data, true);
      $wp_all_imports_array = array();
      foreach ($data_as_array as $in => $inner_array) {
        // an array of **objects** is required
        array_push($wp_all_imports_array, wp_json_encode(array("pet" => $inner_array), JSON_FORCE_OBJECT));
      }
      $temp_string = wp_json_encode($wp_all_imports_array);
      // get rid of all the backslashes and extra quote marks
      $wp_all_imports_json = stripcslashes($temp_string);
      $wp_all_imports_json = str_replace('}}"', "}}", $wp_all_imports_json);
      $wp_all_imports_json = str_replace('"{', "{", $wp_all_imports_json);
      // overwrite the JSON file (or create for first time) 
      file_put_contents(SHELTERBUDDY_GET_PETS_PLUGIN_PATH . self::$json_file, $wp_all_imports_json, LOCK_EX);
      // TODO: trigger WP All Imports import

    }

    public static function export_json($data)
    {
      self::export_json_for_wp_all_import($data);
    }

    public static function get_instance()
    {
      if (null == self::$instance) {
        self::$instance = new self;
      }
      return self::$instance;
    }
  }

endif;

// add_action( 'plugins_loaded', array('Shelterbuddy_Get_Pets_Exporter', 'get_instance'), 0 );
add_action('init', array('Shelterbuddy_Get_Pets_Exporter', 'get_instance'), 0);
