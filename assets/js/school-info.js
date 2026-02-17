// School Information Form JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize DOM elements
    const form = document.getElementById('schoolForm');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const spinner = document.getElementById('spinner');
    
    // Role-specific elements
    const legislativeSelect = document.getElementById('legislativeDistricts');
    const schoolDistrictSelect = document.getElementById('SchoolDistricts');
    const nameInput = document.getElementById('name');
    
    // Initialize based on user role
    initializeRoleBasedFeatures();
    
    // Setup form submission handler
    if (form) {
        setupFormSubmission();
    }
    
    /**
     * Initialize role-specific features
     */
    function initializeRoleBasedFeatures() {
        // School users (user role)
        if (userRole === 'user' && legislativeSelect) {
            initializeSchoolUserFeatures();
        }
        
        // Division users
        if (userRole === 'division' && nameInput && schoolDistrictSelect) {
            initializeDivisionUserFeatures();
        }
        
        // District users
        if (userRole === 'district' && nameInput && schoolDistrictSelect) {
            initializeDistrictUserFeatures();
        }
    }
    
    /**
     * Initialize features for school users
     */
    function initializeSchoolUserFeatures() {
        // Handle legislative district change
        legislativeSelect.addEventListener('change', function() {
            const legislativeDistrict = this.value;
            loadSchoolDistricts(legislativeDistrict);
        });
        
        // Load initial districts if user has existing legislative district
        if (userLegislativeDistrict) {
            setTimeout(() => {
                loadSchoolDistricts(userLegislativeDistrict);
            }, 100);
        }
    }
    
    /**
     * Load school districts based on legislative district
     */
    function loadSchoolDistricts(legislativeDistrict) {
        if (!schoolDistrictSelect) return;
        
        schoolDistrictSelect.disabled = true;
        schoolDistrictSelect.innerHTML = '<option value="" disabled selected>Loading school districts...</option>';
        
        if (legislativeDistrict) {
            console.log('Fetching school districts for:', legislativeDistrict);
            
            fetch(`${siteUrl}?legislative_district=${encodeURIComponent(legislativeDistrict)}`)
                .then(handleFetchResponse)
                .then(data => {
                    console.log('School districts data:', data);
                    renderSchoolDistricts(data);
                })
                .catch(handleFetchError);
        }
    }
    
    /**
     * Handle fetch response
     */
    function handleFetchResponse(response) {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    }
    
    /**
     * Render school districts in select dropdown
     */
    function renderSchoolDistricts(data) {
        if (!schoolDistrictSelect) return;
        
        schoolDistrictSelect.innerHTML = '<option value="" disabled selected>Select School District</option>';
        
        if (data && data.length > 0) {
            data.forEach(district => {
                const option = document.createElement('option');
                option.value = district.name;
                option.textContent = district.name;
                schoolDistrictSelect.appendChild(option);
            });
            schoolDistrictSelect.disabled = false;
            
            // Set existing value if any
            if (userSchoolDistrict) {
                const option = Array.from(schoolDistrictSelect.options).find(opt => opt.value === userSchoolDistrict);
                if (option) {
                    schoolDistrictSelect.value = userSchoolDistrict;
                }
            }
        } else {
            schoolDistrictSelect.innerHTML = '<option value="" disabled selected>No school districts found</option>';
        }
    }
    
    /**
     * Handle fetch error
     */
    function handleFetchError(error) {
        console.error('Error fetching school districts:', error);
        if (schoolDistrictSelect) {
            schoolDistrictSelect.innerHTML = '<option value="" disabled selected>Error loading school districts</option>';
        }
    }
    
    /**
     * Initialize features for division users
     */
    function initializeDivisionUserFeatures() {
        // Sync the name with SchoolDistricts for division users
        nameInput.addEventListener('input', function() {
            schoolDistrictSelect.value = this.value;
        });
        
        // Initialize if there's existing name
        if (nameInput.value) {
            schoolDistrictSelect.value = nameInput.value;
        }
    }
    
    /**
     * Initialize features for district users
     */
    function initializeDistrictUserFeatures() {
        // Mirror the District Office name into the read-only SchoolDistricts field
        nameInput.addEventListener('input', function() {
            schoolDistrictSelect.value = this.value;
        });
        
        // Initialize read-only field from current name value
        if (nameInput.value) {
            schoolDistrictSelect.value = nameInput.value;
        }
    }
    
    /**
     * Setup form submission handler
     */
    function setupFormSubmission() {
        form.addEventListener('submit', function(e) {
            if (validateForm()) {
                handleFormSubmission();
            } else {
                e.preventDefault();
                showValidationErrors();
            }
        });
    }
    
    /**
     * Validate form before submission
     */
    function validateForm() {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        return isValid;
    }
    
    /**
     * Show validation errors
     */
    function showValidationErrors() {
        const firstInvalid = form.querySelector('.is-invalid');
        if (firstInvalid) {
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    /**
     * Handle form submission
     */
    function handleFormSubmission() {
        if (submitBtn && submitText && spinner) {
            submitBtn.disabled = true;
            
            // Update button text based on user role
            if (userRole === 'division') {
                submitText.textContent = 'Saving Division Information...';
            } else if (userRole === 'district') {
                submitText.textContent = 'Saving District Information...';
            } else {
                submitText.textContent = 'Saving School Information...';
            }
            
            spinner.classList.remove('d-none');
        }
    }
    
    /**
     * Utility function to show loading state
     */
    function showLoading() {
        document.body.classList.add('loading');
    }
    
    /**
     * Utility function to hide loading state
     */
    function hideLoading() {
        document.body.classList.remove('loading');
    }
    
    /**
     * Utility function to show success message
     */
    function showSuccessMessage(message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
        alertDiv.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        form.insertAdjacentElement('beforebegin', alertDiv);
        
        // Auto-dismiss after 3 seconds
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 300);
        }, 3000);
    }
    
    /**
     * Utility function to show error message
     */
    function showErrorMessage(message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
        alertDiv.innerHTML = `
            <i class="fas fa-exclamation-circle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        form.insertAdjacentElement('beforebegin', alertDiv);
    }
});

/**
 * Additional utility functions for form handling
 */
const SchoolInfoForm = {
    /**
     * Reset form to initial state
     */
    resetForm: function() {
        const form = document.getElementById('schoolForm');
        if (form) {
            form.reset();
            form.querySelectorAll('.is-invalid').forEach(field => {
                field.classList.remove('is-invalid');
            });
        }
    },
    
    /**
     * Get form data as object
     */
    getFormData: function() {
        const form = document.getElementById('schoolForm');
        if (!form) return {};
        
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        return data;
    },
    
    /**
     * Check if form is dirty (has unsaved changes)
     */
    isFormDirty: function() {
        const form = document.getElementById('schoolForm');
        if (!form) return false;
        
        const originalData = {};
        const currentData = {};
        
        // This would need to be implemented based on your initial form values
        // For now, return false
        return false;
    }
};

// Export for use in other modules if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SchoolInfoForm;
}