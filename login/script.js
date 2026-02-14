// Get form element
const form = document.getElementById('loginForm');

// Form submission handler
form.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    // Validate email
    if (!email) {
        alert('Please enter your email address');
        return;
    }
    
    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address');
        return;
    }
    
    // Validate password
    if (!password) {
        alert('Please enter your password');
        return;
    }
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.textContent;
    submitBtn.textContent = 'Logging in...';
    submitBtn.disabled = true;
    
    try {
        // Call the PHP login API
        const response = await fetch('../api/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email: email,
                password: password,
                userType: 'user' // This is the user login page (employees and customers)
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Login successful
            alert(`Login successful! Welcome ${data.data.full_name}`);
            
            // Store user info in session storage
            sessionStorage.setItem('isLoggedIn', 'true');
            sessionStorage.setItem('userEmail', data.data.email || email);
            sessionStorage.setItem('userRole', data.data.user_type);
            sessionStorage.setItem('userName', data.data.full_name);
            
            // Redirect to appropriate page
            window.location.href = data.data.redirect_url;
        } else {
            // Login failed
            alert(data.message || 'Invalid email or password. Please try again.');
        }
    } catch (error) {
        console.error('Login error:', error);
        alert('An error occurred during login. Please make sure the server is running and try again.');
    } finally {
        // Restore button state
        submitBtn.textContent = originalBtnText;
        submitBtn.disabled = false;
    }
});

// Check if already logged in
window.addEventListener('DOMContentLoaded', function() {
    if (sessionStorage.getItem('isLoggedIn') === 'true') {
        const userRole = sessionStorage.getItem('userRole');
        
        // Redirect based on role
        if (userRole === 'employee') {
            window.location.href = '../employee/employee-dashboard.php';
        } else if (userRole === 'customer') {
            window.location.href = '../customer/landing.php';
        }
    }
});
