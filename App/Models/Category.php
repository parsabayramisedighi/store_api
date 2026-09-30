<?

class Category{
    private $db;
    
    public function __construct(){
        $this->db = pdo();
    }
    
    public function insert($name, $parent_id = null){
        $stmt = $this->db->prepare("INSERT INTO `categories` (`name`, `parent_id`, `created_at`) VALUES (:name, :parent_id, :created_at)");
        $stmt->execute([
            ':name' => $name,
            ':parent_id' => $parent_id,
            ':created_at' => time()
        ]);
        
        return [
          'id' => (int)$this->db->lastInsertId(),
          'name' => $name,
          'parent_id' => $parent_id
        ];
    }
    
    public function update($id, $name, $parent_id = null){
        $stmt = $this->db->prepare("UPDATE categories SET `name` = :name, `parent_id` = :parent_id WHERE `ID` = :id");
        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':parent_id' => $parent_id
        ]);
    }
    
    public function delete($id){
        $stmt = $this->db->prepare("DELETE FROM `categories` WHERE `ID` = :id");
        return $stmt->execute([
            ':id' => $id
        ]);
    }
    
    public function findById($id){
        $stmt = $this->db->prepare("SELECT * FROM `categories` WHERE `ID` = :id");
        $stmt->execute([
            ':id' => $id
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findAll(){
        $stmt = $this->db->prepare("SELECT * FROM `categories`");
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function findAllParents(){
        $stmt = $this->db->prepare("SELECT * FROM `categories` WHERE `parent_id` IS NULL");
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function findParent($id){
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE ID = :id");
        $stmt->execute([
            ':id' => $id
        ]);
    
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findChildren($parent_id){
        $stmt = $this->db->prepare("SELECT * FROM `categories` WHERE `parent_id` = :parent_id");
        $stmt->execute([
            ':parent_id' => $parent_id
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function exists($name){
        $stmt = $this->db->prepare("SELECT * FROM `categories` WHERE `name` = :name");
        $stmt->execute([
            ':name' => $name
        ]);
        
        if($stmt->rowCount() == 1){
            return true;
        }
        else{
            return false;
        }
    }
}