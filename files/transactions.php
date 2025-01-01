<?php
require_once 'config.php';
require_once 'auth.php';

class Transaction {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function createTransaction($userId, $packageId, $type, $amount) {
        try {
            // Begin transaction
            $this->pdo->beginTransaction();
            
            // Insert transaction record
            $stmt = $this->pdo->prepare("
                INSERT INTO transactions (user_id, package_id, type, amount, status)
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$userId, $packageId, $type, $amount]);
            
            // Update user balance based on transaction type
            $balanceModifier = ($type == 'deposit' || $type == 'return') ? '+' : '-';
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET balance = balance {$balanceModifier} ?
                WHERE id = ?
            ");
            $stmt->execute([$amount, $userId]);
            
            // If it's an investment, create investment record
            if ($type == 'investment') {
                $stmt = $this->pdo->prepare("
                    INSERT INTO investments (user_id, package_id, amount)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$userId, $packageId, $amount]);
            }
            
            // Commit transaction
            $this->pdo->commit();
            
            return ['success' => true, 'message' => 'Transaction processed successfully'];
        } catch (PDOException $e) {
            // Rollback transaction on error
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getUserTransactions($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT t.*, p.name as package_name 
                FROM transactions t
                LEFT JOIN packages p ON t.package_id = p.id
                WHERE t.user_id = ?
                ORDER BY t.created_at DESC
            ");
            $stmt->execute([$userId]);
            return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getTransactionById($transactionId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT t.*, p.name as package_name 
                FROM transactions t
                LEFT JOIN packages p ON t.package_id = p.id
                WHERE t.id = ? AND t.user_id = ?
            ");
            $stmt->execute([$transactionId, $userId]);
            return ['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC)];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function updateTransactionStatus($transactionId, $status) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE transactions 
                SET status = ?
                WHERE id = ?
            ");
            $stmt->execute([$status, $transactionId]);
            return ['success' => true, 'message' => 'Transaction status updated'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function calculateReturns() {
        try {
            // Get active investments that are due for returns
            $stmt = $this->pdo->prepare("
                SELECT i.*, p.return_rate, p.duration_days
                FROM investments i
                JOIN packages p ON i.package_id = p.id
                WHERE i.status = 'active'
                AND DATEDIFF(NOW(), i.start_date) >= p.duration_days
            ");
            $stmt->execute();
            $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($investments as $investment) {
                $returnAmount = $investment['amount'] * (1 + $investment['return_rate'] / 100);
                
                // Create return transaction
                $this->createTransaction(
                    $investment['user_id'],
                    $investment['package_id'],
                    'return',
                    $returnAmount
                );
                
                // Update investment status
                $stmt = $this->pdo->prepare("
                    UPDATE investments
                    SET status = 'completed', end_date = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$investment['id']]);
            }
            
            return ['success' => true, 'message' => 'Returns calculated and processed'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

// Initialize Transaction class
$transaction = new Transaction($pdo);

// Handle API requests if this file is accessed directly
if (basename($_SERVER['PHP_SELF']) == 'transactions.php') {
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
                echo json_encode($transaction->getTransactionById($_GET['id'], $userId));
            } else {
                echo json_encode($transaction->getUserTransactions($userId));
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode($transaction->createTransaction(
                $userId,
                $data['package_id'] ?? null,
                $data['type'],
                $data['amount']
            ));
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}
?>