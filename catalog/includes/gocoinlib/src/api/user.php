<?php
/**
 * User Class
 *
 * @author Roman A <future.roman3@gmail.com>
 * @version 0.1.2
 *
 * @author Smith L <smith@gocoin.com>
 * @since  0.1.2
 */

class User {

    private $api;

    public function __construct($api) {
        $this->api = $api;
    }
    public function create($params) {            
        $route = '/users';
        $options = array(
            'body' => $params['data']
        );
        return $this->api->request($route, $options);
    }
    public function delete($id) {            
        $route = "/users/" . $id;
        $options = array(
            'method' => 'POST',
            'body' => $params['data']
        );
        return $this->api->request($route, $options);
    }
    
    public function get($id) {            
        $route = "/users/" . $id;
        $options = array();
        return $this->api->request($route, $options);
    }
    
    public function _list() {            
        $route = '/users';
        $options = array();
        return $this->api->request($route, $options);
    }
    
    public function update($params) {            
        $route = "/users/" . $params['id'];
        $options = array(
            'method' => 'PATCH',
            'body' => $params['data']
        );
        return $this->api->request($route, $options);
    }
    
    public function self() {            
        $route = '/user';
        $options = array();
        return $this->api->request($route, $options);
    }
    
    public function update_password($params) {            
        $route = "/users/" . $params['id'] . "/password";
        $options = array(
            'method' => 'PATCH',
            'body' => $params['data']
        );
        return $this->api->request($route, $options);
    }
    
    public function request_password_reset($params) {      
        $route = "/users/request_password_reset";
        $config = array(
            'host' => $this->api->client->options['host'],
            'path' => "" . $this->api->client->options['path'] . "/" . $this->api->client->options['api_version'] . $route,
            'method' => 'POST',
            'port' => $this->api->client.port(),
            'headers' => $this->api->client->headers,
            'body' => $params['data']
        );
        return $this->api->client->raw_request($config);
    }
    
    public function request_new_confirmation_email($params) {            
        $route = "/users/request_new_confirmation_email";
        $config = array(
            'host' => $this->api->client->$options['host'],
            'path' => "" . $this->api->client->options['path'] . "/" . $this->api->client->options['api_version'] . $route,
            'method' => 'POST',
            'port' => $this->api->client->port(),
            'headers' => $this->api->client->headers,
            'body' => $params['data']
        );
        return $this->api->client->raw_request($config);
    }
    
    public function reset_password_with_token($params) {            
        $route = "/users/" . $params['id'] . "/reset_password/" . $params['reset_token'];
        $config = array(
            'host' => $this->api->client->options['host'],
            'path' => "" . $this->api->client->options['path'] . "/" . $this->api->client->options['api_version'] . $route,
            'method' => 'PATCH',
            'port' => $this->api->client->port(),
            'headers' => $this->api->client->headers,
            'body' => $params['data']
        );
        return $this->api->client->raw_request($config);
    }
}
?>
