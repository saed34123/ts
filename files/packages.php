<?php
require_once 'config.php';
require_once 'auth.php';

class Package {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAllPackages() {
        try {
            $stmt = $this->pdo->query("
                SELECT * FROM packages 
                WHERE status = 'active'
                ORDER BY minimum_investment ASC
            ");
            return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getPackageById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM packages 
                WHERE id = ? AND status = 'active'
            ");
            $stmt->execute([$id]);
            return ['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC)];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function createPackage($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO packages (
                    name, description, minimum_investment, 
                    maximum_investment, return_rate, duration_days
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['name'],
                $data['description'],
                $data['minimum_investment'],
                $data['maximum_investment'],
                $data['return_rate'],
                $data['duration_days']
            ]);
            return ['success' => true, 'message' => 'Package created successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function updatePackage($id, $data) {
        try {
            $updates = [];
            $params = [];
            
            foreach ($data as $key => $value) {
                if ($key != 'id') {
                    $updates[] = "$key = ?";
                    $params[] = $value;
                }
            }
            
            $params[] = $id;
            
            $stmt = $this->pdo->prepare("
                UPDATE packages 
                SET " . implode(', ', $updates) . "
                WHERE id = ?
            ");
            $stmt->execute($params);
            
            return ['success' => true, 'message' => 'Package updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function deletePackage($id) {
        try {
            // Soft delete by setting status to inactive
            $stmt = $this->pdo->prepare("
                UPDATE packages 
                SET status = 'inactive'
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            
            return ['success' => true, 'message' => 'Package deleted successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getUserInvestments($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT i.*, p.name, p.return_rate, p.duration_days
                FROM investments i
                JOIN packages p ON i.package_id = p.id
                WHERE i.user_id = ?
                ORDER BY i.start_date DESC
            ");
            $stmt->execute([$userId]);
            return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function validateInvestment($packageId, $amount, $userId) {
        try {
            $package = $this->getPackageById($packageId)['data'];
            
            if (!$package) {
                return ['success' => false, 'message' => 'Invalid package'];
            }
            
            if ($amount < $package['minimum_investment']) {
                return ['success' => false, 'message' => 'Amount below minimum investment'];
            }
            
            if ($package['maximum_investment'] && $amount > $package['maximum_investment']) {
                return ['success' => false, 'message' => 'Amount above maximum investment'];
            }
            
            // Check user balance
            $stmt = $this->pdo->prepare("SELECT balance FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($amount > $user['balance']) {
                return ['success' => false, 'message' => 'Insufficient balance'];
            }
            
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

// Initialize Package class
$package = new Package($pdo);

// Handle API requests if this file is accessed directly
if (basename($_SERVER['PHP_SELF']) == 'packages.php') {
    header('Content-Type: application/json');
    
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    $userId = $_SESSION['user_id'];
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                echo json_encode($package->getPackageById($_GET['id']));
            } else if (isset($_GET['investments'])) {
                echo json_encode($package->getUserInvestments($userId));
            } else {
                echo json_encode($package->getAllPackages());
            }
            break;
            
        case 'POST':
            if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode($package->createPackage($data));
            break;
            
        case 'PUT':
            if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode($package->updatePackage($data['id'], $data));
            break;
            
        case 'DELETE':
            if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden']);
                break;
            }
            
            echo json_encode($package->deletePackage($_GET['id']));
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}
?>