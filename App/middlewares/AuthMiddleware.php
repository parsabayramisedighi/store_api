<?
require_once __DIR__ . '/../../core/Auth.php';

class AuthMiddleware{
    public static function handle($requiredRole = null){
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        
        if(empty($authHeader)){
            send_json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز است، لطفاً وارد حساب کاربری شوید'
            ], 401);
        }
        elseif(strpos($authHeader, 'Bearer ') !== 0){
            send_json([
                'success' => false,
                'message' => 'فرمت احراز هویت نامعتبر است'
            ], 401);
        }
        
        $token = substr($authHeader, strlen('Bearer '));
        $payload = jwt_decode($token);

        if(empty($payload)){
            send_json([
                'success' => false,
                'message' => 'توکن نامعبتر میباشد'
            ], 401);
        }
        
        $db = pdo();
        $stmt = $db->prepare("SELECT * FROM `user_tokens` WHERE `token` = :token");
        $stmt->execute([':token' => $token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(empty($result) || $result['expires_at'] < time()){
            $stmt = $db->prepare("DELETE FROM `user_tokens` WHERE `token` = :token");
            $stmt->execute([':token' => $token]);
            
            send_json([
                'success' => false,
                'message' => 'توکن شما منقضی شده'
            ], 401);
        }
        
        
        if(isset($payload['role']) && $payload['role'] == 3){
            $stmt = $db->prepare("DELETE FROM user_tokens WHERE token = :token");
            $stmt->execute([':token' => $token]);
            
            send_json([
                'success' => false,
                'message' => 'اکانت شما مسدود میباشد'
            ], 403);
        }
        elseif($requiredRole !== null && $payload['role'] != $requiredRole){
            send_json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز میباشد'
            ], 403);
        }
        
        Auth::setUser($payload);
    }
}
















?>