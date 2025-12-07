// Forgot password form functionality
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('forgotPasswordForm');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value.trim();
            
            // Clear previous errors
            clearAllErrors();
            
            // Validate email
            if (!validateEmail(email)) {
                showError('email', 'Please enter a valid email address');
                return;
            }
            
            // Show loading state
            showLoading();
            
            try {
                const response = await fetch('../api/users/forgot_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email: email })
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Reset Link Sent!',
                        text: 'A password reset link has been sent to your email address. Please check your inbox.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'login.html';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Request Failed',
                        text: result.message || 'An error occurred. Please try again.',
                        confirmButtonText: 'OK'
                    });
                }
            } catch (error) {
                console.error('Forgot password error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Could not connect to the server. Please try again later.',
                    confirmButtonText: 'OK'
                });
            } finally {
                hideLoading();
            }
        });
    }
    
    // Helper functions
    function showLoading() {
        document.getElementById('loadingOverlay').classList.add('active');
    }
    
    function hideLoading() {
        document.getElementById('loadingOverlay').classList.remove('active');
    }
    
    function showError(fieldId, message) {
        const errorElement = document.getElementById(fieldId + '-error');
        const inputElement = document.getElementById(fieldId);
        
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
        
        if (inputElement) {
            inputElement.classList.add('error-field');
        }
    }
    
    function clearAllErrors() {
        const errorElements = document.querySelectorAll('.error-message');
        const inputElements = document.querySelectorAll('input');
        
        errorElements.forEach(element => {
            element.style.display = 'none';
        });
        
        inputElements.forEach(element => {
            element.classList.remove('error-field');
        });
    }
    
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
});