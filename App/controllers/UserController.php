<?
require __DIR__ . '/../Models/User.php';

class UserController{
    private $userModel;
    
    public function __construct(){
        $this->userModel = new User();
    }
    
    public function index(){
        $result = $this->userModel->all();
        
        if(!$result){
            send_json([
                'success' => false,
                'message' => 'کاربری پیدا نشد'
            ], 404);
        }
        foreach($result as &$user){
            unset($user['password']);
        }
        
        send_json([
            'success' => true,
            'data' => $result
        ]);
    }
    
    public function show(){
        $userId = $_GET['id'] ?? null ;
        
        if(empty($userId) || !is_numeric($userId)){
            send_json([
                'success' => false,
                'message' => 'آیدی نامعتبر است'
            ], 400);
        }
        
        $user = $this->userModel->findById($userId);
        if(!$user){
            send_json([
                'success' => false,
                'message' => 'کاربر پیدا نشد'
            ], 404);
        }
        unset($user['password']);
        
        send_json([
           'success' => true,
           'data' => $user
        ]);
    }
    
    public function delete(){
        $userId = $_GET['id'] ?? null ;
        
        if(empty($userId) || !is_numeric($userId)){
            send_json([
                'success' => false,
                'message' => 'آیدی نامعتبر است'
            ], 400);
        }
        
        $user = $this->userModel->findById($userId);
        if(!$user){
            send_json([
                'success' => false,
                'message' => 'کاربر پیدا نشد'
            ], 404);
        }
        
        if((int)$user['ID'] == (int)Auth::id()){
            send_json([
                'success' => false,
                'message' => 'نمی‌توانید حساب کاربری خود را حذف کنید'
            ], 403);
        }
        
        $result = $this->userModel->delete($userId);
        if(!$result){
            send_json([
                'success' => false,
                'message' => 'خطا در عملیات'
            ], 500);
        }
        
        send_json([
            'success' => true,
            'message' => 'کاربر با موفقیت حذف شد'
        ]);
        
    }
    
    public function profile(){
        $user = $this->userModel->findById(Auth::id());
        
        if(empty($user)){
            send_json([
                'success' => false,
                'message' => 'کاربر پیدا نشد'
            ],404);
        }
        unset($user['password']);
        
        send_json([
            'success' => true,
            'data' => $user
        ]);
    }
    
    // by user
    public function profileUpdate(){
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = Auth::id();
        
        if(array_key_exists('username', $data)){
            $username = $data['username'] ;
            
            if($this->userModel->existsByField('username', $username, $userId)){
                send_json([
                    'success' => false,
                    'message' => 'این نام کاربری قبلا ثبت شده است'
                ],422);
            }
            elseif(strlen($username) < 4){
                send_json([
                    'success' => false,
                    'message' => 'نام کاربری حداقل باید 4 کاراکتر باشد'
                ],422);
            }
        }
        if(array_key_exists('email', $data)){
            $email = $data['email'] ;
            
            if($this->userModel->existsByField('email', $email, $userId)){
                send_json([
                    'success' => false,
                    'message' =>  'این ایمیل قبلا ثبت شده است'
                ],422);
            }
            elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
                send_json([
                    'success' => false,
                    'message' => 'فرمت ایمیل درست نیست'
                ],422);
            }
        }
        if(array_key_exists('phone', $data)){
            $phone = '0'.ltrim($data['phone'],'0');
            
            if(strlen($phone) !== 11){
                send_json([
                    'success' => false,
                    'message' => 'شماره تلفن باید 11 رقم باشد'
                ],422);
            }
        }
        unset($data['password']);
        unset($data['user_lvl']);
        
        $result = $this->userModel->updateUser($userId, $data);
        if($result == true){
            send_json([
                'success' => true,
                'message' => 'پروفایل شما با موفقیت آپدیت شد'
            ]);
        }
        else{
            send_json([
                'success' => false,
                'message' => 'حداقل یک فیلد ارسال کنید'
            ], 400);
        }
    }
    
    // by admin
    public function userUpdate(){
        $id = $_GET['id'] ?? null ;
        $data = json_decode(file_get_contents('php://input'), true);
        
        if(empty($id) || !is_numeric($id)){
            send_json([
               'success' => false,
               'message' => 'آیدی نامعتبر'
            ], 404);
        }
        
        $user = $this->userModel->findById($id);
        
        if(empty($user)){
            send_json([
               'success' => false,
               'message' => 'کاربر پیدا نشد'
            ], 404);
        }
        
       if((int)$user['ID'] == (int)Auth::id()){
            send_json([
                'success' => false,
                'message' => 'نمی‌توانید حساب کاربری خود را ویرایش کنید'
            ], 403);
        }
        
        if(array_key_exists('username', $data)){
            $username = $data['username'] ;
            
            if($this->userModel->existsByField('username', $username, $id)){
                send_json([
                    'success' => false,
                    'message' => 'این نام کاربری قبلا ثبت شده است'
                ],422);
            }
            elseif(strlen($username) < 4){
                send_json([
                    'success' => false,
                    'message' => 'نام کاربری حداقل باید 4 کاراکتر باشد'
                ],422);
            }
        }
        if(array_key_exists('email', $data)){
            $email = $data['email'] ;
            
            if($this->userModel->existsByField('email', $email, $id)){
                send_json([
                    'success' => false,
                    'message' => 'این ایمیل قبلا ثبت شده است'
                ],422);
            }
            elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
                send_json([
                    'success' => false,
                    'message' => 'فرمت ایمیل درست نیست'
                ],422);
            }
        }
        if(array_key_exists('password', $data)){
            $password = $data['password'];
            
            if(strlen($password) < 4){
                send_json([
                    'success' => false,
                    'message' => 'پسوورد باید حداقل 4 کاراکتر باشد'
                ],422);
            }
        }
        if(array_key_exists('phone', $data)){
            $phone = '0'.ltrim($data['phone'],'0');
            
            if(strlen($phone) !== 11 || !is_numeric($phone)){
                send_json([
                    'success' => false,
                    'message' => 'شماره تلفن باید 11 رقم باشد'
                ],422);
            }
        }
        if(array_key_exists('user_lvl', $data)){
            $user_lvl = $data['user_lvl'];
            
            if(!is_numeric($user_lvl) || !in_array($user_lvl, [1,2,3])){
                send_json([
                    'success' => false,
                    'message' => 'نقش نامتعبر میباشد'
                ],422);
            }
            
            $data['user_lvl'] = (int)$user_lvl;
        }
        
        $result = $this->userModel->updateUser($id, $data);
        if($result == true){
            send_json([
                'success' => true,
                'message' => 'کاربر با موفقیت ویرایش شد'
            ]);
        }
        else{
            send_json([
                'success' => false,
                'message' => 'حداقل یک فیلد ارسال کنید'
            ], 400);
        }
        
    }
}