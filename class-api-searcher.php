<?php

if (!class_exists('Shelterbuddy_Get_Pets_API_Searcher')) :

  class Shelterbuddy_Get_Pets_API_Searcher
  {

    // Enforce the singleton pattern
    public static $instance = null;

    // EDIT: provide correct URL
    private static $fetch_path = "https://client.shelterbuddy.com/api/v2/animal/list?";
    private static $default_page_size = 10;
    private static $headers = array('content-type:application/x-www-form-urlencoded');
    private static $shelterbuddy_search_group_id = 8; // pets up for adoption
    private static $shelterbuddy_updated_since = "2000-01-01T01:00:00Z";
    private static $response_info = array();
    private static $pets = array();

    /**
     * Create an instance of this class
     * @since 1.1.0
     */
    private function __construct() {}

    /**
     * Search ShelterBuddy API for all **adoptable** animals
     */
    private static function fetch_all_shelterbuddy_pets($token, $page_size)
    {
      array_push(self::$headers, wp_sprintf("authorization:bearer %s", $token));
      array_push(self::$headers, wp_sprintf("sb-auth-token:%s", $token));

      $page_size = (empty($page_size)) ? self::$default_page_size : $page_size;
      $page_size = intval($page_size);
      $page_num = 1;
      $next = "not null";

      $sig = self::$shelterbuddy_search_group_id;
      $us = self::$shelterbuddy_updated_since;
      $shelterbuddy_search_model = "SearchGroupId=$sig&UpdatedSince=$us";

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HTTPHEADER, self::$headers);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $shelterbuddy_search_model);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

      $response = array();
      while ($next !== null) {
        $url = self::$fetch_path . wp_sprintf("page=%d&pageSize=%d", $page_num, $page_size);
        curl_setopt($ch, CURLOPT_URL, $url);
        try {
          $response = curl_exec($ch);
          self::$response_info = curl_getinfo($ch); // reset
        } catch (\Throwable $th) {
          throw $th;
        }

        if (isset(self::$response_info["http_code"]) && self::$response_info["http_code"] === 200) {
          $response_object = json_decode($response, true);
          self::$pets = array_merge(self::$pets, $response_object["Data"]);
          self::$response_info["paging"] = $response_object["Paging"];
          self::$response_info["time"] = time();
          $page_num++;
          $next = $response_object["Paging"]["Next"]; // could be null or a string
        } else {
          $next = null;
        }
      }
    }

    public static function get_pets($token, $page_size)
    {
      self::fetch_all_shelterbuddy_pets($token, $page_size);
      return array(self::$response_info, self::$pets);
    }

    public static function have_pets()
    {
      return !empty(self::$pets); // **TODO**
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

// add_action( 'plugins_loaded', array('Shelterbuddy_Get_Pets_API_Searcher', 'get_instance'), 0 );
add_action('init', array('Shelterbuddy_Get_Pets_API_Searcher', 'get_instance'), 0);
