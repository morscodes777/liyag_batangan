document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('vendorProfileModal');
    const closeBtn = modal.querySelector('.modal-close-btn');
    const viewProfileButtons = document.querySelectorAll('.view-profile-btn');
    const modalContent = document.getElementById('modalContent');
    const modalLoading = document.getElementById('modalLoading');
    // const suspendButton = document.getElementById('suspendVendorButton'); // Removed
    let currentVendorId = null;

    // Function to open the modal
    function openModal() {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }

    // Function to close the modal
    function closeModal() {
        modal.classList.remove('open');
        document.body.style.overflow = 'auto';
        // Clear content when closing
        modalContent.style.display = 'none';
        modalLoading.style.display = 'block';
        // suspendButton.style.display = 'none'; // Removed
        currentVendorId = null;
    }

    // Close modal events
    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Handle View Profile click
    viewProfileButtons.forEach(button => {
        button.addEventListener('click', function() {
            currentVendorId = this.getAttribute('data-vendor-id');
            if (currentVendorId) {
                openModal();
                fetchVendorDetails(currentVendorId);
            }
        });
    });
    
    // Suspend Button Action (Placeholder) - REMOVED
    
    // Function to populate and show modal content
    function populateModal(data) {
        document.getElementById('storeLogo').src = data.logo_url || 'public/assets/images/default_logo.png';
        document.getElementById('businessName').textContent = data.business_name || 'N/A';
        document.getElementById('ownerName').textContent = data.name || 'N/A';
        document.getElementById('ownerEmail').textContent = data.email || 'N/A';
        document.getElementById('ownerPhone').textContent = data.phone || 'N/A';
        document.getElementById('businessAddress').textContent = data.business_address || 'N/A';
        document.getElementById('registrationDate').textContent = data.registration_date ? new Date(data.registration_date).toLocaleDateString() : 'N/A';
        
        const statusTag = document.getElementById('storeStatus');
        statusTag.textContent = data.status || 'N/A';
        statusTag.className = `status-tag status-${(data.status || '').toLowerCase()}`;
        
        const description = data.business_description || 'No description provided.';
        document.getElementById('businessDescription').textContent = description;
        
        // Document Link
        const docLink = document.getElementById('verificationDocumentLink');
        const docNone = document.getElementById('verificationDocumentNone');
        if (data.business_permit_url) {
            docLink.href = data.business_permit_url;
            docLink.style.display = 'inline-block';
            docNone.style.display = 'none';
        } else {
            docLink.style.display = 'none';
            docNone.style.display = 'inline';
        }

        modalLoading.style.display = 'none';
        modalContent.style.display = 'block';
        // suspendButton.style.display = 'inline-block'; // Removed
    }

    // Function to fetch vendor details via AJAX
    function fetchVendorDetails(vendorId) {
        // Show loading state first
        modalContent.style.display = 'none';
        modalLoading.style.display = 'block';
        // suspendButton.style.display = 'none'; // Removed

        // ✅ CORRECT: URL is now just index.php, parameters are sent in the body via POST
        const apiUrl = `index.php`; 
        
        // Create form data for the POST request
        const formData = new URLSearchParams();
        formData.append('action', 'view_vendor'); // <-- Set the correct POST action
        formData.append('vendor_id', vendorId);

        fetch(apiUrl, {
            method: 'POST', // ✅ CRITICAL: Change method to POST
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded' // Specify form data type
            },
            body: formData // Send the action and vendor_id in the request body
        })
            .then(response => {
                if (!response.ok) {
                    // This handles 401 Unauthorized errors from index.php
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.vendor) {
                    populateModal(data.vendor);
                } else {
                    // Handle case where vendor is not found or API returns an error
                    document.getElementById('businessName').textContent = 'Error Loading Data';
                    document.getElementById('businessAddress').textContent = data.message || 'Could not fetch vendor details.';
                    modalLoading.style.display = 'none';
                    modalContent.style.display = 'block';
                    // suspendButton.style.display = 'none'; // Removed
                    console.error('Vendor details fetch failed:', data.message);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                // This displays the error message when the response is not valid JSON (like the HTML page)
                document.getElementById('businessName').textContent = 'Network Error';
                document.getElementById('businessAddress').textContent = 'Failed to connect to the server or received invalid data.';
                modalLoading.style.display = 'none';
                modalContent.style.display = 'block';
                // suspendButton.style.display = 'none'; // Removed
            });
    }
});