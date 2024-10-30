<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

class MatrixSeo_Config {

	/**
	 * @since    1.0.0
	 * @access   public
	 * @var      array
	 */
    public static $defaultConfig = array(
        'mx_version'                        =>  '1.0.10',
        'mx_stop_words_checksum'		    =>  '67e73e75ed70da1dec48940dde7f4696',
        'mx_activate_cronlog'               =>  '0',
    	'mx_key'							=>  '',
    	'mx_total_se'						=>  '0',
    	'mx_total_ref'						=>	'0',
    	'mx_total_act'						=>	'0',
        'mx_need_upgrade'                   =>  '0',
   		'mx_debug_level'					=> 	'1',
    	'mx_end_title_separator'			=>  '',
    	'mx_save_page_content'              =>  '0',
    	'mx_widget_enabled'                 =>  '0',
    	'mx_beginning_title_separator'		=>  ' - ',
        'mx_plugin_active'                  =>  '0',
        'mx_signature_active'               =>  '1',
        'mx_max_send_size'                  =>  '16000000',
        'mx_max_filesize'                   =>  '500000',
        'mx_interval'                       =>  '3600',
	    'mx_throttle_active'                =>  '1'
	);

    /**
     * This function sets a var through the Config class.
     * @since   1.0.0
     * @access   public
     * @param 	string  $key
     * @param   string  $val
     * @return 	string
     */
    public static function set($key, $val) {
        update_option($key,$val);
        self::updateCachedOption($key,$val);
        return $val;
    }

    /**
     * This function gets a var through the Config class.
     * @since   1.0.0
     * @acces public
     * @param   string  $key
     * @return  string
     */
    public static function get($key) {
        return (self::getCachedOption($key));
    }

    /**
     * This function initializes the default values on plugin install.
     * @since   1.0.0
     * @acces   public
     * @param   void
     * @return  void
     */
	public static function setDefaults() {
        foreach (self::$defaultConfig as $key => $value) {
            if (self::get($key) === false) {
               add_option($key, $value);
            }
        }
        MatrixSeo_Utils::cronDebug("Default config settings set", 3);
    }

	/**
     * @since   1.0.1
     * @access  public
	 * @param   string    $key
	 * @param   string    $value
	 */
    public static function add($key,$value){
	    if(self::get($key) === false){
	        add_option($key, $value);
	        self::updateCachedOption($key,$value);
        }
    }

    /**
     * This function cleans the WP instance on plugin uninstall.
     * @since   1.0.0
     * @acces   public
     * @param   void
     * @return  void
     */
    public static function unsetDefaults(){
        foreach (self::$defaultConfig as $key => $value) {
            if (get_option($key) !== false) {
                delete_option($key);
            }
        }
    }

    /**
     * @since   1.0.7
     * @access  public
     * @param   void
     * @return  void
     */
    public static function unsetNetworkDefaults(){
        global $wpdb;
        $networkSites=get_sites();
        if(is_array($networkSites)) {
            foreach ($networkSites as $networkSite) {
                $prefix = $wpdb->get_blog_prefix($networkSite->blog_id);
                $wpOptions = $prefix."options";
                $mFields = implode('\',\'',array_keys(self::$defaultConfig));
                $mFields = '(\''.$mFields.'\')';
                $query="delete from `".$wpOptions."` where `option_name` in ".$mFields;
                $wpdb->query($query);
            }
        }
    }
    
    /**
     * This function loads the actual Config vars.
     * @since   1.0.0
     * @acces   private
     * @param   void
     * @return  array
     */
	private static function loadAllOptions() {
        $options = wp_cache_get('mx_options', MatrixSeo_Utils::MATRIXSEO);
        if (!$options) {
            foreach (self::$defaultConfig as $key=>$value) {
                $options[$key]=get_option($key);
            }
         }
        wp_cache_add_non_persistent_groups(MatrixSeo_Utils::MATRIXSEO);
        wp_cache_add('mx_options', $options, MatrixSeo_Utils::MATRIXSEO);
        return $options;
    }

    /**
     * This function updates a var and stores it.
     * @since   1.0.0
     * @acces   private
     * @param   string  $name
     * @param   string  $val
     * @return  void
     */
	private static function updateCachedOption($name, $val) {
        $options = self::loadAllOptions();
        $options[$name] = $val;
        wp_cache_set('mx_options', $options, MatrixSeo_Utils::MATRIXSEO);
    }

    /**
     * This function sets a cached option.
     * @since   1.0.0
     * @acces   private
     * @param   string  $name
     * @return  string
     */
	private static function getCachedOption($name) {
        $options = self::loadAllOptions();
        if (isset($options[$name])) {
            return $options[$name];
        }
        return get_option($name);
    }

    /**
     * This function checks if the API key is set.
     * @since   1.0.0
     * @access  public
     * @param   bool    $forceCall
     * @return  boolean
     */
    public static function isKeySet($forceCall=false){ //if isNotSet then try to set it
        $key = self::get('mx_key');
        $isSet=!($key == false || $key == "");
        if($isSet){
            return true;
        }
        if($forceCall){
            MatrixSeo_Utils::cronDebug("Force generating key...",3);
            $newKey=self::generateKey();
            if($newKey != false) {
                MatrixSeo_Utils::cronDebug("New key aquired.",3);
                MatrixSeo_Config::set("mx_key", $newKey);
                $api = MatrixSeo_API::getInstance();
                $api->setApiKey($newKey);
                return true;
            }
        }
        return $isSet;
    }

    /**
     * This function gets the API key.
     * @since   1.0.0
     * @access  public
     * @param   void
     * @return 	mixed   Return the api key or false if it doesn't exist.
     */
    public static function getKey(){
        if (self::isKeySet())
            return self::get('mx_key');
        return false;
    }

    /**
     * This function generates the API key.
     * @since   1.0.0
     * @access  public
     * @param   void
     * @return  mixed   API key or boolean false
     */
    public static function generateKey(){
        require_once MatrixSeo_Utils::getBasePath("lib".DIRECTORY_SEPARATOR."class-matrixseo-api.php");
        $api = MatrixSeo_API::getInstance();

        $keyData = $api->call('get-anon-api-key', array(), array(), true);
        if (isset($keyData['ok']) && isset($keyData['apiKey'])) {
            MatrixSeo_Utils::cronDebug("API Key generated", 3);
            return ($keyData['apiKey']);
        } else {
            MatrixSeo_Utils::cronDebug("Could not understand the response we received from the MatrixSeo API when applying for a free API key.", 3);
            return false;
        }
    }

    /**
     * This function checks if the key is set. If is not set we generate it and set it now.
     * @since   1.0.0
     * @access  public
     * @param   void
     * @return  bool
     */
    public static function checkKey(){
        MatrixSeo_Utils::cronDebug("Checking key",3);
        if (!self::isKeySet()) {
            $genKey = self::generateKey();
            if ($genKey !== false) {
                self::set('mx_key', $genKey);
                MatrixSeo_Utils::cronDebug("Set new API key: ".$genKey,1);
                return true;
            }
            MatrixSeo_Utils::cronDebug("Failed to set new API key.",1);
            return false;
        }
        MatrixSeo_Utils::cronDebug("Key checked OK.",3);
        return true;
    }

	/**
	 * @since   1.0.5
	 * @access  public
	 * @param   void
	 * @return  mixed
	 */
    public static function getCallInterval(){
        return MatrixSeo_Config::get("mx_interval");
    }

	/**
	 * @since   1.0.5
	 * @access  public
	 * @param   int $timeAdd
	 * @return  void
	 */
    public static function setCallInterval($timeAdd){
        MatrixSeo_Utils::cronDebug("Throttle changed to [ ".$timeAdd." seconds ]",2);
        MatrixSeo_Config::set('mx_interval', $timeAdd);
        wp_clear_scheduled_hook('matrixseocronjob');
        wp_schedule_event( time() + $timeAdd,  'mx_interval','matrixseocronjob');
    }

	/**
	 * @since   1.0.5
	 * @access  public
	 * @param   string    $curStatus    [empty, normal, full]
	 * @return  void
	 */
    public static function updateThrottle($curStatus){
    	if(self::get("mx_throttle_active")=="0"){
    		return;
	    }
	    $medInterval    = 3600;
    	$minInterval    = $medInterval / 4;
    	$maxInterval    = $medInterval * 24 * 7;

	    $curInterval    = self::getCallInterval();
	    $newInterval    = $curInterval;

	    switch($curStatus){
		    case 'empty':
		    	    $newInterval    = $curInterval * 2;
		    	break;
		    case 'normal':
		    	    if( $curInterval > $medInterval ){
		    	    	$newInterval    = $curInterval / 2;
			        }
			        elseif( $curInterval < $medInterval ){
		    	    	$newInterval    = $curInterval * 2;
			        }
		    	break;
		    case 'full':
		    	    $newInterval    = $curInterval / 2;
		    	break;
	    }

	    $newInterval = $newInterval > $maxInterval ? $maxInterval : ( $newInterval < $minInterval ? $minInterval : $newInterval );

	    if( $newInterval != $curInterval ) {
            self::setCallInterval($newInterval);
        }
    }
}
?>