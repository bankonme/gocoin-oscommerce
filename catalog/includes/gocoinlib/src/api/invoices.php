<?php

/**
 * Invoice Class
 *
 * @author Roman A <future.roman3@gmail.com>
 * @version 0.1.2
 *
 * @author Smith L <smith@gocoin.com>
 * @since  0.1.2
 */
  
class Invoices {

    private $api;

    public function __construct($api) {
        $this->api = $api;
    }
    
    public function create($params) {            
        $route = "/merchants/" . $params['id'] . "/invoices";
        $options = array(
            'method' => 'POST',
            'body' => $params['data']
        );
        return $this->api->request($route, $options);
    }
    
    public function get($id) {           
        $route = "/invoices/" . $id;
        $options = array();
        return $this->api->request($route, $options);
    }
    
    public function search($params) {            
        $params = http_build_query($params);
        $route = "/invoices/search?" . $params;
        $options = array();
        return $this->api->request($route, $options);
    }
    
    public function update($params) {      
        $route = "/invoices/" . $params['id'];
        $options = array(
            'method' => 'PATCH',
            'body' => $params['data']
        );
        return $this->api->request($route, $options);
    }
}
?>