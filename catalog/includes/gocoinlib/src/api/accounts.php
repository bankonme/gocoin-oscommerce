<?php
/**
 * Account Class
 *
 * @author Roman A <future.roman3@gmail.com>
 * @version 0.1.2
 *
 * @author Smith L <smith@gocoin.com>
 * @since  0.1.2
 */

class  Accounts {
    private $api;
    
    public function  __construct($api){ 
        $this->api = $api;
    }
    
    public function create($params) {            
      $route = "/merchants/" . $params['id'] . "/accounts";

      $options = array (
        'method' => 'POST',
        'body' => $params['data']
      );
      return $this->api->request($route, $options);
    }
    
    public function get($id) {      
      $route = "/accounts/" . $id;
      $options = array();
      return $this->api->request($route, $options);
    }
    
    public function update($params) {      
      $route = "/accounts/" . $params['id'];
      $options = array(
        'method' => 'PATCH',
        'body' => $params['data']
      );
      return $this->api->request($route, $options);
    }
    
    public function alist($id) {            
      $route = "/merchants/" . $id . "/accounts";
      $options = array();
      return $this->api->request($route, $options);
    }
    
    public function delete($id) {            
      $route = "/accounts/" . $id;
      $options = array (
        'method' => 'DELETE'
      );
      return $this->api->request($route, $options);
    }
    
    public function verify($params) {       
      $route = "/accounts/" . $params['id'] . "/verifications";
      $options = array(
        'method' => 'POST',
        'body' => $params['data']
      );
      return $this->api->request($route, $options);
    }
}

?>