<?
require __DIR__ . '/../Models/Cart.php';

class CartController{
    private $cartModel;
    private $db;
    
    public function __construct(){
        $this->cartModel = new Cart();
        $this->db        = pdo();
    }
    
    public function index(){
        $userId = Auth::id();
        
        if(empty($userId)){
            send_json([
                'success' => false,
                'message' => 'کاربر پیدا نشد'
            ], 404);
        }
        
        $userCarts = $this->cartModel->getUserCart($userId);
        $grand_total = 0;
        
        foreach($userCarts as &$item){
            $productId = (int)$item['product_id'];
            $stmt = $this->db->prepare("SELECT * FROM `products` WHERE `ID` = :id");
            $stmt->execute([':id' => $productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $item['name'] = $product['product_name'];
            
            if($product['product_discount_price'] !== null){
                $item['price'] = (int)$product['product_discount_price'];
            }
            else{
                $item['price'] = (int)$product['product_price'];
            }
            
            $item['total'] = (int)($item['price'] * $item['count']);
            $grand_total += $item['total'];
        }
        $data['items'] = $userCarts;
        $data['grand_total'] = $grand_total;

        send_json([
            'success' => true,
            'data'    => $data
        ]);
    }
    
    public function addOrUpdateCartItem(){
        $data = json_decode(file_get_contents('php://input'), true);
        $type = $data['action'];
        $userId = Auth::id();
        $productId = $data['product_id'];
        
        if(empty($userId)){
            send_json([
                'success' => false,
                'message' => 'لطفا وارد اکانت خود شوید'
            ], 401);
        }
        
        if(!is_numeric($productId)){
            send_json([
                'success' => false,
                'message' => 'شناسه محصول نامعتبر است'
            ], 422);
        }
        
        $stmt = $this->db->prepare("SELECT * FROM `products` WHERE `ID` = :id");
        $stmt->execute([':id' => $productId]);
        
        if($stmt->rowCount() == 0){
            send_json([
                'success' => false,
                'message' => 'محصول پیدا نشد'
            ], 404);
        }
        
        $result = $this->cartModel->addOrUpdateCart($userId, $productId, $type);
        $count  = $this->cartModel->getItemCount($userId, $productId);
        
        if($result == false){
            send_json([
                'success' => false,
                'count'   => $count,
                'action'  => $type,
                'message' =>  'تعداد محصول نمی‌تواند کمتر از 1 شود'
            ], 422);
        }
        
        send_json([
            'success' => true,
            'count'   => $count,
            'action'  => $type
        ]);

    }
    
    public function deleteItem(){
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = Auth::id();
        $productId = $data['product_id'];
        
        if(empty($userId)){
            send_json([
                'success' => false,
                'message' => 'لطفا وارد اکانت خود شوید'
            ], 401);
        }
        
        if(!is_numeric($productId)){
            send_json([
                'success' => false,
                'message' => 'شناسه محصول نامعتبر است'
            ], 422);
        }
        
        $stmt = $this->db->prepare("SELECT * FROM `products` WHERE `ID` = :id");
        $stmt->execute([':id' => $productId]);
        if($stmt->rowCount() == 0){
            send_json([
                'success' => false,
                'message' => 'محصول پیدا نشد'
            ], 404);
        }
        
        
        
        $count = $this->cartModel->getItemCount($userId, $productId);
        if($count == null){
            send_json([
                'success' => false,
                'message' => 'محصول در سبد خرید شما نیست'
            ], 404);
        }
        
        $result = $this->cartModel->deleteItem($userId, $productId);
        send_json([
           'success' => true,
           'message' => 'محصول با موفقیت از سبد شما شد'
        ]);
    }

    
}