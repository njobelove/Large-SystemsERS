// Common JavaScript functions for all pages

// Global variables
let currentUser = null;
let currentRole = null;

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Load user data from session/localStorage if needed
    loadUserData();

    // Initialize common UI elements
    initializeUI();

    // Set up event listeners
    setupEventListeners();
}

function loadUserData() {
    // This would typically load from session or localStorage
    // For now, we'll keep it simple
    console.log('Loading user data...');
}

function updateUserInfo() {
    // Update user info in header if needed
    console.log('Updating user info...');
}

function initializeUI() {
    // Initialize tooltips, dropdowns, etc.
    initializeTooltips();
    initializeDropdowns();
}

function setupEventListeners() {
    // Common event listeners
    document.addEventListener('click', handleGlobalClick);
}

// Navigation Functions
function showSection(sectionPath) {
    // Navigate to different sections
    window.location.href = sectionPath;
}

function goBack() {
    window.history.back();
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        // Use absolute paths so it works from any subdirectory
        fetch('/acedemics2/logout.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).finally(() => {
            window.location.href = '/acedemics2/index.php';
        });
    }
}

// Modal Functions
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

function handleGlobalClick(event) {
    // Close modals when clicking outside
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }

    // Close dropdowns when clicking outside
    if (!event.target.matches('.dropdown-toggle')) {
        closeAllDropdowns();
    }
}

// Utility Functions
function showMessage(message, type = 'info') {
    // Create and show a toast message
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#d4edda' : type === 'error' ? '#f8d7da' : '#d1ecf1'};
        color: ${type === 'success' ? '#155724' : type === 'error' ? '#721c24' : '#0c5460'};
        padding: 10px 15px;
        border-radius: 4px;
        border: 1px solid ${type === 'success' ? '#c3e6cb' : type === 'error' ? '#f5c6cb' : '#bee5eb'};
        z-index: 10000;
        max-width: 300px;
    `;

    document.body.appendChild(toast);

    // Auto remove after 3 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 3000);
}

function confirmAction(message) {
    return confirm(message);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString();
}

function formatTime(timeString) {
    const time = new Date(`1970-01-01T${timeString}`);
    return time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
        } else {
            input.classList.remove('error');
        }
    });

    return isValid;
}

// AJAX helper functions
function makeRequest(url, method = 'GET', data = null) {
    return fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
        body: data ? JSON.stringify(data) : null
    })
    .then(response => response.json())
    .catch(error => {
        console.error('Request failed:', error);
        showMessage('Request failed. Please try again.', 'error');
    });
}

// Table sorting and filtering
function sortTable(tableId, columnIndex) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();

        if (isNaN(aValue) || isNaN(bValue)) {
            return aValue.localeCompare(bValue);
        } else {
            return parseFloat(aValue) - parseFloat(bValue);
        }
    });

    rows.forEach(row => tbody.appendChild(row));
}

function filterTable(tableId, searchTerm) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm.toLowerCase())) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Initialize tooltips
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');

    tooltipElements.forEach(element => {
        element.addEventListener('mouseover', showTooltip);
        element.addEventListener('mouseout', hideTooltip);
    });
}

function showTooltip(event) {
    const tooltipText = event.target.getAttribute('data-tooltip');
    if (!tooltipText) return;

    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = tooltipText;
    tooltip.style.cssText = `
        position: absolute;
        background: #333;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        z-index: 1000;
        pointer-events: none;
    `;

    document.body.appendChild(tooltip);

    const rect = event.target.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
}

function hideTooltip() {
    const tooltips = document.querySelectorAll('.tooltip');
    tooltips.forEach(tooltip => {
        if (tooltip.parentNode) {
            tooltip.parentNode.removeChild(tooltip);
        }
    });
}

// Initialize dropdowns
function initializeDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown');

    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        if (toggle) {
            toggle.addEventListener('click', function() {
                dropdown.classList.toggle('active');
            });
        }
    });
}

function closeAllDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(dropdown => {
        dropdown.classList.remove('active');
    });
}

// Export functions for use in other modules
window.CommonApp = {
    showSection,
    goBack,
    logout,
    showModal,
    closeModal,
    showMessage,
    confirmAction,
    validateForm,
    makeRequest,
    sortTable,
    filterTable
};
