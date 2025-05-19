<?php
require_once(SHELTERBUDDY_GET_PETS_PLUGIN_PATH . "class-api-searcher.php");
require_once(SHELTERBUDDY_GET_PETS_PLUGIN_PATH . "class-api-image-searcher.php");
require_once(SHELTERBUDDY_GET_PETS_PLUGIN_PATH . "class-exporter.php");

if (! class_exists('Shelterbuddy_Get_Pets')) :

        /**
         * Calls the API for a token, then requests pets from the API using the token
         */
        class Shelterbuddy_Get_Pets
        {

                // Enforce singleton pattern
                protected static $instance = null;

                // EDIT: provide correct URL
                private static $auth_path = "https://client.shelterbuddy.com/api/v2/authenticate?";
                private static $username = "ShelterBuddy API";
                // TODO: provide secure environment variable
                private static $password = "abc123";
                private static $headers = array("content-type:application/json");
                private static $token;
                private static $pets = array();
                private static $items_per_page = 4;
                private static $images_per_page = 5;
                private static $log_filename = "plugin.log";

                /**
                 * Create an instance of this class
                 * @since 1.0.0
                 */
                private function __construct()
                {
                        # both of these are ok!
                        #   self::log_message("hello");
                        #   $this->log_message("goodbye");
                }

                /**
                 * Call the API to get a token, then again to get a list of pets.
                 * @since 2.0.0
                 */
                public static function call_api()
                {
                        // get token
                        self::$token = self::get_token();
                        // $token_msg = ( empty(self::$token) )? __( "Token not received from ShelterBuddy" ) : wp_sprintf( "Token received from ShelterBuddy: %s", self::$token );
                        // self::log_message( $token_msg );
                        // get pets and info about the API response in the form of 2 arrays
                        $api_responses = Shelterbuddy_Get_Pets_API_Searcher::get_pets(self::$token, self::$items_per_page);
                        // self::log_message( print_r( $api_responses[0], true ) );
                        // self::log_message( print_r( $api_responses[1], true ) );
                        self::$pets = $api_responses[1];
                        if (self::have_pets()) {
                                // self::log_message( print_r( self::$pets, true ) );
                                foreach (self::$pets as $pet => $pet_data) {
                                        self::log_message($pet_data['Name'] . " is #" . $pet_data["Id"]);
                                        usleep(250000);
                                        $image_searcher = new Shelterbuddy_Get_Pets_API_Image_Searcher($pet_data['Id']);
                                        $image_api_responses = $image_searcher->get_images(self::$token, self::$images_per_page);
                                        self::$pets[$pet]['Photos'] = $image_api_responses[1];
                                        // self::log_message( print_r( $image_api_responses[1], true) );
                                        // self::log_message( print_r( self::$pets[$pet], true) );
                                        // loop over 2nd response, which is an indexed array
                                        // say each item is an array called "item"
                                        // then item['Photo'] is the URI of one photo: "/storage/image/..."
                                        // photo can be retrieved at https://sonomahs.shelterbuddy.com . item['Photo']

                                }
                                $pets_as_json = wp_json_encode(self::$pets);
                                Shelterbuddy_Get_Pets_Exporter::export_json($pets_as_json);
                                self::log_message("adoptable-pets.json created or updated");
                        }
                }

                public static function get_token()
                {
                        $u = urlencode(self::$username);
                        $p = urlencode(self::$password);
                        $url = self::$auth_path . wp_sprintf("username=%s&password=%s", $u, $p);

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_HTTPHEADER, self::$headers);
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                        $response = null;
                        try {
                                $response = curl_exec($ch);
                                error_log($response);
                                return json_decode($response);
                        } catch (\Throwable $th) {
                                throw $th;
                        }

                        return "";
                }

                public static function have_pets()
                {
                        return (count(self::$pets) > 0); // NEEDS TESTING; try PHP Unit
                }

                /**
                 * Write a message to the plugin's own log file or, failing that, to the PHP system log
                 */
                public static function log_message(String $msg = "", Bool $is_reset = false)
                {
                        $is_logging = false;
                        $msg = date("Y-m-d, H:i:s") . " UTC\n" . $msg . "\n\n";

                        if ($is_reset) {
                                // overwrite
                                $is_logging = file_put_contents(SHELTERBUDDY_GET_PETS_PLUGIN_PATH . self::$log_filename, $msg, LOCK_EX);
                        } else {
                                // append
                                $is_logging = file_put_contents(SHELTERBUDDY_GET_PETS_PLUGIN_PATH . self::$log_filename, $msg, FILE_APPEND | LOCK_EX);
                        }

                        if (! $is_logging) {
                                error_log($msg);
                        }
                }

                /**
                 * Return an instance of this class
                 * @return object A single instance of this class
                 * @since   1.0.0
                 */
                public static function get_instance()
                {
                        // ignore AJAX calls and cron jobs
                        // if(( defined('DOING_AJAX') && DOING_AJAX ) || ( defined('DOING_CRON') && DOING_CRON )) {
                        //      return;
                        // }
                        // if an instance has not yet been set, do so now
                        if (null == self::$instance) {
                                self::$instance = new self;
                        }
                        return self::$instance;
                }
        }

        // add_action( 'plugins_loaded', array('Shelterbuddy_Get_Pets', 'get_instance'), 0 );
        add_action('init', array('Shelterbuddy_Get_Pets', 'get_instance'), 0);


endif;
