<?


class Auth{
    private static $user = null;
    
    public static function setUser($payload){
        self::$user = $payload;
    }
    
    public static function user(){
        return self::$user;
    }
    
    public static function id(){
        return self::$user['user_id'] ?? '' ;
    }
    
    public static function role(){
        return self::$user['role'] ?? '' ;
    }
    
    public static function check() {
        return self::$user !== null;
    }
}





?>