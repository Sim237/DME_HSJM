// Configuration
if (typeof BASE_URL === 'undefined') {
    const BASE_URL = window.BASE_URL || '/dme_hospital/';
}

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap tooltips
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(el => new bootstrap.Tooltip(el));
    
    // Bootstrap popovers
    const popovers = document.querySelectorAll('[data-bs-toggle="popover"]');
    popovers.forEach(el => new bootstrap.Popover(el));
});
