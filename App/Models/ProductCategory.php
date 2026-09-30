<?


class ProductCategory{
    private $db;
    
    public function __construct(){
        $this->db = pdo();
    }
    
    private function attach($product_id, array $categories_id){
        $stmt = $this->db->prepare("INSERT INTO `products_categories` (`product_id`, `category_id`) VALUES (:product_id, :category_id)");
        
        foreach($categories_id as $category_id){
            if(!$stmt->execute([':product_id' => $product_id,':category_id' => $category_id])){
                return false;
            }
        }
        return true;
    }
    
    public function detach($productId){
        $stmt = $this->db->prepare("
            DELETE FROM `products_categories`
            WHERE `product_id` = :product_id
        ");
    
        return $stmt->execute([
            ':product_id' => $productId
        ]);
    }
    
    public function sync($productId, array $categoriesId){
        if(!$this->detach($productId)){
            return false;
        }
   
        return $this->attach($productId, $categoriesId);
    }
    
    public function getCategoriesByProduct($product_id){
        $stmt = $this->db->prepare("
            SELECT c.*
            FROM categories c
            INNER JOIN products_categories pc
                ON c.ID = pc.category_id
            WHERE pc.product_id = :product_id
        ");
    
        $stmt->execute([
            ':product_id' => $product_id
        ]);
    
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($result as &$item){
            unset($item['created_at']);
        }
        return $result;
    }
    
    public function getProductsByCategory($category_id){
        $stmt = $this->db->prepare("
            SELECT p.*
            FROM products p
            INNER JOIN products_categories pc
                ON p.ID = pc.product_id
            WHERE pc.category_id = :category_id
        ");
    
        $stmt->execute([
            ':category_id' => $category_id
        ]);
    
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
}