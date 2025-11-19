document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('viewDocsModal');
    const closeModalBtn = document.getElementById('closeModal');
    const viewDocsBtns = document.querySelectorAll('.view-docs-btn');
    const businessDocumentArea = document.getElementById('businessDocumentArea');
    const showDocsButton = document.getElementById('showDocsButton');
    const modalApproveBtn = document.getElementById('modalApproveBtn');
    const modalRejectBtn = document.getElementById('modalRejectBtn');
    
    const PLACEHOLDER_IMG = 'public/assets/images/doc_placeholder.png'; 

    function updateText(id, content) {
        document.getElementById(id).textContent = content || 'N/A';
    }

    function openModal(vendorId) {
        updateText('modalVendorName', 'Loading Vendor Details...');
        updateText('ownerName', 'Loading...');
        updateText('businessName', 'Loading...');
        document.getElementById('vendorLogo').src = 'public/assets/images/default_logo.png'; 
        businessDocumentArea.style.display = 'none'; 
        showDocsButton.innerHTML = '<i class="bi bi-file-earmark-check"></i> Show Business Documents';
        
        modal.classList.add('active'); 

        fetch('index.php', { 
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'view_vendor', 
                vendor_id: vendorId
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok.');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.vendor) {
                const vendor = data.vendor;
                
                updateText('modalVendorName', vendor.business_name);
                updateText('ownerName', vendor.name);
                updateText('businessName', vendor.business_name);
                updateText('vendorEmail', vendor.email);
                updateText('vendorPhone', vendor.phone);
                updateText('regDate', vendor.registration_date);
                updateText('vendorID', vendorId);
                
                const logoUrl = vendor.logo_url && vendor.logo_url !== '' ? vendor.logo_url : 'public/assets/images/default_logo.png';
                document.getElementById('vendorLogo').src = logoUrl;
                
                document.getElementById('permitDocument').src = vendor.business_permit_url || PLACEHOLDER_IMG;
                
                // Set vendor ID for action buttons
                modalApproveBtn.setAttribute('data-vendor-id', vendorId);
                modalRejectBtn.setAttribute('data-vendor-id', vendorId);

            } else {
                updateText('modalVendorName', 'Error');
                updateText('businessName', data.message || 'Vendor details not found.');
                ['ownerName', 'vendorEmail', 'vendorPhone', 'regDate', 'vendorID'].forEach(id => updateText(id, 'N/A'));
                alert('Error fetching vendor details: ' + (data.message || 'Details not found or an error occurred on the server side.'));
            }
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            updateText('modalVendorName', 'Connection Error');
            updateText('businessName', 'An unexpected connection error occurred.');
            alert('A network or parsing error occurred. Check the console for details.');
        });
    }

    function closeModal() {
        modal.classList.remove('active');
        businessDocumentArea.style.display = 'none';
        showDocsButton.innerHTML = '<i class="bi bi-file-earmark-check"></i> Show Business Documents';
    }

    // === Action Handler Functions (NEW) ===

    function handleVendorAction(action, vendorId, reason = null) {
        const actionData = {
            action: action, // 'approve_vendor' or 'reject_vendor'
            vendor_id: vendorId,
        };
        
        if (reason) {
            actionData.reason = reason;
        }

        const bodyParams = new URLSearchParams(actionData);

        fetch('index.php', { // Target the main controller
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: bodyParams
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Success! ${data.message}`);
                closeModal();
                // Reload the page to refresh the table and remove the vendor
                window.location.reload(); 
            } else {
                alert(`Action Failed: ${data.message}`);
            }
        })
        .catch(error => {
            console.error('Action AJAX Error:', error);
            alert('A network error occurred while performing the action.');
        });
    }
    
    // === Event Listeners (UPDATED/NEW) ===

    showDocsButton.addEventListener('click', function() {
        if (businessDocumentArea.style.display === 'block') {
            businessDocumentArea.style.display = 'none';
            this.innerHTML = '<i class="bi bi-file-earmark-check"></i> Show Business Documents';
        } else {
            businessDocumentArea.style.display = 'block';
            this.innerHTML = '<i class="bi bi-file-earmark-minus"></i> Hide Business Documents';
        }
    });

    viewDocsBtns.forEach(button => {
        button.addEventListener('click', function() {
            const vendorId = this.getAttribute('data-vendor-id');
            openModal(vendorId);
        });
    });

    closeModalBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });
    
    // --- Approve/Reject Listeners ---

    modalApproveBtn.addEventListener('click', function() {
        const vendorId = this.getAttribute('data-vendor-id');
        const businessName = document.getElementById('businessName').textContent;
        
        if (confirm(`Are you sure you want to APPROVE the vendor: ${businessName}?`)) {
            handleVendorAction('approve_vendor', vendorId);
        }
    });

    modalRejectBtn.addEventListener('click', function() {
        const vendorId = this.getAttribute('data-vendor-id');
        const businessName = document.getElementById('businessName').textContent;
        
        if (confirm(`Are you sure you want to REJECT the vendor: ${businessName}?`)) {
            let reason = prompt("Please enter the reason for rejection (required):");
            
            if (reason === null) {
                return;
            }

            reason = reason.trim();
            
            if (reason.length === 0) {
                alert("Rejection reason cannot be empty.");
                return;
            }

            handleVendorAction('reject_vendor', vendorId, reason);
        }
    });
});