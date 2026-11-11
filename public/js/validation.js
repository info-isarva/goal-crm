document.addEventListener('DOMContentLoaded', function() {
    // Clear error state when input changes
    function clearErrorState(input) {
        input.classList.remove('error-field');
        const errorDiv = input.parentElement.nextElementSibling;
        if (errorDiv && errorDiv.classList.contains('error-message')) {
            errorDiv.style.display = 'none';
        }
    }

    // Add error state and message
    function showError(input, message) {
        input.classList.add('error-field');
        let errorDiv = input.parentElement.nextElementSibling;
        if (!errorDiv || !errorDiv.classList.contains('error-message')) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            input.parentElement.parentNode.insertBefore(errorDiv, input.parentElement.nextSibling);
        }
        errorDiv.textContent = message;
        errorDiv.style.display = 'flex';
    }

    // Real-time validation
    const form = document.querySelector('form');
    if (form) {
        const inputs = form.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                clearErrorState(this);
            });

            input.addEventListener('change', function() {
                clearErrorState(this);
                
                // Validate required fields
                if (this.required && !this.value.trim()) {
                    showError(this, 'This field is required');
                }
                
                // Validate email fields
                if (this.type === 'email' && this.value.trim()) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(this.value.trim())) {
                        showError(this, 'Please enter a valid email address');
                    }
                }

                // Validate phone fields
                if (this.id.includes('phone') && this.value.trim()) {
                    const phoneRegex = /^\+?[0-9\s-()]{10,}$/;
                    if (!phoneRegex.test(this.value.trim())) {
                        showError(this, 'Please enter a valid phone number');
                    }
                }
            });
        });

        // Form submission validation
        form.addEventListener('submit', function(e) {
            let hasErrors = false;
            
            // Clear all previous errors
            document.querySelectorAll('.error-message').forEach(err => {
                err.style.display = 'none';
            });
            document.querySelectorAll('.error-field').forEach(field => {
                field.classList.remove('error-field');
            });

            // Validate required fields
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    showError(field, 'This field is required');
                    hasErrors = true;
                }
            });

            // Show summary message if there are errors
            if (hasErrors) {
                e.preventDefault();
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert-validation';
                alertDiv.innerHTML = '<strong>Please correct the following errors:</strong><ul><li>Please fill in all required fields</li></ul>';
                
                const existingAlert = form.querySelector('.alert-validation');
                if (existingAlert) {
                    existingAlert.remove();
                }
                form.insertBefore(alertDiv, form.firstChild);
                
                // Scroll to first error
                const firstError = form.querySelector('.error-field');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    }
});