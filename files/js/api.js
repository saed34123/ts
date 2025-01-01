import os

# Create js directory if it doesn't exist
if not os.path.exists('js'):
    os.makedirs('js')

# Write the API JavaScript code to api.js
api_js_content = """// API Configuration
const API_BASE_URL = 'https://api.yourdomain.com';

// API Service Class
class ApiService {
    // Authentication
    static async login(email, password) {
        return await this.post('/auth.php', { email, password });
    }
    
    static async register(userData) {
        return await this.post('/auth.php', { ...userData, action: 'register' });
    }
    
    static async logout() {
        return await this.post('/auth.php', { action: 'logout' });
    }
    
    // Transactions
    static async getTransactions() {
        return await this.get('/transactions.php');
    }
    
    static async createTransaction(data) {
        return await this.post('/transactions.php', data);
    }
    
    // Packages
    static async getPackages() {
        return await this.get('/packages.php');
    }
    
    static async getPackageById(id) {
        return await this.get(`/packages.php?id=${id}`);
    }
    
    // Dashboard
    static async getDashboardData() {
        return await this.get('/dashboard.php');
    }
    
    static async getAdminDashboard() {
        return await this.get('/dashboard.php?admin=true');
    }
    
    static async getUserStatistics() {
        return await this.get('/dashboard.php?statistics=true');
    }
    
    // Payments
    static async getPaymentMethods() {
        return await this.get('/payment.php?methods=true');
    }
    
    static async createPayment(data) {
        return await this.post('/payment.php', data);
    }
    
    static async confirmPayment(paymentId) {
        return await this.put('/payment.php', { payment_id: paymentId });
    }
    
    // Helper methods
    static async get(endpoint) {
        try {
            const response = await fetch(API_BASE_URL + endpoint, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json'
                }
            });
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }
    
    static async post(endpoint, data) {
        try {
            const response = await fetch(API_BASE_URL + endpoint, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }
    
    static async put(endpoint, data) {
        try {
            const response = await fetch(API_BASE_URL + endpoint, {
                method: 'PUT',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }
    
    static async delete(endpoint) {
        try {
            const response = await fetch(API_BASE_URL + endpoint, {
                method: 'DELETE',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json'
                }
            });
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }
}"""

with open('js/api.js', 'w') as f:
    f.write(api_js_content)

print("File js/api.js has been created successfully!")