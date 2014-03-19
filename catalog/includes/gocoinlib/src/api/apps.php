<?php
/**
 * App Class
 *
 * @author Roman A <future.roman3@gmail.com>
 * @version 0.1.2
 *
 * @author Smith L <smith@gocoin.com>
 * @since  0.1.2
 */

class  Apps{

    private $api;

    public function __construct($api) {
        $this->api = $api;
    }
    public function create($params) {            
        $route = '/oauth/applications';
        $options = array(
            'method' => 'POST',
            'body' => $params
        );
        return $this->api->request($route, $options);
    }
    
    public function create_code($params) {            
        $route = '/oauth/authorize';
        $options = array(
            'method' => 'POST',
            'body' => $params
        );
        return $this->api->request($route, $options);
    }
    
    public function delete($id, $callback) {      
        $route = "/oauth/applications/" . $id;
        $options = array(
            'method' => 'DELETE'
        );
        return $this->api->request($route, $options);
    }
    
    public function delete_authorized($id, $callback) {            
        $route = "/oauth/authorized_applications/" . $id;
        $options = array(
            'method' => 'DELETE'
        );
        return $this->api->request($route, $options);
    }
    
    public function get($id, $callback) {            
        $route = "/oauth/applications/" . $id;
        $options = array();
        return $this->api->request($route, $options);
    }
    
    public function alist($id, $callback) {            
        $route = "/users/" . $id . "/applications";
        $options = array();
        return $this->api->request($route, $options);
    }
    
    public function list_authorized($id, $callback) {            
        $route = "/users/" . $id . "/authorized_applications";
        $options = array();
        return $this->api->request($route, $options);
    }
    
    public function update($params) {            
        $route = "/oauth/applications/" . $params['id'];
        $options = array(
            'method' => 'PATCH',
            'body' => $params['data']
        );
        return $this->api->request($route, $options);
    }
    
    public function new_secret($id, $callback) {            
        $route = "/oauth/applications/" . $id . "/request_new_secret";
        $options = array(
            'method' => 'POST'
        );
        return $this->api->request($route, $options);
    }    
}

?>