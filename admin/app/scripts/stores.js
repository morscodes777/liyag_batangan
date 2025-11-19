// app/scripts/stores.js

document.addEventListener('DOMContentLoaded', () => {
    // Select the main toggle link for the Vendor dropdown
    const dropdownToggle = document.querySelector('.sidebar-dropdown .dropdown-toggle');
    
    // Select the container that holds the link and the menu
    const sidebarDropdown = document.querySelector('.sidebar-dropdown');

    if (dropdownToggle && sidebarDropdown) {
        dropdownToggle.addEventListener('click', function(e) {
            // Prevent the default link behavior (like navigating to #)
            e.preventDefault(); 
            
            // Toggle the 'open' class on the parent container (.sidebar-dropdown)
            // CSS will use this class to show the menu
            sidebarDropdown.classList.toggle('open');
            
            // Optionally, toggle the arrow icon rotation
            const arrow = this.querySelector('.dropdown-arrow');
            if (arrow) {
                arrow.classList.toggle('rotated');
            }
        });
    }

    // --- Optional: Keep the dropdown open if one of the sub-links is active ---
    // This is good for user experience when they navigate to a sub-page (like pending_stores)
    const activeSubLink = document.querySelector('.dropdown-menu .active-sub');
    if (activeSubLink) {
        // Find the closest parent .sidebar-dropdown and add the 'open' class
        const parentDropdown = activeSubLink.closest('.sidebar-dropdown');
        if (parentDropdown) {
            parentDropdown.classList.add('open');
            
            // Optionally, ensure the arrow is rotated if the menu starts open
            const arrow = parentDropdown.querySelector('.dropdown-arrow');
            if (arrow) {
                arrow.classList.add('rotated');
            }
        }
    }
});