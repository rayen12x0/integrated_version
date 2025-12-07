// Particle system for animated background
class Particle {
    constructor(canvas) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.x = Math.random() * canvas.width;
        this.y = Math.random() * canvas.height;
        this.vx = (Math.random() - 0.5) * 0.5;
        this.vy = (Math.random() - 0.5) * 0.5;
        this.radius = Math.random() * 2 + 1;
        this.color = `rgba(255, 255, 255, ${Math.random() * 0.5 + 0.1})`;
    }

    update() {
        this.x += this.vx;
        this.y += this.vy;

        if (this.x < 0 || this.x > this.canvas.width) this.vx *= -1;
        if (this.y < 0 || this.y > this.canvas.height) this.vy *= -1;
    }

    draw() {
        this.ctx.beginPath();
        this.ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
        this.ctx.fillStyle = this.color;
        this.ctx.fill();
    }
}

class ParticleSystem {
    constructor() {
        this.canvas = document.getElementById('particleCanvas');
        this.particles = [];
        this.init();
        this.animate();
    }

    init() {
        if (!this.canvas) return;

        this.canvas.width = window.innerWidth;
        this.canvas.height = window.innerHeight;

        // Create particles
        for (let i = 0; i < 50; i++) {
            this.particles.push(new Particle(this.canvas));
        }

        // Add resize listener
        window.addEventListener('resize', () => {
            this.canvas.width = window.innerWidth;
            this.canvas.height = window.innerHeight;
        });
    }

    animate() {
        if (!this.canvas) return;

        const ctx = this.canvas.getContext('2d');
        ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        // Update and draw particles
        this.particles.forEach(particle => {
            particle.update();
            particle.draw();
        });

        // Draw connections between nearby particles
        this.drawConnections(ctx);

        requestAnimationFrame(() => this.animate());
    }

    drawConnections(ctx) {
        const maxDistance = 100;

        for (let i = 0; i < this.particles.length; i++) {
            for (let j = i + 1; j < this.particles.length; j++) {
                const dx = this.particles[i].x - this.particles[j].x;
                const dy = this.particles[i].y - this.particles[j].y;
                const distance = Math.sqrt(dx * dx + dy * dy);

                if (distance < maxDistance) {
                    const opacity = 1 - distance / maxDistance;
                    ctx.beginPath();
                    ctx.strokeStyle = `rgba(255, 255, 255, ${opacity * 0.2})`;
                    ctx.lineWidth = 1;
                    ctx.moveTo(this.particles[i].x, this.particles[i].y);
                    ctx.lineTo(this.particles[j].x, this.particles[j].y);
                    ctx.stroke();
                }
            }
        }
    }
}

// Ripple effect utility
function createRipple(element, x, y) {
    const ripple = document.createElement('span');
    ripple.classList.add('ripple');
    ripple.style.left = `${x}px`;
    ripple.style.top = `${y}px`;

    element.appendChild(ripple);

    setTimeout(() => {
        ripple.remove();
    }, 600);
}

// Show loading overlay
function showLoading() {
    document.getElementById('loadingOverlay').classList.add('active');
}

// Hide loading overlay
function hideLoading() {
    document.getElementById('loadingOverlay').classList.remove('active');
}

// Set user session via API
async function setUserSession(userId) {
    try {
        const response = await fetch('../api/users/set_session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userId })
        });

        const result = await response.json();
        return result.success;
    } catch (error) {
        console.error('Error setting session:', error);
        return false;
    }
}

// Authenticate user via email and password
async function authenticateUser(email, password) {
    try {
        const response = await fetch('../api/users/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: email,
                password: password
            })
        });

        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error authenticating user:', error);
        return { success: false, message: 'Authentication failed' };
    }
}

// Tab switching functionality
document.getElementById('loginTab')?.addEventListener('click', function() {
    document.getElementById('loginTab').classList.add('active');
    document.getElementById('registerTab').classList.remove('active');
    document.getElementById('loginForm').style.display = 'block';
});

document.getElementById('registerTab')?.addEventListener('click', function() {
    document.getElementById('registerTab').classList.add('active');
    document.getElementById('loginTab').classList.remove('active');
    document.getElementById('loginForm').style.display = 'none';
});

// Handle login form submission
document.getElementById('loginForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('password').value;

    // Clear previous errors
    clearAllErrors();

    // Basic validation
    if (!email || !password) {
        Swal.fire({
            icon: 'error',
            title: 'Missing Fields',
            text: 'Please fill in all required fields.',
            confirmButtonText: 'OK'
        });
        return;
    }

    // Validate email format
    if (!validateEmail(email)) {
        showError('email', 'Please enter a valid email address');
        return;
    }

    // Show loading state
    showLoading();

    try {
        // Call the authentication API
        const result = await authenticateUser(email, password);

        if (result.success) {
            // Store user data in session/storage
            if (result.user) {
                // Set session via API
                const sessionSuccess = await setUserSession(result.user.id);
                if (sessionSuccess) {
                    // Redirect based on user role
                    if (result.user.role === 'admin') {
                        window.location.href = '../dashboard/index.html';
                    } else {
                        window.location.href = '../dashboard/index.html';
                    }
                } else {
                    hideLoading();
                    Swal.fire({
                        icon: 'error',
                        title: 'Session Error',
                        text: 'Login was successful but session could not be established. Please try again.',
                        confirmButtonText: 'OK'
                    });
                }
            } else {
                hideLoading();
                Swal.fire({
                    icon: 'error',
                    title: 'Authentication Error',
                    text: 'Invalid user data returned from server. Please try again.',
                    confirmButtonText: 'OK'
                });
            }
        } else {
            hideLoading();
            Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: result.message || 'Incorrect email or password. Please try again.',
                confirmButtonText: 'OK'
            });
        }
    } catch (error) {
        hideLoading();
        console.error('Login error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'Could not connect to the server. Please try again later.',
            confirmButtonText: 'OK'
        });
    }
});

// Handle demo login buttons
document.getElementById('adminDemoBtn')?.addEventListener('click', async function(e) {
    // Create ripple effect
    const rect = this.getBoundingClientRect();
    createRipple(this, e.clientX - rect.left, e.clientY - rect.top);

    // Show loading state
    showLoading();

    // Set session and redirect
    const success = await setUserSession(1);
    if (success) {
        window.location.href = '../dashboard/index.html';
    } else {
        hideLoading();
        Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            text: 'Please try again.',
            confirmButtonText: 'OK'
        });
    }
});

document.getElementById('userDemoBtn')?.addEventListener('click', async function(e) {
    // Create ripple effect
    const rect = this.getBoundingClientRect();
    createRipple(this, e.clientX - rect.left, e.clientY - rect.top);

    // Show loading state
    showLoading();

    // Set session and redirect
    const success = await setUserSession(2);
    if (success) {
        window.location.href = '../dashboard/index.html';
    } else {
        hideLoading();
        Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            text: 'Please try again.',
            confirmButtonText: 'OK'
        });
    }
});

// Initialize particle system
document.addEventListener('DOMContentLoaded', () => {
    new ParticleSystem();

    // Add ripple effect to role buttons
    const roleBtns = document.querySelectorAll('.role-btn');
    roleBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            createRipple(this, e.clientX - rect.left, e.clientY - rect.top);
        });
    });
});

// Add smooth animations for scroll-triggered elements
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe elements with animate-on-scroll class
document.addEventListener('DOMContentLoaded', () => {
    const animateElements = document.querySelectorAll('.animate-on-scroll');
    animateElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
});

// Add keyboard navigation support
document.addEventListener('keydown', (e) => {
    // Support Enter key on buttons
    if (e.key === 'Enter' && e.target.classList.contains('role-btn')) {
        e.target.click();
    }
});

// Validation functions
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
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