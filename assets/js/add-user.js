/**
 * Add User Form JavaScript
 * Handles password confirmation validation and form enhancements
 */

(function() {
    'use strict';

    // Wait for DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        initializePasswordValidation();
        initializeFormValidation();
        initializeRoleDescriptions();
    });

    /**
     * Initialize password confirmation validation
     */
    function initializePasswordValidation() {
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        if (!password || !confirmPassword) {
            console.warn('Password fields not found');
            return;
        }

        // Function to validate password match
        function validatePasswordMatch() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity("Passwords don't match");
                confirmPassword.classList.add('is-invalid');
                
                // Create or update error message
                let errorDiv = confirmPassword.nextElementSibling;
                if (!errorDiv || !errorDiv.classList.contains('password-match-error')) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback password-match-error';
                    confirmPassword.parentNode.appendChild(errorDiv);
                }
                errorDiv.textContent = "Passwords don't match";
            } else {
                confirmPassword.setCustomValidity('');
                confirmPassword.classList.remove('is-invalid');
                
                // Remove error message if exists
                const errorDiv = confirmPassword.nextElementSibling;
                if (errorDiv && errorDiv.classList.contains('password-match-error')) {
                    errorDiv.remove();
                }
            }
        }

        // Add event listeners
        password.addEventListener('change', validatePasswordMatch);
        password.addEventListener('keyup', validatePasswordMatch);
        confirmPassword.addEventListener('keyup', validatePasswordMatch);
        confirmPassword.addEventListener('change', validatePasswordMatch);

        // Initial validation if fields have values
        if (password.value || confirmPassword.value) {
            validatePasswordMatch();
        }
    }

    /**
     * Initialize form validation enhancements
     */
    function initializeFormValidation() {
        const form = document.getElementById('addUserForm');
        
        if (!form) {
            console.warn('Add user form not found');
            return;
        }

        // Email validation
        const email = document.getElementById('email');
        if (email) {
            email.addEventListener('blur', function() {
                validateEmail(this);
            });
        }

        // Password strength indicator
        const password = document.getElementById('password');
        if (password) {
            password.addEventListener('keyup', function() {
                checkPasswordStrength(this);
            });
        }

        // Form submit validation
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                showFormErrors();
            }
        });
    }

    /**
     * Validate email format
     */
    function validateEmail(input) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValid = emailRegex.test(input.value);
        
        if (input.value && !isValid) {
            input.classList.add('is-invalid');
            
            // Create or update error message
            let errorDiv = input.nextElementSibling;
            if (!errorDiv || !errorDiv.classList.contains('email-format-error')) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback email-format-error';
                input.parentNode.appendChild(errorDiv);
            }
            errorDiv.textContent = 'Please enter a valid email address';
        } else {
            input.classList.remove('is-invalid');
            
            // Remove error message if exists
            const errorDiv = input.nextElementSibling;
            if (errorDiv && errorDiv.classList.contains('email-format-error')) {
                errorDiv.remove();
            }
        }
        
        return isValid;
    }

    /**
     * Check password strength
     */
    function checkPasswordStrength(input) {
        const password = input.value;
        let strength = 0;
        const feedback = [];

        // Remove existing strength indicator
        const existingIndicator = document.getElementById('password-strength');
        if (existingIndicator) {
            existingIndicator.remove();
        }

        if (password.length === 0) return;

        // Check length
        if (password.length >= 8) {
            strength += 25;
        } else {
            feedback.push('At least 8 characters');
        }

        // Check for uppercase
        if (/[A-Z]/.test(password)) {
            strength += 25;
        } else {
            feedback.push('Uppercase letter');
        }

        // Check for lowercase
        if (/[a-z]/.test(password)) {
            strength += 25;
        } else {
            feedback.push('Lowercase letter');
        }

        // Check for numbers or special characters
        if (/[0-9!@#$%^&*]/.test(password)) {
            strength += 25;
        } else {
            feedback.push('Number or special character');
        }

        // Create strength indicator
        const indicator = document.createElement('div');
        indicator.id = 'password-strength';
        indicator.className = 'mt-2';
        
        // Add progress bar
        const progressBar = document.createElement('div');
        progressBar.className = 'progress';
        progressBar.style.height = '5px';
        
        const progress = document.createElement('div');
        progress.className = 'progress-bar';
        progress.style.width = strength + '%';
        progress.setAttribute('aria-valuenow', strength);
        progress.setAttribute('aria-valuemin', '0');
        progress.setAttribute('aria-valuemax', '100');
        
        // Set color based on strength
        if (strength <= 25) {
            progress.classList.add('bg-danger');
        } else if (strength <= 50) {
            progress.classList.add('bg-warning');
        } else if (strength <= 75) {
            progress.classList.add('bg-info');
        } else {
            progress.classList.add('bg-success');
        }
        
        progressBar.appendChild(progress);
        indicator.appendChild(progressBar);
        
        // Add feedback text
        if (feedback.length > 0) {
            const feedbackText = document.createElement('small');
            feedbackText.className = 'text-muted d-block mt-1';
            feedbackText.textContent = 'Missing: ' + feedback.join(', ');
            indicator.appendChild(feedbackText);
        }
        
        // Add strength text
        const strengthText = document.createElement('small');
        strengthText.className = 'd-block mt-1';
        let strengthLabel = '';
        if (strength <= 25) strengthLabel = 'Weak';
        else if (strength <= 50) strengthLabel = 'Fair';
        else if (strength <= 75) strengthLabel = 'Good';
        else strengthLabel = 'Strong';
        
        strengthText.innerHTML = `<strong>Password Strength:</strong> ${strengthLabel}`;
        indicator.appendChild(strengthText);
        
        // Insert after password field
        input.parentNode.appendChild(indicator);
    }

    /**
     * Validate entire form before submission
     */
    function validateForm() {
        let isValid = true;
        
        // Check required fields
        const requiredFields = document.querySelectorAll('#addUserForm [required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            }
        });
        
        // Check password match
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        if (password && confirmPassword && password.value !== confirmPassword.value) {
            confirmPassword.classList.add('is-invalid');
            isValid = false;
        }
        
        // Check email format
        const email = document.getElementById('email');
        if (email && email.value && !validateEmail(email)) {
            isValid = false;
        }
        
        return isValid;
    }

    /**
     * Show form errors
     */
    function showFormErrors() {
        const firstError = document.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstError.focus();
        }
    }

    /**
     * Initialize role descriptions toggle
     */
    function initializeRoleDescriptions() {
        const roleSelect = document.getElementById('role');
        
        if (!roleSelect) return;
        
        roleSelect.addEventListener('change', function() {
            const selectedRole = this.value;
            const roleCards = document.querySelectorAll('.role-description-card');
            
            // Remove highlight from all cards
            roleCards.forEach(card => {
                card.classList.remove('border-primary', 'bg-light');
            });
            
            // Highlight the corresponding role card
            if (selectedRole) {
                const roleKey = selectedRole.toLowerCase().replace('_', ' ');
                const cards = document.querySelectorAll('.card-body .col-md-6');
                
                cards.forEach(card => {
                    const heading = card.querySelector('h6');
                    if (heading && heading.textContent.toLowerCase().includes(roleKey)) {
                        card.classList.add('border-primary', 'bg-light');
                    }
                });
            }
        });
    }

})();

/**
 * Export utilities for testing if needed
 */
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        validateEmail,
        checkPasswordStrength,
        validateForm
    };
}