<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';
?>

<?php require_once 'includes/sidebar.php'; ?>

<style>
    .filter-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }
    
    .filter-tab {
        padding: 10px 20px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .filter-tab.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-color: transparent;
    }
    
    .filter-tab:hover {
        border-color: rgba(102, 126, 234, 0.4);
    }
    
    .reservation-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 20px;
    }
    
    .reservation-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .status-badge {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-pending {
        background: rgba(251, 191, 36, 0.2);
        color: #fbbf24;
        border: 1px solid rgba(251, 191, 36, 0.3);
    }
    
    .status-approved {
        background: rgba(34, 197, 94, 0.2);
        color: #22c55e;
        border: 1px solid rgba(34, 197, 94, 0.3);
    }
    
    .status-rejected {
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }
    
    .reservation-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }
    
    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    
    .detail-label {
        font-size: 0.85rem;
        color: var(--zinc-400);
    }
    
    .detail-value {
        font-size: 1rem;
        font-weight: 500;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 16px;
    }
    
    .btn-approve {
        padding: 8px 16px;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    
    .btn-approve:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
    }
    
    .btn-reject {
        padding: 8px 16px;
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    
    .btn-reject:hover {
        background: rgba(239, 68, 68, 0.3);
    }
    
    .btn-verify {
        padding: 6px 12px;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.85rem;
        transition: all 0.2s ease;
    }
    
    .btn-verify:hover {
        transform: scale(1.05);
    }
    
    .payment-item {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 10px;
    }
    
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    
    .modal-content {
        background: #1a1a2e;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 30px;
        max-width: 500px;
        width: 90%;
    }
</style>

<h1>Plot Reservations Management</h1>

<!-- DEBUG: Test Button -->
<div class="glass-card" style="margin-bottom: 20px; padding: 20px; background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.3);">
    <h3 style="margin: 0 0 10px 0; color: #fbbf24;">🔧 DEBUG MODE</h3>
    <p style="margin: 0 0 15px 0; font-size: 0.9rem; color: rgba(255,255,255,0.7);">Test if JavaScript is working:</p>
    <button onclick="alert('JavaScript is working!'); console.log('Test button clicked');" class="btn-primary">
        Test JavaScript
    </button>
    <button onclick="testApprove()" class="btn-secondary" style="margin-left: 10px;">
        Test Approve Function
    </button>
</div>

<div class="glass-card" style="margin-bottom: 30px;">
    <div class="filter-tabs">
        <button class="filter-tab active" onclick="filterReservations('all')">All</button>
        <button class="filter-tab" onclick="filterReservations('pending')">Pending</button>
        <button class="filter-tab" onclick="filterReservations('approved')">Approved</button>
        <button class="filter-tab" onclick="filterReservations('rejected')">Rejected</button>
    </div>
    
    <div id="reservationsContainer">
        <div style="text-align: center; padding: 40px;">
            <p style="color: var(--zinc-400);">Loading reservations...</p>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <h2 style="margin-bottom: 20px;">Reject Reservation</h2>
        <form id="rejectForm">
            <input type="hidden" id="reject_reservation_id">
            <div class="form-group">
                <label>Rejection Reason *</label>
                <textarea id="rejection_reason" rows="4" class="input-field" required placeholder="Explain why this reservation is being rejected..."></textarea>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn-primary">Submit</button>
                <button type="button" onclick="closeRejectModal()" class="btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Payment Verification Modal -->
<div id="paymentModal" class="modal">
    <div class="modal-content">
        <h2 style="margin-bottom: 20px;">Verify Payment</h2>
        <div id="paymentDetails"></div>
        <form id="verifyPaymentForm">
            <input type="hidden" id="payment_id">
            <div class="form-group">
                <label>Notes</label>
                <textarea id="verification_notes" rows="3" class="input-field" placeholder="Add verification notes..."></textarea>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="verifyPayment('verify')" class="btn-primary">✓ Verify</button>
                <button type="button" onclick="verifyPayment('reject')" class="btn-reject">✗ Reject</button>
                <button type="button" onclick="closePaymentModal()" class="btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

        </main>
    </div>
    
    <script src="../assets/js/theme.js"></script>
    <script>
        let currentFilter = 'all';
        
        // TEST FUNCTION
        function testApprove() {
            console.log('=== TEST FUNCTION CALLED ===');
            alert('Test function is working!\nCheck console for details.');
            console.log('If you see this, JavaScript is working fine.');
            console.log('Current filter:', currentFilter);
            console.log('Trying to call approveReservation with ID 1...');
            approveReservation(1);
        }
        
        async function loadReservations() {
            try {
                const response = await fetch(`../api/admin_get_reservations.php?status=${currentFilter}`);
                const data = await response.json();
                
                if (data.success) {
                    displayReservations(data.reservations);
                } else {
                    showError('Error loading reservations');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Error loading reservations');
            }
        }
        
        function displayReservations(reservations) {
            const container = document.getElementById('reservationsContainer');
            
            if (reservations.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: var(--zinc-400); padding: 40px;">No reservations found</p>';
                return;
            }
            
            container.innerHTML = reservations.map(res => `
                <div class="reservation-card">
                    <div class="reservation-header">
                        <div>
                            <h3 style="margin-bottom: 4px;">Reservation #${res.id}</h3>
                            <p style="color: var(--zinc-400); font-size: 0.9rem; margin: 0;">
                                ${res.visitor_name} • ${res.visitor_email}
                                ${res.visitor_phone ? ' • ' + res.visitor_phone : ''}
                            </p>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <span class="status-badge status-${res.status}">${res.status}</span>
                            <span class="status-badge status-${res.payment_status}">${res.payment_status}</span>
                        </div>
                    </div>
                    
                    <div class="reservation-details">
                        <div class="detail-item">
                            <span class="detail-label">Plot Number</span>
                            <span class="detail-value">${res.plot_number || 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Type</span>
                            <span class="detail-value">${res.reservation_type}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Total Amount</span>
                            <span class="detail-value">₱${parseFloat(res.total_amount).toFixed(2)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Amount Paid</span>
                            <span class="detail-value">₱${parseFloat(res.amount_paid).toFixed(2)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Balance</span>
                            <span class="detail-value">₱${(parseFloat(res.total_amount) - parseFloat(res.amount_paid)).toFixed(2)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Contact</span>
                            <span class="detail-value">${res.contact_number || 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Reservation Date</span>
                            <span class="detail-value">${new Date(res.reservation_date).toLocaleString()}</span>
                        </div>
                    </div>
                    
                    ${res.intended_for ? `
                        <div class="detail-item" style="margin-bottom: 12px;">
                            <span class="detail-label">Intended For</span>
                            <span class="detail-value">${res.intended_for}</span>
                        </div>
                    ` : ''}
                    
                    ${res.purpose ? `
                        <div class="detail-item" style="margin-bottom: 12px;">
                            <span class="detail-label">Purpose</span>
                            <span class="detail-value">${res.purpose}</span>
                        </div>
                    ` : ''}
                    
                    ${res.status === 'pending' ? `
                        <div class="action-buttons">
                            <button onclick="approveReservation(${res.id})" class="btn-approve">✓ Approve</button>
                            <button onclick="openRejectModal(${res.id})" class="btn-reject">✗ Reject</button>
                        </div>
                    ` : ''}
                    
                    ${res.approved_by_name && res.approved_date ? `
                        <p style="font-size: 0.85rem; color: var(--zinc-400); margin-top: 12px;">
                            ${res.status === 'approved' ? 'Approved' : 'Rejected'} by ${res.approved_by_name} on ${new Date(res.approved_date).toLocaleString()}
                        </p>
                    ` : ''}
                    
                    ${res.rejection_reason ? `
                        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 12px; margin-top: 12px;">
                            <strong style="color: #ef4444;">Rejection Reason:</strong>
                            <p style="margin: 4px 0 0 0;">${res.rejection_reason}</p>
                        </div>
                    ` : ''}
                    
                    ${res.payments && res.payments.length > 0 ? `
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                            <h4 style="margin-bottom: 12px;">Payment History</h4>
                            ${res.payments.map(payment => `
                                <div class="payment-item">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <div>
                                            <strong>₱${parseFloat(payment.amount).toFixed(2)}</strong>
                                            <span style="color: var(--zinc-400); margin-left: 10px;">${payment.payment_method}</span>
                                            ${payment.reference_number ? ` • Ref: ${payment.reference_number}` : ''}
                                        </div>
                                        <div>
                                            <span class="status-badge status-${payment.verification_status}">${payment.verification_status}</span>
                                            ${payment.verification_status === 'pending' ? `
                                                <button onclick="openPaymentModal(${payment.id}, ${JSON.stringify(payment).replace(/"/g, '&quot;')})" class="btn-verify">Verify</button>
                                            ` : ''}
                                        </div>
                                    </div>
                                    <div style="font-size: 0.85rem; color: var(--zinc-400);">
                                        Submitted: ${new Date(payment.payment_date).toLocaleString()}
                                        ${payment.verified_by_name ? `<br>Verified by: ${payment.verified_by_name}` : ''}
                                        ${payment.proof_of_payment ? `<br><a href="../uploads/payments/${payment.proof_of_payment}" target="_blank" style="color: #667eea;">View Proof</a>` : ''}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
            `).join('');
        }
        
        function filterReservations(status) {
            currentFilter = status;
            
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Find and activate the clicked tab
            const tabs = document.querySelectorAll('.filter-tab');
            tabs.forEach(tab => {
                if ((status === 'all' && tab.textContent.trim() === 'All') ||
                    (status === 'pending' && tab.textContent.trim() === 'Pending') ||
                    (status === 'approved' && tab.textContent.trim() === 'Approved') ||
                    (status === 'rejected' && tab.textContent.trim() === 'Rejected')) {
                    tab.classList.add('active');
                }
            });
            
            loadReservations();
        }
        
        async function approveReservation(id) {
            if (!confirm('Are you sure you want to approve this reservation?')) return;
            
            console.log('=== APPROVE RESERVATION DEBUG ===');
            console.log('Reservation ID:', id);
            
            try {
                const formData = new FormData();
                formData.append('reservation_id', id);
                formData.append('action', 'approve');
                
                console.log('FormData contents:');
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }
                
                console.log('Sending request to test endpoint first...');
                
                // Test endpoint first
                const testResponse = await fetch('../api/test_approve.php', {
                    method: 'POST',
                    body: formData
                });
                
                console.log('Test response status:', testResponse.status);
                console.log('Test response headers:', Object.fromEntries(testResponse.headers.entries()));
                
                const testText = await testResponse.text();
                console.log('Test raw response:', testText);
                
                let testData;
                try {
                    testData = JSON.parse(testText);
                    console.log('Test parsed data:', testData);
                    
                    // Show the actual API message
                    if (!testData.success) {
                        console.error('❌ API ERROR:', testData.message);
                        if (testData.message === 'Unauthorized') {
                            themeUtils.showAlert('ERROR: You are not logged in as admin! Please login first.', 'error');
                            setTimeout(() => {
                                window.location.href = 'login.php';
                            }, 2000);
                            return;
                        }
                    }
                } catch (e) {
                    console.error('Failed to parse test response:', e);
                    themeUtils.showAlert('Error: Invalid JSON response from test endpoint', 'error');
                    return;
                }
                
                if (testData.success) {
                    // Now try the real endpoint
                    console.log('Test succeeded, trying real endpoint...');
                    
                    const formData2 = new FormData();
                    formData2.append('reservation_id', id);
                    formData2.append('action', 'approve');
                    
                    const realResponse = await fetch('../api/admin_approve_reservation.php', {
                        method: 'POST',
                        body: formData2
                    });
                    
                    console.log('Real response status:', realResponse.status);
                    
                    const realData = await realResponse.json();
                    console.log('Real response data:', realData);
                    
                    if (realData.success) {
                        themeUtils.showAlert(realData.message, 'success');
                        loadReservations();
                    } else {
                        themeUtils.showAlert('Error: ' + realData.message, 'error');
                    }
                } else {
                    console.error('Test endpoint failed:', testData);
                    themeUtils.showAlert('Test failed: ' + testData.message + ' - Check console for details', 'error');
                }
            } catch (error) {
                console.error('=== ERROR ===');
                console.error('Error object:', error);
                console.error('Error message:', error.message);
                console.error('Error stack:', error.stack);
                themeUtils.showAlert('Error: ' + error.message, 'error');
            }
        }
        
        function openRejectModal(id) {
            document.getElementById('reject_reservation_id').value = id;
            document.getElementById('rejectModal').style.display = 'flex';
        }
        
        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
            document.getElementById('rejectForm').reset();
        }
        
        document.getElementById('rejectForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const id = document.getElementById('reject_reservation_id').value;
            const reason = document.getElementById('rejection_reason').value;
            
            const formData = new FormData();
            formData.append('reservation_id', id);
            formData.append('action', 'reject');
            formData.append('rejection_reason', reason);
            
            try {
                const response = await fetch('../api/admin_approve_reservation.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    themeUtils.showAlert(data.message, 'success');
                    closeRejectModal();
                    loadReservations();
                } else {
                    themeUtils.showAlert('Error: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                themeUtils.showAlert('Error rejecting reservation', 'error');
            }
        });
        
        function openPaymentModal(id, payment) {
            document.getElementById('payment_id').value = id;
            document.getElementById('paymentDetails').innerHTML = `
                <div style="background: rgba(255, 255, 255, 0.05); padding: 16px; border-radius: 8px; margin-bottom: 16px;">
                    <p><strong>Amount:</strong> ₱${parseFloat(payment.amount).toFixed(2)}</p>
                    <p><strong>Method:</strong> ${payment.payment_method}</p>
                    ${payment.reference_number ? `<p><strong>Reference:</strong> ${payment.reference_number}</p>` : ''}
                    ${payment.proof_of_payment ? `<p><a href="../uploads/payments/${payment.proof_of_payment}" target="_blank" style="color: #667eea;">View Proof of Payment</a></p>` : ''}
                    ${payment.notes ? `<p><strong>Notes:</strong> ${payment.notes}</p>` : ''}
                </div>
            `;
            document.getElementById('paymentModal').style.display = 'flex';
        }
        
        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }
        
        async function verifyPayment(action) {
            const paymentId = document.getElementById('payment_id').value;
            const notes = document.getElementById('verification_notes').value;
            
            const formData = new FormData();
            formData.append('payment_id', paymentId);
            formData.append('action', action);
            formData.append('notes', notes);
            
            try {
                const response = await fetch('../api/admin_verify_payment.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    themeUtils.showAlert(data.message, 'success');
                    closePaymentModal();
                    loadReservations();
                } else {
                    themeUtils.showAlert('Error: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                themeUtils.showAlert('Error verifying payment', 'error');
            }
        }
        
        function showError(message) {
            document.getElementById('reservationsContainer').innerHTML = 
                `<p style="text-align: center; color: #ef4444; padding: 40px;">${message}</p>`;
        }
        
        // Load reservations on page load
        loadReservations();
    </script>
</body>
</html>
