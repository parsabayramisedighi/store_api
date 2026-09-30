<?
require __DIR__ . '/../Models/ProductCategory.php';
require __DIR__ . '/../Models/Category.php';


class ProductController{
    private $db;
    private $ProductCategory;
    private $categoryModel;
    
    
    public function __construct(){
        $this->db = pdo();
        $this->ProductCategory = new ProductCategory();
        $this->categoryModel = new Category();
    }
    
    public function index(){
        $result = $this->listProducts(false);
        
        send_json([
            'success' => true,
            'page'    => $result['page'],
            'size'    => $result['size'],
            'total'   => $result['total'],
            'data'    => $result['data']
        ]);
    }
    
    public function publicIndex(){
        $result = $this->listProducts(true);
        
        send_json([
            'success' => true,
            'page'    => $result['page'],
            'size'    => $result['size'],
            'total'   => $result['total'],
            'data'    => $result['data']
        ]);
    }
    
    public function create(){
        $data = json_decode(file_get_contents('php://input'), true);
        
        $product_name = trim($data['product_name'] ?? '');
        $product_description = trim($data['product_description'] ?? '');
        $product_price = $data['product_price'] ?? '' ;
        $product_discount_price = $data['product_discount_price'] ?? '' ;
        $categories = $data['categories'] ?? [] ;
        $stock = $data['stock'] ?? 1 ;
        
        if(empty($product_name) || empty($product_description) || empty($product_price)){
            send_json([
               'success' => false,
               'message' => 'فیلدها را کامل ارسال کنید'
            ], 400);
        }
        elseif(empty($categories) || !is_array($categories)){
            send_json([
                'success' => false,
                'message' => 'حداقل یک دسته بندی انتخاب کنید'
            ], 422);
        }
        elseif(!is_numeric($product_price) || ($product_discount_price !== '' && !is_numeric($product_discount_price))){
            send_json([
                'success'=>false,
                'message'=> 'مقدار قیمت را عدد وارد کنید'
            ], 422);
        }
        elseif($product_discount_price !== '' && $product_discount_price >= $product_price) {
            send_json([
                'success'=> false,
                'message'=> 'قیمت تخفیف بیشتر از قیمت اصلی است'
            ], 422);
        }
        elseif (!isset($data['active']) || !in_array($data['active'], [0, 1, '0', '1'], true)) {
            send_json([
                'success' => false,
                'message' => 'مقدار فعال بودن محصول نامعتبر است'
            ], 422);
        }
        $active = (int)$data['active'];
        
        
        foreach ($categories as $id) {
            if (!is_numeric($id)) {
                send_json([
                    'success' => false,
                    'message' => 'شناسه دسته بندی نامعتبر است'
                ], 422);
            }
            $category = $this->categoryModel->findById($id);
            if(!$category){
                send_json([
                    'success' => false,
                    'message' => 'دسته بندی انتخابی وجود ندارد'
                ], 404);
            }
            
            if($category['parent_id'] == null){
                $children = $this->categoryModel->findChildren($category['ID']);
                if(!empty($children)){
                    foreach($children as $child){
                        $categories[] = $child['ID'];
                    }
                }
            }
            else{
                $parent = $this->categoryModel->findParent($category['parent_id']);
                if(!empty($parent)){
                    $categories[] = $parent['ID'];
                }
            }
        }
        $categories = array_map('intval', array_values(array_unique($categories)));
        
        $stmt = $this->db->prepare("INSERT INTO `products` (`product_name`, `product_description`, `product_price`, `product_discount_price`, `active`) VALUES (:product_name, :product_description, :product_price, :product_discount_price, :active)");
        $stmt->execute([
            ':product_name' => $product_name,
            ':product_description' => $product_description,
            ':product_price' => $product_price,
            ':product_discount_price' => $product_discount_price,
            ':active' => $active
        ]);
        
        $lastId = $this->db->lastInsertId();
        $this->ProductCategory->sync($lastId, $categories);
        
        send_json([
            'success' => true,
            'message' => 'محصول با موفقیت اضافه شد',
            'product_id' => $lastId
        ], 201);
    }
    
    public function update(){
        $data = json_decode(file_get_contents('php://input'), true);
        $productId = $_GET['id'] ?? null;
                
        if(empty($productId) || !is_numeric($productId)){
            send_json([
               'success' => false,
               'message' => 'مقدار معتبر نیس'
            ], 404);
        }
    
        $product_name = trim($data['product_name'] ?? '');
        $product_description = trim($data['product_description'] ?? '');
        $product_price = $data['product_price'] ?? '' ;
        $product_discount_price = $data['product_discount_price'] ?? '' ;
        $categories = $data['categories'] ?? [] ;
        $active = $data['active'] ?? null;

        
        $stmt = $this->db->prepare("SELECT * FROM `products` WHERE `ID` = :id");
        $stmt->execute([':id' => $productId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(empty($result)){
            send_json([
                'success' => false,
                'message' => 'محصول پیدا نشد'
            ], 404);
        }
        
        if(($product_price !== '' && !is_numeric($product_price)) || ($product_discount_price !== '' && !is_numeric($product_discount_price))){
            send_json([
                'success'=>false,
                'message'=> 'مقدار قیمت را عدد وارد کنید'
            ], 422);
        }
        elseif($product_discount_price !== '' && $product_discount_price >= $result['product_price']) {
            send_json([
                'success'=> false,
                'message'=> 'قیمت تخفیف بیشتر از قیمت اصلی است'
            ], 422);
        }
        elseif(!empty($categories) && !is_array($categories)){
            send_json([
                'success' => false,
                'message' => 'لطفا دسته بندی محصول را به صورت آرایه بفرستید'
            ], 422);
        }
        elseif($active !== null && !in_array($active, [0, 1, '0', '1'], true)) {
            send_json([
                'success' => false,
                'message' => 'مقدار فعال بودن محصول نامعتبر است'
            ], 422);
        }
    

        $fields = [];
        $params   = [':id' => $productId];
        
        if($product_name !== ''){
            $fields[] = 'product_name = :product_name';
            $params[':product_name'] = $product_name;
        }
        if($product_description !== ''){
            $fields[] = 'product_description = :product_description';
            $params[':product_description'] = $product_description;
        }
        if($product_price !== ''){
            $fields[] = 'product_price = :product_price';
            $params[':product_price'] = $product_price;
        }
        if (array_key_exists('product_discount_price', $data)) {
            if ($data['product_discount_price'] === null || $data['product_discount_price'] === '') {
                $fields[] = 'product_discount_price = NULL';
            } else {
                $fields[] = 'product_discount_price = :product_discount_price';
                $params[':product_discount_price'] = $data['product_discount_price'];
            }
        }
        if($active !== null){
            $fields[] = 'active = :active';
            $params[':active'] = (int)$active;
        }
        
        if(empty($fields) && empty($categories)){
            send_json([
                'success' => false,
                'message' => 'حداقل یک فیلد ارسال کنید'
            ], 400);
        }
        
        if(!empty($categories)){
            foreach ($categories as $id) {
                if (!is_numeric($id)) {
                    send_json([
                        'success' => false,
                        'message' => 'شناسه دسته بندی نامعتبر است'
                    ], 422);
                }
                $category = $this->categoryModel->findById($id);
                if(!$category){
                    send_json([
                        'success' => false,
                        'message' => 'دسته بندی انتخابی وجود ندارد'
                    ], 404);
                }
                
                if($category['parent_id'] == null){
                    $children = $this->categoryModel->findChildren($category['ID']);
                    if(!empty($children)){
                        foreach($children as $child){
                            $categories[] = $child['ID'];
                        }
                    }
                }
                else{
                    $parent = $this->categoryModel->findParent($category['parent_id']);
                    if(!empty($parent)){
                        $categories[] = $parent['ID'];
                    }
                }
            }
            $categories = array_map('intval', array_values(array_unique($categories)));
            $this->ProductCategory->sync($result['ID'], $categories);
        }
        if(!empty($fields)){
            $stmt = $this->db->prepare("UPDATE `products` SET " . implode(', ', $fields) . " WHERE `ID` = :id");
            $stmt->execute($params);
        }
        
        
        $stmt = $this->db->prepare("SELECT * FROM `products` WHERE `ID` = :id");
        $stmt->execute([':id' => $productId]);
        $productUpdated = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->formatProducts($productUpdated);
        $productUpdated['category'] = $this->ProductCategory->getCategoriesByProduct($productId);
        
        send_json([
            'success' => true,
            'message' => 'محصول با موفقیت بروزرسانی شد',
            'data' => $productUpdated
        ]);
    
    }
    
    public function adminShow(){
        $productId = $_GET['id'] ?? '' ;
        
        if(empty($productId) || !is_numeric($productId)){
            send_json([
                'success' => false,
                'message' => 'پیدا نشد'
            ], 404);
        }
        
        $stmt = $this->db->prepare("SELECT * FROM `products` WHERE `ID` = :id");
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if(empty($product)){
            send_json([
                'success' => false,
                'message' => 'پیدا نشد'
            ], 404);
        }
        
        $this->formatProducts($product);
        $product['category'] = $this->ProductCategory->getCategoriesByProduct($productId);
        send_json([
            'success' => true,
            'data' => $product
        ]);
    }
    
    public function show(){
        $productId = $_GET['id'] ?? '' ;
        
        if(empty($productId) || !is_numeric($productId)){
            send_json([
                'success' => false,
                'message' => 'پیدا نشد'
            ], 404);
        }
        
        $stmt = $this->db->prepare("SELECT * FROM `products` WHERE `ID` = :id AND `active` = 1");
        $stmt->execute([
            ':id' => $productId
        ]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(empty($product)){
            send_json([
                'success' => false,
                'message' => 'پیدا نشد'
            ], 404);
        }
        
        $this->formatProducts($product);
        $product['category'] = $this->ProductCategory->getCategoriesByProduct($product['ID']);
        send_json([
            'success' => true,
            'data' => $product
        ]);
    }
    
    public function delete(){
        $productId = $_GET['id'] ?? '' ;
        
        if(empty($productId) || !is_numeric($productId)){
            send_json([
                'success' => false,
                'message' => 'ایدی نامعتبر است'
            ], 400);
        }
        
        $stmt = $this->db->prepare("SELECT * FROM `products` WHERE `ID` = :id");
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if(empty($product)){
            send_json([
                'success' => false,
                'message' => 'پیدا نشد'
            ], 404);
        }
        
        $this->ProductCategory->detach($productId);
        $stmt = $this->db->prepare("DELETE FROM `products` WHERE `ID` = :id");
        $stmt->execute([':id' => $productId]);
        
        send_json([
            'success' => true,
            'message' => 'محصول با موفقیت حذف شد'
        ]);
    }
    
    private function formatProducts(&$products){
        if(isset($products[0])){
            foreach($products as &$product){
                $product['active'] = (bool) $product['active'];
                $product['product_price'] = (int)$product['product_price'];
                if($product['product_discount_price'] == '0'){
                    $product['product_discount_price'] = null;
                }else{
                    $product['product_discount_price'] = (int)$product['product_discount_price'];
                }
            }
        }
        else{
            $products['active'] = (bool) $products['active'];
            $products['product_price'] = (int)$products['product_price'];
            if($products['product_discount_price'] == '0'){
                $products['product_discount_price'] = null;
            }else{
                $products['product_discount_price'] = (int)$products['product_discount_price'];
            }
        }
    }
    
    private function listProducts(bool $onlyActive){
        $searchVal   = $_GET['search'] ?? '';
        $min_price   = $_GET['min_price'] ?? '';
        $max_price   = $_GET['max_price'] ?? '';
        $category_id = $_GET['category_id'] ?? '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $size = isset($_GET['size']) ? (int)$_GET['size'] : 10;
        if ($page < 1) {
            $page = 1;
        }
        if ($size < 1) {
            $size = 10;
        }
        $offset = ($page - 1) * $size;
    
        $sql = "SELECT products.* FROM products";
        $sqlCount = "SELECT COUNT(DISTINCT products.ID) FROM products";
    
        $params = [];
    
        // category filter
        if (!empty($category_id) && is_numeric($category_id)) {
            $sql .= " JOIN products_categories ON products.ID = products_categories.product_id";
            $sqlCount .= " JOIN products_categories ON products.ID = products_categories.product_id";
            $sql .= " WHERE products_categories.category_id = :category_id";
            $sqlCount .= " WHERE products_categories.category_id = :category_id";
            $params[':category_id'] = (int)$category_id;
    
        }else{
            $sql .= " WHERE 1=1";
            $sqlCount .= " WHERE 1=1";
        }
    
        // active
        if ($onlyActive) {
            $sql .= " AND products.active = 1";
            $sqlCount .= " AND products.active = 1";
        }
    
        // search
        if (!empty($searchVal)) {
            $sql .= " AND products.product_name LIKE :search";
            $sqlCount .= " AND products.product_name LIKE :search";
    
            $params[':search'] = "%{$searchVal}%";
        }
    
        // min price
        if ($min_price !== '' && is_numeric($min_price)) {
            $sql .= " AND products.product_price >= :min_price";
            $sqlCount .= " AND products.product_price >= :min_price";
    
            $params[':min_price'] = $min_price;
        }
    
        // max price
        if ($max_price !== '' && is_numeric($max_price)) {
            $sql .= " AND products.product_price <= :max_price";
            $sqlCount .= " AND products.product_price <= :max_price";
    
            $params[':max_price'] = $max_price;
        }
    
        $sql .= " LIMIT $size OFFSET $offset";
    
        // products
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    
        $listedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$listedProducts) {
            return [
                'page'  => $page,
                'size'  => $size,
                'total' => 0,
                'data'  => []
            ];
        }
        $this->formatProducts($listedProducts);
        
        
        foreach ($listedProducts as &$product) {
            $categories = $this->ProductCategory->getCategoriesByProduct($product['ID']);
            $product['category'] = $categories;
        }
    
        // total
        $stmt = $this->db->prepare($sqlCount);
        $stmt->execute($params);
    
        $total = (int)$stmt->fetchColumn();
    
        return [
            'page'  => $page,
            'size'  => $size,
            'total' => $total,
            'data'  => $listedProducts
        ];
    }

}


?>