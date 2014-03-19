<?php

/**
 * Merchant Class
 *
 * @author Roman A <future.roman3@gmail.com>
 * @version 0.1.2
 *
 * @author Smith L <smith@gocoin.com>
 * @since  0.1.2
 */

class Merchant {

    private $api;
    
    public function __construct($api) {
      $this->api = $api;
    }
    
    public function create($params) {      
      $route = '/merchants';
      $options = array(
        'method' => 'POST',
        'body' =>  $params
      );
      return $this->api->request($route, $options);
    }
    public function delete($id) {            
      $route = "/merchants/" . $id;
      $options = array(
        'method' => 'DELETE'
      );
      return $this->api->request($route, $options);
    }
    public function get($id) {            
      $route = "/merchants/" . $id;
      $options = array();
      return $this->api->request($route, $options);
    }
    
    public function _list() {            
      $route = '/merchants';
      $options = array();
      return $this->api->request($route, $options);
    }
    
    public function update($params, $callback) {          
      $route = "/merchants/" . $params['id'];
      $options = array(
        'method' => 'PATCH',
        'body' => $params['data']
      );
      return $this->api->request($route, $options);
    }
}
?>
