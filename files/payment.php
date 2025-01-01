<?php
require_once 'config.php';
require_once 'auth.php';

class Payment {
    private $pdo;
    private $auth;
    
    // Payment gateway configurations
    private $stripe_secret_key = 'YOUR_STRIPE_SECRET_KEY';
    private $stripe_public_key = 'YOUR_STRIPE_PUBLIC_KEY';
    private $paypal_client_id = 'YOUR_PAYPAL_CLIENT_ID';
    private $paypal_secret = 'YOUR_PAYPAL_SECRET';
    
    public function __construct($pdo, $auth) {
        $this->pdo = $pdo;
        $this->auth = $auth;
        
        // Initialize Stripe
        \Stripe\Stripe::setApiKey($this->stripe_secret_key);
    }
    
    public function createStripePayment($amount, $currency = 'USD') {
        try {
            $payment_intent = \Stripe\PaymentIntent::create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => $currency,
                'metadata' => [
                    'user_id' => $_SESSION['user_id']
                ]
            ]);
            
            return [
                'success' => true,
                'client_secret' => $payment_intent->client_secret,
                'public_key' => $this->stripe_public_key
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function createPayPalOrder($amount, $currency = 'USD') {
        try {
            $ch = curl_init();
            
            // Get PayPal access token
            curl_setopt($ch, CURLOPT_URL, "https://api.paypal.com/v1/oauth2/token");
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $this->paypal_client_id.":".$this->paypal_secret);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
            
            $result = curl_exec($ch);
            $access_token = json_decode($result)->access_token;
            
            // Create PayPal order
            curl_setopt($ch, CURLOPT_URL, "https://api.paypal.com/v2/checkout/orders");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer " . $access_token,
                "Content-Type: application/json"
            ));
            
            $payload = json_encode([
                "intent" => "CAPTURE",
                "purchase_units" => [[
                    "amount" => [
                        "currency_code" => $currency,
                        "value" => $amount
                    ]
                ]],
                "application_context" => [
                    "return_url" => "https://yourdomain.com/payment/success",
                    "cancel_url" => "https://yourdomain.com/payment/cancel"
                ]
            ]);
            
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            $result = curl_exec($ch);
            
            curl_close($ch);
            
            return ['success' => true, 'data' => json_decode($result)];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function processPayment($paymentId, $paymentMethod, $amount) {
        try {
            $this->pdo->beginTransaction();
            
            // Record payment
            $stmt = $this->pdo->prepare("
                INSERT INTO payments (
                    user_id, payment_id, payment_method, 
                    amount, status, created_at
                ) VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $paymentId,
                $paymentMethod,
                $amount
            ]);
            
            // Create transaction record
            $stmt = $this->pdo->prepare("
                INSERT INTO transactions (
                    user_id, type, amount, 
                    status, created_at
                ) VALUES (?, 'deposit', ?, 'pending', NOW())
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $amount
            ]);
            
            $this->pdo->commit();
            return ['success' => true, 'message' => 'Payment processed successfully'];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function confirmPayment($paymentId) {
        try {
            $this->pdo->beginTransaction();
            
            // Update payment status
            $stmt = $this->pdo->prepare("
                UPDATE payments 
                SET status = 'completed'
                WHERE payment_id = ?
            ");
            $stmt->execute([$paymentId]);
            
            // Get payment details
            $stmt = $this->pdo->prepare("
                SELECT user_id, amount 
                FROM payments 
                WHERE payment_id = ?
            ");
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Update user balance
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET balance = balance + ?
                WHERE id = ?
            ");
            $stmt->execute([$payment['amount'], $payment['user_id']]);
            
            // Update transaction status
            $stmt = $this->pdo->prepare("
                UPDATE transactions 
                SET status = 'completed'
                WHERE user_id = ? AND type = 'deposit' AND status = 'pending'
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$payment['user_id']]);
            
            $this->pdo->commit();
            return ['success' => true, 'message' => 'Payment confirmed successfully'];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getPaymentMethods() {
        return [
            'stripe' => [
                'name' => 'Credit Card (Stripe)',
                'public_key' => $this->stripe_public_key
            ],
            'paypal' => [
                'name' => 'PayPal',
                'client_id' => $this->paypal_client_id
            ]
        ];
    }
    
    public function getUserPayments($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.*, t.status as transaction_status
                FROM payments p
                LEFT JOIN transactions t ON t.user_id = p.user_id 
                    AND t.type = 'deposit' 
                    AND DATE(t.created_at) = DATE(p.created_at)
                WHERE p.user_id = ?
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([$userId]);
            return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

// Initialize Payment class
$payment = new Payment($pdo, $auth);

// Handle API requests if this file is accessed directly
if (basename($_SERVER['PHP_SELF']) == 'payment.php') {
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
            if (isset($_GET['methods'])) {
                echo json_encode(['success' => true, 'data' => $payment->getPaymentMethods()]);
            } else {
                echo json_encode($payment->getUserPayments($userId));
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (isset($data['provider'])) {
                switch ($data['provider']) {
                    case 'stripe':
                        echo json_encode($payment->createStripePayment($data['amount'], $data['currency'] ?? 'USD'));
                        break;
                    case 'paypal':
                        echo json_encode($payment->createPayPalOrder($data['amount'], $data['currency'] ?? 'USD'));
                        break;
                    default:
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Invalid payment provider']);
                        break;
                }
            } else if (isset($data['payment_id'])) {
                echo json_encode($payment->processPayment(
                    $data['payment_id'],
                    $data['payment_method'],
                    $data['amount']
                ));
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode($payment->confirmPayment($data['payment_id']));
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}
?>