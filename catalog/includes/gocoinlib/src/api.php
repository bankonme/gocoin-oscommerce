<?php
/**
 * GoCoin Api
 * A PHP-based GoCoin client library with a focus on simplicity and ease of integration
 *
 * @author Roman A <future.roman3@gmail.com>
 * @version 0.1.3
 *
 * @author Smith L <smith@gocoin.com>
 * @since  0.1.2
 */

require_once('api/merchant.php');
require_once('api/user.php');
require_once('api/apps.php');
require_once('api/invoices.php');
require_once('api/accounts.php');

class Api{   
            
    /**
     * Constructor for the API     
     * @param Object $client  instance of client
     */
    public function  __construct($client){ 
        $this->client = $client;               
        $this->user = new User($this);
        $this->merchant = new Merchant($this);
        $this->apps = new Apps($this);
        $this->invoices = new Invoices($this);
        $this->accounts = new Accounts($this);
    }
    
    /**
    * Do process request
    *  
    * @param string $route Route string for request
    * @param array $options Array of options
    * 
    */


    public function request($route, $options) {

        if (!(($route != null) && is_string($route))) {
            $this->client->setError('Api Request: Route was not defined');
            return false;
        }
        if (!$this->client->getToken()) {
            $this->client->setError('Api not ready: Token was not defined');
            return false;
        }

        // temp checks to remove php notices
        if(!isset($options['header'])){ $options['header'] = null; }
        if(!isset($options['headers'])){ $options['headers'] = null; }
        if(!isset($options['body'])){ $options['body'] = null; }
        if(!isset($options['method'])){ $options['method'] = null; }

        $headers = $options['header'] ? $options['headers']:  $this->client->default_headers;

        $headers['Authorization'] = "Bearer ".$this->client->getToken();

        // separated $options & $client_options
        $client_options = $this->client->options;

        // tweaked config since method was coming through
        $config = array (
            'host' => $client_options['host'],
            'path' => "".$client_options['path']."/".$client_options['api_version'].$route,
            'method' => $options['method'],
            'port' => $this->client->port($client_options['port']),
            'headers' => $headers,
            'body' => $options['body']
        );

        return $this->client->raw_request($config);
    }

}

?>