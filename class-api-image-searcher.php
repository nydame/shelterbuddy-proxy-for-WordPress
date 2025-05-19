<?php

if (! class_exists('Shelterbuddy_Get_Pets_API_Image_Searcher')):

  class Shelterbuddy_Get_Pets_API_Image_Searcher
  {

    private $shelterbuddy_id;
    private $fetch_path;
    private $default_page_size;
    private $headers;
    private $shelterbuddy_updated_since;
    private $response_info;
    private $images;

    /**
     * Create an instance of this class
     * @since 3.1.0
     * @param int $sbid is the ShelterBuddy ID of the animal in question
     */
    public function __construct($sbid)
    {
      $this->shelterbuddy_id = $sbid;
      // EDIT: provide correct URL
      $this->fetch_path = "https://client.shelterbuddy.com/api/v2/animal/photo/list?";
      $this->default_page_size = 1;
      $this->headers = array('content-type:application/x-www-form-urlencoded');
      $this->shelterbuddy_updated_since = "2000-01-01T01:00:00Z";
      $this->response_info = array();
      $this->images = array();
    }

    /**
     * Search ShelterBuddy API for all **adoptable** animals
     */
    private function fetch_shelterbuddy_images($token, $page_size)
    {
      array_push($this->headers, wp_sprintf("authorization:bearer %s", $token));
      array_push($this->headers, wp_sprintf("sb-auth-token:%s", $token));

      $page_size = (empty($page_size)) ? $this->default_page_size : $page_size;
      $page_size = intval($page_size);
      $page_num = 1;
      $start_index = 0;
      $order_by = "string";
      $next = "not null";

      $sbid = $this->shelterbuddy_id;
      $us = $this->shelterbuddy_updated_since;
      $shelterbuddy_search_model = "AnimalId=$sbid&UpdatedSince=$us";

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $shelterbuddy_search_model);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

      $response = array();
      while ($next !== null) {
        $url = $this->fetch_path . wp_sprintf("page=%d&pageSize=%d&startIndex=%d&orderBy=%s", $page_num, $page_size, $start_index, $order_by);
        curl_setopt($ch, CURLOPT_URL, $url);
        try {
          $response = curl_exec($ch);
          $this->response_info = curl_getinfo($ch); // reset
        } catch (\Throwable $th) {
          throw $th;
        }

        if (isset($this->response_info["http_code"]) && $this->response_info["http_code"] === 200) {
          $response_object = json_decode($response, true);
          $this->images = array_merge($this->images, $response_object["Data"]);
          $this->response_info["paging"] = $response_object["Paging"];
          $this->response_info["time"] = time();
          $page_num++;
          $next = $response_object["Paging"]["Next"]; // could be null or a string
        } else {
          $next = null;
        }
      }
    }

    public function get_images($token, $page_size)
    {
      if ($this->shelterbuddy_id) {
        $this->fetch_shelterbuddy_images($token, $page_size);
        return array($this->response_info, $this->images);
      }
    }

    public function have_images()
    {
      return ! empty($this->images); // **TODO**
    }
  }

endif;
