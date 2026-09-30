<?

class User{
    private $db;
    
    public function __construct(){
        $this->db = pdo();
    }
    
    public function findById($id){
        $stmt = $this->db->prepare("SELECT * FROM `users` WHERE `ID` = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function create($username, $email, $password, $phone){
        $stmt = $this->db->prepare("INSERT INTO `users` (`username`, `email`, `password`, `phone`) VALUES (:username, :email, :password, :phone)");
        $stmt->execute([
            ':username' => $username,
            ':email'    => $email,
            ':password' => password_hash($password, PASSWORD_BCRYPT),
            ':phone'    => $phone
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function updateUser($id, array $data){
        $fields = [];
        $params = [':id' => $id];
        
        if(isset($data['username'])){
            $fields[] = 'username = :username';
            $params[':username'] = $data['username'];
        }
        if(isset($data['email'])){
            $fields[] = 'email = :email';
            $params[':email'] = $data['email'];
        }
        if(!empty($data['password'])){
            $fields[] = 'password = :password';
            $params[':password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        if(isset($data['phone'])){
            $fields[] = 'phone = :phone';
            $params[':phone'] = $data['phone'];
        }
        if(isset($data['user_lvl'])){
            $fields[] = 'user_lvl = :user_lvl';
            $params[':user_lvl']  = $data['user_lvl'];
        }
        
        if(empty($fields)){
            return false;
        }
        
        $stmt = $this->db->prepare("UPDATE `users` SET ". implode(', ',$fields) . " WHERE `ID` = :id");
        return $stmt->execute($params);
    }
    
    public function existsByField($fields, $value, $exceptId = null){
        $sql = "SELECT * FROM `users` WHERE $fields = :value";
        $params = [':value' => $value];
        
        if($exceptId !== null){
            $sql .= " AND `ID` != :id";
            $params[':id'] = $exceptId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch() ? true : false;
        
    }
    
    public function delete($id){
        $stmt = $this->db->prepare("DELETE FROM `users` WHERE `ID` = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    public function all(){
        $stmt = $this->db->prepare("SELECT * FROM `users`");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
