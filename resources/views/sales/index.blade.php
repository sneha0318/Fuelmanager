@extends('layouts.master-without-page-title')

@section('css')
    <!-- Bootstrap datepicker -->
    <link href="{{ URL::asset('build/libs/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
    
<style>
    .stock-info {
        font-size: 0.875rem;
        margin-top: 0.5rem;
    }
    .stock-warning {
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }

    /* Date picker container */
    .date-picker-container {
        position: relative;
        max-width: 300px;
    }
    
    .date-picker-input {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        padding: 0.375rem 0.75rem;
        width: 100%;
        font-size: 14px;
        cursor: pointer;
        background-color: #fff;
    }

    /* Mobile-specific styles */
    @media (max-width: 768px) {
        /* Header controls - Date filter and Add button */
        .mobile-header-controls .row {
            margin: 0;
        }
        
        .mobile-header-controls .col-md-4 {
            padding: 0 5px;
            margin-bottom: 10px;
        }
        
        .mobile-header-controls .col-md-8 {
            padding: 0 5px;
        }
        
        .mobile-header-controls .input-group {
            flex-wrap: nowrap;
        }
        
        .mobile-header-controls .input-group .form-control {
            font-size: 14px;
            padding: 8px 12px;
            min-width: 120px;
            flex: 1;
        }
        
        .mobile-header-controls .input-group .btn {
            font-size: 12px;
            padding: 8px 12px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        .mobile-header-controls #addSaleButton {
            font-size: 14px;
            padding: 8px 12px;
            width: 100%;
        }
        
        .mobile-header-controls #addSaleButton i {
            margin-right: 4px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table thead {
            display: none;
        }

        .table tbody tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            background-color: #fff;
        }

        .table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            border: none;
            border-bottom: 1px solid #dee2e6;
            font-size: 16px !important;
        }

        .table tbody td:last-child {
            border-bottom: none;
        }

        .table tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            margin-right: 1rem;
            font-size: 16px !important;
        }

        .table tbody td:last-child {
            justify-content: flex-end;
        }

        /* Additional mobile text size adjustments */
        .table tbody td .fw-bold,
        .table tbody td .text-muted,
        .table tbody td .badge,
        .table tbody td a,
        .table tbody td button {
            font-size: 20px !important;
        }

        .table tbody td .small {
            font-size: 14px !important;
        }

        /* Action buttons spacing for mobile */
        .table tbody td .d-flex {
            gap: 8px !important;
            flex-wrap: wrap;
        }

        .table tbody td .btn {
            margin: 2px !important;
            flex-shrink: 0;
        }

        .table tbody td .btn-sm {
            font-size: 12px !important;
        }

        /* Ensure action buttons container has proper spacing */
        .table tbody td:last-child .d-flex {
            justify-content: flex-end !important;
            align-items: center;
            gap: 10px !important;
        }
    }

    /* Extra small mobile devices */
    @media (max-width: 576px) {
        .mobile-header-controls .input-group .form-control {
            font-size: 12px;
            padding: 6px 8px;
            min-width: 100px;
        }
        
        .mobile-header-controls .input-group .btn {
            font-size: 11px;
            padding: 6px 8px;
        }
        
        .mobile-header-controls #addSaleButton {
            font-size: 12px;
            padding: 6px 8px;
        }
        
        .page-title-box h4 {
            font-size: 18px !important;
        }
        
        .page-title-box p {
            font-size: 12px !important;
        }
    }
</style>
@endsection

@section('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <!-- Bootstrap datepicker -->
    <script src="{{ URL::asset('build/libs/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
    <meta name="user-type" content="{{ auth()->user()->user_type }}">
    <meta name="user-email" content="{{ auth()->user()->email }}">
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize bootstrap datepicker for sales filter
            $("#sales_date_picker").datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,
                todayHighlight: true,
                todayBtn: "linked",
            }).on('changeDate', function(e) {
                // Format date for server (YYYY-MM-DD)
                const selectedDate = e.date;
                const formattedDate = selectedDate.getFullYear() + '-' + 
                                     ('0' + (selectedDate.getMonth() + 1)).slice(-2) + '-' + 
                                     ('0' + selectedDate.getDate()).slice(-2);
                
                // Set formatted date in hidden field
                document.getElementById('formatted_date').value = formattedDate;
                
                // Submit the form when a date is selected
                $("#salesDateFilterForm").submit();
            });
            
            // Function to show toast notification
            function showToast(message, type = 'success') {
                const alertDiv = document.createElement('div');
                let icon = 'check-circle';
                let alertClass = 'alert-success';

                switch(type) {
                    case 'warning':
                        icon = 'edit';
                        alertClass = 'alert-warning';
                        break;
                    case 'danger':
                        icon = 'trash';
                        alertClass = 'alert-danger';
                        break;
                    default:
                        icon = 'check-circle';
                        alertClass = 'alert-success';
                }

                alertDiv.className = `alert ${alertClass} alert-dismissible fade show`;
                alertDiv.innerHTML = `
                    <i class="fas fa-${icon} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                
                // Insert at the top of the content section
                const contentSection = document.querySelector('.container-fluid');
                const rowElement = document.querySelector('.row');
                contentSection.insertBefore(alertDiv, rowElement.nextSibling);
            }

            // Function to properly cleanup modal
            function cleanupModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    // Remove modal backdrop
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                    // Remove modal-open class from body
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                    // Reset modal state
                    modal.classList.remove('show');
                    modal.style.display = 'none';
                    modal.setAttribute('aria-hidden', 'true');
                    modal.removeAttribute('aria-modal');
                    modal.removeAttribute('role');
                }
            }

            // Delete Sale Modal functionality
            const deleteButtons = document.querySelectorAll('.delete-sale-btn');
            const deleteForm = document.getElementById('deleteSaleForm');
            const deleteSaleDate = document.getElementById('deleteSaleDate');
            const deleteSaleModal = document.getElementById('deleteSaleModal');

            // Add event listener for modal close
            if (deleteSaleModal) {
                deleteSaleModal.addEventListener('hidden.bs.modal', function () {
                    cleanupModal('deleteSaleModal');
                });
            }

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const saleId = this.dataset.id;
                    const saleDate = this.dataset.date;

                    // Update the delete form action and sale date in the modal
                    deleteForm.action = `/new-sales/${saleId}`;
                    deleteSaleDate.textContent = saleDate;
                });
            });

            // Add event listener for delete form submission
            if (deleteForm) {
                deleteForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    // Get the submit button
                    const submitButton = this.querySelector('button[type="submit"]');
                    // Disable the submit button to prevent double submission
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...';

                    const formData = new FormData(this);
                    formData.append('_method', 'DELETE'); // Add DELETE method for Laravel

                    fetch(this.action, {
                        method: 'POST', // Use POST for Laravel's DELETE method
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Close the modal and cleanup
                            cleanupModal('deleteSaleModal');

                            // Show success toast
                            showToast(data.message, 'danger');

                            // Remove the deleted row from the table
                            const row = document.querySelector(`tr[data-sale-id="${data.sale.id}"]`);
                            if (row) {
                                row.remove();
                            }
                        } else {
                            // Show error toast
                            showToast(data.message, 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Failed to delete sale. Please try again.', 'danger');
                    })
                    .finally(() => {
                        // Re-enable the submit button
                        submitButton.disabled = false;
                        submitButton.innerHTML = '<i class="fas fa-trash me-1"></i>Delete';
                    });
                });
            }

            // Add event listener for cancel button
            const cancelButtons = document.querySelectorAll('[data-bs-dismiss="modal"]');
            cancelButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const modal = this.closest('.modal');
                    if (modal) {
                        cleanupModal(modal.id);
                    }
                });
            });
            
            const addSaleButton = document.getElementById('addSaleButton');

            // Initialize modals with error handling
            let addSaleModal, editSaleModal;
            try {
                if (typeof bootstrap !== 'undefined') {
                    const addSaleModalElement = document.getElementById('addSaleModal');
                    const editSaleModalElement = document.getElementById('editSaleModal');

                    if (addSaleModalElement) addSaleModal = new bootstrap.Modal(addSaleModalElement);
                    if (editSaleModalElement) editSaleModal = new bootstrap.Modal(editSaleModalElement);
                }
            } catch (error) {
                console.error('Error initializing modals:', error);
            }

            const pumpSelect = document.getElementById('pump_id');
            const nozzleSelect = document.getElementById('nozzle');
            const editPumpSelect = document.getElementById('edit_pump_id');
            const editNozzleSelect = document.getElementById('edit_nozzle');
            const saleDateInput = document.getElementById('sale_date');
            const saleTimeInput = document.getElementById('sale_time');
            const rateInput = document.getElementById('rate');

            // Function to load nozzles when a pump is selected
            function loadNozzles(pumpId, selectedNozzleId = null, isEdit = false) {
                return new Promise((resolve, reject) => {
                    const nozzleSelect = isEdit ? document.getElementById('edit_nozzle') : document.getElementById('nozzle');
                    const userType = document.querySelector('meta[name="user-type"]').content;
                    const userEmail = document.querySelector('meta[name="user-email"]').content;

                    // Clear current options
                    nozzleSelect.innerHTML = '<option value="">Loading...</option>';

                    // Determine which URL to use based on user type
                    const url = userType === 'super_admin' || userType === 'admin'
                        ? `/api/pumps/${pumpId}/all-nozzles`
                        : `/new-sales/nozzles-by-pump/${pumpId}?operator_email=${userEmail}`;

                    fetch(url, {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.length === 0) {
                            nozzleSelect.innerHTML = '<option value="">No nozzles available</option>';
                            resolve();
                            return;
                        }

                        nozzleSelect.innerHTML = '<option value="">Select Nozzle</option>';
                        data.forEach(nozzle => {
                            const option = document.createElement('option');
                            option.value = nozzle.id;

                            // For admin users, show shift information if available
                            let displayText = nozzle.display_text;
                            if ((userType === 'super_admin' || userType === 'admin') && nozzle.shift_info && nozzle.shift_info.length > 0) {
                                const shiftInfo = nozzle.shift_info.map(info =>
                                    `${info.shift_name} (${info.operator_name})`
                                ).join(', ');
                                displayText += ` [${shiftInfo}]`;
                            }

                            option.textContent = displayText;
                            option.dataset.itemId = nozzle.item_id;
                            option.dataset.itemType = nozzle.item_type || 'Litre';
                            option.dataset.shiftIds = JSON.stringify(nozzle.shift_ids || []);
                            if (selectedNozzleId && nozzle.id == selectedNozzleId) {
                                option.selected = true;
                            }
                            nozzleSelect.appendChild(option);
                        });
                        resolve();
                    })
                    .catch(error => {
                        console.error('Error loading nozzles:', error);
                        nozzleSelect.innerHTML = '<option value="">Error loading nozzles</option>';
                        reject(error);
                    });
                });
            }

            // Function to fetch and update rate
            function updateRate(pumpId, itemId, date) {
                if (!pumpId || !itemId || !date) return;

                fetch(`/api/pumps/${pumpId}/items/${itemId}/rate?date=${date}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.rate !== null) {
                            rateInput.value = data.rate;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching rate:', error);
                    });
            }

            // Function to update payment history and summary
            function updatePaymentData(date) {
                // Get the current nozzle ID
                const nozzleId = nozzleSelect.value;
                if (!nozzleId) return;

                // Fetch payment history and summary for the selected date
                fetch(`/api/payment-data/${nozzleId}?date=${date}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        // Update payment history table
                        const paymentHistoryTable = document.querySelector('#paymentHistoryTable tbody');
                        if (paymentHistoryTable) {
                            let paymentHistoryHtml = '';
                            data.paymentHistory.forEach(entry => {
                                paymentHistoryHtml += `
                                    <tr>
                                        <td>${entry.date}</td>
                                        <td>${entry.bill_no}</td>
                                        <td>${entry.payment_item}</td>
                                        <td>${entry.rate}</td>
                                        <td>${entry.litre}</td>
                                        <td>${entry.amount}</td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-info edit-payment"
                                                        data-id="${entry.id}"
                                                        data-bill_no="${entry.bill_no}"
                                                        data-litre="${entry.litre}"
                                                        data-rate="${entry.rate}"
                                                        data-amount="${entry.amount}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-payment" data-id="${entry.id}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                `;
                            });
                            paymentHistoryTable.innerHTML = paymentHistoryHtml;
                        }

                        // Update payment summary table
                        const paymentSummaryTable = document.querySelector('#paymentSummaryTable tbody');
                        if (paymentSummaryTable) {
                            let paymentSummaryHtml = '';
                            data.paymentSummary.forEach(summary => {
                                paymentSummaryHtml += `
                                    <tr>
                                        <td>${summary.payment_item}</td>
                                        <td>${summary.litre}</td>
                                        <td>${summary.amount}</td>
                                    </tr>
                                `;
                            });
                            paymentSummaryTable.innerHTML = paymentSummaryHtml;
                        }
                    })
                    .catch(error => {
                        console.error('Error updating payment data:', error);
                    });
            }

            // Add event listener for Add Sale button
            if (addSaleButton) {
                addSaleButton.addEventListener('click', function() {
                    // Set current date and time in the form
                    const now = new Date();
                    const formattedDate = now.toISOString().split('T')[0];
                    const formattedTime = now.toLocaleTimeString('en-GB', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    if (saleDateInput) saleDateInput.value = formattedDate;
                    if (saleTimeInput) saleTimeInput.value = formattedTime;

                    // Clear other form fields
                    if (pumpSelect) pumpSelect.value = '';
                    if (nozzleSelect) {
                        nozzleSelect.innerHTML = '<option value="">Select Nozzle</option>';
                    }

                    const openMeterInput = document.getElementById('open_meter');
                    const testLitreInput = document.getElementById('test_litre');
                    const statusSelect = document.getElementById('status');

                    if (openMeterInput) openMeterInput.value = '';
                    if (testLitreInput) testLitreInput.value = '';
                    if (rateInput) rateInput.value = '';
                    if (statusSelect) statusSelect.value = 'Pending';

                    // Remove any previous stock info if present
                    const stockInfo = document.getElementById('stockInfo');
                    if (stockInfo) stockInfo.remove();

                    // Show the modal - try both approaches for compatibility
                    try {
                        if (addSaleModal) {
                            addSaleModal.show();
                        } else {
                            const modalElement = document.getElementById('addSaleModal');
                            if (modalElement) {
                                const bsModal = new bootstrap.Modal(modalElement);
                                bsModal.show();
                            }
                        }
                    } catch (error) {
                        console.error('Error showing modal:', error);
                        // Fallback to jQuery if bootstrap.Modal fails
                        try {
                            $('#addSaleModal').modal('show');
                        } catch (jqError) {
                            console.error('jQuery modal fallback error:', jqError);
                        }
                    }
                });
            }

            // Add event listener for pump selection in add form
            if (pumpSelect) {
                pumpSelect.addEventListener('change', function () {
                    loadNozzles(this.value, null, false);
                });
            }

            // Add event listener for nozzle selection
            if (nozzleSelect) {
                nozzleSelect.addEventListener('change', function () {
                    const selectedOption = this.options[this.selectedIndex];
                    const itemId = selectedOption.dataset.itemId;
                    const pumpId = pumpSelect.value;
                    const date = saleDateInput.value;
                    const time = saleTimeInput.value;
                    const shiftIds = selectedOption.dataset.shiftIds ? JSON.parse(selectedOption.dataset.shiftIds) : [];

                    // Clear previous stock info when nozzle changes
                    const stockInfo = document.getElementById('stockInfo');
                    if (stockInfo) {
                        stockInfo.innerHTML = '';
                        stockInfo.className = 'alert mt-2';
                    }

                    // Check if nozzle has assigned shifts
                    if (shiftIds.length === 0) {
                        const submitButton = document.querySelector('#addSaleModal button[type="submit"]');
                        if (submitButton) submitButton.disabled = true;

                        if (stockInfo) {
                            stockInfo.className = 'alert alert-danger mt-2';
                            stockInfo.innerHTML = `
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <div>
                                        <strong>Nozzle Not Assigned!</strong><br>
                                        <small>This nozzle is not assigned to any shift. Please assign the nozzle to a shift first.</small>
                                    </div>
                                </div>
                            `;
                        }
                        return;
                    }

                    if (itemId && pumpId && date && time) {
                        updateRate(pumpId, itemId, date + ' ' + time);
                        updatePaymentData(date);

                        // Check stock availability with current opening meter value
                        const openMeterInput = document.getElementById('open_meter');
                        const openingMeter = openMeterInput ? parseFloat(openMeterInput.value) : null;

                        if (!isNaN(openingMeter) && openingMeter > 0) {
                            checkStockAvailability(pumpId, this.value, openingMeter);
                        } else {
                            checkStockAvailability(pumpId, this.value);
                        }
                    }
                });
            }

            // Function to check stock availability
            function checkStockAvailability(pumpId, nozzleId, openingMeter = null) {
                if (!pumpId || !nozzleId) return;

                const data = {
                    pump_id: pumpId,
                    nozzle_id: nozzleId
                };

                if (openingMeter !== null) {
                    data.opening_meter = openingMeter;
                }

                fetch('/new-sales/check-stock-availability', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    const stockInfo = document.getElementById('stockInfo');
                    const openMeterInput = document.getElementById('open_meter');
                    const submitButton = document.querySelector('#addSaleModal button[type="submit"]');

                    if (!stockInfo) {
                        // Create stock info element if it doesn't exist
                        const stockInfoDiv = document.createElement('div');
                        stockInfoDiv.id = 'stockInfo';
                        stockInfoDiv.className = 'alert mt-2';

                        const openMeterGroup = openMeterInput.closest('.col-md-6');
                        openMeterGroup.appendChild(stockInfoDiv);
                    }

                    const stockInfoElement = document.getElementById('stockInfo');

                    if (!data.success || !data.has_stock) {
                        stockInfoElement.className = 'alert alert-danger mt-2';
                        stockInfoElement.innerHTML = `
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <div>
                                    <strong>Stock Alert!</strong><br>
                                    <small>${data.message}</small>
                                </div>
                            </div>
                        `;
                        if (submitButton) submitButton.disabled = true;
                    } else {
                        // Get the selected nozzle option to determine item type
                        const selectedNozzle = nozzleSelect.options[nozzleSelect.selectedIndex];
                        const itemType = selectedNozzle.dataset.itemType || 'Litre';
                        const unit = itemType === 'KG' ? 'KG' : 'L';
                        
                        stockInfoElement.className = 'alert alert-info mt-2';
                        stockInfoElement.innerHTML = `
                            <div class="d-flex align-items-center">
                                <i class="fas fa-info-circle me-2"></i>
                                <div>

                                    <small class="text-muted">

                                        <span class="badge bg-success">Available: ${data.available_stock || 0}${unit}</span>
                                    </small>
                                </div>
                            </div>
                        `;
                        if (submitButton) submitButton.disabled = false;
                        if (openMeterInput) openMeterInput.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Stock check error:', error);
                });
            }

            // Add event listener for opening meter input
            const openMeterInput = document.getElementById('open_meter');
            if (openMeterInput) {
                openMeterInput.addEventListener('input', function() {
                    const pumpId = pumpSelect.value;
                    const nozzleId = nozzleSelect.value;
                    const openingMeter = parseFloat(this.value);

                    if (pumpId && nozzleId) {
                        if (!isNaN(openingMeter) && openingMeter > 0) {
                            checkStockAvailability(pumpId, nozzleId, openingMeter);
                        } else if (this.value === '') {
                            // Clear stock info when input is empty
                            const stockInfo = document.getElementById('stockInfo');
                            if (stockInfo) {
                                stockInfo.innerHTML = '';
                                stockInfo.className = 'alert mt-2';
                            }
                            // Re-enable submit button when input is cleared
                            const submitButton = document.querySelector('#addSaleModal button[type="submit"]');
                            if (submitButton) submitButton.disabled = false;
                        }
                    }
                });
            }

            // Add event listener for date change
            if (saleDateInput) {
                saleDateInput.addEventListener('change', function () {
                    const selectedOption = nozzleSelect.options[nozzleSelect.selectedIndex];
                    const itemId = selectedOption.dataset.itemId;
                    const pumpId = pumpSelect.value;
                    const date = this.value;
                    const time = saleTimeInput.value;

                    if (itemId && pumpId && date && time) {
                        updateRate(pumpId, itemId, date + ' ' + time);
                        updatePaymentData(date);
                    }
                });
            }

            // Add event listener for time change
            if (saleTimeInput) {
                saleTimeInput.addEventListener('change', function () {
                    const selectedOption = nozzleSelect.options[nozzleSelect.selectedIndex];
                    const itemId = selectedOption.dataset.itemId;
                    const pumpId = pumpSelect.value;
                    const date = saleDateInput.value;
                    const time = this.value;

                    if (itemId && pumpId && date && time) {
                        updateRate(pumpId, itemId, date + ' ' + time);
                    }
                });
            }

            // Add event listener for pump selection in edit form
            if (editPumpSelect) {
                editPumpSelect.addEventListener('change', function () {
                    loadNozzles(this.value, null, true);
                });
            }

            // Add event listener for nozzle selection in edit form
            if (editNozzleSelect) {
                editNozzleSelect.addEventListener('change', function () {
                    const selectedOption = this.options[this.selectedIndex];
                    const itemId = selectedOption.dataset.itemId;
                    const pumpId = editPumpSelect.value;
                    const date = document.getElementById('edit_sale_date').value;
                    const time = document.getElementById('edit_sale_time').value;

                    if (itemId && pumpId && date && time) {
                        updateRate(pumpId, itemId, date + ' ' + time);
                    }
                });
            }

            // Function to populate edit modal
            window.editSale = function (sale) {
                document.getElementById('edit_sale_id').value = sale.id;

                // Set original sale date and time
                const saleDate = new Date(sale.sale_date);
                const formattedDate = saleDate.toISOString().split('T')[0];
                const formattedTime = sale.sale_time;

                document.getElementById('edit_sale_date').value = formattedDate;
                document.getElementById('edit_sale_time').value = formattedTime;

                // Set pump and nozzle
                document.getElementById('edit_pump_id').value = sale.pump_id;

                // Load nozzles for the selected pump and set the correct nozzle
                loadNozzles(sale.pump_id, sale.nozzle_id, true).then(() => {
                    document.getElementById('edit_nozzle').value = sale.nozzle_id;

                    // Update rate after nozzle is loaded
                    const selectedOption = document.getElementById('edit_nozzle').options[document.getElementById('edit_nozzle').selectedIndex];
                    const itemId = selectedOption.dataset.itemId;
                    if (itemId) {
                        updateRate(sale.pump_id, itemId, formattedDate + ' ' + formattedTime);
                    }
                });

                // Set other fields
                document.getElementById('edit_open_meter').value = sale.open_meter;
                document.getElementById('edit_close_meter').value = sale.close_meter || '';
                document.getElementById('edit_test_litre').value = sale.test_litre || '';
                document.getElementById('edit_rate').value = sale.rate;
                document.getElementById('edit_status').value = sale.status;

                // Show/hide close meter and close time based on status
                const closeMeterContainer = document.getElementById('closeMeterContainer');
                if (sale.status === 'Completed') {
                    closeMeterContainer.style.display = 'block';
                    // Set close time if it exists, otherwise use current time
                    const closeTime = sale.close_time || new Date().toLocaleTimeString('en-US', {
                        hour12: false,
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    document.getElementById('edit_close_time').value = closeTime;
                } else {
                    closeMeterContainer.style.display = 'none';
                    document.getElementById('edit_close_meter').value = '';
                    document.getElementById('edit_close_time').value = '';
                }

                // Update form action URL
                const editForm = document.getElementById('editSaleForm');
                editForm.action = `/new-sales/${sale.id}`;

                editSaleModal.show();
            }

            // Add event listener for status change
            const editStatusSelect = document.getElementById('edit_status');
            if (editStatusSelect) {
                editStatusSelect.addEventListener('change', function () {
                    const closeMeterContainer = document.getElementById('closeMeterContainer');
                    if (this.value === 'Completed') {
                        closeMeterContainer.style.display = 'block';
                        // Set current time when status changes to Completed
                        const now = new Date();
                        const formattedTime = now.toLocaleTimeString('en-US', {
                            hour12: false,
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit'
                        });
                        document.getElementById('edit_close_time').value = formattedTime;
                    } else {
                        closeMeterContainer.style.display = 'none';
                    }
                });
            }

            // Add event listener for form submission
            const editForm = document.getElementById('editSaleForm');
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    // Get the submit button
                    const submitButton = this.querySelector('button[type="submit"]');
                    // Disable the submit button to prevent double submission
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';

                    const formData = new FormData(this);

                    fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Close the modal first
                            const editSaleModal = bootstrap.Modal.getInstance(document.getElementById('editSaleModal'));
                            if (editSaleModal) {
                                editSaleModal.hide();
                            }

                            // Show success toast
                            showToast(data.message, 'warning');

                            // Update the table row with new data
                            const row = document.querySelector(`tr[data-sale-id="${data.sale.id}"]`);
                            if (row) {
                                // Update all cells with new data
                                row.cells[1].innerHTML = `
                                    <div class="fw-bold">${new Date(data.sale.sale_date).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' })}</div>
                                    <div class="text-muted small">${data.sale.sale_time}</div>
                                `;
                                row.cells[2].textContent = data.sale.pump?.pump_name || 'N/A';
                                row.cells[3].innerHTML = data.sale.nozzle ?
                                    `<a href="/payment-items/${data.sale.nozzle.id}" class="text-primary">
                                        Nozzle #${data.sale.nozzle.nozzle_number} - ${data.sale.nozzle.item?.name || 'N/A'}
                                    </a>` : 'N/A';
                                row.cells[4].textContent = data.sale.open_meter;
                                row.cells[5].textContent = data.sale.close_meter || 'Not Set';
                                row.cells[6].textContent = data.sale.test_litre;
                                row.cells[7].textContent = data.sale.rate;
                                row.cells[8].textContent = data.sale.close_meter ?
                                    Math.abs(data.sale.close_meter - data.sale.open_meter).toFixed(2) : '-';
                                row.cells[9].innerHTML = data.sale.close_time ?
                                    `<div class="fw-bold">${new Date(data.sale.close_time).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' })}</div>
                                     <div class="text-muted small">${data.sale.close_time}</div>` :
                                    '<span class="text-muted">-</span>';
                                row.cells[10].innerHTML = `
                                    <span class="badge bg-${data.sale.status === 'Completed' ? 'success' : 'warning'}">
                                        ${data.sale.status}
                                    </span>
                                `;
                            }
                        } else {
                            // Show error toast
                            showToast(data.message, 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Failed to update sale. Please try again.', 'danger');
                    })
                    .finally(() => {
                        // Re-enable the submit button
                        submitButton.disabled = false;
                        submitButton.innerHTML = '<i class="fas fa-edit me-2"></i>Update Sale';
                    });
                });
            }

            // Add form validation before submit
            const addSaleForm = document.querySelector('#addSaleModal form');
            if (addSaleForm) {
                addSaleForm.addEventListener('submit', function(e) {
                    const stockInfo = document.getElementById('stockInfo');
                    const submitButton = this.querySelector('button[type="submit"]');

                    // Only check stock info if it exists and has an error class
                    if (stockInfo && stockInfo.classList.contains('alert-danger')) {
                        e.preventDefault();

                        // Show toast or alert
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Cannot create sale: Insufficient stock or opening meter exceeds available stock');
                        } else {
                            alert('Cannot create sale: Insufficient stock or opening meter exceeds available stock');
                        }
                        return false;
                    }

                    // Basic validation
                    const pumpId = pumpSelect ? pumpSelect.value : '';
                    const nozzleId = nozzleSelect ? nozzleSelect.value : '';
                    const openMeter = document.getElementById('open_meter') ? document.getElementById('open_meter').value : '';
                    const rateValue = document.getElementById('rate') ? document.getElementById('rate').value : '';

                    // Allow submission if required fields are filled
                    if (pumpId && nozzleId && openMeter && rateValue) {
                        return true; // Allow form submission
                    }
                });
            }
        });
    </script>
@endsection

@section('title')
    Sales Management
@endsection

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .nozzle-select {
            min-width: 200px;
        }

        .nozzle-option {
            padding: 8px;
        }
    </style>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fs-16 fw-semibold mb-1">Sales Management</h4>
                    <p class="text-muted mb-0">Manage your sales records here</p>
                </div>
                <div class="mt-3 mt-sm-0">
                    <button type="button" class="btn btn-primary" id="addSaleButton">
                        <i class="fas fa-plus me-1"></i> Add New Sale
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    {{-- Date Filter --}}
                    <div class="row mb-3 mobile-header-controls">
                        <div class="col-md-2 col-12">
                            <form method="GET" action="" id="salesDateFilterForm">
                                @php
                                    // Format the displayed date in dd/mm/yyyy format for datepicker
                                    $displayDate = request('date') ? \Carbon\Carbon::parse(request('date'))->format('d/m/Y') : \Carbon\Carbon::now()->format('d/m/Y');
                                    
                                    // Keep the backend date value in YYYY-MM-DD format
                                    $backendDate = request('date') ?? \Carbon\Carbon::now()->format('Y-m-d');
                                @endphp
                                <div class="input-group date">
                                    <input type="text" name="display_date" id="sales_date_picker" class="form-control" 
                                          value="{{ $displayDate }}" placeholder="Select date" readonly>
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                </div>
                                <input type="hidden" name="date" id="formatted_date" value="{{ $backendDate }}">
                            </form>
                        </div>
                    </div>

                    @if(session('success'))
                        @php
                            $alertClass = 'alert-success';
                            $icon = 'fa-check-circle';
                            
                            if (session('action') == 'edit') {
                                $alertClass = 'alert-warning';
                                $icon = 'fa-edit';
                            } elseif (session('action') == 'delete') {
                                $alertClass = 'alert-danger';
                                $icon = 'fa-trash';
                            }
                        @endphp
                        <div class="alert {{ $alertClass }} alert-dismissible fade show" role="alert">
                            <i class="fas {{ $icon }} me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Sale Date</th>
                                    <th>Pump</th>
                                    <th>Nozzle</th>
                                    <th>Open Meter</th>
                                    <th>Close Meter</th>
                                    <th>Test Litre</th>
                                    <th>Net Sale (Ltr)</th>
                                    <th>Rate</th>
                                    <th>Variation</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $selectedDate = request('date') ?? \Carbon\Carbon::now()->format('Y-m-d');
                                    $filteredSales = $sales->filter(function ($sale) use ($selectedDate) {
                                        $saleDate = \Carbon\Carbon::parse($sale->sale_date)->format('Y-m-d');
                                        $closeDate = $sale->close_date ? \Carbon\Carbon::parse($sale->close_date)->format('Y-m-d') : null;
                                        return $saleDate === $selectedDate || $closeDate === $selectedDate;
                                    });
                                @endphp
                                @forelse ($filteredSales->reverse() as $sale)
                                    @php
                                        $isSuperAdmin = auth()->user()->user_type === 'super_admin';
                                        $isOwner = $isSuperAdmin ||
                                            (auth()->user()->user_type === 'admin' && $sale->pump->pumpUsers->contains('email', auth()->user()->email)) ||
                                            (auth()->user()->user_type === 'operator' && $sale->pump->shifts()
                                                ->whereHas('pumpUser', function ($query) {
                                                    $query->where('email', auth()->user()->email);
                                                })
                                                ->exists());
                                        $canView = $isOwner;
                                    @endphp
                                    @if($canView)
                                        <tr data-sale-id="{{ $sale->id }}">
                                            <td data-label="S.No">{{ $loop->iteration }}</td>
                                            <td data-label="Sale Date">
                                                <div class="fw-bold">{{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</div>
                                                <div class="text-muted small">{{ \Carbon\Carbon::parse($sale->sale_time)->format('h:i A') }}</div>
                                            </td>
                                            <td data-label="Pump">{{ $sale->pump->pump_name ?? 'N/A' }}</td>
                                            <td data-label="Nozzle">
                                                @if($sale->nozzle)
                                                    <a href="{{ route('payment-items.show', $sale->id) }}" class="text-primary">
                                                        Nozzle #{{ $sale->nozzle->nozzle_number }} - {{ $sale->nozzle->item->name ?? 'N/A' }}
                                                    </a>
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td data-label="Open Meter">{{ $sale->open_meter }}</td>
                                            <td data-label="Close Meter">{{ $sale->close_meter ? number_format($sale->close_meter, 2) : '-' }}</td>
                                            <td data-label="Test Litre">{{ $sale->test_litre ? number_format($sale->test_litre, 2) : '-' }}</td>
                                            <td data-label="Net Sale (Ltr)">
                                                @if($sale->net_sale_litre !== null)
                                                    {{ number_format($sale->net_sale_litre, 2) }}
                                                @else
                                                    @php
                                                        $netSale = $sale->paymentEntries->sum('litre');
                                                    @endphp
                                                    {{ number_format($netSale, 2) }}
                                                @endif
                                            </td>
                                            <td data-label="Rate">{{ number_format($sale->rate, 2) }}</td>
                                            <td data-label="Variation">
                                                @if($sale->variation !== null)
                                                    <span class="badge bg-{{ $sale->variation > 0 ? 'warning' : ($sale->variation < 0 ? 'danger' : 'success') }}">
                                                        {{ number_format($sale->variation, 2) }}
                                                        @php
                                                            $itemType = $sale->nozzle && $sale->nozzle->item ? $sale->nozzle->item->item_type : 'Litre';
                                                        @endphp
                                                        <span class="text-muted small">{{ strtoupper($itemType) == 'KG' ? 'KG' : 'L' }}</span>
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td data-label="Status">
                                                <span class="badge bg-{{ $sale->status === 'Completed' ? 'success' : 'warning' }}">
                                                    {{ $sale->status }}
                                                </span>
                                            </td>
                                            <td data-label="Actions">
                                                @if($isSuperAdmin || (auth()->user()->user_type === 'admin'))
                                                    <button type="button" class="btn btn-sm btn-warning edit-item-btn" onclick="editSale({
                                                            id: '{{ $sale->id }}',
                                                            sale_date: '{{ $sale->sale_date }}',
                                                            sale_time: '{{ $sale->sale_time }}',
                                                            pump_id: '{{ $sale->pump_id }}',
                                                            nozzle_id: '{{ $sale->nozzle_id }}',
                                                            open_meter: '{{ $sale->open_meter }}',
                                                            close_meter: '{{ $sale->close_meter }}',
                                                            test_litre: '{{ $sale->test_litre }}',
                                                            rate: '{{ $sale->rate }}',
                                                            status: '{{ $sale->status }}',
                                                            close_time: '{{ $sale->close_time }}'
                                                        })">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger delete-sale-btn"
                                                        data-bs-toggle="modal" data-bs-target="#deleteSaleModal"
                                                        data-id="{{ $sale->id }}"
                                                        data-date="{{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @else
                                                    <span class="text-muted">View Only</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="13" class="text-center">No sales found for selected date</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Sale Modal -->
    <div class="modal fade" id="addSaleModal" tabindex="-1" aria-labelledby="addSaleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title text-dark" id="addSaleModalLabel"><i class="fas fa-plus me-2"></i>Add New Sale
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('new-sales.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="sale_date" class="form-label">Sale Date</label>
                                <input type="date" class="form-control @error('sale_date') is-invalid @enderror"
                                    id="sale_date" name="sale_date" readonly required>
                                @error('sale_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="sale_time" class="form-label">Sale Time</label>
                                <input type="time" class="form-control @error('sale_time') is-invalid @enderror"
                                    id="sale_time" name="sale_time" readonly required>
                                @error('sale_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="pump_id" class="form-label">Select Pump</label>
                                <select class="form-select @error('pump_id') is-invalid @enderror" id="pump_id"
                                    name="pump_id" required>
                                    <option value="">Select Pump</option>
                                    @foreach($pumps as $pump)
                                        @if(auth()->user()->user_type === 'super_admin' || $pump->pumpUsers->contains('email', auth()->user()->email))
                                            @php
                                                $shiftInfo = $activeShifts->where('pump_id', $pump->id)->first();
                                                //  $shiftText = $shiftInfo ? " (Shift: {$shiftInfo->shift_name} - {$shiftInfo->pumpUser->user_name})" : '';
                                            @endphp
                                            <option value="{{ $pump->id }}" data-shift="{{ $shiftInfo ? $shiftInfo->id : '' }}">
                                                {{ $pump->pump_name }}
                                                {{-- {{ $shiftText }} --}}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('pump_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="nozzle" class="form-label">Select Nozzle</label>
                                <select class="form-select nozzle-select @error('nozzle') is-invalid @enderror" id="nozzle"
                                    name="nozzle" required>
                                    <option value="">Select Nozzle</option>
                                </select>
                                @error('nozzle')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="open_meter" class="form-label">Opening Meter</label>
                                <input type="number" step="0.01"
                                    class="form-control @error('open_meter') is-invalid @enderror" id="open_meter"
                                    name="open_meter" required>
                                @error('open_meter')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <!-- Stock info will be dynamically added here -->
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="test_litre" class="form-label">Testing Litre</label>
                                <input type="number" step="0.01"
                                    class="form-control @error('test_litre') is-invalid @enderror" id="test_litre"
                                    name="test_litre">
                                @error('test_litre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="rate" class="form-label">Rate</label>
                                <input type="number" step="0.01" class="form-control @error('rate') is-invalid @enderror"
                                    id="rate" name="rate" readonly required>
                                @error('rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label"></label>
                                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status"
                                    required>
                                    <option value="Pending">Pending</option>
                                    <option value="Completed">Completed</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-secondary me-2"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save
                                Sale</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Sale Modal -->
    <div class="modal fade" id="editSaleModal" tabindex="-1" aria-labelledby="editSaleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title text-dark" id="editSaleModalLabel"><i class="fas fa-edit me-2"></i>Edit Sale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editSaleForm" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="edit_sale_id" name="sale_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_sale_date" class="form-label">Sale Date</label>
                                <input type="date" class="form-control @error('sale_date') is-invalid @enderror"
                                    id="edit_sale_date" name="sale_date" readonly>
                                @error('sale_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_sale_time" class="form-label">Sale Time</label>
                                <input type="time" class="form-control @error('sale_time') is-invalid @enderror"
                                    id="edit_sale_time" name="sale_time" readonly>
                                @error('sale_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="edit_pump_id" class="form-label">Select Pump</label>
                                <select class="form-select @error('pump_id') is-invalid @enderror" id="edit_pump_id"
                                    name="pump_id" required>
                                    <option value="">Select Pump</option>
                                    @foreach($pumps as $pump)
                                        @if(auth()->user()->user_type === 'super_admin' || $pump->pumpUsers->contains('email', auth()->user()->email))
                                            <option value="{{ $pump->id }}">
                                                {{ $pump->pump_name }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('pump_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="edit_nozzle" class="form-label">Select Nozzle</label>
                                <select class="form-select nozzle-select @error('nozzle') is-invalid @enderror"
                                    id="edit_nozzle" name="nozzle" required>
                                    <option value="">Select Nozzle</option>
                                </select>
                                @error('nozzle')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_open_meter" class="form-label">Opening Meter</label>
                                <input type="number" step="0.01"
                                    class="form-control @error('open_meter') is-invalid @enderror" id="edit_open_meter"
                                    name="open_meter" required>
                                @error('open_meter')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="edit_test_litre" class="form-label">Testing Litre</label>
                                <input type="number" step="0.01" class="form-control @error('test_litre') is-invalid @enderror"
                                    id="edit_test_litre" name="test_litre">
                                @error('test_litre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_rate" class="form-label">Rate</label>
                                <input type="number" step="0.01" class="form-control @error('rate') is-invalid @enderror"
                                    id="edit_rate" name="rate" readonly required>
                                @error('rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select @error('status') is-invalid @enderror" id="edit_status" name="status"
                                    required>
                                    <option value="Pending">Pending</option>
                                    <option value="Completed">Completed</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div id="closeMeterContainer" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_close_meter" class="form-label">Closing Meter</label>
                                    <input type="number" step="0.01" class="form-control @error('close_meter') is-invalid @enderror"
                                        id="edit_close_meter" name="close_meter">
                                    @error('close_meter')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_close_time" class="form-label">Close Time</label>
                                    <input type="text" class="form-control" id="edit_close_time" name="close_time" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-secondary me-2"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning"><i class="fas fa-edit me-2"></i>Update
                                Sale</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteSaleModal" tabindex="-1" aria-labelledby="deleteSaleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteSaleModalLabel"></i>Delete Sale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                        </div>
                        <div>
                            <p class="mb-0">Are you sure you want to delete this sale from <strong id="deleteSaleDate"></strong>?</p>
                            <p class="text-muted small mb-0">This action cannot be undone.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteSaleForm" method="POST" class="d-inline m-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-delete-hover">Delete</button>
                    </form>
                </div>            
            </div>
        </div>
    </div>
    </div>
@endsection
