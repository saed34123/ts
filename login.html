// التحقق من صحة النموذج قبل الإرسال (التسجيل)
document.getElementById('register-form').addEventListener('submit', (event) => {
  event.preventDefault();

  const fullname = document.getElementById('fullname').value;
  const email = document.getElementById('email').value;
  const password = document.getElementById('password').value;
  const confirmPassword = document.getElementById('confirm-password').value;
  const countryCode = document.getElementById('country-code').value;
  const phoneNumber = document.getElementById('phone-number').value;
  const key = document.getElementById('key').value;
  const confirmKey = document.getElementById('confirm-key').value;

  if (password !== confirmPassword) {
    alert('Passwords do not match.');
    return;
  }

  if (key !== confirmKey) {
    alert('Keys do not match.');
    return;
  }

  // إرسال البيانات إلى الخادم (Backend)
  fetch('/register', {
    method: 'POST',
    body: JSON.stringify({ fullname, email, password, countryCode, phoneNumber, key }),
    headers: { 'Content-Type': 'application/json' }
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Registration successful! Please log in.');
        window.location.href = 'login.html';
      } else {
        alert('Error registering. Please try again.');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred. Please try again.');
    });
});

// تسجيل الدخول
document.getElementById('login-form').addEventListener('submit', (event) => {
  event.preventDefault();

  const email = document.getElementById('email').value;
  const password = document.getElementById('password').value;

  // إرسال البيانات إلى الخادم (Backend)
  fetch('/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
    headers: { 'Content-Type': 'application/json' }
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Login successful!');
        window.location.href = 'dashboard.html'; // توجيه المستخدم إلى لوحة التحكم
      } else {
        alert('Error logging in. Please try again.');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred. Please try again.');
    });
});