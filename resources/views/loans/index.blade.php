@extends('layouts.master-without-page-title')
@section('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        function calculateRemainingAmount(loanId, totalAmount) {
            const payableAmount = parseFloat(document.querySelector(`#paymentModal${loanId} #amount`).value) || 0;
            const remainingAmount = totalAmount - payableAmount;
            document.querySelector(`#paymentModal${loanId} #remaining_amount`).value = remainingAmount.toFixed(2);
        }

        // Function to show alerts on main page
        function showMainPageAlert(type, message) {
            const alertContainer = document.getElementById('mainPageAlerts');
            let alertClass, icon;
            
            switch(type) {
                case 'success':
                    alertClass = 'alert-success';
                    icon = 'fa-check-circle';
                    break;
                case 'warning':
                    alertClass = 'alert-warning';
                    icon = 'fa-edit';
                    break;
                case 'error':
                default:
                    alertClass = 'alert-danger';
                    icon = 'fa-exclamation-circle';
                    break;
            }
            
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <i class="fas ${icon} me-2"></i>${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            alertContainer.innerHTML = alertHtml;
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }

        // Function to add new loan to table dynamically
        function addNewLoanToTable(loan) {
            const tableBody = document.querySelector('tbody');
            const noLoansRow = tableBody.querySelector('td[colspan]');
            
            // Remove "No loans found" row if it exists
            if (noLoansRow) {
                noLoansRow.parentElement.remove();
            }
            
            // Get current table row count for S.No
            const currentRows = tableBody.querySelectorAll('tr:not([style*="display: none"])').length;
            const serialNo = currentRows + 1;
            
            // Get pump name from the loan data
            const pumpName = loan.pump ? loan.pump.pump_name : 'N/A';
            
            // Format created date
            const createdDate = new Date().toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
            
            // Check user type for conditional buttons
            const userType = @json(auth()->user()->user_type);
            const actionButtons = userType !== 'operator' ? `
                <button type="button" class="btn btn-sm btn-warning edit-loan-btn" 
                    data-bs-toggle="modal" data-bs-target="#editLoanModal"
                    data-id="${loan.id}"
                    data-name="${loan.name}"
                    data-company="${loan.company}"
                    data-phone="${loan.phone_no}"
                    data-address="${loan.address}"
                    data-pump-id="${loan.pump_id}">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn btn-sm btn-danger delete-loan-btn" 
                    data-bs-toggle="modal" data-bs-target="#deleteLoanModal" 
                    data-id="${loan.id}" data-name="${loan.name}">
                    <i class="fas fa-trash"></i>
                </button>
            ` : '';
            
            // Create new row HTML
            const newRowHTML = `
                <tr>
                    <td>${serialNo}</td>
                    <td><a href="/loans/${loan.id}">${loan.name}</a></td>
                    <td>${loan.company}</td>
                    <td>${pumpName}</td>
                    <td>${loan.phone_no}</td>
                    <td>${createdDate}</td>
                    <td>0.00</td>
                    <td>
                        <a href="/loans/${loan.id}" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                        </a>
                        ${actionButtons}
                    </td>
                </tr>
            `;
            
            // Add the new row to the table
            tableBody.insertAdjacentHTML('beforeend', newRowHTML);
            
            // Re-attach event listeners to the new row
            const newRow = tableBody.lastElementChild;
            attachEventListenersToNewRow(newRow);
        }

        // Function to attach event listeners to newly added rows
        function attachEventListenersToNewRow(row) {
            // Add event listener to the edit button
            const editButton = row.querySelector('.edit-loan-btn');
            if (editButton) {
                editButton.addEventListener('click', function() {
                    const loanId = this.dataset.id;
                    const loanName = this.dataset.name;
                    const loanCompany = this.dataset.company;
                    const loanPhone = this.dataset.phone;
                    const loanAddress = this.dataset.address;
                    const loanPumpId = this.dataset.pumpId;

                    // Populate the edit modal fields
                    document.getElementById('editLoanForm').action = `/loans/${loanId}`;
                    document.getElementById('edit_name').value = loanName;
                    document.getElementById('edit_company').value = loanCompany;
                    document.getElementById('edit_phone_no').value = loanPhone;
                    document.getElementById('edit_address').value = loanAddress;
                    document.getElementById('edit_pump_id').value = loanPumpId;
                });
            }

            // Add event listener to the delete button
            const deleteButton = row.querySelector('.delete-loan-btn');
            if (deleteButton) {
                deleteButton.addEventListener('click', function() {
                    const loanId = this.dataset.id;
                    const loanName = this.dataset.name;

                    document.getElementById('deleteLoanForm').action = `/loans/${loanId}`;
                    document.getElementById('deleteLoanName').textContent = loanName;
                });
            }
        }

        // Function to update total outstanding amount
        function updateTotalOutstandingAmount() {
            // Since new loans start with 0 amount, we don't need to change the total
            // But we can add this functionality later if needed
        }

        // Function to update loan in table dynamically
        function updateLoanInTable(loan) {
            // Find the row containing this loan by checking the edit button's data-id
            const allRows = document.querySelectorAll('tbody tr');
            let targetRow = null;
            
            allRows.forEach(row => {
                const editButton = row.querySelector('.edit-loan-btn');
                if (editButton && editButton.dataset.id == loan.id) {
                    targetRow = row;
                }
            });
            
            if (targetRow) {
                // Get pump name from the loan data
                const pumpName = loan.pump ? loan.pump.pump_name : 'N/A';
                
                // Update the row data
                const cells = targetRow.querySelectorAll('td');
                cells[1].innerHTML = `<a href="/loans/${loan.id}">${loan.name}</a>`; // Name
                cells[2].textContent = loan.company; // Company  
                cells[3].textContent = pumpName; // Pump
                cells[4].textContent = loan.phone_no; // Phone
                // Keep the same created date and total amount
                
                // Update the action buttons data attributes
                const editButton = targetRow.querySelector('.edit-loan-btn');
                const deleteButton = targetRow.querySelector('.delete-loan-btn');
                
                if (editButton) {
                    editButton.dataset.name = loan.name;
                    editButton.dataset.company = loan.company;
                    editButton.dataset.phone = loan.phone_no;
                    editButton.dataset.address = loan.address;
                    editButton.dataset.pumpId = loan.pump_id;
                }
                
                if (deleteButton) {
                    deleteButton.dataset.name = loan.name;
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Delete Loan Modal functionality
            const deleteButtons = document.querySelectorAll('.delete-loan-btn');
            const deleteForm = document.getElementById('deleteLoanForm');
            const deleteLoanName = document.getElementById('deleteLoanName');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const loanId = this.dataset.id;
                    const loanName = this.dataset.name;

                    deleteForm.action = `/loans/${loanId}`;
                    deleteLoanName.textContent = loanName;
                });
            });

            // Edit Loan Modal functionality
            const editButtons = document.querySelectorAll('.edit-loan-btn');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const loanId = this.dataset.id;
                    const loanName = this.dataset.name;
                    const loanCompany = this.dataset.company;
                    const loanPhone = this.dataset.phone;
                    const loanAddress = this.dataset.address;
                    const loanPumpId = this.dataset.pumpId;

                    // Populate the edit modal fields
                    document.getElementById('editLoanForm').action = `/loans/${loanId}`;
                    document.getElementById('edit_name').value = loanName;
                    document.getElementById('edit_company').value = loanCompany;
                    document.getElementById('edit_phone_no').value = loanPhone;
                    document.getElementById('edit_address').value = loanAddress;
                    document.getElementById('edit_pump_id').value = loanPumpId;
                });
            });

            // Add pump filter functionality
            const pumpFilter = document.getElementById('pump_filter');
            pumpFilter.addEventListener('change', function() {
                const selectedPumpId = this.value;
                const tableRows = document.querySelectorAll('tbody tr');

                tableRows.forEach(row => {
                    // Skip the "No loans found" row
                    if (row.querySelector('td[colspan]')) {
                        return;
                    }

                    if (!selectedPumpId) {
                        row.style.display = ''; // Show all rows when no pump is selected
                        return;
                    }

                    // Get the pump name from the fourth column (Assigned Pump)
                    const pumpCell = row.querySelector('td:nth-child(4)');
                    if (pumpCell) {
                        const pumpText = pumpCell.textContent.trim();
                        // Get the selected pump name from the filter
                        const selectedPumpName = pumpFilter.options[pumpFilter.selectedIndex].textContent.trim();
                        
                        // Check if the pump name matches
                        const matchFound = pumpText.includes(selectedPumpName);
                        row.style.display = matchFound ? '' : 'none';
                    }
                });
            });

            // Edit modal validation - same as create modal
            const editPhoneInput = document.getElementById('edit_phone_no');
            const editNameInput = document.getElementById('edit_name');
            const editCompanyInput = document.getElementById('edit_company');
            const editAddressInput = document.getElementById('edit_address');

            // Phone number input filtering and validation for edit modal
            editPhoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 10) {
                    value = value.substring(0, 10);
                }
                e.target.value = value;

                if (value.length === 0) {
                    this.classList.remove('is-valid', 'is-invalid');
                } else if (validatePhoneNumber(value)) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    this.nextElementSibling.textContent = 'Please enter a valid 10-digit phone number.';
                }
            });

            // Name input validation for edit modal
            editNameInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
                
                if (this.value.length > 200) {
                    this.classList.add('is-invalid');
                    this.nextElementSibling.textContent = 'Name cannot exceed 200 characters.';
                } else if (this.value.length > 0 && validateTextInput(this.value)) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else if (this.value.length > 0) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    this.nextElementSibling.textContent = 'Name can only contain alphabets and spaces.';
                } else {
                    this.classList.remove('is-valid', 'is-invalid');
                }
            });

            // Company input validation for edit modal
            editCompanyInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
                
                if (this.value.length > 200) {
                    this.classList.add('is-invalid');
                    this.nextElementSibling.textContent = 'Company cannot exceed 200 characters.';
                } else if (this.value.length > 0 && validateTextInput(this.value)) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else if (this.value.length > 0) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    this.nextElementSibling.textContent = 'Company can only contain alphabets and spaces.';
                } else {
                    this.classList.remove('is-valid', 'is-invalid');
                }
            });

            // Address input validation for edit modal
            editAddressInput.addEventListener('input', function(e) {
                if (this.value.length > 200) {
                    this.classList.add('is-invalid');
                    this.nextElementSibling.textContent = 'Address cannot exceed 200 characters.';
                } else if (validateAddress(this.value)) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid', 'is-invalid');
                }
            });

            // Edit form submission handling with AJAX
            const editLoanForm = document.getElementById('editLoanForm');
            editLoanForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validate all fields before submission
                let hasErrors = false;
                const formData = new FormData(this);
                
                // Validate pump selection
                if (!formData.get('pump_id')) {
                    document.getElementById('edit_pump_id').classList.add('is-invalid');
                    hasErrors = true;
                } else {
                    document.getElementById('edit_pump_id').classList.remove('is-invalid');
                }
                
                // Validate name
                if (!validateTextInput(formData.get('name'))) {
                    editNameInput.classList.add('is-invalid');
                    hasErrors = true;
                }
                
                // Validate company
                if (!validateTextInput(formData.get('company'))) {
                    editCompanyInput.classList.add('is-invalid');
                    hasErrors = true;
                }
                
                // Validate phone
                if (!validatePhoneNumber(formData.get('phone_no'))) {
                    editPhoneInput.classList.add('is-invalid');
                    hasErrors = true;
                }
                
                // Validate address
                if (!validateAddress(formData.get('address'))) {
                    editAddressInput.classList.add('is-invalid');
                    editAddressInput.nextElementSibling.textContent = 'Please enter a valid address.';
                    hasErrors = true;
                }
                
                if (hasErrors) {
                    return;
                }
                
                // Disable submit button and show loading
                const editSubmitBtn = this.querySelector('button[type="submit"]');
                editSubmitBtn.disabled = true;
                editSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Updating...';
                
                // Submit form via AJAX
                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal first
                        bootstrap.Modal.getInstance(document.getElementById('editLoanModal')).hide();
                        
                        // Show success message on main page with warning (yellow) color for edit
                        showMainPageAlert('warning', data.message || 'Loan updated successfully.');
                        
                        // Update the loan row in the table instead of reloading
                        updateLoanInTable(data.loan);
                    } else {
                        // Close modal and show validation errors on main page
                        bootstrap.Modal.getInstance(document.getElementById('editLoanModal')).hide();
                        
                        if (data.errors) {
                            let errorMessages = Object.values(data.errors).flat().join('<br>');
                            showMainPageAlert('error', errorMessages);
                        } else {
                            showMainPageAlert('error', data.message || 'Please check your input and try again.');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Close modal and show error on main page
                    bootstrap.Modal.getInstance(document.getElementById('editLoanModal')).hide();
                    showMainPageAlert('error', 'An error occurred while updating the loan. Please try again.');
                })
                .finally(() => {
                    // Re-enable submit button
                    editSubmitBtn.disabled = false;
                    editSubmitBtn.innerHTML = '<i class="fas fa-edit me-2"></i>Update Loan';
                });
            });

            // Reset edit modal when it's closed
            document.getElementById('editLoanModal').addEventListener('hidden.bs.modal', function() {
                editLoanForm.reset();
                document.querySelectorAll('#editLoanModal .is-valid, #editLoanModal .is-invalid').forEach(el => {
                    el.classList.remove('is-valid', 'is-invalid');
                });
                const editSubmitBtn = editLoanForm.querySelector('button[type="submit"]');
                editSubmitBtn.disabled = false;
                editSubmitBtn.innerHTML = '<i class="fas fa-edit me-2"></i>Update Loan';
            });

            // Create Loan Modal functionality
            const createLoanForm = document.getElementById('createLoanModalForm');
            const modalSubmitBtn = document.getElementById('modalSubmitBtn');
            const modalAlertsContainer = document.getElementById('modalAlerts');

            // Phone number validation function
            function validatePhoneNumber(phone) {
                return /^[0-9]{10}$/.test(phone);
            }

            // Text input validation (name, company)
            function validateTextInput(value) {
                return /^[a-zA-Z\s]+$/.test(value) && value.length <= 200;
            }

            // Address validation
            function validateAddress(value) {
                return value.length <= 200 && value.trim().length > 0;
            }

            // Real-time validation for modal inputs
            const modalPhoneInput = document.getElementById('modal_phone_no');
            const modalNameInput = document.getElementById('modal_name');
            const modalCompanyInput = document.getElementById('modal_company');
            const modalAddressInput = document.getElementById('modal_address');

            // Phone number input filtering and validation
            modalPhoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 10) {
                    value = value.substring(0, 10);
                }
                e.target.value = value;

                if (value.length === 0) {
                    this.classList.remove('is-valid', 'is-invalid');
                } else if (validatePhoneNumber(value)) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    this.nextElementSibling.textContent = 'Please enter a valid 10-digit phone number.';
                }
            });

            // Name input validation
            modalNameInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
                
                if (this.value.length > 200) {
                    this.classList.add('is-invalid');
                    this.nextElementSibling.textContent = 'Name cannot exceed 200 characters.';
                } else if (this.value.length > 0 && validateTextInput(this.value)) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else if (this.value.length > 0) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    this.nextElementSibling.textContent = 'Name can only contain alphabets and spaces.';
                } else {
                    this.classList.remove('is-valid', 'is-invalid');
                }
            });

            // Company input validation
            modalCompanyInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
                
                if (this.value.length > 200) {
                    this.classList.add('is-invalid');
                    this.nextElementSibling.textContent = 'Company cannot exceed 200 characters.';
                } else if (this.value.length > 0 && validateTextInput(this.value)) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else if (this.value.length > 0) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    this.nextElementSibling.textContent = 'Company can only contain alphabets and spaces.';
                } else {
                    this.classList.remove('is-valid', 'is-invalid');
                }
            });

            // Address input validation
            modalAddressInput.addEventListener('input', function(e) {
                if (this.value.length > 200) {
                    this.classList.add('is-invalid');
                    this.nextElementSibling.textContent = 'Address cannot exceed 200 characters.';
                } else if (validateAddress(this.value)) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid', 'is-invalid');
                }
            });

            // Form submission handling
            createLoanForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Clear previous alerts
                modalAlertsContainer.innerHTML = '';
                
                // Validate all fields
                let hasErrors = false;
                const formData = new FormData(this);
                
                // Validate pump selection
                if (!formData.get('pump_id')) {
                    document.getElementById('modal_pump_id').classList.add('is-invalid');
                    document.querySelector('#modal_pump_id + .invalid-feedback').textContent = 'Please select a pump.';
                    hasErrors = true;
                } else {
                    document.getElementById('modal_pump_id').classList.remove('is-invalid');
                }
                
                // Validate name
                if (!validateTextInput(formData.get('name'))) {
                    modalNameInput.classList.add('is-invalid');
                    hasErrors = true;
                }
                
                // Validate company
                if (!validateTextInput(formData.get('company'))) {
                    modalCompanyInput.classList.add('is-invalid');
                    hasErrors = true;
                }
                
                // Validate phone
                if (!validatePhoneNumber(formData.get('phone_no'))) {
                    modalPhoneInput.classList.add('is-invalid');
                    hasErrors = true;
                }
                
                // Validate address
                if (!validateAddress(formData.get('address'))) {
                    modalAddressInput.classList.add('is-invalid');
                    modalAddressInput.nextElementSibling.textContent = 'Please enter a valid address.';
                    hasErrors = true;
                }
                
                if (hasErrors) {
                    return;
                }
                
                // Disable submit button and show loading
                modalSubmitBtn.disabled = true;
                modalSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creating...';
                
                // Submit form via AJAX
                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal first
                        bootstrap.Modal.getInstance(document.getElementById('createLoanModal')).hide();
                        
                        // Show success message on main page
                        showMainPageAlert('success', data.message);
                        
                        // Reset form
                        createLoanForm.reset();
                        document.querySelectorAll('#createLoanModal .is-valid, #createLoanModal .is-invalid').forEach(el => {
                            el.classList.remove('is-valid', 'is-invalid');
                        });
                        
                        // Add new loan to the table dynamically instead of reloading page
                        addNewLoanToTable(data.loan);
                        
                        // Update total outstanding amount
                        updateTotalOutstandingAmount();
                    } else {
                        // Close modal and show validation errors on main page
                        bootstrap.Modal.getInstance(document.getElementById('createLoanModal')).hide();
                        
                        if (data.errors) {
                            let errorMessages = Object.values(data.errors).flat().join('<br>');
                            showMainPageAlert('error', errorMessages);
                        } else {
                            showMainPageAlert('error', data.message || 'Please check your input and try again.');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Close modal and show error on main page
                    bootstrap.Modal.getInstance(document.getElementById('createLoanModal')).hide();
                    showMainPageAlert('error', 'An error occurred while creating the loan. Please try again.');
                })
                .finally(() => {
                    // Re-enable submit button
                    modalSubmitBtn.disabled = false;
                    modalSubmitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Save Loan';
                });
            });

            // Reset modal when it's closed
            document.getElementById('createLoanModal').addEventListener('hidden.bs.modal', function() {
                createLoanForm.reset();
                modalAlertsContainer.innerHTML = '';
                document.querySelectorAll('#createLoanModal .is-valid, #createLoanModal .is-invalid').forEach(el => {
                    el.classList.remove('is-valid', 'is-invalid');
                });
                modalSubmitBtn.disabled = false;
                modalSubmitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Save Loan';
            });
        });
    </script>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <div>
                <h4 class="fs-16 fw-semibold mb-1">Loan Management</h4>
                <p class="text-muted mb-0">Manage your loans here</p>
            </div>
            <div class="mt-3 mt-sm-0">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createLoanModal">
                    <i class="fas fa-plus me-1"></i> Add New Loan
                </button>
            </div>
        </div>
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

<!-- Alert container for main page messages -->
<div id="mainPageAlerts"></div>

<!-- Total Amount Summary Card -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h5 class="mb-1">Total Outstanding Amount</h5>
                        <h3 class="text-primary mb-0">
                            ₹{{ number_format($loans->sum(function($loan) {
                                return $loan->paymentEntries->where('amount', '>', 0)->sum('amount') -
                                       abs($loan->paymentEntries->where('amount', '<', 0)->sum('amount'));
                            }), 2) }}
                        </h3>
                    </div>
                    <div class="avatar-sm">
                        <span class="avatar-title bg-primary rounded-circle">
                            <i class="fas fa-money-bill-wave fa-2x text-white"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <!-- Pump Selection -->
                <div class="row mb-3">
                    <div class="col-md-2">
                        <label for="pump_filter" class="form-label">Select Pump</label>
                        <select class="form-select" id="pump_filter" name="pump_filter">
                            <option value="">All Pumps</option>
                            @foreach($pumps as $pump)
                                <option value="{{ $pump->id }}">{{ $pump->pump_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-centered table-nowrap mb-0">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Name</th>
                                <th>Company</th>
                                <th>Assigned Pump</th>
                                <th>Phone Number</th>
                                <th>Created On</th>
                                <th>Total Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($loans as $key => $loan)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td><a href="{{ route('loans.show', $loan) }}">{{ $loan->name }}</a></td>
                                    <td>{{ $loan->company }}</td>
                                    <td>{{ $loan->pump ? $loan->pump->pump_name : 'N/A' }}</td>
                                    <td>{{ $loan->phone_no }}</td>
                                    <td>{{ $loan->created_at->format('d M Y') }}</td>
                                    <td>
                                        @php
                                            $totalAmount = $loan->paymentEntries->where('amount', '>', 0)->sum('amount') - abs($loan->paymentEntries->where('amount', '<', 0)->sum('amount'));
                                        @endphp
                                        {{ number_format($totalAmount, 2) }}
                                    </td>
                                    <td>
                                        @if($totalAmount > 0)
                                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal{{ $loan->id }}">
                                                <i class="fas fa-money-bill-wave me-1"></i> Pay
                                            </button>

                                            <!-- Payment Modal -->
                                            <div class="modal fade" id="paymentModal{{ $loan->id }}" tabindex="-1" aria-labelledby="paymentModalLabel{{ $loan->id }}" aria-hidden="true">
                                                <div class="modal-dialog modal-md">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-light">
                                                            <h5 class="modal-title text-dark" id="paymentModalLabel{{ $loan->id }}">
                                                                <i class="fas fa-money-bill-wave me-2"></i>Make Payment
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form action="{{ route('loans.payment', $loan) }}" method="POST">
                                                                @csrf
                                                                <div class="row">
                                                                    <div class="col-md-12 mb-3">
                                                                        <label class="form-label">Customer Name</label>
                                                                        <input type="text" class="form-control" value="{{ $loan->name }}" readonly>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-12 mb-3">
                                                                        <label class="form-label">Total Amount</label>
                                                                        <input type="text" class="form-control" id="total_amount" value="{{ number_format($totalAmount, 2) }}" readonly>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-12 mb-3">
                                                                        <label for="amount" class="form-label">Payable Amount</label>
                                                                        <input type="number" step="0.01" class="form-control" id="amount" name="amount" required
                                                                               oninput="calculateRemainingAmount({{ $loan->id }}, {{ $totalAmount }})">
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-12 mb-3">
                                                                        <label for="remaining_amount" class="form-label">Remaining Amount</label>
                                                                        <input type="text" class="form-control" id="remaining_amount"
                                                                               value="{{ number_format($totalAmount, 2) }}" readonly>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-12 mb-3">
                                                                        <label for="payment_method" class="form-label">Payment Method</label>
                                                                        <select class="form-select" id="payment_method" name="payment_method" required>
                                                                            <option value="">Select Payment Method</option>
                                                                            @foreach($paymentItems as $paymentItem)
                                                                                <option value="{{ $paymentItem->name }}">{{ $paymentItem->name }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="text-end">
                                                                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                                                                        <i class="fas fa-times me-1"></i>Cancel
                                                                    </button>
                                                                    <button type="submit" class="btn btn-primary">
                                                                        <i class="fas fa-check me-1"></i>Make Payment
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        <a href="{{ route('loans.show', $loan) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if(auth()->user()->user_type !== 'operator')
                                            <button type="button" class="btn btn-sm btn-warning edit-loan-btn" 
                                                data-bs-toggle="modal" data-bs-target="#editLoanModal"
                                                data-id="{{ $loan->id }}"
                                                data-name="{{ $loan->name }}"
                                                data-company="{{ $loan->company }}"
                                                data-phone="{{ $loan->phone_no }}"
                                                data-address="{{ $loan->address }}"
                                                data-pump-id="{{ $loan->pump_id }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-loan-btn" data-bs-toggle="modal" data-bs-target="#deleteLoanModal" data-id="{{ $loan->id }}" data-name="{{ $loan->name }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">No loans found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteLoanModal" tabindex="-1" aria-labelledby="deleteLoanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteLoanModalLabel">Delete Loan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                    </div>
                    <div>
                        <p class="mb-0">Are you sure you want to delete the loan <strong id="deleteLoanName"></strong>?</p>
                        <p class="text-muted small mb-0">This action cannot be undone.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteLoanForm" method="POST" class="d-inline m-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-delete-hover">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Create Loan Modal -->
<div class="modal fade" id="createLoanModal" tabindex="-1" aria-labelledby="createLoanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title text-dark" id="createLoanModalLabel">
                    <i class="fas fa-plus me-2"></i>Add New Loan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Alert container for modal -->
                <div id="modalAlerts"></div>

                <form method="POST" action="{{ route('loans.store') }}" id="createLoanModalForm">
                    @csrf

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="modal_pump_id" class="form-label">Select Pump</label>
                            <select class="form-select @error('pump_id') is-invalid @enderror" id="modal_pump_id" name="pump_id" required>
                                <option value="">Select Pump</option>
                                @foreach($pumps as $pump)
                                    <option value="{{ $pump->id }}" {{ old('pump_id') == $pump->id ? 'selected' : '' }}>
                                        {{ $pump->pump_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('pump_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="modal_name" class="form-label">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                id="modal_name" name="name" value="{{ old('name') }}"
                                placeholder="Enter name"
                                pattern="[a-zA-Z\s]+" title="Only alphabets and spaces allowed" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="modal_company" class="form-label">Company</label>
                            <input type="text" class="form-control @error('company') is-invalid @enderror" 
                                id="modal_company" name="company" value="{{ old('company') }}"
                                placeholder="Enter company"
                                pattern="[a-zA-Z\s]+" title="Only alphabets and spaces allowed" required>
                            @error('company')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="modal_phone_no" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control @error('phone_no') is-invalid @enderror" 
                                id="modal_phone_no" name="phone_no" value="{{ old('phone_no') }}"
                                pattern="[0-9]{10}" maxlength="10" 
                                placeholder="Enter 10-digit phone number" required>
                            @error('phone_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="modal_address" class="form-label">Address</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                id="modal_address" name="address" rows="3" 
                                placeholder="Enter address" required>{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="modalSubmitBtn">
                            <i class="fas fa-save me-2"></i>Save Loan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Loan Modal -->
<div class="modal fade" id="editLoanModal" tabindex="-1" aria-labelledby="editLoanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title text-dark" id="editLoanModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Loan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editLoanForm" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="edit_pump_id" class="form-label">Select Pump</label>
                            <select class="form-select @error('pump_id') is-invalid @enderror" id="edit_pump_id" name="pump_id" required>
                                <option value="">Select Pump</option>
                                @foreach($pumps as $pump)
                                    <option value="{{ $pump->id }}">{{ $pump->pump_name }}</option>
                                @endforeach
                            </select>
                            @error('pump_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="edit_name" class="form-label">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                id="edit_name" name="name" 
                                placeholder="Enter name"
                                pattern="[a-zA-Z\s]+" title="Only alphabets and spaces allowed" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="edit_company" class="form-label">Company</label>
                            <input type="text" class="form-control @error('company') is-invalid @enderror" 
                                id="edit_company" name="company" 
                                placeholder="Enter company"
                                pattern="[a-zA-Z\s]+" title="Only alphabets and spaces allowed" required>
                            @error('company')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="edit_phone_no" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control @error('phone_no') is-invalid @enderror" 
                                id="edit_phone_no" name="phone_no" 
                                pattern="[0-9]{10}" maxlength="10" 
                                placeholder="Enter 10-digit phone number" required>
                            @error('phone_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="edit_address" class="form-label">Address</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                id="edit_address" name="address" rows="3" 
                                placeholder="Enter address" required></textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>Update Loan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
