<?
require __DIR__ . '/../Models/Category.php';

class CategoryController{
    private $categoryModel;
    
    public function __construct(){
        $this->categoryModel = new Category();
    }
    
    public function index(){
        $categories = $this->formatCategoryApi('index');
        
        send_json([
           'success' => true,
           'data' => $categories
        ]);
    }
    
    public function show(){
        $categoryId = $_GET['id'] ?? null;
        if(!is_numeric($categoryId) || empty($categoryId)){
            send_json([
               'success' => false,
               'message' => 'آیدی دسته بندی نامعتبر میباشد'
            ], 400);
        }
        
        $category = $this->formatCategoryApi('show', $categoryId);
        if(empty($category)){
            send_json([
               'success' => false,
               'message' => 'دسته بندی مورد نظر پیدا نشد'
            ], 404);
        }
        
        send_json([
           'success' => true,
           'data' => $category
        ]);
    }
    
    public function create(){
        $data = json_decode(file_get_contents('php://input'), true);
        $name = trim($data['name'] ?? '');
        $parent_id = $data['parent_id'] ?? null;
        
        if(empty($name)){
            send_json([
               'success' => false,
               'message' => 'اسم دسته بندی را انتخاب کنید'
            ], 400);
        }
        
        $checkExist = $this->categoryModel->exists($name);
        if($checkExist == true){
            send_json([
               'success' => false,
               'message' => 'اسم دسته بندی تکراری میباشد'
            ], 409);
        }
        if ($parent_id !== null && !$this->categoryModel->findById($parent_id)){
            send_json([
                'success' => false,
                'message' => 'دسته بندی والد وجود ندارد'
            ], 404);
        }
        
        $result = $this->categoryModel->insert($name, $parent_id);
        send_json([
           'success' => true, 
           'message' => 'دسته بندی ایجاد شد',
           'data' => $result
        ], 201);
        
    }
    
    public function delete(){
        $categoryId = $_GET['id'] ?? null;
        
        if(empty($categoryId)){
            send_json([
               'success' => false,
               'message' =>  'آیدی دسته بندی مورد نظر را وارد کنید'
            ], 400);
        }
        if (!is_numeric($categoryId)) {
            send_json([
                'success' => false,
                'message' => 'آیدی نامعتبر است'
            ], 400);
        }
        
        $category = $this->categoryModel->findById($categoryId);
        if(!$category){
            send_json([
                'success' => false,
                'message' => 'دسته بندی پیدا نشد'
            ], 404);
        }
        
        $children = $this->categoryModel->findChildren($categoryId);
        if(!empty($children)){
            send_json([
                'success' => false,
                'message' => 'ابتدا زیر دسته ها را حذف کنید'
            ], 400);
        }
        
        $result = $this->categoryModel->delete($categoryId);
        if($result){
            send_json([
                'success' => true,
                'message' => "دست بندی {$category['name']} با موفقیت حذف شد"
            ]);
        }
        
        send_json([
            'success' => false,
            'message' => 'خطا در حذف دسته بندی'
        ], 500);
        
    }

    private function formatCategoryApi($type, $id = null){
       if($type == 'index'){
           $categories = $this->categoryModel->findAll();
           $result = [];
           
           foreach($categories as $parent){
               if($parent['parent_id'] !== null){
                   continue;
               }
               
               $item = [
                   'id' => $parent['ID'],
                   'name'  => $parent['name'],
                   'children' => []
               ];
               
               foreach($categories as $child){
                   if($parent['ID'] == $child['parent_id']){
                       $item['children'][] = [
                           'id' => $child['ID'],
                           'name' => $child['name']
                       ];
                   }
               }
               
               $result[] = $item;
           }
           return $result;
       }
       elseif($type == 'show' && !empty($id)){
           $category = $this->categoryModel->findById($id);
           if(empty($category)){
               return [];
           }
          
           if($category['parent_id'] == null){
               $children = $this->categoryModel->findChildren($category['ID']);
               $item = [
                   'id' => $category['ID'],
                   'name' => $category['name'],
                   'children' => []
               ];
               
               foreach($children as $child){
                   $item['children'][] = [
                     'id' => $child['ID'],
                     'name' => $child['name']
                   ];
               }
               return $item;
           }
           else{
               unset($category['parent_id']);
               unset($category['created_at']);
               $category['children'] = [];
               
               return $category;
           }
       }
    }
    
}