// Reset password form functionality
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('resetPasswordForm');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    // Get token from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    
    if (!token) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Link',
            text: 'The password reset link is invalid or has expired.',
            confirmButtonText: 'Go to Homepage'
        }).then(() => {
            window.location.href = '../vue/index.html';
        });
        return;
    }
    
    // Set the token in the hidden input
    document.getElementById('resetToken').value = token;
    
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            // Clear previous errors
            clearAllErrors();
            
            // Validate passwords
            if (newPassword.length < 6) {
                showError('password', 'Password must be at least 6 characters');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                showError('confirmPassword', 'Passwords do not match');
                return;
            }
            
            // Show loading state
            showLoading();
            
            try {
                const response = await fetch('../api/users/reset_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        token: token,
                        password: newPassword,
                        confirmPassword: confirmPassword
                    })
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Password Reset!',
                        text: 'Your password has been reset successfully. You can now log in with your new password.',
                        confirmButtonText: 'Go to Login'
                    }).then(() => {
                        window.location.href = 'login.html';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Reset Failed',
                        text: result.message || 'An error occurred. Please try again.',
                        confirmButtonText: 'OK'
                    });
                }
            } catch (error) {
                console.error('Reset password error:', error);
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
});