<?php 

/**
 * GoCoin Api
 * Client class
 * Main interface to use GoCoin Api
 *
 * @author Roman A <future.roman3@gmail.com>
 * @version 0.1.3
 *
 * @author Smith L <smith@gocoin.com>
 * @since  0.1.2
 * 
 */

require_once('api.php');
require_once('auth.php');

class Client {            
    /**
    * The default api options array
    * 
    * @var array
    */
    private $default_options = array (
                  'client_id' => null,
                  'client_secret' =>null,
                  'host' => 'api.gocoin.com',
                  'dashboard_host' => 'dashboard.gocoin.com',
                  'port' => null,
                  'path' => '/api',
                  'api_version' => 'v1',
                  'secure' => true,
                  'method' => 'GET',
                  'headers' => null,
                  'request_id' => null,
                  'redirect_uri' => null
            );
            
    /**
    * The default header array
    * 
    * @var array
    */
    
    public $default_headers = array('Content-Type' => 'application/json');    
    
    /**
    * options array object
    * 
    * @var array
    */
    public $options = array();
    
    /**
    * $header array object
    * 
    * @var array
    */
    public $headers = array();
    
    /**
    * token string
    * 
    * @var string
    */
    private $token = null;
    
    /**
    * error string;
    * 
    * @var mixed
    */
    private $error = "";
    
    /**
    * Constructor
    * 
    * @param array $options: initial options to use api
    */
    
    public function  __construct($options){ 
        if ($options == null) {
            $options = array();
        }
        $this->options = $this->set_default_value($options, $this->default_options);

        if(!isset($options['headers'])){ $options['headers'] = null; }

        $this->headers = $options['headers'] != null ? array_merge($options['headers'], $this->default_headers) : $this->default_headers;

        if ($this->options['request_id'] != null) {
            $this->headers['X-Request-Id'] = $this->options['request_id'];
        }
        $this->auth = new Auth($this);
        $this->api = new Api($this);
        $this->user = $this->api->user;
        $this->merchant = $this->api->merchant;
        $this->apps = $this->api->apps;
        $this->invoices = $this->api->invoices;
        $this->accounts = $this->api->accounts;
    }
    
    /**
    * Authorization process
    * @return boolean    
    */
   
    public function authorize_api($code=null) {
        if ($this->getToken() !== null) {
            return true;
        }        
        return $this->get_token_from_request($code);        
    }
    
    /**
    * Get authorization code and setToken
    *  if process is done successfully, return true else return false
    * @return boolean
    */
    
    public function get_token_from_request($code=null) {  
        $auth_code = isset($_GET['code'])? $_GET['code']:null;
        if ($code != null) $auth_code = $code;
        if ($auth_code) {
            $options['grant_type'] = "authorization_code";
            $options['code'] = $auth_code;
            $options['client_id'] = $this->options['client_id'];
            $options['client_secret'] = $this->options['client_secret'];
            $options['redirect_uri'] = $this->get_current_url();            
            $options = $this->set_default_value($options, $this->options);
            $auth_result = $this->auth->authenticate($options);            
            $this->setToken($auth_result->access_token);
        } else {
            $this->setError("Can not get authroization code");
            return false;
        }
        return true;
    }
    
    /**
    * Initialize access token and session data
    * 
    */
    
    public function initToken() {
        unset($_SESSION['gocoin_access_token']);
        $this->token = null;
    }
    
    /**
    * Return client id
    *  @return String $client_id
    */
    
    public function getClientId() {
        return $this->options['client_id'];
    }
    
    /**
    * Set client_id in options array
    * 
    * @param mixed $client_id
    * @return Client
    */
    
    public function setClientId($client_id) {
        $this->options['client_id'] = $client_id;
        return $this;
    }
    
    /**
    * Return client secret 
    *  @return String $client_secret
    */
    
    public function getClientSecret() {
        return $this->options['client_secret'];  
    }
    
    /**
    * Set client secret in options array
    * 
    * @param mixed $secret
    * @return Client
    */
    
    public function setClientSecret($secret) {
        $this->options['client_secret'] = $secret;
        return $this;
    }
    
    /**
    * Set access token
    * 
    * @param string $token
    */
    
    public function setToken($token) {
        $_SESSION['gocoin_access_token'] = $token;
        $this->token = $token;
    }
    
    /**
    * Return access token
    *  @return String $token
    */
    
    public function getToken() {
        if ($this->token == null) {
            if (isset($_SESSION['gocoin_access_token'])) {
                $this->token = $_SESSION['gocoin_access_token'];
            }
        }
        return $this->token;
    }
    
    /**
    * Return operation error
    *  @return  String $error
    */
    
    public function getError() {
        return $this->error;
    }
    
    /**
    *  Set error string for operation
    * 
    * @param mixed $error
    * @return Client
    */
    
    public function setError($error) {
        $this->error = $error;
        return $this;
    }
    
    /**
    * Return Api's url
    * 
    * @param mixed $options  The Array value including api options
    * @return string
    */
    
    public function get_api_url($options) {
        $options = $this->set_default_value($options, $this->options);
        $url = $this->request_client($options['secure'])."://".$options['host'].$options['path']."/".$options['api_version'];
        return $url;
    }
    
    /**
    * Return dashboard api's url
    * @return string
    */
    
    public function get_dashboard_url() {
        $url = $this->request_client($this->options['secure'])."://".$this->options['dashboard_host'];
        return $url;
    }
    
    /**
    * Return Authorization url
    *  @return string
    */
    
    public function get_auth_url() {        
        /*$url = $this->get_dashboard_url($this->options)."/auth";
        $options = array(
            'response_type' => 'code',
            'client_id' => $this->options['client_id'],
            'redirect_uri' => $this->get_current_url(),
            'scope' => 'user_read',
        );
        $url = $this->create_get_url($url, $options);*/
        return $this->auth->get_auth_url();
    }
    
    /**
    * Return protocol string for http
    * 
    * @param mixed $secure
    * @return mixed
    */
    
    public function request_client($secure) {

        if ($secure === null) {
            $secure = true;
        }
        if ($secure) {
            return 'https';
        } else {
            return 'http';
        }
    }
    /**
    * Return api port
    * 
    * @param mixed $secure
    * @return int
    */
    
    public function port($secure) {
        if ($secure === null) {
            $secure = true;
        }

        if ($this->options['port'] != null) {
            return $this->options['port'];
        } else if ($secure) {
            return 443;
        } else {
            return 80;
        }
    }
    
    /**
    * Get result from curl and process it
    * 
    * @param mixed $config configuration parameter
    *
    * @return Object
    */
    
    public function raw_request($config) {

        $url = $this->request_client($this->options['secure'])."://".$config['host'] . $config['path'];

        $headers = $this->default_headers;

        $result = $this->do_request($url, $config['body'], $config['headers'], $config['method']);

        $result = json_decode($result);

        if (isset($result->error)) {
            $this->setError($result->error_description);
            return false;
        }
        return $result;
    }
    
    // Helper Functions 
    
    /**
    * merge two array's value 
    * if $arr have no element in $default_arr then insert the element from $default_arr
    * 
    * @param mixed $arr
    * @param mixed $default_arr
    * @return array
    */
    
    public function set_default_value($arr, $default_arr) {
        $result = array();
        $result = $default_arr;        
        foreach ($arr as $key => $value) {            
            $result[$key] = $value;            
        }
        return $result;
    }
    
     /**
     * create_get_url
     * Create complete url for GET method with auth parameters     
     * @param String $url The base URL for api
     * @param Array $params The parameters to pass to the URL
     * @return string
     */    
    public function create_get_url($url,$params){
 
        if(!empty($params) && $params){
            foreach($params as $param_name=>$param_value){
                $arr_params[] = "$param_name=".$param_value;
            }
            $str_params = implode('&',$arr_params);
            //$str_params = http_build_query($params);
            $url = trim($url) . '?' . $str_params;
        }        
        return $url;
    }

    /**
     * get xrate
     *
     * Gets the xrate - aka current btc exchange rate in US Dollars
     *
     * @throws Exception error
     * @return Array
     */

    public function get_xrate() {

        $xrate_config['url'] = 'http://x.g0cn.com/prices';
        $xrate_config['method'] = 'GET';

        $result = $this->do_request($xrate_config['url'], '', '', $xrate_config['method']);

        $result = json_decode($result);

        if (isset($result->error)) {
            $e = new Exception($result->error_description);
            throw $e;
        }
        return $result;
    }
   
    /**
     * do_request
     *
     * Performs a cUrl request with a url . The useragent of the request is hardcoded
     * as the Google Chrome Browser agent
     *
     * @param String $url The base url to query
     * @param Boolean $params The parameters to pass to the request
     * @param Array $headers curl header
     * @param String $method curl type
     *
     *
     * @return Array
     */
     
    public function do_request($url, $params=false, $headers, $method="POST") {

        if (!isset($ch)) {
          $ch = curl_init();
        }

        $opts = array(
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT        => 60,            
        );

        if ($method == "POST") {
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $params;
        }

        if ($method == "PATCH") {
            $opts[CURLOPT_CUSTOMREQUEST] = "PATCH";
            $opts[CURLOPT_POSTFIELDS] = $params;
        }


        /* if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
             $opts[CURLOPT_USERAGENT] = $_SERVER['HTTP_USER_AGENT'];
         } else {
             // Handle the useragent like we are Google Chrome
             $opts[CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.X.Y.Z Safari/525.13.';
         }*/

        $opts[CURLOPT_URL] = $url;
        $opts[CURLOPT_HEADER] = false;
        $opts[CURLOPT_SSL_VERIFYPEER] = false;
        
        $curl_header = array();
        if ($headers && count($headers)) {
            foreach ($headers as $key => $value) {
                $curl_header[] = $key.': '.$value;
            }
        }
        
        if (isset($opts[CURLOPT_HTTPHEADER])) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($curl_header, $opts[CURLOPT_HTTPHEADER]);
        } else {
            $opts[CURLOPT_HTTPHEADER] = $curl_header;
        }

        curl_setopt_array($ch, $opts);

        $result = curl_exec($ch);

        if ($result === false) {
            $this->setError(curl_error($ch));
            curl_close($ch);
            return false;
        }
        curl_close($ch);         
        return $result;
    }
    
    /**
    * get_current_url
    * Returns the Current URL, drop params what is included in default params
    *  @return string
    */
    public function get_current_url() {
        if (isset($_SERVER['HTTPS']) &&
            ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
          $protocol = 'https://';
        }
        else {
          $protocol = 'http://';
        }
        $currentUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $parts = parse_url($currentUrl);

        $query = '';
        if (!empty($parts['query'])) {
          // drop known params          
          $params = explode('&', $parts['query']);
          $retained_params = array();
          foreach ($params as $param) {
            if ($this->should_drop_param($param)) {
              $retained_params[] = $param;
            }
          }

          if (!empty($retained_params)) {
            $query = '?'.implode($retained_params, '&');
          }
        }
        
        // use port if non default
        $port =
          isset($parts['port']) &&
          (($protocol === 'http://' && $parts['port'] !== 80) ||
           ($protocol === 'https://' && $parts['port'] !== 443))
          ? ':' . $parts['port'] : '';

        // rebuild
        return $protocol . $parts['host'] . $port . $parts['path']; //. $query;
    }
    
    /**
    * Return the Array including the params should be removed in options
    * 
    * @param mixed $param
    *
    * @return boolean
    */
    
    public function should_drop_param($param) {        
        $drop_params = array('code');
        foreach ( $drop_params as $drop_param ) {
            if (strpos($param, $drop_param) === 0) {
                return false;
            }
        }
        return true;
    }
}

?>