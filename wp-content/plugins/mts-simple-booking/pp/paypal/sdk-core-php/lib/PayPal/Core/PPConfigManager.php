<?php
namespace PayPal\Core;
use PayPal\Core\PPConfigManager;
use PayPal\Exception\PPConfigurationException;
/**
 * PPConfigManager loads the SDK configuration file and
 * hands out appropriate config params to other classes
 */


class PPConfigManager {

	private $config;
	
	//default config values
	public static $defaults = array(
		"http.ConnectionTimeOut" => "30",
		"http.Retry" => "5",
	);

	/**
	 * @var PPConfigManager
	 */
	private static $instance;

	private function __construct(){
		if(defined('PP_CONFIG_PATH')) {
			$configFile = constant('PP_CONFIG_PATH') . '/sdk_config.ini';
		} else {		
			$configFile = implode(DIRECTORY_SEPARATOR,
				array(dirname(__FILE__), "..", "config", "sdk_config.ini"));
		}
		$this->load($configFile);
	}

	// create singleton object for PPConfigManager
	public static function getInstance()
	{
		if ( !isset(self::$instance) ) {
			self::$instance = new PPConfigManager();
		}
		return self::$instance;
	}

	//used to load the file
	private function load($fileName) {
        //Gracefully check for ini file
        if(@parse_ini_file($fileName)) {
            $this->config = @parse_ini_file($fileName);
        }

        // sdk_config.iniファイルの代替え
        else {
            $this->config = parse_ini_string($this->mts_sdk_config_ini());
        }
    }

    // sdk_config.iniファイルの代替え
    private function mts_sdk_config_ini()
    {
        global $mts_simple_booking;

        $credential = $mts_simple_booking->oPPManager->getCredentials();
        $mode = $mts_simple_booking->oPPManager->getUseSandbox() ? 'sandbox' : 'live';

        return <<<EOINI
;Account credentials
[Account]

;acct1.ClientId = AQkquBDf1zctJOWGKWUEtKXm6qVhueUEMvXO_-MCI4DQQ4-LWvkDLIN2fGsd
;acct1.ClientSecret = EL1tVxAjhT7cJimnz5-Nsx9k2reTKSVfErNQF-CmrwJgxRtylkGTKlU4RvrX 
;acct1.UserName = jb-us-seller_api1.paypal.com
;acct1.Password = WX4WTU3S8MY44S7F
;acct1.Signature = AFcWxV21C7fd0v3bYYYRCpSSRl31A7yDhhsPUU2XhtMoZXsWHFxu-RWy
acct1.UserName = {$credential['pp_username']}
acct1.Password = {$credential['pp_password']}
acct1.Signature = {$credential['pp_signature']}
;acct1.AppId = APP-80W284485P519543T
; Subject is optional and is required only in case of third party authorization 
acct1.Subject = 

; Certificate Credentials Test Account
;acct2.UserName = platfo_1255170694_biz_api1.gmail.com
;acct2.Password = 2DPPKUPKB7DQLXNR
; Certificate path relative to config folder or absolute path in file system
;acct2.CertPath = cacert.pem


;Connection Information
[Http]
http.ConnectionTimeOut = 60
;http.Retry = 1
;http.Proxy

;Logging Information
[Log]
log.FileName=../PayPal.log
log.LogLevel=INFO
log.LogEnabled=false

mode = {$mode}
 
EOINI;
    }

	/**
	 * simple getter for configuration params
	 * If an exact match for key is not found,
	 * does a "contains" search on the key
	 */
	public function get($searchKey){

		if(array_key_exists($searchKey, $this->config))
		{
			return $this->config[$searchKey];
		}
		else {
			$arr = array();
			foreach ($this->config as $k => $v){
				if(strstr($k, $searchKey)){
					$arr[$k] = $v;
				}
			}
			
			return $arr;
		}

	}

	/**
	 * Utility method for handling account configuration
	 * return config key corresponding to the API userId passed in
	 *
	 * If $userId is null, returns config keys corresponding to
	 * all configured accounts
	 */
	public function getIniPrefix($userId = null) {

		if($userId == null) {
			$arr = array();
			foreach ($this->config as $key => $value) {
				$pos = strpos($key, '.');
				if(strstr($key, "acct")){
					$arr[] = substr($key, 0, $pos);
				}
			}
			return array_unique($arr);
		} else {
			$iniPrefix = array_search($userId, $this->config);
			$pos = strpos($iniPrefix, '.');
			$acct = substr($iniPrefix, 0, $pos);
			
			return $acct;
		}
	}
	
	/**
	 * returns the config file hashmap
	 * 
	 */
	private function getConfigHashmap()
	{
		return $this->config;
	}
	
	/**
	 * use  the default configuration if it is not passed in hashmap
	 */
	public static function getConfigWithDefaults($config=null)
	{
		return array_merge(PPConfigManager::$defaults, 
				($config != null) ? $config : PPConfigManager::getInstance()->getConfigHashmap());
	}
}

    
