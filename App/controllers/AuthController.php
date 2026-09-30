<?
require_once __DIR__ . '/../Models/User.php';


class AuthController{
    
    private $db;
    private $user;
    
    public function __construct(){
        $this->db = pdo();
        $this->user = new User();
    }
    
    public function register(){
        $data = json_decode(file_get_contents('php://input'), true);
        
        $username = $data['username'] ?? '';
        $email    = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $phone = '0'.ltrim($data['phone'],'0');
        
        if(empty($username) || empty($email) || empty($password) || empty($data['phone'])){
            send_json([
                'success' => false,
                'message' => 'لطفا فیلدهارا کامل ارسال کنید'
            ], 400);
        }
        elseif(strlen($username) < 4){
            send_json([
                'success' => false,
                'message' => 'نام کاربری حداقل باید 4 کاراکتر باشد'
            ],422);
        }
        elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            send_json([
                'success' => false,
                'message' => 'فرمت ایمیل درست نیست'
            ],422);
        }
        elseif(strlen($password) < 4){
            send_json([
                'success' => false,
                'message' => 'پسوورد حداقل باید 4 کاراکتر باشد'
            ],422);
        }
        elseif(strlen($phone) !== 11){
            send_json([
                'success' => false,
                'message' => 'شماره تلفن باید 11 رقم باشد'
            ],422);
        }

        if ($this->user->existsByField('username', $username)) {
            send_json([
                'success' => false,
                'message' => 'نام کاربری از قبل وجود دارد'
            ], 409);
        }
        if ($this->user->existsByField('email', $email)) {
            send_json([
                'success' => false,
                'message' => 'ایمیل از قبل وجود دارد'
            ], 409);
        }

        $lastId = $this->user->create($username, $email, $password, $phone);
        send_json([
            'success' => true,
            'message' => 'کاربر با موفقیت ساخته شد',
            'userId'  => $lastId
        ]);
    }
    
    public function login(){
        $data = json_decode(file_get_contents('php://input'), true);
        
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $remember = $data['remember'] ?? '';
        
        if(!empty($remember)){
            $expSeconds = 30 * 24 * 60 * 60;
        }else{
            $expSeconds = 15 * 60;
        }
        
        if(empty($username) || empty($password)){
            send_json([
                'success' => false,
                'message' => 'لطفا همه فیلدهارا پر کنید'
            ], 400);
        }
        
        $stmt = $this->db->prepare('SELECT * FROM `users` WHERE `username` = :username');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$user || !password_verify($password, $user['password'])){
            send_json([
                'success' => false,
                'message' => 'نام کاربری و یا رمز عبور اشتباه است'
            ], 401);
        }
        
        $stmt = $this->db->prepare("DELETE FROM `user_tokens` WHERE `user_id` = :user_id");
        $stmt->execute([':user_id' => $user['ID']]);
        
        $payload = [
            'user_id' => $user['ID'],
            'role'    => $user['user_lvl']
        ];
        $token = jwt_encode($payload, $expSeconds);
        
        $stmt = $this->db->prepare("INSERT INTO `user_tokens` (`user_id`, `token`, `expires_at`) VALUES (:user_id, :token, :expires_at)");
        $stmt->execute([
            'user_id' => $user['ID'],
            'token'   => $token,
            'expires_at' => time() + $expSeconds
        ]);
        
        
        send_json([
            'success' => true,
            'token'   => $token
        ]);
        
    }
    
    public function logout(){
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        
        if(empty($authHeader)){
            send_json([
                'success' => false,
                'message' => 'ابتدا وارد حساب کاربری خود شوید'
            ]);
        }
        elseif(strpos($authHeader, 'Bearer ') !== 0) {
            send_json([
                'success' => false,
                'message' => 'فرمت احراز هویت نامعتبر است'
            ], 401);
        }
        
        $token = substr($authHeader, strlen('Bearer '));
        
        $stmt = $this->db->prepare("DELETE FROM `user_tokens` WHERE `token` = :token");
        $stmt->execute([':token' => $token]);
        
        if ($stmt->rowCount() === 0) {
            send_json([
                'success' => false,
                'message' => 'توکن معتبر نیست'
            ], 401);
        }
        
        send_json([
            'success' => true,
            'message' => 'با موفقیت خارج شدید'
        ]);
    }
}









?>