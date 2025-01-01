<?php
require_once 'config.php';
require_once 'auth.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Get request method and endpoint
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
$endpoint = $request[0] ?? '';

// Handle CORS preflight requests
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// API endpoints
switch($endpoint) {
    case 'login':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $response = $auth->login($data['email'], $data['password']);
            echo json_encode($response);
        }
        break;
        
    case 'register':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $response = $auth->register($data['username'], $data['email'], $data['password']);
            echo json_encode($response);
        }
        break;
        
    case 'logout':
        if ($method === 'POST') {
            $response = $auth->logout();
            echo json_encode($response);
        }
        break;
        
    case 'profile':
        if (!$auth->isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            break;
        }
        
        switch($method) {
            case 'GET':
                $user = $auth->getCurrentUser();
                echo json_encode(['success' => true, 'data' => $user]);
                break;
                
            case 'PUT':
                $data = json_decode(file_get_contents('php://input'), true);
                $response = $auth->updateProfile($_SESSION['user_id'], $data);
                echo json_encode($response);
                break;
        }
        break;
        
    case 'packages':
        if (!$auth->isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            break;
        }
        
        switch($method) {
            case 'GET':
                try {
                    $stmt = $pdo->query("SELECT * FROM packages");
                    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode(['success' => true, 'data' => $packages]);
                } catch(PDOException $e) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                break;
        }
        break;
        
    case 'transactions':
        if (!$auth->isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            break;
        }
        
        switch($method) {
            case 'GET':
                try {
                    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode(['success' => true, 'data' => $transactions]);
                } catch(PDOException $e) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                break;
                
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                try {
                    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, status) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $_SESSION['user_id'],
                        $data['type'],
                        $data['amount'],
                        'pending'
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Transaction created successfully']);
                } catch(PDOException $e) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                break;
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
        break;
}
?>