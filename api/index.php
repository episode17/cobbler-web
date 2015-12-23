<?php
class FrontController
{
    protected $controller;
    protected $action;
    protected $params = array();
    
    public function __construct()
    {
        header('Content-Type: application/json');
    }
    
    public function run($controller)
    {
        $this->setController($controller);
        $this->parseUri();
        
        $func = array(new $this->controller(), $this->action);
        @call_user_func_array($func, $this->params);
    }
    
    protected function setController($controller)
    {
        $controller = ucfirst(strtolower($controller)) . 'Controller';
        if (!class_exists($controller)) {
            $this->error(400, 'Unknown controller');
        }
        
        $this->controller = $controller;
    }
    
    protected function parseUri()
    {        
        if (!empty($_GET['method'])) {
            $action = $_GET['method'];
            $parts = explode('/', rtrim($action, '/'));
            
            $this->setAction('action' . ucfirst(array_shift($parts)), $parts);
            
        } else {
            $this->error(400, 'Method is required');
        }
    }
    
    protected function setAction($action, $params)
    {
        try {
            $reflector = new ReflectionMethod($this->controller, $action);
            if (!$reflector->isPublic()) {
                throw new Exception();
            }
            if (count($params) < $reflector->getNumberOfRequiredParameters()) {
                $this->error(400, 'Method is missing required parameters');
            }
        } catch (Exception $e) {
            $this->error(404, 'Unknown method');
        }
        
        $this->action = $action;
        $this->params = $params;
    }
    
    protected function error($code, $message)
    {
        http_response_code($code);
        echo json_encode(array(
            'status' => 'ERROR',
            'message' => $message
        ));
        exit();
    }
}


class ApiController
{   
    protected $data = array();
    
    public function __construct()
    {
        $redis = new Redis();
        
        try {
            $redis->connect('127.0.0.1', 6379);
            
            $this->data = array(
                'count' => $redis->get('cobbler:count'),
                'speed' => $redis->get('cobbler:speed')
            );
            
            $redis->close();
        } catch (Exception $e) {
            $this->error(500, $e->getMessage());
        }
    }
    
    public function actionGetAll()
    {   
        $this->success($this->data);
    }
    
    public function actionGetCount()
    {
        $this->success(array(
            'count' => $this->data['count']
        ));
    }
    
    public function actionGetSpeed()
    {
        $this->success(array(
            'speed' => $this->data['speed']
        ));
    }
    
    protected function success($data)
    {
        $out = array_merge(
            array('status' => 'OK'),
            $data
        );
        
        echo json_encode($out);
        exit();
    }
    
    protected function error($code, $message)
    {
        http_response_code($code);
        echo json_encode(array(
            'status' => 'ERROR',
            'message' => $message
        ));
        exit();
    }
}


// Go go go!
$frontController = new FrontController();
$frontController->run('Api');