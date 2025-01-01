# Create validation.js file
js_content = """
// Form Validation Functions
function validateWithdrawForm() {
    const amount = document.getElementById('withdrawAmount').value;
    const paymentMethod = document.querySelector('input[name="payment"]:checked');
    
    if (!amount || amount < 100) {
        showAlert('Please enter a valid amount (minimum $100)', 'error');
        return false;
    }
    
    if (!paymentMethod) {
        showAlert('Please select a payment method', 'error');
        return false;
    }
    
    showAlert('Withdrawal request submitted successfully!', 'success');
    return true;
}

function validateSettingsForm() {
    const fullName = document.getElementById('fullName').value;
    const phone = document.getElementById('phone').value;
    
    if (!fullName || fullName.length < 3) {
        showAlert('Please enter a valid full name', 'error');
        return false;
    }
    
    if (!phone || phone.length < 10) {
        showAlert('Please enter a valid phone number', 'error');
        return false;
    }
    
    showAlert('Settings updated successfully!', 'success');
    return true;
}

// Helper Functions
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.querySelector('.main-content').insertBefore(alertDiv, document.querySelector('.main-content').firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Withdraw Form
    const withdrawForm = document.getElementById('withdrawForm');
    if (withdrawForm) {
        withdrawForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (validateWithdrawForm()) {
                // Add API call here
            }
        });
    }
    
    // Settings Form
    const settingsForm = document.getElementById('settingsForm');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (validateSettingsForm()) {
                // Add API call here
            }
        });
    }
    
    // Profile Image Upload
    const imageUpload = document.querySelector('input[type="file"]');
    if (imageUpload) {
        imageUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-image').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Responsive Sidebar Toggle
    const sidebarToggle = document.createElement('button');
    sidebarToggle.className = 'btn btn-primary d-md-none position-fixed';
    sidebarToggle.style.cssText = 'top: 1rem; right: 1rem; z-index: 1000;';
    sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
    document.body.appendChild(sidebarToggle);
    
    sidebarToggle.addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });
});

// Theme Switcher
function toggleTheme() {
    const body = document.body;
    const isDark = body.classList.toggle('dark-theme');
    localStorage.setItem('darkTheme', isDark);
    
    // Update theme colors
    if (isDark) {
        document.documentElement.style.setProperty('--primary-color', '#1a1a1a');
        document.documentElement.style.setProperty('--secondary-color', '#2980b9');
    } else {
        document.documentElement.style.setProperty('--primary-color', '#2c3e50');
        document.documentElement.style.setProperty('--secondary-color', '#3498db');
    }
}

// Check saved theme preference
const savedTheme = localStorage.getItem('darkTheme');
if (savedTheme === 'true') {
    toggleTheme();
}
"""

# Write the content to validation.js
with open('validation.js', 'w', encoding='utf-8') as f:
    f.write(js_content)

print("validation.js file has been created successfully!")