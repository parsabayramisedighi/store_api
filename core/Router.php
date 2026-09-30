<?



class Router{
    
    private $routes = [];
    private $currentMiddleware = null;
    
    public function __construct(){
        
    }
    
    public function add($method, $path, $handler, $middlewares = []){
        
        if($this->currentMiddleware !== null){
            if(!empty($middlewares)){
                $middlewares = array_merge((array)$middlewares, (array)$this->currentMiddleware);
            }else{
                $middlewares = (array)$this->currentMiddleware;
            }
        }
        
        $this->routes[] = [
            'method' => strtoupper($method),
            'path'   => trim($path, '/'),
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }
    
    public function run(){
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUrl    = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        
        if(strpos($requestUrl, 'store_api/') === 0){
            $requestUrl  = substr($requestUrl, strlen('store_api/'));
        }
        
        foreach($this->routes as $route){
            if($route['method'] === $requestMethod && $route['path'] === $requestUrl){
                $this->dispatch($route);
                return ;
            }
        }
        
        send_json(['error' => 'پیدا نشد'], 404);
    }
    
    private function dispatch(array $route){
        list($class, $method) = explode('@', $route['handler']);
        
        foreach($route['middlewares'] as $middleware){
            call_user_func($middleware);
        }
       
        require __DIR__ . '/../App/controllers/' . $class . '.php' ;
        $controller = new $class();
        $controller->$method();
        
    }
    
    public function middleware($middleware, callable $call_back){
        $this->currentMiddleware = $middleware;
        $call_back($this);
        $this->currentMiddleware = null ;
    }
    
}


?>