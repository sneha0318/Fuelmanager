@extends('layouts.master-without-page-title')

@section('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        // Function to add new party list to table dynamically
        function addNewPartyListToTable(partyList) {
            const tableBody = document.querySelector('tbody');
            const noPartyListsRow = tableBody.querySelector('td[colspan]');
            
            // Remove "No party lists found" row if it exists
            if (noPartyListsRow) {
                noPartyListsRow.parentElement.remove();
            }
            
            // Get current table row count for serial number
            const currentRows = tableBody.querySelectorAll('tr:not([style*="display: none"])').length;
            const serialNo = currentRows + 1;
            
            // Format created date
            const createdDate = new Date().toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // Determine name display based on group type
            let nameDisplay = '';
            let groupTypeBadge = '';
            let amountDisplay = '-';
            
            if (partyList.group_type === 'Credit Sale') {
                nameDisplay = `
                    <a href="/party-list/${partyList.id}" class="btn btn-outline-secondary btn-sm clickable-name">
                        <i class="fas fa-user me-1"></i>${partyList.ledger_section}
                    </a>
                `;
                groupTypeBadge = '<span class="badge bg-success me-1">Credit Sale</span>';
                amountDisplay = '₹0.00';
            } else if (partyList.group_type === 'Payment') {
                nameDisplay = `
                    <span class="btn btn-outline-info btn-sm">
                        <i class="fas fa-credit-card me-1"></i>${partyList.ledger_section}
                    </span>
                `;
                groupTypeBadge = '<span class="badge bg-info me-1">Payment</span>';
            } else if (partyList.group_type === 'Expense') {
                nameDisplay = `
                    <span class="btn btn-outline-secondary btn-sm expense-name">
                        <i class="fas fa-receipt me-1"></i>${partyList.ledger_section}
                    </span>
                `;
                groupTypeBadge = '<span class="badge bg-danger me-1">Expense</span>';
            } else {
                nameDisplay = partyList.ledger_section;
                groupTypeBadge = `<span class="badge bg-secondary me-1">${partyList.group_type}</span>`;
            }
            
            // Create new row HTML
            const newRowHTML = `
                <tr>
                    <td>${serialNo}</td>
                    <td>${nameDisplay}</td>
                    <td>${groupTypeBadge}</td>
                    <td>${amountDisplay}</td>
                    <td>${createdDate}</td>
                    <td>
                        <button class="btn btn-sm btn-warning edit-item-btn edit-btn"
                                data-id="${partyList.id}"
                                data-group-type="${partyList.group_type}"
                                data-ledger-section="${partyList.ledger_section}"
                                data-bs-toggle="modal"
                                data-bs-target="#editPartyListModal">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-btn"
                                data-id="${partyList.id}"
                                data-name="${partyList.ledger_section}"
                                data-bs-toggle="modal" data-bs-target="#deletePartyListModal">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            
            // Add the new row to the table
            tableBody.insertAdjacentHTML('beforeend', newRowHTML);
            
            // Re-attach event listeners to the new row
            const newRow = tableBody.lastElementChild;
            attachEventListenersToNewRow(newRow);
        }

        // Function to update party list in table dynamically
        function updatePartyListInTable(partyList) {
            // Find the row containing this party list by checking the edit button's data-id
            const allRows = document.querySelectorAll('tbody tr');
            let targetRow = null;
            
            allRows.forEach(row => {
                const editButton = row.querySelector('.edit-btn');
                if (editButton && editButton.dataset.id == partyList.id) {
                    targetRow = row;
                }
            });
            
            if (targetRow) {
                // Update the row data
                const cells = targetRow.querySelectorAll('td');
                
                // Update name (column 2)
                let nameDisplay = '';
                if (partyList.group_type === 'Credit Sale') {
                    nameDisplay = `
                        <a href="/party-list/${partyList.id}" class="btn btn-outline-secondary btn-sm clickable-name">
                            <i class="fas fa-user me-1"></i>${partyList.ledger_section}
                        </a>
                    `;
                } else if (partyList.group_type === 'Payment') {
                    nameDisplay = `
                        <span class="btn btn-outline-info btn-sm">
                            <i class="fas fa-credit-card me-1"></i>${partyList.ledger_section}
                        </span>
                    `;
                } else if (partyList.group_type === 'Expense') {
                    nameDisplay = `
                        <span class="btn btn-outline-secondary btn-sm expense-name">
                            <i class="fas fa-receipt me-1"></i>${partyList.ledger_section}
                        </span>
                    `;
                } else {
                    nameDisplay = partyList.ledger_section;
                }
                cells[1].innerHTML = nameDisplay;
                
                // Update group type (column 3)
                let groupTypeBadge = '';
                if (partyList.group_type === 'Credit Sale') {
                    groupTypeBadge = '<span class="badge bg-success me-1">Credit Sale</span>';
                } else if (partyList.group_type === 'Payment') {
                    groupTypeBadge = '<span class="badge bg-info me-1">Payment</span>';
                } else if (partyList.group_type === 'Expense') {
                    groupTypeBadge = '<span class="badge bg-danger me-1">Expense</span>';
                } else {
                    groupTypeBadge = `<span class="badge bg-secondary me-1">${partyList.group_type}</span>`;
                }
                cells[2].innerHTML = groupTypeBadge;
                
                // Update the action buttons data attributes
                const editButton = targetRow.querySelector('.edit-btn');
                const deleteButton = targetRow.querySelector('.delete-btn');
                
                if (editButton) {
                    editButton.dataset.groupType = partyList.group_type;
                    editButton.dataset.ledgerSection = partyList.ledger_section;
                }
                
                if (deleteButton) {
                    deleteButton.dataset.name = partyList.ledger_section;
                }
            }
        }

        // Function to attach event listeners to newly added rows
        function attachEventListenersToNewRow(row) {
            // Add event listener to the edit button
            const editButton = row.querySelector('.edit-btn');
            if (editButton) {
                editButton.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const groupType = this.getAttribute('data-group-type');
                    const ledgerSection = this.getAttribute('data-ledger-section');

                    document.getElementById('edit_party_list_id').value = id;
                    document.getElementById('edit_group_type').value = groupType;
                    document.getElementById('edit_ledger_section').value = ledgerSection;
                });
            }

            // Add event listener to the delete button
            const deleteButton = row.querySelector('.delete-btn');
            if (deleteButton) {
                deleteButton.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');

                    // Set up the delete modal
                    document.getElementById('deletePartyListForm').action = `/party-list/${id}`;
                    document.getElementById('deletePartyListName').textContent = name;
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Helper function to show alerts
            function showAlert(message, type = 'success', autoHide = true) {
                const alertContainer = document.getElementById('mainPageAlerts');
                let alertClass, icon;
                
                switch(type) {
                    case 'success':
                        alertClass = 'alert-success';
                        icon = 'fa-check-circle';
                        break;
                    case 'warning':
                        alertClass = 'alert-warning';
                        icon = 'fa-exclamation-triangle';
                        break;
                    case 'danger':
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
                
                // Auto-dismiss after 5 seconds if autoHide is true
                if (autoHide) {
                    setTimeout(() => {
                        const alert = alertContainer.querySelector('.alert');
                        if (alert) {
                            const bsAlert = new bootstrap.Alert(alert);
                            bsAlert.close();
                        }
                    }, 5000);
                }
            }

            // Dynamic Table Update Functions
            function addNewPartyListToTable(partyListData) {
                const tbody = document.querySelector('#partyListTable tbody');
                if (!tbody) return;

                // Get current row count to determine the new row number
                const currentRows = tbody.querySelectorAll('tr').length;
                const newRowNumber = currentRows + 1;

                const newRow = createPartyListRow(partyListData, newRowNumber);
                if (newRow) {
                    tbody.appendChild(newRow);
                    attachEventListenersToNewRow(newRow);
                }
            }

            function updatePartyListInTable(partyListData) {
                const existingRow = document.querySelector(`tr[data-party-list-id="${partyListData.id}"]`);
                if (!existingRow) return;

                const rowNumber = existingRow.querySelector('td:first-child').textContent;
                const newRow = createPartyListRow(partyListData, rowNumber);
                newRow.setAttribute('data-party-list-id', partyListData.id);
                
                existingRow.replaceWith(newRow);
                attachEventListenersToNewRow(newRow);
            }

            function removePartyListFromTable(partyListId) {
                const row = document.querySelector(`tr[data-party-list-id="${partyListId}"]`);
                if (row) {
                    row.remove();
                    
                    // Renumber remaining rows
                    const tbody = document.querySelector('#partyListTable tbody');
                    const rows = tbody.querySelectorAll('tr');
                    rows.forEach((row, index) => {
                        const firstCell = row.querySelector('td:first-child');
                        if (firstCell) {
                            firstCell.textContent = index + 1;
                        }
                    });
                }
            }

            function createPartyListRow(partyList, rowNumber) {
                console.log('Creating row for party list:', partyList); // Debug log
                console.log('PartyList expense data:', partyList.expense); // Debug log
                console.log('PartyList group_type:', partyList.group_type); // Debug log
                
                // Ensure partyList has required properties
                if (!partyList || !partyList.id) {
                    console.error('Invalid party list data:', partyList);
                    return null;
                }
                
                const row = document.createElement('tr');
                row.setAttribute('data-party-list-id', partyList.id);
                
                // Create the name/display content based on group type
                let nameContent = '';
                let amountContent = '-';
                
                const ledgerSection = partyList.ledger_section || 'Unknown';
                const groupType = partyList.group_type || 'Unknown';
                
                if (groupType === 'Credit Sale') {
                    nameContent = `
                        <a href="/party-list/${partyList.id}"
                           class="btn btn-outline-secondary btn-sm clickable-name"
                           title="Click to view loan details, payments and loans"
                           data-bs-toggle="tooltip"
                           data-bs-placement="top">
                            <i class="fas fa-user me-1"></i>${ledgerSection}
                        </a>
                    `;
                    // For Credit Sale, calculate total amount from loan data if available
                    if (partyList.loan && partyList.loan.payment_entries) {
                        const positiveEntries = partyList.loan.payment_entries.filter(entry => entry.amount > 0);
                        const negativeEntries = partyList.loan.payment_entries.filter(entry => entry.amount < 0);
                        const positiveSum = positiveEntries.reduce((sum, entry) => sum + parseFloat(entry.amount), 0);
                        const negativeSum = Math.abs(negativeEntries.reduce((sum, entry) => sum + parseFloat(entry.amount), 0));
                        const totalAmount = positiveSum - negativeSum;
                        amountContent = `₹${totalAmount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                    }
                } else if (groupType === 'Payment') {
                    nameContent = `
                        <span class="text-muted">
                            <i class="fas fa-credit-card me-1"></i>${ledgerSection}
                        </span>
                    `;
                    amountContent = '-';
                } else if (groupType === 'Expense' || groupType === 'Expenses') {
                    // For expenses, show the expense name with category if available
                    if (partyList.expense) {
                        let expenseName = partyList.expense.name || ledgerSection;
                        let categoryInfo = '';
                        if (partyList.expense.category && partyList.expense.category.category_name) {
                            categoryInfo = ` <small class="ms-1">(${partyList.expense.category.category_name})</small>`;
                        }
                        nameContent = `
                            <span class="btn btn-outline-secondary btn-sm expense-name">
                                <i class="fas fa-receipt me-1"></i>${expenseName}${categoryInfo}
                            </span>
                        `;
                        // For expenses, show the amount as negative (expense)
                        const expenseAmount = parseFloat(partyList.expense.amount) || 0;
                        amountContent = `-₹${expenseAmount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                        console.log('Expense amount calculated:', amountContent, 'from amount:', partyList.expense.amount); // Debug log
                    } else {
                        console.log('No expense data found in party list, using ledger section'); // Debug log
                        nameContent = `
                            <span class="btn btn-outline-secondary btn-sm expense-name">
                                <i class="fas fa-receipt me-1"></i>${ledgerSection}
                            </span>
                        `;
                    }
                } else {
                    nameContent = ledgerSection;
                }

                // Create badge for group type
                let badgeContent = '';
                switch(groupType) {
                    case 'Credit Sale':
                        badgeContent = '<span class="badge bg-success me-1">Credit Sale</span>';
                        break;
                    case 'Payment':
                        badgeContent = '<span class="badge bg-info me-1">Payment</span>';
                        break;
                    case 'Expense':
                    case 'Expenses':
                        badgeContent = '<span class="badge bg-danger me-1">Expense</span>';
                        break;
                    case 'Cash Sale':
                        badgeContent = '<span class="badge bg-primary me-1">Cash Sale</span>';
                        break;
                    default:
                        badgeContent = `<span class="badge bg-secondary me-1">${groupType}</span>`;
                        break;
                }

                // Format the created date
                const createdDate = new Date(partyList.created_at).toLocaleString('en-GB', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                });
                
                row.innerHTML = `
                    <td>${rowNumber}</td>
                    <td>${nameContent}</td>
                    <td>${badgeContent}</td>
                    <td>${amountContent}</td>
                    <td>${createdDate}</td>
                    <td>
                        <button class="btn btn-sm btn-warning edit-item-btn edit-btn"
                                data-id="${partyList.id}"
                                data-group-type="${groupType}"
                                data-ledger-section="${ledgerSection}"
                                data-bs-toggle="modal"
                                data-bs-target="#editPartyListModal">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-btn"
                                data-id="${partyList.id}"
                                data-name="${ledgerSection}"
                                data-bs-toggle="modal" data-bs-target="#deletePartyListModal">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                
                return row;
            }

            function attachEventListenersToNewRow(row) {
                // Edit button
                const editBtn = row.querySelector('.edit-btn');
                if (editBtn) {
                    editBtn.addEventListener('click', function() {
                        const id = this.getAttribute('data-id');
                        const groupType = this.getAttribute('data-group-type');
                        const ledgerSection = this.getAttribute('data-ledger-section');
                        
                        document.getElementById('edit_party_list_id').value = id;
                        document.getElementById('edit_group_type').value = groupType;
                        document.getElementById('edit_ledger_section').value = ledgerSection;
                    });
                }

                // Delete button
                const deleteBtn = row.querySelector('.delete-btn');
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', function() {
                        const id = this.getAttribute('data-id');
                        const name = this.getAttribute('data-name');
                        
                        document.getElementById('deletePartyListForm').action = `/party-list/${id}`;
                        document.getElementById('deletePartyListName').textContent = name;
                    });
                }
            }

            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // Phone number validation function
            function validatePhoneNumber(phone) {
                return /^[0-9]{10}$/.test(phone);
            }

            // Initialize phone number validation for loan phone field
            const loanPhoneInput = document.getElementById('loan_phone_no');
            const loanPhoneError = document.getElementById('loan_phone_no_error');

            if (loanPhoneInput) {
                // Real-time phone number input filtering
                loanPhoneInput.addEventListener('input', function(e) {
                    // Remove any non-digit characters
                    let value = e.target.value.replace(/\D/g, '');

                    // Limit to 10 digits
                    if (value.length > 10) {
                        value = value.substring(0, 10);
                    }

                    e.target.value = value;

                    // Visual feedback
                    if (value.length === 0) {
                        loanPhoneInput.classList.remove('is-valid', 'is-invalid');
                        loanPhoneError.classList.add('d-none');
                    } else if (validatePhoneNumber(value)) {
                        loanPhoneInput.classList.remove('is-invalid');
                        loanPhoneInput.classList.add('is-valid');
                        loanPhoneError.classList.add('d-none');
                    } else {
                        loanPhoneInput.classList.remove('is-valid');
                        loanPhoneInput.classList.add('is-invalid');
                        loanPhoneError.classList.remove('d-none');
                    }
                });

                // Handle paste events
                loanPhoneInput.addEventListener('paste', function(e) {
                    setTimeout(() => {
                        let value = e.target.value.replace(/\D/g, '');
                        if (value.length > 10) {
                            value = value.substring(0, 10);
                        }
                        e.target.value = value;

                        // Trigger input event for validation
                        e.target.dispatchEvent(new Event('input'));
                    }, 0);
                });
            }

            // Handle clickable name clicks with loading state using event delegation
            // Temporarily disabled to test link functionality
            /*
            document.addEventListener('click', function(e) {
                if (e.target.closest('.clickable-name')) {
                    const link = e.target.closest('.clickable-name');
                    // Add loading state but don't prevent navigation
                    const originalHtml = link.innerHTML;
                    
                    // Add a small delay to show loading state, then navigate
                    setTimeout(() => {
                        link.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Loading...';
                    }, 50);
                    
                    // Let the browser handle the navigation normally
                    // The page will navigate before the 5-second timeout
                }
            });
            */

            // Handle clickable payment type clicks with loading state using event delegation
            document.addEventListener('click', function(e) {
                if (e.target.closest('.clickable-payment-type')) {
                    const link = e.target.closest('.clickable-payment-type');
                    // Add loading state
                    const originalHtml = link.innerHTML;
                    link.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Loading payment details...';
                    link.style.pointerEvents = 'none';

                    // If navigation fails for some reason, restore original state after 5 seconds
                    setTimeout(() => {
                        link.innerHTML = originalHtml;
                        link.style.pointerEvents = 'auto';
                    }, 5000);
                }
            });

            // Check if sync alert should be shown
            const syncAlert = document.getElementById('syncAlert');
            const showSyncInfoRow = document.getElementById('showSyncInfoRow');
            const showSyncInfoLink = document.getElementById('showSyncInfoLink');

            if (syncAlert && localStorage.getItem('partySyncAlertDismissed') === 'true') {
                syncAlert.style.display = 'none';
                if (showSyncInfoRow) {
                    showSyncInfoRow.style.display = 'block';
                }
            }

            // Handle sync alert dismissal
            const syncAlertCloseBtn = syncAlert?.querySelector('.btn-close');
            if (syncAlertCloseBtn) {
                syncAlertCloseBtn.addEventListener('click', function() {
                    localStorage.setItem('partySyncAlertDismissed', 'true');
                    if (showSyncInfoRow) {
                        showSyncInfoRow.style.display = 'block';
                    }
                });
            }

            // Handle show sync info link click
            if (showSyncInfoLink) {
                showSyncInfoLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    localStorage.removeItem('partySyncAlertDismissed');
                    if (syncAlert) {
                        syncAlert.style.display = 'block';
                    }
                    if (showSyncInfoRow) {
                        showSyncInfoRow.style.display = 'none';
                    }
                });
            }

            // Get DOM elements that will be used across multiple functions
            const groupTypeSelect = document.getElementById('group_type');
            const creditSaleFormSection = document.getElementById('creditSaleFormSection');
            const paymentFormSection = document.getElementById('paymentFormSection');
            const expensesFormSection = document.getElementById('expensesFormSection');
            const expenseEntryFormSection = document.getElementById('expenseEntryFormSection');
            const savePartyListBtn = document.getElementById('savePartyListBtn');
            const saveExpenseEntryBtn = document.getElementById('saveExpenseEntryBtn');

            // Handle Group Type change to show/hide Credit Sale, Payment, and Expenses forms
            groupTypeSelect.addEventListener('change', function() {
                // Hide all sections first
                creditSaleFormSection.style.display = 'none';
                paymentFormSection.style.display = 'none';
                expensesFormSection.style.display = 'none';
                expenseEntryFormSection.style.display = 'none';
                savePartyListBtn.style.display = 'inline-block';
                saveExpenseEntryBtn.style.display = 'none';

                if (this.value === 'Credit Sale') {
                    creditSaleFormSection.style.display = 'block';
                    // Make loan fields required
                    document.getElementById('loan_name').required = true;
                    document.getElementById('loan_company').required = true;
                    document.getElementById('loan_pump_id').required = true;
                    document.getElementById('loan_phone_no').required = true;
                    document.getElementById('loan_address').required = true;

                    // Remove required from payment fields
                    document.getElementById('payment_pump_id').required = false;
                } else if (this.value === 'Payment') {
                    paymentFormSection.style.display = 'block';
                    // Make payment fields required
                    document.getElementById('payment_pump_id').required = true;

                    // Remove required from loan fields
                    document.getElementById('loan_name').required = false;
                    document.getElementById('loan_company').required = false;
                    document.getElementById('loan_pump_id').required = false;
                    document.getElementById('loan_phone_no').required = false;
                    document.getElementById('loan_address').required = false;
                } else if (this.value === 'Expenses') {
                    expensesFormSection.style.display = 'block';
                    
                    // Remove required from loan and payment fields
                    document.getElementById('loan_name').required = false;
                    document.getElementById('loan_company').required = false;
                    document.getElementById('loan_pump_id').required = false;
                    document.getElementById('loan_phone_no').required = false;
                    document.getElementById('loan_address').required = false;
                    document.getElementById('payment_pump_id').required = false;
                } else {
                    // Remove required from all fields for other options
                    document.getElementById('loan_name').required = false;
                    document.getElementById('loan_company').required = false;
                    document.getElementById('loan_pump_id').required = false;
                    document.getElementById('loan_phone_no').required = false;
                    document.getElementById('loan_address').required = false;
                    document.getElementById('payment_pump_id').required = false;

                    // Clear all form fields
                    document.getElementById('loan_name').value = '';
                    document.getElementById('loan_company').value = '';
                    document.getElementById('loan_pump_id').value = '';
                    document.getElementById('loan_phone_no').value = '';
                    document.getElementById('loan_address').value = '';
                    document.getElementById('payment_pump_id').value = '';
                }

                // Clear form fields when switching
                if (this.value !== 'Credit Sale') {
                    document.getElementById('loan_name').value = '';
                    document.getElementById('loan_company').value = '';
                    document.getElementById('loan_pump_id').value = '';
                    document.getElementById('loan_phone_no').value = '';
                    document.getElementById('loan_address').value = '';
                }

                if (this.value !== 'Payment') {
                    document.getElementById('payment_pump_id').value = '';
                }

                if (this.value !== 'Expenses') {
                    document.getElementById('expense_category_id').value = '';
                }
            });

            // Handle expense category selection to show/hide forms
            const expenseCategorySelect = document.getElementById('expense_category_id');

            expenseCategorySelect.addEventListener('change', function() {
                if (this.value) {
                    // Show expense entry form for selected category
                    expenseEntryFormSection.style.display = 'block';
                    savePartyListBtn.style.display = 'none';
                    saveExpenseEntryBtn.style.display = 'inline-block';
                    
                    // Set the category info for expense entry
                    const selectedCategoryName = this.options[this.selectedIndex].text;
                    document.getElementById('expenseEntryTitle').textContent = `Add Expense - ${selectedCategoryName}`;
                    document.getElementById('expense_category_id_inline').value = this.value;
                } else {
                    // Hide expense form
                    expenseEntryFormSection.style.display = 'none';
                    savePartyListBtn.style.display = 'inline-block';
                    saveExpenseEntryBtn.style.display = 'none';
                }
            });

            // Handle expense entry form submission
            saveExpenseEntryBtn.addEventListener('click', function(e) {
                e.preventDefault();

                // Validate expense entry form
                const expenseName = document.getElementById('expense_entry_name_inline').value.trim();
                const expenseAmount = document.getElementById('expense_entry_amount_inline').value;
                const expenseDate = document.getElementById('expense_entry_date_inline').value;
                const expensePaymentMethod = document.getElementById('expense_entry_payment_method_inline').value;
                const expenseCategoryId = document.getElementById('expense_category_id_inline').value;

                if (!expenseName) {
                    showAlert('Please enter expense name.', 'danger', false);
                    document.getElementById('expense_entry_name_inline').focus();
                    return;
                }

                if (!expenseAmount || parseFloat(expenseAmount) <= 0) {
                    showAlert('Please enter a valid expense amount.', 'danger', false);
                    document.getElementById('expense_entry_amount_inline').focus();
                    return;
                }

                if (!expenseDate) {
                    showAlert('Please select expense date.', 'danger', false);
                    document.getElementById('expense_entry_date_inline').focus();
                    return;
                }

                if (!expensePaymentMethod) {
                    showAlert('Please select payment method.', 'danger', false);
                    document.getElementById('expense_entry_payment_method_inline').focus();
                    return;
                }

                // Prepare form data for expense creation
                const formData = new FormData();
                formData.append('name', expenseName);
                formData.append('amount', expenseAmount);
                formData.append('date', expenseDate);
                formData.append('payment_method', expensePaymentMethod);
                formData.append('category_id', expenseCategoryId);
                formData.append('description', document.getElementById('expense_entry_description_inline').value);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                // Submit expense creation
                fetch('{{ route("expenses.store") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Expense creation response:', data); // Debug log
                    console.log('Response party_list_entry data:', data.party_list_entry); // Debug log
                    console.log('Response party_list data:', data.party_list); // Debug log
                    
                    if (data.success) {
                        // Show success message
                        showAlert('Expense added successfully!', 'success');

                        // Add new expense to table
                        // Check both possible response properties
                        const partyListData = data.party_list || data.party_list_entry;
                        if (partyListData) {
                            console.log('Adding expense party list to table:', partyListData); // Debug log
                            addNewPartyListToTable(partyListData);
                        } else {
                            console.warn('No party list data found in response'); // Debug log
                        }

                        // Close modal and reset form
                        bootstrap.Modal.getInstance(document.getElementById('addPartyListModal')).hide();
                        addPartyListForm.reset();
                        creditSaleFormSection.style.display = 'none';
                        paymentFormSection.style.display = 'none';
                        expensesFormSection.style.display = 'none';
                        expenseEntryFormSection.style.display = 'none';
                        savePartyListBtn.style.display = 'inline-block';
                        saveExpenseEntryBtn.style.display = 'none';
                    } else {
                        showAlert(`Error: ${data.message || 'Unknown error'}`, 'danger', false);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Error creating expense. Please try again.', 'danger', false);
                });
            });

            // Handle Add Party List form submission
            const addPartyListForm = document.getElementById('addPartyListForm');
            
            console.log('Form element found:', addPartyListForm); // Debug log
            console.log('Save button element found:', savePartyListBtn); // Debug log
            console.log('Form tag name:', addPartyListForm?.tagName); // Debug log
            console.log('Button tag name:', savePartyListBtn?.tagName); // Debug log
            console.log('Button type attribute:', savePartyListBtn?.type); // Debug log
            
            if (!addPartyListForm) {
                console.error('ERROR: addPartyListForm not found!');
                return;
            }
            
            if (!savePartyListBtn) {
                console.error('ERROR: savePartyListBtn not found!');
                return;
            }
            
            // Test if we can manually trigger form submission
            window.testFormSubmit = function() {
                console.log('Manual form submit test triggered');
                addPartyListForm.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
            };
            
            // Add click event to the Save Party List button to handle form submission manually
            savePartyListBtn.addEventListener('click', function(e) {
                console.log('=== SAVE PARTY LIST BUTTON CLICKED ==='); // Debug log
                console.log('Button type:', this.type); // Debug log
                console.log('Button disabled:', this.disabled); // Debug log
                console.log('Form element:', addPartyListForm); // Debug log
                console.log('Current group type:', groupTypeSelect.value); // Debug log
                console.log('=========================================='); // Debug log
                
                // Manually trigger form submission validation and submission
                handleFormSubmission();
            });

            // Function to handle form submission
            function handleFormSubmission() {
                console.log('=== HANDLING FORM SUBMISSION ==='); // Debug log
                console.log('Form submission started'); // Debug log

                const groupType = groupTypeSelect.value;
                console.log('Group type:', groupType); // Debug log

                // Validate Credit Sale fields if Credit Sale is selected
                if (groupType === 'Credit Sale') {
                    console.log('Validating Credit Sale fields'); // Debug log
                    const loanName = document.getElementById('loan_name').value.trim();
                    const loanCompany = document.getElementById('loan_company').value.trim();
                    const loanPumpId = document.getElementById('loan_pump_id').value;
                    const phoneInput = document.getElementById('loan_phone_no');
                    const phoneError = document.getElementById('loan_phone_no_error');
                    const phoneValue = phoneInput.value.trim();
                    const loanAddress = document.getElementById('loan_address').value.trim();

                    console.log('Loan validation data:', {
                        loanName,
                        loanCompany,
                        loanPumpId,
                        phoneValue,
                        loanAddress
                    }); // Debug log

                    // Validate loan name
                    if (!loanName) {
                        console.log('Validation failed: Missing loan name'); // Debug log
                        showAlert('Please enter the loan name.', 'danger', false);
                        document.getElementById('loan_name').focus();
                        return false;
                    }

                    // Validate loan company
                    if (!loanCompany) {
                        console.log('Validation failed: Missing company name'); // Debug log
                        showAlert('Please enter the company name.', 'danger', false);
                        document.getElementById('loan_company').focus();
                        return false;
                    }

                    // Validate pump selection
                    if (!loanPumpId) {
                        console.log('Validation failed: Missing pump selection'); // Debug log
                        showAlert('Please select a pump.', 'danger', false);
                        document.getElementById('loan_pump_id').focus();
                        return false;
                    }

                    // Validate phone number
                    if (!validatePhoneNumber(phoneValue)) {
                        console.log('Validation failed: Invalid phone number'); // Debug log
                        phoneInput.classList.add('is-invalid');
                        phoneError.classList.remove('d-none');
                        phoneInput.focus();
                        return false;
                    } else {
                        phoneInput.classList.remove('is-invalid');
                        phoneError.classList.add('d-none');
                    }

                    // Validate address
                    if (!loanAddress) {
                        console.log('Validation failed: Missing address'); // Debug log
                        showAlert('Please enter the address.', 'danger', false);
                        document.getElementById('loan_address').focus();
                        return false;
                    }
                    
                    console.log('All Credit Sale validations passed'); // Debug log
                }

                // Validate Expenses if Expenses is selected
                if (groupType === 'Expenses') {
                    const expenseCategoryId = document.getElementById('expense_category_id').value;
                    if (!expenseCategoryId) {
                        showAlert('Please select an expense category.', 'danger', false);
                        return false;
                    }
                    
                    // If expense entry form is visible, validate and include expense data
                    if (expenseEntryFormSection.style.display !== 'none') {
                        const expenseName = document.getElementById('expense_entry_name_inline').value.trim();
                        const expenseAmount = document.getElementById('expense_entry_amount_inline').value;
                        const expenseDate = document.getElementById('expense_entry_date_inline').value;
                        const expensePaymentMethod = document.getElementById('expense_entry_payment_method_inline').value;
                        
                        if (!expenseName) {
                            showAlert('Please enter expense name.', 'danger', false);
                            document.getElementById('expense_entry_name_inline').focus();
                            return false;
                        }
                        
                        if (!expenseAmount || parseFloat(expenseAmount) <= 0) {
                            showAlert('Please enter a valid expense amount.', 'danger', false);
                            document.getElementById('expense_entry_amount_inline').focus();
                            return false;
                        }
                        
                        if (!expenseDate) {
                            showAlert('Please select expense date.', 'danger', false);
                            document.getElementById('expense_entry_date_inline').focus();
                            return false;
                        }
                        
                        if (!expensePaymentMethod) {
                            showAlert('Please select payment method.', 'danger', false);
                            document.getElementById('expense_entry_payment_method_inline').focus();
                            return false;
                        }
                    }
                }

                console.log('All validations passed, proceeding with form submission'); // Debug log

                const formData = new FormData(addPartyListForm);

                let url = '{{ route("party-list.store") }}';

                // If Credit Sale is selected, use a different route to handle both party list and loan creation
                if (groupType === 'Credit Sale') {
                    url = '{{ route("party-list.store-with-loan") }}';
                }
                // If Payment is selected, use a different route to handle payment creation
                else if (groupType === 'Payment') {
                    url = '{{ route("party-list.store-with-payment") }}';
                }
                // If Expenses is selected, use expense creation route
                else if (groupType === 'Expenses') {
                    url = '{{ route("expenses.store") }}';
                    
                    // For expenses, we need to modify the form data to match expense creation format
                    const expenseFormData = new FormData();
                    expenseFormData.append('name', formData.get('expense_name') || '');
                    expenseFormData.append('amount', formData.get('expense_amount') || '');
                    expenseFormData.append('date', formData.get('expense_date') || '');
                    expenseFormData.append('payment_method', formData.get('expense_payment_method') || '');
                    expenseFormData.append('category_id', formData.get('expense_category_id') || '');
                    expenseFormData.append('description', formData.get('expense_description') || '');
                    
                    // Add CSRF token
                    expenseFormData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                    
                    // Replace formData with expenseFormData for expense creation
                    formData = expenseFormData;
                    
                    console.log('Using expense creation route with modified form data'); // Debug log
                    console.log('Expense form data entries:'); // Debug log
                    for (let [key, value] of formData.entries()) {
                        console.log(key, value);
                    }
                }

                console.log('Making request to URL:', url); // Debug log
                console.log('Form data entries:'); // Debug log
                for (let [key, value] of formData.entries()) {
                    console.log(key, value);
                }

                console.log('About to make fetch request...'); // Debug log

                // Disable the submit button to prevent double submission
                savePartyListBtn.disabled = true;
                savePartyListBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';

                fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    console.log('Response status:', response.status); // Debug log
                    console.log('Response ok:', response.ok); // Debug log
                    
                    // Check if response is ok before trying to parse JSON
                    if (!response.ok) {
                        console.error('HTTP error:', response.status, response.statusText);
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data); // Debug log
                    if (data.success) {
                        // Close modal
                        bootstrap.Modal.getInstance(document.getElementById('addPartyListModal')).hide();

                        // Reset form
                        addPartyListForm.reset();
                        creditSaleFormSection.style.display = 'none';
                        paymentFormSection.style.display = 'none';
                        expensesFormSection.style.display = 'none';
                        expenseEntryFormSection.style.display = 'none';
                        savePartyListBtn.style.display = 'inline-block';
                        saveExpenseEntryBtn.style.display = 'none';

                        // Show success message with alert
                        showAlert(data.message || 'Party list created successfully!', 'success');

                        // Add new party list to table (without page reload)
                        console.log('Adding party list to table:', data.data); // Debug log
                        
                        // Handle different response structures
                        let partyListData = data.data;
                        if (data.data && data.data.party_list) {
                            // This is from storeWithLoan or storeWithPayment
                            partyListData = data.data.party_list;
                        }
                        
                        addNewPartyListToTable(partyListData);
                    } else {
                        // Show error message with alert
                        showAlert(`Error creating party list: ${data.message || 'Unknown error'}`, 'danger', false);
                    }
                    
                    // Re-enable the submit button
                    savePartyListBtn.disabled = false;
                    savePartyListBtn.innerHTML = '<i class="fas fa-save me-2"></i>Save Party List';
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    console.error('Error details:', error.message);

                    // Show error message with alert
                    showAlert(`Network error creating party list: ${error.message}. Please try again.`, 'danger', false);
                    
                    // Re-enable the submit button
                    savePartyListBtn.disabled = false;
                    savePartyListBtn.innerHTML = '<i class="fas fa-save me-2"></i>Save Party List';
                });
            }

            // Handle Edit button clicks using event delegation
            document.addEventListener('click', function(e) {
                if (e.target.closest('.edit-btn')) {
                    const button = e.target.closest('.edit-btn');
                    const id = button.getAttribute('data-id');
                    const groupType = button.getAttribute('data-group-type');
                    const ledgerSection = button.getAttribute('data-ledger-section');

                    document.getElementById('edit_party_list_id').value = id;
                    document.getElementById('edit_group_type').value = groupType;
                    document.getElementById('edit_ledger_section').value = ledgerSection;
                }
            });

            // Handle Edit Party List form submission
            const editPartyListForm = document.getElementById('editPartyListForm');
            editPartyListForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const id = document.getElementById('edit_party_list_id').value;

                fetch(`{{ url('party-list') }}/${id}`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'X-HTTP-Method-Override': 'PUT'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal
                        bootstrap.Modal.getInstance(document.getElementById('editPartyListModal')).hide();

                        // Show success message with alert
                        showAlert('Party list updated successfully!', 'warning');

                        // Update the party list in table (without page reload)
                        console.log('Updating party list in table:', data.data); // Debug log
                        updatePartyListInTable(data.data);
                    } else {
                        // Show error message with alert
                        showAlert(`Error updating party list: ${data.message || 'Unknown error'}`, 'danger', false);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);

                    // Show error message with alert
                    showAlert('Error updating party list. Please try again.', 'danger', false);
                });
            });

            // Handle Delete button clicks using event delegation
            document.addEventListener('click', function(e) {
                if (e.target.closest('.delete-btn')) {
                    const button = e.target.closest('.delete-btn');
                    const id = button.getAttribute('data-id');
                    const name = button.getAttribute('data-name');

                    // Set up the delete modal
                    document.getElementById('deletePartyListForm').action = `{{ url('party-list') }}/${id}`;
                    document.getElementById('deletePartyListName').textContent = name;
                }
            });

            // Handle Delete Party List form submission
            const deletePartyListForm = document.getElementById('deletePartyListForm');
            deletePartyListForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);

                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close the modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('deletePartyListModal'));
                        modal.hide();

                        // Show success message in red color for delete
                        showAlert(data.message, 'error');

                        // Remove the row from table (without page reload)  
                        const partyListId = this.action.split('/').pop();
                        removePartyListFromTable(partyListId);
                    } else {
                        // Show error message
                        showAlert(`Error deleting party list: ${data.message || 'Unknown error'}`, 'danger', false);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);

                    // Show error message
                    showAlert('Error deleting party list. Please try again.', 'danger', false);
                });
            });

            // Initialize event listeners for existing rows
            document.querySelectorAll('#partyListTable tbody tr[data-party-list-id]').forEach(row => {
                attachEventListenersToNewRow(row);
            });
        });
    </script>
@endsection

@section('title')
    Party List Management
@endsection

@section('css')
    <style>
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .alert {
            margin-bottom: 1rem;
            border-radius: 0.375rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .btn-sm .fas.fa-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .fade-out {
            transition: opacity 0.3s ease-out;
            opacity: 0;
        }

        .clickable-payment-type {
            transition: all 0.2s ease-in-out;
        }

        .clickable-payment-type:hover {
            transform: translateY(-1px);
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
        }

        .btn-delete-hover:hover {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
            transform: translateY(-1px);
            box-shadow: 0 0.25rem 0.5rem rgba(220, 53, 69, 0.3);
        }

        .expense-amount {
            font-weight: 600;
            color: #6c757d;
        }

        .expense-name {
            background: none !important;
            border: 1px solid #6c757d !important;
            color: #6c757d !important;
        }

        .expense-name:hover {
            background-color: #6c757d !important;
            color: white !important;
        }
    </style>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fs-16 fw-semibold mb-1">Party List Management</h4>
                    <p class="text-muted mb-0">Manage your party lists here</p>
                </div>
                <div class="mt-3 mt-sm-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPartyListModal">
                        <i class="fas fa-plus me-1"></i> Add Party List
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Page Alerts Container -->
    <div id="mainPageAlerts" class="mb-3"></div>

    <!-- Total Outstanding Amount Summary Card -->
    {{-- <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="mb-1">Total Outstanding Amount (Credit Sales)</h5>
                            <h3 class="text-primary mb-0">
                                ₹{{ number_format($partyLists->where('group_type', 'Credit Sale')->sum(function($partyList) {
                                    if ($partyList->loan && $partyList->loan->paymentEntries) {
                                        return $partyList->loan->paymentEntries->where('amount', '>', 0)->sum('amount') -
                                               abs($partyList->loan->paymentEntries->where('amount', '<', 0)->sum('amount'));
                                    }
                                    return 0;
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
    </div> --}}

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show permanent" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Alert container for main page messages -->
    <div id="mainPageAlerts"></div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0" id="partyListTable">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Name</th>
                                    <th>Group Type</th>
                                    <th>Amount</th>
                                    <th>Created Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($partyLists as $index => $partyList)
                                    <tr data-party-list-id="{{ $partyList->id }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            @if($partyList->group_type === 'Credit Sale')
                                                @php
                                                    // Try to get loan using the relationship or fallback method
                                                    $loan = $partyList->loan;
                                                    if (!$loan && method_exists($partyList, 'getLoanInstance')) {
                                                        $loan = $partyList->getLoanInstance();
                                                    }
                                                @endphp
                                                @if($loan)
                                                    <a href="{{ route('party-list.show', $partyList->id) }}"
                                                       class="btn btn-outline-secondary btn-sm clickable-name"
                                                       title="Click to view loan details, payments and loans"
                                                       data-bs-toggle="tooltip"
                                                       data-bs-placement="top">
                                                        <i class="fas fa-user me-1"></i>{{ $loan->name }}
                                                    </a>
                                                @else
                                                    <a href="{{ route('party-list.show', $partyList->id) }}"
                                                       class="btn btn-outline-secondary btn-sm clickable-name"
                                                       title="Click to try to view loan details"
                                                       data-bs-toggle="tooltip"
                                                       data-bs-placement="top">
                                                        <i class="fas fa-user me-1"></i>{{ $partyList->ledger_section }}
                                                        <small class="text-warning ms-1" title="May need manual verification">
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                        </small>
                                                    </a>
                                                @endif
                                            @elseif($partyList->group_type === 'Payment' && $partyList->payment)
                                                <a href="{{ route('party-list.payment-details', $partyList->payment->item_name) }}"
                                                   class="btn btn-outline-info btn-sm clickable-payment-type"
                                                   title="Click to view payment details for {{ $partyList->payment->item_name }}"
                                                   data-bs-toggle="tooltip"
                                                   data-bs-placement="top">
                                                    <i class="fas fa-credit-card me-1"></i>{{ $partyList->payment->item_name }}
                                                    @if($partyList->payment->pump)
                                                        <small class="text-muted ms-1">({{ $partyList->payment->pump->pump_name }})</small>
                                                    @endif
                                                </a>
                                            @elseif($partyList->group_type === 'Payment')
                                                <span class="text-muted">
                                                    <i class="fas fa-credit-card me-1"></i>{{ $partyList->ledger_section }}
                                                    <small class="text-warning ms-1" title="No payment data available">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                    </small>
                                                </span>
                                            @elseif($partyList->group_type === 'Expense' && $partyList->expense)
                                                <span class="btn btn-outline-secondary btn-sm expense-name">
                                                    <i class="fas fa-receipt me-1"></i>{{ $partyList->expense->name }}
                                                    @if($partyList->expense->category)
                                                        <small class="ms-1">({{ $partyList->expense->category->category_name }})</small>
                                                    @endif
                                                </span>
                                            @elseif($partyList->group_type === 'Expense')
                                                <span class="text-muted">
                                                    <i class="fas fa-receipt me-1"></i>{{ $partyList->ledger_section }}
                                                    <small class="text-warning ms-1" title="No expense data available">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                    </small>
                                                </span>
                                            @else
                                                {{ $partyList->ledger_section }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($partyList->group_type === 'Credit Sale')
                                                <span>{{ $partyList->group_type }}</span>
                                            @elseif($partyList->group_type === 'Payment')
                                                <span>{{ $partyList->group_type }}</span>
                                            @elseif($partyList->group_type === 'Expense')
                                                <span>Expense</span>
                                            @elseif($partyList->group_type === 'Cash Sale')
                                                <span>{{ $partyList->group_type }}</span>
                                            @else
                                                <span>{{ $partyList->group_type }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($partyList->group_type === 'Credit Sale')
                                                @php
                                                    // Try to get loan using the relationship or fallback method
                                                    $loan = $partyList->loan;
                                                    if (!$loan && method_exists($partyList, 'getLoanInstance')) {
                                                        $loan = $partyList->getLoanInstance();
                                                    }
                                                    
                                                    if ($loan && $loan->paymentEntries) {
                                                        $totalAmount = $loan->paymentEntries->where('amount', '>', 0)->sum('amount') - abs($loan->paymentEntries->where('amount', '<', 0)->sum('amount'));
                                                    } else {
                                                        $totalAmount = 0;
                                                    }
                                                @endphp
                                                ₹{{ number_format($totalAmount, 2) }}
                                            @elseif($partyList->group_type === 'Payment' && $partyList->payment)
                                                -
                                            @elseif($partyList->group_type === 'Expense' && $partyList->expense)
                                                -₹{{ number_format($partyList->expense->amount, 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $partyList->created_at->setTimezone('Asia/Kolkata')->format('d M Y H:i') }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-warning edit-item-btn edit-btn"
                                                    data-id="{{ $partyList->id }}"
                                                    data-group-type="{{ $partyList->group_type }}"
                                                    data-ledger-section="{{ $partyList->ledger_section }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editPartyListModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-btn"
                                                    data-id="{{ $partyList->id }}"
                                                    data-name="{{ $partyList->ledger_section }}"
                                                    data-bs-toggle="modal" data-bs-target="#deletePartyListModal">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No party lists found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Party List Modal -->
    <div class="modal fade" id="addPartyListModal" tabindex="-1" aria-labelledby="addPartyListModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title text-dark" id="addPartyListModalLabel"><i class="fas fa-plus me-2"></i>Add Party List</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addPartyListForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="group_type" class="form-label">Group Type <span class="text-danger"></span></label>
                                <select class="form-select" id="group_type" name="group_type" required>
                                    <option value="">Select Group Type</option>
                                    <option value="Credit Sale">Credit Sale</option>
                                    <option value="Payment">Payment</option>
                                    <option value="Expenses">Expenses</option>
                                    {{-- <option value="Other">Other</option> --}}
                                </select>
                            </div>
                        </div>

                        <!-- Payment Form Section -->
                        <div id="paymentFormSection" style="display: none;">
                            <hr class="my-4">
                            <h6 class="text-primary mb-3">Payment Details</h6>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="payment_item_name" class="form-label">Item Name <span class="text-danger"></span></label>
                                    <input type="text" class="form-control" id="payment_item_name" name="payment_item_name" placeholder="Enter item name">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="payment_pump_id" class="form-label">Pump name <span class="text-danger"></span></label>
                                    <select class="form-select" id="payment_pump_id" name="payment_pump_id">
                                        <option value="">Select Pump</option>
                                        @if(isset($pumps))
                                            @foreach($pumps as $pump)
                                                <option value="{{ $pump->id }}">{{ $pump->pump_name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Expenses Form Section -->
                        <div id="expensesFormSection" style="display: none;">
                            <hr class="my-4">
                            <h6 class="text-primary mb-3">Select Expense Category</h6>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="expense_category_id" class="form-label">Select Category <span class="text-danger">*</span></label>
                                    <select class="form-select" id="expense_category_id" name="expense_category_id">
                                        <option value="">Select Expense Category</option>
                                        @if(isset($expenseCategories))
                                            @foreach($expenseCategories as $category)
                                                <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Expense Entry Form (hidden by default, shows after category selection) -->
                        <div id="expenseEntryFormSection" style="display: none;">
                            <hr class="my-4">
                            <h6 class="text-success mb-3" id="expenseEntryTitle">Add Expense</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="expense_entry_name_inline" class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="expense_name" id="expense_entry_name_inline" class="form-control" required>
                                    <div class="invalid-feedback d-none" id="expense_entry_inline_warning">
                                        Name cannot exceed 200 characters and should only contain letters, numbers, and spaces.
                                    </div>
                                    <div class="valid-feedback d-none" id="expense_entry_inline_success_icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="expense_entry_date_inline" class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="expense_date" id="expense_entry_date_inline" class="form-control" required value="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="expense_entry_amount_inline" class="form-label">Amount <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="expense_amount" id="expense_entry_amount_inline" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="expense_entry_payment_method_inline" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                    <select name="expense_payment_method" id="expense_entry_payment_method_inline" class="form-control" required>
                                        <option value="">Select</option>
                                        <option value="cash">Cash</option>
                                        <option value="bank">Bank</option>
                                        <option value="cheque">Cheque</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="expense_entry_description_inline" class="form-label">Description (Optional)</label>
                                    <input type="text" name="expense_description" id="expense_entry_description_inline" class="form-control">
                                </div>
                            </div>
                            <input type="hidden" name="expense_category_id_inline" id="expense_category_id_inline">
                        </div>

                        <div id="creditSaleFormSection" style="display: none;">
                            <hr class="my-4">
                            <h6 class="text-primary mb-3">Create New Loan</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="loan_name" class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="loan_name" name="loan_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="loan_company" class="form-label">Company <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="loan_company" name="loan_company" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="loan_pump_id" class="form-label">Select Pump <span class="text-danger">*</span></label>
                                    <select class="form-select" id="loan_pump_id" name="loan_pump_id" required>
                                        <option value="">Select Pump</option>
                                        @if(isset($pumps))
                                            @foreach($pumps as $pump)
                                                <option value="{{ $pump->id }}">{{ $pump->pump_name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="loan_phone_no" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="loan_phone_no" name="loan_phone_no"
                                           pattern="[0-9]{10}" maxlength="10" required
                                           placeholder="Enter phone number">
                                    <div class="invalid-feedback d-none" id="loan_phone_no_error">Please enter a valid 10-digit phone number.</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="loan_address" class="form-label">Address <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="loan_address" name="loan_address" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="savePartyListBtn"><i class="fas fa-save me-2"></i>Save Party List</button>
                            <button type="button" class="btn btn-success" id="saveExpenseEntryBtn" style="display: none;"><i class="fas fa-plus me-2"></i>Add Expense</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Party List Modal -->
    <div class="modal fade" id="editPartyListModal" tabindex="-1" aria-labelledby="editPartyListModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title text-dark" id="editPartyListModalLabel"><i class="fas fa-edit me-2"></i>Edit Party List</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editPartyListForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="edit_party_list_id" name="id">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="edit_group_type" class="form-label">Group Type <span class="text-danger"></span></label>
                                <select class="form-select" id="edit_group_type" name="group_type" required>
                                    <option value="">Select Group Type</option>
                                    <option value="Credit Sale">Credit Sale</option>
                                    <option value="Payment">Payment</option>
                                    <option value="Expenses">Expenses</option>
                                    {{-- <option value="Cash Sale">Cash Sale</option> --}}
                                    {{-- <option value="Other">Other</option> --}}
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="edit_ledger_section" class="form-label">Name <span class="text-danger"></span></label>
                                <input type="text" class="form-control" id="edit_ledger_section" name="ledger_section" required>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning"><i class="fas fa-edit me-2"></i>Update Party List</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Party List Modal -->
    <div class="modal fade" id="deletePartyListModal" tabindex="-1" aria-labelledby="deletePartyListModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deletePartyListModalLabel">Delete Party List</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                        </div>
                        <div>
                            <p class="mb-0">Are you sure you want to delete the party list <strong id="deletePartyListName"></strong>?</p>
                            <p class="text-muted small mb-0">This action cannot be undone.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                    <form id="deletePartyListForm" method="POST" class="d-inline m-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-delete-hover">Delete</button>
                    </form>
                </div>            
            </div>
        </div>
    </div>

    <!-- Show sync info link for dismissed alerts -->
    <div class="row mt-2" id="showSyncInfoRow" style="display: none;">
        <div class="col-12 text-center">
            <small class="text-muted">
                <a href="#" id="showSyncInfoLink" class="text-decoration-none">
                    <i class="fas fa-info-circle me-1"></i>
                    Show synchronization information
                </a>
            </small>
        </div>
    </div>
@endsection