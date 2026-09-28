@extends('layouts.master-without-page-title')
@section('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        // Function to show alerts on main page that auto-dismiss after 5 seconds
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

        // Function to attach event listeners to newly added rows - moved to global scope
        function attachEventListenersToNewRow(row) {
            // Add event listener to the edit button
            const editButton = row.querySelector('.edit-pump-btn');
            if (editButton) {
                editButton.addEventListener('click', function() {
                    const pumpId = this.dataset.id;
                    const pumpName = this.dataset.name;
                    const pumpLocation = this.dataset.location;
                    const pumpStatus = this.dataset.status;
                    const totalNozzles = this.dataset.nozzles;

                    // Populate the modal fields
                    document.getElementById('editPumpForm').action = `/pumps/${pumpId}`;
                    document.getElementById('editPumpName').value = pumpName;
                    document.getElementById('editLocation').value = pumpLocation;
                    document.getElementById('editStatus').value = pumpStatus;
                    document.getElementById('editTotalNozzles').value = totalNozzles;
                });
            }

            // Add event listener to the delete button
            const deleteButton = row.querySelector('.delete-pump-btn');
            if (deleteButton) {
                deleteButton.addEventListener('click', function() {
                    const pumpId = this.dataset.id;
                    const pumpName = this.dataset.name;

                    // Update the delete form action and pump name in the modal
                    const deleteForm = document.getElementById('deletePumpForm');
                    deleteForm.action = `/pumps/${pumpId}`;

                    const deletePumpName = document.getElementById('deletePumpName');
                    deletePumpName.textContent = pumpName;
                });
            }
        }

        // JavaScript to populate the Edit Modal with pump data
        document.addEventListener('DOMContentLoaded', function() {
            // Add input validation for pump name and location
            const pumpNameInputs = document.querySelectorAll('#pump_name, #editPumpName');
            const locationInputs = document.querySelectorAll('#location, #editLocation');
            const totalNozzlesInputs = document.querySelectorAll('#total_nozzles, #editTotalNozzles');

            // Attach event listeners to existing rows
            document.querySelectorAll('tbody tr').forEach(row => {
                attachEventListenersToNewRow(row);
            });

            // Pump name validation (50 characters, no special characters)
            pumpNameInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    // Determine which error container to use based on input ID
                    const errorElementId = this.id === 'editPumpName' ? 'edit_pump_name_error' :
                        'pump_name_error';
                    const errorElement = document.getElementById(errorElementId);
                    if (errorElement) errorElement.textContent = '';

                    // Check for special characters and length without auto-correction
                    const hasSpecialChars = /[^a-zA-Z0-9\s]/.test(this.value);
                    const isOverLimit = this.value.length > 50;

                    // Add validation feedback without auto-correction
                    if (this.value.length > 0 && this.value.length <= 50 && !hasSpecialChars) {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                        if (errorElement) errorElement.textContent = '';
                    } else if (this.value.length > 0) {
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                        if (hasSpecialChars) {
                            if (errorElement) errorElement.textContent =
                                'Pump name can only contain letters, numbers and spaces.';
                        } else if (isOverLimit) {
                            if (errorElement) errorElement.textContent =
                                'Pump name cannot exceed 50 characters.';
                        }
                    } else {
                        this.classList.remove('is-valid', 'is-invalid');
                    }
                });

                // Add paste validation - show error instead of auto-correction
                input.addEventListener('paste', function(e) {
                    setTimeout(() => {
                        const hasSpecialChars = /[^a-zA-Z0-9\s]/.test(this.value);
                        const isOverLimit = this.value.length > 50;
                        const errorElementId = this.id === 'editPumpName' ?
                            'edit_pump_name_error' : 'pump_name_error';
                        const errorElement = document.getElementById(errorElementId);

                        if (hasSpecialChars || isOverLimit) {
                            this.classList.add('is-invalid');
                            if (hasSpecialChars && isOverLimit) {
                                if (errorElement) errorElement.textContent =
                                    'Pump name can only contain letters, numbers and spaces, and cannot exceed 50 characters.';
                            } else if (hasSpecialChars) {
                                if (errorElement) errorElement.textContent =
                                    'Pump name can only contain letters, numbers and spaces.';
                            } else if (isOverLimit) {
                                if (errorElement) errorElement.textContent =
                                    'Pump name cannot exceed 50 characters.';
                            }
                        }
                        this.dispatchEvent(new Event('input'));
                    }, 0);
                });
            });

            // Location validation (50 characters, no special characters)
            locationInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    // Determine which error container to use based on input ID
                    const errorElementId = this.id === 'editLocation' ? 'edit_location_error' :
                        'location_error';
                    const errorElement = document.getElementById(errorElementId);
                    if (errorElement) errorElement.textContent = '';

                    // Check for special characters and length without auto-correction
                    const hasSpecialChars = /[^a-zA-Z0-9\s,.]/.test(this.value);
                    const isOverLimit = this.value.length > 50;

                    // Add validation feedback without auto-correction
                    if (this.value.length > 0 && this.value.length <= 50 && !hasSpecialChars) {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                        if (errorElement) errorElement.textContent = '';
                    } else if (this.value.length > 0) {
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                        if (hasSpecialChars) {
                            if (errorElement) errorElement.textContent =
                                'Location can only contain letters, numbers, spaces, commas and periods.';
                        } else if (isOverLimit) {
                            if (errorElement) errorElement.textContent =
                                'Location cannot exceed 50 characters.';
                        }
                    } else {
                        this.classList.remove('is-valid', 'is-invalid');
                    }
                });


                input.addEventListener('paste', function(e) {
                    setTimeout(() => {
                        const hasSpecialChars = /[^a-zA-Z0-9\s,.]/.test(this.value);
                        const isOverLimit = this.value.length > 50;
                        const errorElementId = this.id === 'editLocation' ?
                            'edit_location_error' : 'location_error';
                        const errorElement = document.getElementById(errorElementId);

                        if (hasSpecialChars || isOverLimit) {
                            this.classList.add('is-invalid');
                            if (hasSpecialChars && isOverLimit) {
                                if (errorElement) errorElement.textContent =
                                    'Location can only contain letters, numbers, spaces, commas and periods, and cannot exceed 50 characters.';
                            } else if (hasSpecialChars) {
                                if (errorElement) errorElement.textContent =
                                    'Location can only contain letters, numbers, spaces, commas and periods.';
                            } else if (isOverLimit) {
                                if (errorElement) errorElement.textContent =
                                    'Location cannot exceed 50 characters.';
                            }
                        }
                        this.dispatchEvent(new Event('input'));
                    }, 0);
                });
            });

            // Total nozzles validation (1-30, cannot be 0)
            totalNozzlesInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    // Remove any non-digit characters
                    this.value = this.value.replace(/[^0-9]/g, '');

                    // Determine which error container to use based on input ID
                    const errorElementId = this.id === 'editTotalNozzles' ?
                        'edit_total_nozzles_error' : 'total_nozzles_error';
                    const errorElement = document.getElementById(errorElementId);
                    if (errorElement) errorElement.textContent = '';

                    // Convert to number and validate range
                    const value = parseInt(this.value);

                    // Add validation feedback without auto-correction
                    if (value >= 1 && value <= 30) {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                        if (errorElement) errorElement.textContent = '';
                    } else if (this.value) {
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                        if (value < 1) {
                            if (errorElement) errorElement.textContent =
                                'Total nozzles must be at least 1.';
                        } else if (value > 30) {
                            if (errorElement) errorElement.textContent =
                                'Total nozzles cannot exceed 30.';
                        }
                    } else {
                        this.classList.remove('is-valid', 'is-invalid');
                    }
                });

                // Prevent setting value to 0 on blur - only set to 1 if completely empty
                input.addEventListener('blur', function(e) {
                    const errorElementId = this.id === 'editTotalNozzles' ?
                        'edit_total_nozzles_error' : 'total_nozzles_error';
                    const errorElement = document.getElementById(errorElementId);
                    if (!this.value) {
                        this.value = '1';
                        if (errorElement) errorElement.textContent = '';
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    }
                });

                // Prevent typing 0 as first character
                input.addEventListener('keydown', function(e) {
                    // If field is empty and user tries to type 0, prevent it
                    if (this.value === '' && e.key === '0') {
                        e.preventDefault();
                        const errorElementId = this.id === 'editTotalNozzles' ?
                            'edit_total_nozzles_error' : 'total_nozzles_error';
                        const errorElement = document.getElementById(errorElementId);
                        if (errorElement) errorElement.textContent =
                            'Total nozzles cannot start with 0. Minimum value is 1.';
                    }
                });
            });

            const editButtons = document.querySelectorAll('.edit-pump-btn');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const pumpId = this.dataset.id;
                    const pumpName = this.dataset.name;
                    const pumpLocation = this.dataset.location;
                    const pumpStatus = this.dataset.status;
                    const totalNozzles = this.dataset.nozzles;

                    // Populate the modal fields
                    document.getElementById('editPumpForm').action = `/pumps/${pumpId}`;
                    document.getElementById('editPumpName').value = pumpName;
                    document.getElementById('editLocation').value = pumpLocation;
                    document.getElementById('editStatus').value = pumpStatus;
                    document.getElementById('editTotalNozzles').value = totalNozzles;
                });
            });

            // Add form validation before submission
            const addForm = document.getElementById('addPumpForm');
            const editForm = document.getElementById('editPumpForm');

            // Add validation for Add Pump form (with alerts)
            if (addForm) {
                addForm.addEventListener('submit', function(e) {
                    const pumpNameInput = this.querySelector('input[name="pump_name"]');
                    const locationInput = this.querySelector('input[name="location"]');
                    const totalNozzlesInput = this.querySelector('input[name="total_nozzles"]');

                    // Validate pump name
                    if (pumpNameInput && (pumpNameInput.value.length === 0 || pumpNameInput.value.length >
                            50)) {
                        e.preventDefault();
                        pumpNameInput.classList.add('is-invalid');
                        alert('Pump name is required and must be 50 characters or less');
                        pumpNameInput.focus();
                        return false;
                    }

                    // Validate location
                    if (locationInput && (locationInput.value.length === 0 || locationInput.value.length >
                            50)) {
                        e.preventDefault();
                        locationInput.classList.add('is-invalid');
                        alert('Location is required and must be 50 characters or less');
                        locationInput.focus();
                        return false;
                    }

                    // Validate total nozzles
                    const nozzleValue = parseInt(totalNozzlesInput.value);
                    if (totalNozzlesInput && (isNaN(nozzleValue) || nozzleValue < 1 || nozzleValue > 30)) {
                        e.preventDefault();
                        totalNozzlesInput.classList.add('is-invalid');
                        alert('Total nozzles must be between 1 and 30');
                        totalNozzlesInput.focus();
                        return false;
                    }
                });
            }

            // Add validation for Edit Pump form (without alerts - just show error messages)
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    const pumpNameInput = this.querySelector('input[name="pump_name"]');
                    const locationInput = this.querySelector('input[name="location"]');
                    const totalNozzlesInput = this.querySelector('input[name="total_nozzles"]');

                    let hasErrors = false;

                    // Clear previous error messages
                    const pumpNameError = document.getElementById('edit_pump_name_error');
                    const locationError = document.getElementById('edit_location_error');
                    const totalNozzlesError = document.getElementById('edit_total_nozzles_error');

                    if (pumpNameError) pumpNameError.textContent = '';
                    if (locationError) locationError.textContent = '';
                    if (totalNozzlesError) totalNozzlesError.textContent = '';

                    // Validate pump name
                    if (pumpNameInput && (pumpNameInput.value.length === 0 || pumpNameInput.value.length >
                            50)) {
                        e.preventDefault();
                        pumpNameInput.classList.add('is-invalid');
                        if (pumpNameError) pumpNameError.textContent =
                            'Pump name is required and must be 50 characters or less';
                        pumpNameInput.focus();
                        hasErrors = true;
                    }

                    // Validate location
                    if (locationInput && (locationInput.value.length === 0 || locationInput.value.length >
                            50)) {
                        e.preventDefault();
                        locationInput.classList.add('is-invalid');
                        if (locationError) locationError.textContent =
                            'Location is required and must be 50 characters or less';
                        if (!hasErrors) locationInput.focus();
                        hasErrors = true;
                    }

                    // Validate total nozzles
                    const nozzleValue = parseInt(totalNozzlesInput.value);
                    if (totalNozzlesInput && (isNaN(nozzleValue) || nozzleValue < 1 || nozzleValue > 30)) {
                        e.preventDefault();
                        totalNozzlesInput.classList.add('is-invalid');
                        if (totalNozzlesError) totalNozzlesError.textContent =
                            'Total nozzles must be between 1 and 30';
                        if (!hasErrors) totalNozzlesInput.focus();
                        hasErrors = true;
                    }

                    if (hasErrors) {
                        return false;
                    }
                });
            }
        });

        document.getElementById('savePumpBtn').addEventListener('click', function() {
            const form = document.getElementById('addPumpForm');
            const formData = new FormData(form);

            // Clear previous error messages
            document.getElementById('pump_name_error').textContent = '';
            document.getElementById('location_error').textContent = '';
            document.getElementById('total_nozzles_error').textContent = '';

            // Client-side validation before sending
            const pumpNameInput = form.querySelector('input[name="pump_name"]');
            const locationInput = form.querySelector('input[name="location"]');
            const totalNozzlesInput = form.querySelector('input[name="total_nozzles"]');

            let hasErrors = false;

            // Validate pump name
            if (!pumpNameInput.value || pumpNameInput.value.length > 50) {
                document.getElementById('pump_name_error').textContent = 'Pump name is required and must be 50 characters or less';
                pumpNameInput.classList.add('is-invalid');
                hasErrors = true;
            }

            // Validate location
            if (!locationInput.value || locationInput.value.length > 50) {
                document.getElementById('location_error').textContent = 'Location is required and must be 50 characters or less';
                locationInput.classList.add('is-invalid');
                hasErrors = true;
            }

            // Validate total nozzles
            const nozzleValue = parseInt(totalNozzlesInput.value);
            if (isNaN(nozzleValue) || nozzleValue < 1 || nozzleValue > 30) {
                document.getElementById('total_nozzles_error').textContent = 'Total nozzles must be between 1 and 30';
                totalNozzlesInput.classList.add('is-invalid');
                hasErrors = true;
            }

            if (hasErrors) {
                return; // Don't submit if there are validation errors
            }

            // Show loading state
            const saveButton = this;
            const originalButtonText = saveButton.innerHTML;
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(async response => {
                const data = await response.json();
                
                if (!response.ok) {
                    // If the response is not ok but contains validation errors
                    if (data.errors) {
                        throw { type: 'validation', errors: data.errors };
                    }
                    // For other types of errors
                    throw { type: 'server', message: data.message || 'Server error occurred' };
                }
                
                return data;
            })
            .then(data => {
                if (data.errors) {
                    // Display validation errors
                    if (data.errors.pump_name) {
                        document.getElementById('pump_name_error').textContent = data.errors.pump_name[0];
                    }
                    if (data.errors.location) {
                        document.getElementById('location_error').textContent = data.errors.location[0];
                    }
                    if (data.errors.total_nozzles) {
                        document.getElementById('total_nozzles_error').textContent = data.errors.total_nozzles[0];
                    }
                } else if (data.success) {
                    // Close the modal
                    const addPumpModal = bootstrap.Modal.getInstance(document.getElementById('addPumpModal'));
                    addPumpModal.hide();
                    
                    // Show success alert that auto-dismisses after 5 seconds
                    showMainPageAlert('success', data.success);

                    // Add the new pump to the table
                    const tbody = document.querySelector('tbody');
                    const newRow = document.createElement('tr');
                    
                    // Get the current row count for the S.No
                    const rowCount = tbody.querySelectorAll('tr:not([style*="display: none"])').length + 1;
                    
                    newRow.innerHTML = `
                        <td>${rowCount}</td>
                        <td>${data.pump.pump_name}</td>
                        <td>${data.pump.location}</td>
                        <td>${data.pump.total_nozzles}</td>
                        <td>
                            <span class="badge bg-${data.pump.status === 'active' ? 'success' : 'danger'}">
                                ${data.pump.status.charAt(0).toUpperCase() + data.pump.status.slice(1)}
                            </span>
                        </td>
                        <td>${new Date(data.pump.created_at).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'})}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-warning edit-pump-btn"
                                data-id="${data.pump.id}"
                                data-name="${data.pump.pump_name}"
                                data-location="${data.pump.location}"
                                data-status="${data.pump.status}"
                                data-nozzles="${data.pump.total_nozzles}"
                                data-bs-toggle="modal"
                                data-bs-target="#editPumpModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger delete-pump-btn"
                                data-id="${data.pump.id}"
                                data-name="${data.pump.pump_name}"
                                data-bs-toggle="modal"
                                data-bs-target="#deletePumpModal">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;

                    // Add the new row at the beginning of the table
                    if (tbody.firstChild) {
                        tbody.insertBefore(newRow, tbody.firstChild);
                    } else {
                        tbody.appendChild(newRow);
                    }

                    // Attach event listeners to the new row's buttons
                    attachEventListenersToNewRow(newRow);
                    
                    // Reset the form
                    form.reset();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Only show error alert if it's not a validation error
                if (error.type !== 'validation') {
                    // Show error alert that auto-dismisses after 5 seconds
                    showMainPageAlert('error', error.message || 'An error occurred while saving the pump. Please try again.');
                }
            })
            .finally(() => {
                // Reset button state
                saveButton.disabled = false;
                saveButton.innerHTML = originalButtonText;
            });
        });

        // Delete pump confirmation
        document.querySelectorAll('.delete-pump-btn').forEach(button => {
            button.addEventListener('click', function() {
                const pumpId = this.dataset.id;
                const pumpName = this.dataset.name;

                // Update the delete form action and pump name in the modal
                const deleteForm = document.getElementById('deletePumpForm');
                deleteForm.action = `/pumps/${pumpId}`;

                const deletePumpName = document.getElementById('deletePumpName');
                deletePumpName.textContent = pumpName;
            });
        });

        // Button in the modal already submits the form, no need for extra JavaScript
    </script>
@endsection
@section('title')
    Pump Management
@endsection
@section('css')

<style>

/* =========================================
   PUMP MANAGEMENT PAGE
   ========================================= */

.pump-management-page {

      min-height: calc(100vh - 70px);

    width: calc(100% + 40px);
    margin-left: -20px;
    margin-right: -20px;
margin-top: -30px;
    padding: 25px 25px 50px 25px;

    background-image: url("{{ asset('build/images/dashboard-bg.png') }}");
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;

    box-sizing: border-box;
}

/* Light overlay */
/* .pump-management-page::before {
    content: "";
    position: absolute;
    inset: 0;
    background: rgba(235, 250, 246, 0.72);
    z-index: 0;
} */

/* Everything above background */
.pump-management-page > * {
    position: relative;
    z-index: 1;
}


/* =========================================
   PAGE TITLE
   ========================================= */
/* Pump Management Heading */
/* =========================================
   PUMP MANAGEMENT HEADING
   ========================================= */
.pump-management-page .page-title-box h4 {
    font-size: 30px !important;
    font-weight: 900 !important;
    color: #073b4c !important;
    margin-bottom: 6px !important;
}

.pump-management-page .pump-green-text {
    color: #20b486 !important;
}

.pump-management-page .page-title-box p {
    font-size: 16px !important;
    font-weight: 600 !important;
    color: #477080 !important;
    margin-bottom: 0 !important;
}


/* =========================================
   ADD NEW PUMP BUTTON
   ========================================= */

.pump-management-page .add-pump-btn {
    background: #20b486 !important;
    border: none !important;
    color: #ffffff !important;

    font-size: 14px;
    font-weight: 600;

    padding: 9px 18px;
    border-radius: 8px;

    box-shadow: 0 5px 15px rgba(32, 180, 134, 0.20);
}

.pump-management-page .add-pump-btn:hover {
    background: #159c73 !important;
    color: #ffffff !important;
}


/* =========================================
   MAIN TABLE CARD
   ========================================= */

.pump-management-page .pump-table-card {
    background: rgba(255, 255, 255, 0.96);

    border: 1px solid rgba(32, 180, 134, 0.12);

    border-radius: 16px;

    box-shadow:
        0 10px 30px rgba(20, 80, 70, 0.08);

    overflow: hidden;
}


/* =========================================
   TABLE
   ========================================= */

.pump-management-page table {
    margin-bottom: 0;
}

.pump-management-page thead {
    background: #eaf8f4;
}

.pump-management-page thead th {
    color: #123f45 !important;

    font-size: 13px;
    font-weight: 700;

    padding: 15px 14px;

    border-bottom: 1px solid #cfe9e1;
}

.pump-management-page tbody td {
    color: #294f58 !important;

    font-size: 14px;
    font-weight: 500;

    padding: 15px 14px;

    border-bottom: 1px solid #e8f0ee;
    vertical-align: middle;
}

.pump-management-page tbody tr:hover {
    background: #f5fbf9;
}


/* =========================================
   STATUS
   ========================================= */

.pump-management-page .pump-status {
    background: #dff7ef !important;
    color: #159c73 !important;

    font-size: 12px;
    font-weight: 600;

    padding: 6px 11px;

    border-radius: 20px;
}


/* =========================================
   ACTION BUTTONS
   ========================================= */

.pump-management-page .edit-pump-btn {
    width: 34px;
    height: 34px;

    padding: 0;

    border: none !important;
    border-radius: 7px;

    background: #ffe08a !important;
    color: #735900 !important;

    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.pump-management-page .edit-pump-btn:hover {
    background: #ffcf4a !important;
}


.pump-management-page .delete-pump-btn {
    width: 34px;
    height: 34px;

    padding: 0;

    border: none !important;
    border-radius: 7px;

    background: #ff6b6b !important;
    color: #ffffff !important;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    margin-left: 5px;
}

.pump-management-page .delete-pump-btn:hover {
    background: #f04444 !important;
}


/* =========================================
   EMPTY TABLE
   ========================================= */

.pump-management-page .no-pumps {
    color: #607d84 !important;
    font-size: 14px;
    padding: 30px !important;
}


/* =========================================
   MODALS
   ========================================= */

.pump-management-page .modal-content {
    border: none;
    border-radius: 14px;

    box-shadow: 0 20px 50px rgba(0,0,0,0.15);
}

.pump-management-page .modal-header {
    border-bottom: 1px solid #e5efec;
}

.pump-management-page .modal-title {
    color: #123f45 !important;
    font-weight: 700;
}

.pump-management-page .form-label {
    color: #294f58 !important;
    font-size: 14px;
    font-weight: 600;
}

.pump-management-page .form-control,
.pump-management-page .form-select {
    border: 1px solid #d7e8e3;
    border-radius: 8px;
}

.pump-management-page .form-control:focus,
.pump-management-page .form-select:focus {
    border-color: #20b486;
    box-shadow: 0 0 0 0.15rem rgba(32,180,134,0.15);
}


/* =========================================
   MOBILE
   ========================================= */

@media (max-width: 768px) {

    .pump-management-page {
        padding: 15px;
    }

    .pump-management-page .page-title-box {
        display: block !important;
    }

    .pump-management-page .add-pump-btn {
        margin-top: 12px;
    }

}

</style>

@endsection

@section('content')

<div class="pump-management-page fuel-pump-bg">

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fs-16 fw-semibold mb-1">
    PUMP <span class="pump-green-text">MANAGEMENT :</span>
</h4>
                    <p class="text-muted mb-0">Manage your petrol pumps here</p>
                </div>
                <div class="mt-3 mt-sm-0">
                    <!-- Button to trigger Add Pump modal -->
                   <button type="button" 
        class="btn add-pump-btn" 
        data-bs-toggle="modal" 
        data-bs-target="#addPumpModal">

    <i class="fas fa-plus me-1"></i>
    Add New Pump

</button>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
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
    
    <!-- Alert container for main page messages -->
    <div id="mainPageAlerts"></div>

    <div class="row">
        <div class="col-12">
           <div class="card pump-table-card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Pump Name</th>
                                    <th>Location</th>
                                    <th>Total Nozzles</th>
                                    <th>Status</th>
                                    <th>Created On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pumps-> reverse() as $key => $pump)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $pump->pump_name }}</td>
                                        <td>{{ $pump->location }}</td>
                                        <td>{{ $pump->total_nozzles }}</td>
                                        <td>
<span class="badge pump-status">
    {{ ucfirst($pump->status) }}
</span>
                                        </td>
                                        <td>{{ $pump->created_at->format('d M Y') }}</td>
                                        <td>
                                            <!-- Edit Button -->
                                            <button type="button" class="btn btn-sm btn-warning edit-pump-btn"
                                                data-id="{{ $pump->id }}" data-name="{{ $pump->pump_name }}"
                                                data-location="{{ $pump->location }}" data-status="{{ $pump->status }}"
                                                data-nozzles="{{ $pump->total_nozzles }}" data-bs-toggle="modal"
                                                data-bs-target="#editPumpModal">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-sm btn-danger delete-pump-btn"
                                                data-id="{{ $pump->id }}" data-name="{{ $pump->pump_name }}"
                                                data-bs-toggle="modal" data-bs-target="#deletePumpModal">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No pumps found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Pump Modal -->
    <div class="modal fade" id="addPumpModal" tabindex="-1" aria-labelledby="addPumpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md"> <!-- Changed to modal-md for a smaller modal -->
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title text-dark" id="addPumpModalLabel"><i class="fas fa-gas-pump me-2"></i>Add New
                        Pump</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addPumpForm" action="{{ route('pumps.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <!-- Pump Name -->
                            <div class="col-md-12 mb-3">
                                <label for="pump_name" class="form-label">Pump Name</label>
                                <input type="text" class="form-control" id="pump_name" name="pump_name"
                                    placeholder="Enter pump name" required>
                                <span class="text-danger" id="pump_name_error"></span> <!-- Error message container -->
                            </div>
                        </div>


                        
                        <div class="row">
                            <!-- Location -->
                            <div class="col-md-12 mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control @error('location') is-invalid @enderror"
                                    id="location" name="location" value="{{ old('location') }}"
                                    placeholder="Enter location" required>
                                <span class="text-danger" id="location_error"></span> <!-- Error message container -->
                                @error('location')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Total Nozzles -->
                            <div class="col-md-6 mb-3">
                                <label for="total_nozzles" class="form-label">Total Nozzles</label>
                                <input type="number" class="form-control @error('total_nozzles') is-invalid @enderror"
                                    id="total_nozzles" name="total_nozzles" value="{{ old('total_nozzles', 1) }}"
                                    placeholder="Enter total nozzles" required>
                                <span class="text-danger" id="total_nozzles_error"></span> <!-- Error message container -->
                                @error('total_nozzles')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div class="col-md-6 mb-3">
                                {{-- <label for="status" class="form-label">Status</label> --}}
                                <select class="form-select @error('status') is-invalid @enderror" id="status"
                                    name="status" required>
                                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active
                                    </option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive
                                    </option>
                                </select>
                                @error('status')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-secondary me-2"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="button" id="savePumpBtn" class="btn btn-primary"><i
                                    class="fas fa-save me-2"></i>Save Pump</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Pump Modal -->
    <div class="modal fade" id="editPumpModal" tabindex="-1" aria-labelledby="editPumpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title text-dark" id="editPumpModalLabel"><i class="fas fa-edit me-2"></i>Edit Pump
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editPumpForm" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <!-- Pump Name -->
                            <div class="col-md-12 mb-3">
                                <label for="editPumpName" class="form-label">Pump Name</label>
                                <input type="text" class="form-control @error('pump_name') is-invalid @enderror"
                                    id="editPumpName" name="pump_name" placeholder="Enter pump name" required>
                                <span class="text-danger" id="edit_pump_name_error"></span>
                                <!-- Error message container -->
                                @error('pump_name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Location -->
                            <div class="col-md-12 mb-3">
                                <label for="editLocation" class="form-label">Location</label>
                                <input type="text" class="form-control @error('location') is-invalid @enderror"
                                    id="editLocation" name="location" placeholder="Enter location" required>
                                <span class="text-danger" id="edit_location_error"></span>
                                <!-- Error message container -->
                                @error('location')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Total Nozzles -->
                            <div class="col-md-6 mb-3">
                                <label for="editTotalNozzles" class="form-label">Total Nozzles</label>
                                <input type="number" class="form-control @error('total_nozzles') is-invalid @enderror"
                                    id="editTotalNozzles" name="total_nozzles" placeholder="Enter total nozzles"
                                    required>
                                <span class="text-danger" id="edit_total_nozzles_error"></span>
                                <!-- Error message container -->
                                @error('total_nozzles')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div class="col-md-6 mb-3">
                                <label for="editStatus" class="form-label">Status</label>
                                <select class="form-select @error('status') is-invalid @enderror" id="editStatus"
                                    name="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                @error('status')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-secondary me-2"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning"><i class="fas fa-edit me-2"></i>Update
                                Pump</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deletePumpModal" tabindex="-1" aria-labelledby="deletePumpModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deletePumpModalLabel">Delete Pump</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                        </div>
                        <div>
                            <p class="mb-0">Are you sure you want to delete pump <strong id="deletePumpName"></strong>?
                            </p>
                            <p class="text-muted small mb-0">This action cannot be undone.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                    <form id="deletePumpForm" method="POST" class="d-inline m-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-delete-hover">Delete</button>
                    </form>
                </div>            
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Initialize toasts
        document.addEventListener('DOMContentLoaded', function() {
            var toastElList = [].slice.call(document.querySelectorAll('.toast'));
            toastElList.map(function(toastEl) {
                return new bootstrap.Toast(toastEl);
            });
            
            // Auto-dismiss server-side alerts after 5 seconds
            const serverAlerts = document.querySelectorAll('.alert:not(#mainPageAlerts .alert)');
            serverAlerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>

    @if ($errors->any())
        <script>
            var addPumpModal = new bootstrap.Modal(document.getElementById('addPumpModal'));
            addPumpModal.show();

            // Display a pop-up for duplicate pump names
            @if ($errors->has('pump_name'))
                alert("{{ $errors->first('pump_name') }}");
            @endif
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Delete Pump Modal functionality
            const deleteButtons = document.querySelectorAll('.delete-pump-btn');
            const deleteForm = document.getElementById('deletePumpForm');
            const deletePumpName = document.getElementById('deletePumpName');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const pumpId = this.dataset.id;
                    const pumpName = this.dataset.name;

                    deleteForm.action = `/pumps/${pumpId}`;
                    deletePumpName.textContent = pumpName;
                });
            });
        });
    </script>
    </div>
@endsection

<!-- Delete Confirmation Modal -->
