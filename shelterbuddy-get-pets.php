<?php
/*
Plugin Name: ShelterBuddy Proxy
Description: Fetches a list of adoptable pets in a JSON file using the ShelterBuddy API and WordPress
Version: 4.0.0
Author: Felicia Betancourt
Author URI: https://go-firefly.com
*/
defined('ABSPATH') || exit('Nice try!');

define('SHELTERBUDDY_GET_PETS_VERSION', '4.0.0');
define('SHELTERBUDDY_GET_PETS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SHELTERBUDDY_PET_LIST', SHELTERBUDDY_GET_PETS_PLUGIN_PATH . "adoptable-pets.json");
define('SHELTERBUDDY_CACHE_TIME', MINUTE_IN_SECONDS * 5);
// define('SHELTERBUDDY_GET_PETS_PLUGIN_DIR', dirname( __FILE__ ));

# get list of pets if none exists
if (!file_exists(SHELTERBUDDY_PET_LIST)) {
  require_once(SHELTERBUDDY_GET_PETS_PLUGIN_PATH . "class-get-pets.php");
  Shelterbuddy_Get_Pets::log_message("Start logging", true);
  Shelterbuddy_Get_Pets::call_api();
} else {
  # periodically refresh list of pets
  if (time() - filemtime(SHELTERBUDDY_PET_LIST) > SHELTERBUDDY_CACHE_TIME) {
    require_once(SHELTERBUDDY_GET_PETS_PLUGIN_PATH . "class-get-pets.php");
    Shelterbuddy_Get_Pets::call_api();
  }
}
