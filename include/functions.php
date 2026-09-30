<?
include('config.php');

function send_json($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    die(json_encode($data, JSON_UNESCAPED_UNICODE));
}

function pdo() {
    $host = 'localhost';
    $dbname = $_ENV['DB_NAME'];
    $username = $_ENV['DB_USERNAME'];
    $password = $_ENV['DB_PASSWORD'];
    
    try {
        // ساخت اتصال به دیتابیس
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password);
    
        // تنظیم مود خطا برای PDO
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    } catch (PDOException $e) {
        echo "خطا در اتصال به دیتابیس: " . $e->getMessage();die;
    }
}

function load_env($file){
    if(!file_exists($file)){
        return;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach($lines as $line){
    
        $line = trim($line);
    
        if($line === '' || substr($line, 0, 1) === '#'){
            continue;
        }
    
        [$key, $value] = explode('=', $line, 2);
    
        $key = trim($key);
        $value = trim($value);
    
        $_ENV[$key] = $value;
    }
}

function toman_builder($price){
    $type = 'تومان';
    
    if(empty($price)){
        return ;
    }else{
        return number_format($price) .' '. $type;
    }
}

function jwt_encode(array $payload, $exp_seconds = 900){
    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];
    
    $payload['exp'] = time() + $exp_seconds;
    
    $header_b64  = base64_encode(json_encode($header));
    $payload_b64 = base64_encode(json_encode($payload));
    
    $header_b64  = str_replace(['+','/','='], ['-','_',''], $header_b64);
    $payload_b64 = str_replace(['+','/','='], ['-','_',''], $payload_b64);
    
    $signature = hash_hmac(
        'sha256',
        $header_b64 . '.' . $payload_b64,
        $_ENV['JWT_SECRET'],
        true
    );
    
    $signature_b64 = str_replace(['+','/','='], ['-','_',''], base64_encode($signature));
    
    return $header_b64 . '.' . $payload_b64 . '.' . $signature_b64 ;
}

function jwt_decode($jwt){
    $parts = explode('.', $jwt);
    
    if(count($parts) !== 3){
        return false;
    }
    
    [$header_b64, $payload_b64, $signature_b64] = $parts;
    
    $check = hash_hmac(
        'sha256',
        $header_b64 . '.' . $payload_b64,
        $_ENV['JWT_SECRET'],
        true
    );
    
    $check = str_replace(['+','/','='], ['-','_',''], base64_encode($check));
    
    if(!hash_equals($check, $signature_b64)){
        return false;
    }
    
    $payload_json = base64_decode(str_replace(['-','_'], ['+','/'], $payload_b64));
    $payload = json_decode($payload_json, true);
    
    if(!isset($payload['exp']) || $payload['exp'] < time()){
        return false;
    }
    
    return $payload;
}
?>