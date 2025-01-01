<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'transactions.php';
require_once 'packages.php';

class Dashboard {
    private $pdo;
    private $auth;
    private $transaction;
    private $package;
    
    public function __construct($pdo, $auth, $transaction, $package) {
        $this->pdo = $pdo;
        $this->auth = $auth;
        $this->transaction = $transaction;
        $this->package = $package;
    }
    
    public function getUserDashboardData($userId) {
        try {
            // Get user information
            $user = $this->auth->getCurrentUser();
            
            // Get recent transactions
            $transactions = $this->transaction->getUserTransactions($userId);
            
            // Get active investments
            $investments = $this->package->getUserInvestments($userId);
            
            // Calculate total invested amount
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) as total_invested
                FROM investments
                WHERE user_id = ? AND status = 'active'
            ");
            $stmt->execute([$userId]);
            $totalInvested = $stmt->fetch(PDO::FETCH_ASSOC)['total_invested'];
            
            // Calculate total returns
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) as total_returns
                FROM transactions
                WHERE user_id = ? AND type = 'return' AND status = 'completed'
            ");
            $stmt->execute([$userId]);
            $totalReturns = $stmt->fetch(PDO::FETCH_ASSOC)['total_returns'];
            
            // Get available packages
            $packages = $this->package->getAllPackages();
            
            return [
                'success' => true,
                'data' => [
                    'user' => $user,
                    'recent_transactions' => array_slice($transactions['data'], 0, 5),
                    'active_investments' => $investments['data'],
                    'total_invested' => $totalInvested,
                    'total_returns' => $totalReturns,
                    'available_packages' => $packages['data']
                ]
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getAdminDashboardData() {
        try {
            // Get total users count
            $stmt = $this->pdo->query("SELECT COUNT(*) as total_users FROM users");
            $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
            
            // Get total investments
            $stmt = $this->pdo->query("
                SELECT COALESCE(SUM(amount), 0) as total_investments
                FROM investments
                WHERE status = 'active'
            ");
            $totalInvestments = $stmt->fetch(PDO::FETCH_ASSOC)['total_investments'];
            
            // Get recent transactions
            $stmt = $this->pdo->query("
                SELECT t.*, u.username, p.name as package_name
                FROM transactions t
                JOIN users u ON t.user_id = u.id
                LEFT JOIN packages p ON t.package_id = p.id
                ORDER BY t.created_at DESC
                LIMIT 10
            ");
            $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get package statistics
            $stmt = $this->pdo->query("
                SELECT p.name, 
                       COUNT(i.id) as total_investments,
                       COALESCE(SUM(i.amount), 0) as total_amount
                FROM packages p
                LEFT JOIN investments i ON p.id = i.package_id
                WHERE p.status = 'active'
                GROUP BY p.id
            ");
            $packageStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => [
                    'total_users' => $totalUsers,
                    'total_investments' => $totalInvestments,
                    'recent_transactions' => $recentTransactions,
                    'package_statistics' => $packageStats
                ]
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getUserStatistics($userId) {
        try {
            // Get monthly investment data
            $stmt = $this->pdo->prepare("
                SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                       SUM(amount) as total_amount
                FROM transactions
                WHERE user_id = ? AND type IN ('investment', 'return')
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month ASC
            ");
            $stmt->execute([$userId]);
            $monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get package distribution
            $stmt = $this->pdo->prepare("
                SELECT p.name, COUNT(*) as count, SUM(i.amount) as total_amount
                FROM investments i
                JOIN packages p ON i.package_id = p.id
                WHERE i.user_id = ?
                GROUP BY p.id
            ");
            $stmt->execute([$userId]);
            $packageDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => [
                    'monthly_data' => $monthlyData,
                    'package_distribution' => $packageDistribution
                ]
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

// Initialize Dashboard class
$dashboard = new Dashboard($pdo, $auth, $transaction, $package);

// Handle API requests if this file is accessed directly
if (basename($_SERVER['PHP_SELF']) == 'dashboard.php') {
    header('Content-Type: application/json');
    
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    $userId = $_SESSION['user_id'];
    
    if (isset($_GET['admin']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        echo json_encode($dashboard->getAdminDashboardData());
    } else if (isset($_GET['statistics'])) {
        echo json_encode($dashboard->getUserStatistics($userId));
    } else {
        echo json_encode($dashboard->getUserDashboardData($userId));
    }
}
?>