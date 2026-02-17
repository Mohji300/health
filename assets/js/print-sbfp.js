// SBFP Print JavaScript

(function() {
    'use strict';
    
    /**
     * Initialize print functionality
     */
    function initializePrint() {
        console.log('Initializing SBFP Form 1A print...');
        
        // Validate configuration
        if (typeof printConfig === 'undefined') {
            console.error('Print configuration not found');
            return;
        }
        
        // Check if there's data to print
        if (!printConfig.hasData) {
            console.warn('No data available for printing');
            showNoDataMessage();
        } else {
            console.log(`Printing ${printConfig.assessmentType} data...`);
        }
        
        // Auto-trigger print
        autoPrint();
    }
    
    /**
     * Auto-trigger print dialog and handle after print
     */
    function autoPrint() {
        // Small delay to ensure CSS is loaded
        setTimeout(() => {
            try {
                window.print();
                console.log('Print dialog triggered');
            } catch (error) {
                console.error('Error triggering print:', error);
                handlePrintError(error);
            }
            
            // Set up after print handling
            setupAfterPrint();
        }, 100);
    }
    
    /**
     * Set up after print event handling
     */
    function setupAfterPrint() {
        // For modern browsers
        if (window.matchMedia) {
            const mediaQueryList = window.matchMedia('print');
            mediaQueryList.addListener(function(mql) {
                if (!mql.matches) {
                    onPrintComplete();
                }
            });
        }
        
        // Fallback for older browsers
        window.onafterprint = onPrintComplete;
        
        // Timeout fallback (close after 1 second if print dialog doesn't trigger afterprint)
        setTimeout(onPrintComplete, 1000);
    }
    
    /**
     * Handle print completion
     */
    function onPrintComplete() {
        console.log('Print completed or cancelled');
        
        // Optional: Close window after printing
        // Uncomment the next line if you want the window to close automatically
        // window.close();
    }
    
    /**
     * Show message when no data is available
     */
    function showNoDataMessage() {
        const messageContainer = document.createElement('div');
        messageContainer.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 10px 20px;
            border-radius: 5px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            z-index: 9999;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            text-align: center;
        `;
        messageContainer.innerHTML = `
            <strong>No Data Available</strong>
            <p style="margin: 5px 0 0 0; font-size: 12px;">
                No ${printConfig.assessmentType} data found to print.
            </p>
        `;
        document.body.appendChild(messageContainer);
        
        // Auto-hide after 3 seconds
        setTimeout(() => {
            messageContainer.style.opacity = '0';
            messageContainer.style.transition = 'opacity 0.3s ease';
            setTimeout(() => messageContainer.remove(), 300);
        }, 3000);
    }
    
    /**
     * Handle print errors
     */
    function handlePrintError(error) {
        const errorDiv = document.createElement('div');
        errorDiv.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 10px 20px;
            border-radius: 5px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            z-index: 9999;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        `;
        errorDiv.innerHTML = `
            <strong>Print Error</strong>
            <p style="margin: 5px 0 0 0; font-size: 12px;">
                There was an error opening the print dialog. 
                Please try again or use your browser's print function (Ctrl+P).
            </p>
        `;
        document.body.appendChild(errorDiv);
        
        // Add manual print button
        const manualBtn = document.createElement('button');
        manualBtn.textContent = 'Manual Print';
        manualBtn.style.cssText = `
            display: block;
            margin: 10px auto;
            padding: 5px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        `;
        manualBtn.onclick = function() {
            window.print();
            this.remove();
        };
        errorDiv.appendChild(manualBtn);
        
        // Auto-hide error after 5 seconds
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.style.opacity = '0';
                errorDiv.style.transition = 'opacity 0.3s ease';
                setTimeout(() => errorDiv.remove(), 300);
            }
        }, 5000);
    }
    
    /**
     * Format date for display (utility function)
     */
    function formatDate(dateString) {
        if (!dateString) return '';
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return dateString;
            
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const year = date.getFullYear();
            
            return `${month}/${day}/${year}`;
        } catch (e) {
            return dateString;
        }
    }
    
    /**
     * Calculate age in years and months (utility function)
     */
    function calculateAge(birthday, weighingDate) {
        if (!birthday || !weighingDate) return '';
        
        try {
            const birth = new Date(birthday);
            const weigh = new Date(weighingDate);
            
            if (isNaN(birth.getTime()) || isNaN(weigh.getTime())) return '';
            
            let years = weigh.getFullYear() - birth.getFullYear();
            let months = weigh.getMonth() - birth.getMonth();
            
            if (months < 0) {
                years--;
                months += 12;
            }
            
            if (weigh.getDate() < birth.getDate()) {
                months--;
                if (months < 0) {
                    months += 12;
                    years--;
                }
            }
            
            return `${years} yrs ${months} mos`;
        } catch (e) {
            return '';
        }
    }
    
    /**
     * Handle keyboard shortcuts
     */
    function handleKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Ctrl+P for print
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            
            // Escape key to close window
            if (e.key === 'Escape') {
                if (confirm('Close print view?')) {
                    window.close();
                }
            }
        });
    }
    
    /**
     * Prepare page for printing
     */
    function prepareForPrint() {
        // Hide unnecessary elements if needed
        // This is where you could hide/show specific elements before printing
        
        // Ensure all dates are properly formatted
        formatAllDates();
        
        // Validate table structure
        validateTable();
    }
    
    /**
     * Format all dates in the table
     */
    function formatAllDates() {
        const dateCells = document.querySelectorAll('td:nth-child(5), td:nth-child(6)');
        dateCells.forEach(cell => {
            const text = cell.textContent.trim();
            if (text && text !== 'N/A') {
                const formatted = formatDate(text);
                if (formatted) {
                    cell.textContent = formatted;
                }
            }
        });
    }
    
    /**
     * Validate table structure
     */
    function validateTable() {
        const table = document.querySelector('table');
        if (!table) {
            console.error('Table not found');
            return false;
        }
        
        const headers = table.querySelectorAll('thead th');
        if (headers.length === 0) {
            console.error('Table headers not found');
            return false;
        }
        
        console.log(`Table validation passed: ${headers.length} columns found`);
        return true;
    }
    
    /**
     * Add page numbers (optional)
     */
    function addPageNumbers() {
        const pages = document.querySelectorAll('.page');
        pages.forEach((page, index) => {
            const pageNum = document.createElement('div');
            pageNum.className = 'page-number';
            pageNum.style.cssText = `
                text-align: center;
                font-size: 8px;
                margin-top: 10px;
                color: #666;
            `;
            pageNum.textContent = `Page ${index + 1}`;
            page.appendChild(pageNum);
        });
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        prepareForPrint();
        initializePrint();
        handleKeyboardShortcuts();
        
        // Optional: Add page numbers if needed
        // addPageNumbers();
        
        console.log('SBFP Form 1A print initialization complete');
    });
    
    // Handle page visibility change
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            console.log('Print view hidden');
        } else {
            console.log('Print view visible');
        }
    });
    
})();

/**
 * Utility functions for SBFP form
 */
const SBFPUtils = {
    /**
     * Convert student data to CSV
     */
    toCSV: function(students) {
        if (!students || students.length === 0) return '';
        
        const headers = [
            'No.', 'Name', 'Sex', 'Grade/Section', 'Date of Birth',
            'Date of Weighing', 'Age', 'Weight (Kg)', 'Height (cm)',
            'BMI', 'BMI-A', 'HFA', 'Parent Consent', '4Ps', 'Previous Beneficiary'
        ];
        
        const rows = students.map((student, index) => [
            index + 1,
            student.name,
            student.sex,
            `${student.grade_level}/${student.section}`,
            student.birthday,
            student.date_of_weighing,
            student.age,
            student.weight,
            student.height,
            student.bmi,
            student.nutritional_status,
            student.height_for_age,
            '', '', ''
        ]);
        
        return [headers, ...rows].map(row => 
            row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')
        ).join('\n');
    },
    
    /**
     * Validate required fields
     */
    validateRequiredFields: function(student) {
        const required = ['name', 'sex', 'grade_level', 'section', 'birthday'];
        const missing = required.filter(field => !student[field]);
        
        return {
            valid: missing.length === 0,
            missing: missing
        };
    },
    
    /**
     * Get summary statistics
     */
    getSummary: function(students) {
        return {
            total: students.length,
            male: students.filter(s => s.sex === 'M').length,
            female: students.filter(s => s.sex === 'F').length,
            baseline: students.filter(s => s.assessment_type === 'baseline').length,
            endline: students.filter(s => s.assessment_type === 'endline').length
        };
    }
};

// Export for use in other modules if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SBFPUtils;
}