document.addEventListener('DOMContentLoaded', function() {
    const commissionCard = document.querySelector('.commission-card');
    const modal = document.getElementById('commissionHistoryModal');
    const tableBody = document.getElementById('commissionHistoryTableBody');
    const closeButtons = modal.querySelectorAll('[data-dismiss="modal"]');

    if (commissionCard) {
        commissionCard.addEventListener('click', function() {
            modal.style.display = 'block';
            fetchCommissionHistory();
        });
    }

    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    });

    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });

    function fetchCommissionHistory() {
        tableBody.innerHTML = '<tr><td colspan="4" style="text-align: center;">Loading commission history...</td></tr>';

        // NOTE: Uses the new AdminController method: getCommissionHistoryAjax
        fetch('index.php?action=getCommissionHistoryAjax&ajax=1') 
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                tableBody.innerHTML = ''; 
                if (data && data.length > 0) {
                    data.forEach(item => {
                        const orderDate = new Date(item.order_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                        const orderTotalFormatted = parseFloat(item.order_total).toLocaleString('en-US', {minimumFractionDigits: 2});
                        const commissionFormatted = parseFloat(item.total_commission).toLocaleString('en-US', {minimumFractionDigits: 2});
                        
                        const row = `
                            <tr>
                                <td>#${item.order_id}</td>
                                <td>${orderDate}</td>
                                <td>₱${orderTotalFormatted}</td>
                                <td>₱${commissionFormatted}</td>
                            </tr>
                        `;
                        tableBody.insertAdjacentHTML('beforeend', row);
                    });
                } else {
                    tableBody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No delivered orders with commission yet.</td></tr>';
                }
            })
            .catch(error => {
                tableBody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: red;">Error loading data.</td></tr>';
            });
    }
});