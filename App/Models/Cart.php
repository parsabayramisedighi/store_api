<?

class Cart{
    private $db;
    
    public function __construct(){
        $this->db = pdo();   
    }
    
    public function getUserCart($userId){
        $stmt = $this->db->prepare("SELECT `product_id`, `count` FROM `carts` WHERE `user_id` = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    
    public function addOrUpdateCart($userId, $productId, $type){
        $stmt = $this->db->prepare("SELECT * FROM `carts` WHERE `user_id` = :user_id AND `product_id` = :product_id");
        $stmt->execute([
            ':user_id' => $userId,
            ':product_id' => $productId
        ]);
        $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($type == 'add'){
            if(empty($cartItem)){
                $stmt = $this->db->prepare("INSERT INTO `carts` (`user_id`, `product_id`) VALUES (:user_id, :product_id)");
                return $stmt->execute([
                    ':user_id' => $userId,
                    ':product_id' => $productId
                ]);
            }
            else{
                $stmt = $this->db->prepare("UPDATE `carts` SET `count` = `count` + 1 WHERE `user_id` = :user_id AND `product_id` = :product_id");
                return $stmt->execute([
                    ':user_id' => $userId,
                    ':product_id' => $productId
                ]);
            }
        }
        elseif($type == 'minus'){
            if(empty($cartItem)){
                return false;
            }
            
            $count = $cartItem['count'];
            if($count > 1){
                $stmt = $this->db->prepare("UPDATE `carts` SET `count` = `count` - 1 WHERE `user_id` = :user_id AND `product_id` = :product_id");
                return $stmt->execute([
                    ':user_id' => $userId,
                    ':product_id' => $productId
                ]);
            }
            
            return false;
        }
    }
    
    public function getItemCount($userId, $productId){
        $stmt = $this->db->prepare("SELECT `count` FROM `carts` WHERE `user_id` = :userId AND `product_id` = :productId");
        $stmt->execute([
            ':userId' => $userId,
            ':productId' => $productId
        ]);
        
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        return $count['count'];
    }
    
    public function deleteItem($userId, $productId){
        $stmt = $this->db->prepare("DELETE FROM `carts` WHERE `user_id` = :userId AND `product_id` = :productId");
        return $stmt->execute([
           ':userId' => $userId,
           ':productId' => $productId
        ]);
    }
}