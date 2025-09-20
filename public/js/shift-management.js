// Shift management functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize bootstrap datepicker for shifts filter
    if ($("#shifts_date_picker").length > 0) {
        $("#shifts_date_picker").datepicker({
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
            document.getElementById('formatted_shift_date').value = formattedDate;
            
            // Submit the form when a date is selected
            $("#shiftsDateFilterForm").submit();
        });
    }
    
    // Get time input elements
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const editStartTimeInput = document.getElementById('edit_start_time');
    const editEndTimeInput = document.getElementById('edit_end_time');

    // Function to format time in MySQL format (HH:mm:ss)
    function formatTime(timeInput) {
        const time = timeInput.value;
        if (!time) return '00:00:00';

        // If time doesn't include seconds, add them
        if (time.length === 5) {
            return time + ':00';
        }
        return time;
    }    // Function to update a shift row in the table after edit
    function updateShiftRow(shiftId, formData) {
        // Try to find the row by ID first
        let shiftRow = document.getElementById(`shift-row-${shiftId}`);
        
        // If not found by ID, try finding by the button's data attribute
        if (!shiftRow) {
            const button = document.querySelector(`button[data-shift="${shiftId}"]`);
            if (button) {
                shiftRow = button.closest('tr');
            }
        }
        
        if (!shiftRow) {
            console.error('Could not find shift row for ID:', shiftId);
            return;
        }

        // Extract the updated values
        const pumpId = formData.get('pump_id');
        const pumpUserId = formData.get('pump_user_id');
        const nozzleId = formData.get('nozzle_id');
        const shiftName = formData.get('shift_name');
        const startTime = formData.get('start_time');
        const endTime = formData.get('end_time');
        const startDate = formData.get('start_date');
        const endDate = formData.get('end_date');
        const isActive = formData.get('is_active') === '1' ? true : false;

        // Apply highlight effect to the row
        shiftRow.classList.add('highlight-update');
        setTimeout(() => {
            shiftRow.classList.remove('highlight-update');
        }, 1500);

        // Update the row content
        const columns = shiftRow.querySelectorAll('td');
        
        // Update status column if present
        const statusColumn = Array.from(columns).find(col => col.textContent.trim() === 'Active' || col.textContent.trim() === 'Inactive');
        if (statusColumn) {
            statusColumn.textContent = isActive ? 'Active' : 'Inactive';
            // Update row styling based on active status
            if (isActive) {
                shiftRow.classList.remove('table-secondary');
            } else {
                shiftRow.classList.add('table-secondary');
            }
        }

        // Update the start time and end time columns if present
        const startTimeCol = columns[5]; // Assuming start time is the 6th column (index 5)
        const endTimeCol = columns[6];   // Assuming end time is the 7th column (index 6)
        const startDateCol = columns[7]; // Assuming start date is the 8th column (index 7)
        const endDateCol = columns[8];   // Assuming end date is the 9th column (index 8)
        
        if (startTimeCol && startTime) {
            const formattedTime = startTime.includes(':') ? startTime : startTime + ':00';
            startTimeCol.textContent = formattedTime;
        }
        
        if (endTimeCol && endTime) {
            const formattedTime = endTime.includes(':') ? endTime : endTime + ':00';
            endTimeCol.textContent = formattedTime;
        }
        
        if (startDateCol && startDate) {
            // Format the date as DD-MM-YYYY
            const dateParts = startDate.split('-');
            startDateCol.textContent = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
        }
        
        if (endDateCol && endDate) {
            // Format the date as DD-MM-YYYY
            const dateParts = endDate.split('-');
            endDateCol.textContent = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
        }

        // Update the shift name column if present
        const shiftNameCol = columns[4]; // Assuming shift name is the 5th column (index 4)
        if (shiftNameCol && shiftName) {
            shiftNameCol.textContent = shiftName;
        }
        
        // Also update the data attributes on the edit button
        const editButton = shiftRow.querySelector('.edit-shift');
        if (editButton) {
            editButton.setAttribute('data-shift-name', shiftName);
            editButton.setAttribute('data-start-time', startTime ? startTime.substring(0, 5) : '');
            editButton.setAttribute('data-end-time', endTime ? endTime.substring(0, 5) : '');
            editButton.setAttribute('data-start-date', startDate);
            editButton.setAttribute('data-end-date', endDate);
            editButton.setAttribute('data-active', isActive ? '1' : '0');
            
            // If the shift is now inactive, disable the edit button
            if (!isActive) {
                editButton.disabled = true;
                editButton.classList.remove('btn-warning');
                editButton.classList.add('btn-secondary');
                editButton.title = 'Cannot edit inactive shifts';
                editButton.innerHTML = 'Inactive';
                editButton.removeAttribute('data-bs-toggle');
                editButton.removeAttribute('data-bs-target');
            }
        }
        
        // Update the updated_at column (last column before actions)
        const updatedAtCol = columns[columns.length - 2]; // Assuming updated_at is the second-last column
        if (updatedAtCol) {
            const currentDate = new Date();
            const day = currentDate.getDate().toString().padStart(2, '0');
            const month = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][currentDate.getMonth()];
            const year = currentDate.getFullYear();
            updatedAtCol.textContent = `${day} ${month} ${year}`;
        }
    }

    // Set default time values with seconds
    if (startTimeInput) startTimeInput.value = '00:00:00';
    if (endTimeInput) endTimeInput.value = '00:00:00';
    if (editStartTimeInput) editStartTimeInput.value = '00:00:00';
    if (editEndTimeInput) editEndTimeInput.value = '00:00:00';    // Handle delete shift functionality
    const deleteShiftButtons = document.querySelectorAll('.delete-shift-btn');
    const deleteShiftForm = document.getElementById('deleteShiftForm');
    const deleteShiftName = document.getElementById('deleteShiftName');
    const deleteShiftPumpName = document.getElementById('deleteShiftPumpName');

    if (deleteShiftButtons.length > 0 && deleteShiftForm) {
        deleteShiftButtons.forEach(button => {
            button.addEventListener('click', function() {
                const shiftId = this.getAttribute('data-shift-id');
                const shiftName = this.getAttribute('data-shift-name') || 'this shift';
                const pumpName = this.getAttribute('data-pump-name') || 'Unknown pump';
                
                // Update the form action URL
                deleteShiftForm.action = `/shifts/${shiftId}`;
                
                // Update the modal text with shift details
                if (deleteShiftName) deleteShiftName.textContent = shiftName;
                if (deleteShiftPumpName) deleteShiftPumpName.textContent = pumpName;
                
                // Store the row reference for removal after deletion
                deleteShiftForm.setAttribute('data-row', this.closest('tr').getAttribute('id'));
            });
        });
        
        // Handle form submission with AJAX
        deleteShiftForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formAction = this.action;
            const rowId = this.getAttribute('data-row');
            
            // Send delete request via AJAX
            fetch(formAction, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-HTTP-Method-Override': 'DELETE',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err));
                }
                return response.json();
            })
            .then(data => {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteShiftModal'));
                if (modal) {
                    modal.hide();
                }
                  // Get the shift ID
                const shiftId = formAction.split('/').pop();
                
                // Try to find the row by ID first
                let row = document.getElementById(`shift-row-${shiftId}`);
                
                // If not found by ID, try finding by the button's data attribute
                if (!row) {
                    const button = document.querySelector(`button[data-shift-id="${shiftId}"]`);
                    if (button) {
                        row = button.closest('tr');
                    }
                }
                
                if (row) {
                    // Add fade-out effect before removal
                    row.style.transition = 'opacity 0.5s ease';
                    row.style.opacity = '0';
                    
                    setTimeout(() => {
                        row.remove();
                        
                        // Update row numbering
                        document.querySelectorAll('table tbody tr').forEach((tr, index) => {
                            const firstCell = tr.querySelector('td:first-child');
                            if (firstCell) firstCell.textContent = index + 1;
                        });
                    }, 500);
                }
                
                // Show success message - toastr notification disabled for delete operations
                // toastr.success(data.message || 'Shift deleted successfully');
                
                // Also add an alert message above the shifts section
                $('#shiftToastContainer').html(`
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-trash me-2"></i>
                        ${data.message || 'Shift deleted successfully'}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
            })
            .catch(error => {
                console.error('Error:', error);
                const errorMsg = error.message || 'An error occurred while deleting the shift. Please try again.';
                
                // Show error in toastr
                toastr.error(errorMsg);
                
                // Also add an alert message above the shifts section
                $('#shiftToastContainer').html(`
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        ${errorMsg}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
            });
        });
    }

    // Handle bulk shift form submission
    const bulkShiftForm = document.getElementById('bulkShiftForm');
    if (bulkShiftForm) {
        bulkShiftForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            // Format the times in MySQL format
            const startTime = formatTime(startTimeInput);
            const endTime = formatTime(endTimeInput);

            // Update form data with formatted times
            formData.set('start_time', startTime);
            formData.set('end_time', endTime);

            // Get the date values
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;

            // Update form data with dates
            formData.set('start_date', startDate);
            formData.set('end_date', endDate);

            const pumps = formData.getAll('pumps[]');
            const pumpUsers = formData.getAll('pump_users[]');
            const nozzles = formData.getAll('nozzles[]');

            if (pumps.length === 0 || pumpUsers.length === 0 || nozzles.length === 0) {
                alert('Please select at least one item from each category (Pumps, Pump Users, and Nozzles)');
                return;
            }

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err));
                }
                return response.json();
            })
            .then(data => {
                // Show success message - toastr notification disabled for create operations
                // toastr.success(data.message || 'Shifts created successfully');
                
                // Also add an alert message above the shifts section
                $('#shiftToastContainer').html(`
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        ${data.message || 'Shifts created successfully'}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
                
                // Reset the form
                document.getElementById('bulkShiftForm').reset();
                
                // Delay reload to allow toast to be seen
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            })
            .catch(error => {
                console.error('Error:', error);
                const errorMsg = error.message || 'An error occurred while creating the shifts. Please try again.';
                
                // Show error message using toastr
                toastr.error(errorMsg);
                
                // Also add an alert message above the shifts section
                $('#shiftToastContainer').html(`
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        ${errorMsg}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
            });
        });
    }    
    
    // Handle edit shift functionality
    const editShiftButtons = document.querySelectorAll('.edit-shift');
    const editShiftForm = document.getElementById('editShiftForm');

    if (editShiftButtons.length > 0 && editShiftForm) {        // Disable edit buttons for inactive shifts
        editShiftButtons.forEach(button => {
            const isActive = button.getAttribute('data-active');
            if (isActive === '0') {
                button.disabled = true;
                button.classList.remove('btn-warning');
                button.classList.add('btn-secondary');
                button.title = 'Cannot edit inactive shifts';
                button.innerHTML = 'Inactive';
                // Remove modal trigger attributes
                button.removeAttribute('data-bs-toggle');
                button.removeAttribute('data-bs-target');
            }
        });
        
        editShiftButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Skip processing if button is disabled (inactive shift)
                if (this.disabled) {
                    return;
                }
                
                const shiftId = this.getAttribute('data-shift');
                const pumpId = this.getAttribute('data-pump');
                const pumpName = this.closest('tr').querySelector('td:nth-child(2)').textContent.trim();
                const pumpUserId = this.getAttribute('data-pump-user');
                const nozzleId = this.getAttribute('data-nozzle');
                const shiftName = this.getAttribute('data-shift-name');
                const startTime = this.getAttribute('data-start-time');
                const endTime = this.getAttribute('data-end-time');
                const startDate = this.getAttribute('data-start-date');
                const endDate = this.getAttribute('data-end-date');
                const isActive = this.getAttribute('data-active');

                console.log("Edit Shift clicked with data: ", {
                    shiftId, pumpId, pumpName, pumpUserId, nozzleId, shiftName,
                    startTime, endTime, startDate, endDate, isActive
                });

                // Set the form action URL
                editShiftForm.action = `/shifts/${shiftId}`;

                // Populate form fields
                document.getElementById('edit_pump_name').value = pumpName;
                document.getElementById('edit_pump_id').value = pumpId;
                document.getElementById('edit_pump_user_id').value = pumpUserId;
                document.getElementById('edit_nozzle_id').value = nozzleId;
                document.getElementById('edit_shift_name').value = shiftName;
                document.getElementById('edit_start_date').value = startDate;
                document.getElementById('edit_end_date').value = endDate;
                document.getElementById('edit_is_active').value = isActive;

                // Format and set times
                if (startTime) {
                    const [hours, minutes] = startTime.split(':');
                    document.getElementById('edit_start_time').value = `${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}:00`;
                }
                
                if (endTime) {
                    const [hours, minutes] = endTime.split(':');
                    document.getElementById('edit_end_time').value = `${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}:00`;
                }
            });
        });

        // Handle edit form submission
        editShiftForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const shiftId = this.action.split('/').pop();

            // Ensure pump_id is set
            const pumpId = document.getElementById('edit_pump_id').value;
            if (!pumpId) {
                alert('Pump ID is required');
                return;
            }
            formData.set('pump_id', pumpId);

            // Format times
            const startTime = document.getElementById('edit_start_time').value;
            const endTime = document.getElementById('edit_end_time').value;

            formData.set('start_time', startTime);
            formData.set('end_time', endTime);

            // Send AJAX request
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-HTTP-Method-Override': 'PUT',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err));
                }
                return response.json();
            })            .then(data => {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('editShiftModal'));
                if (modal) {
                    modal.hide();
                }

                // Show success message - toastr notification disabled for update operations
                // toastr.success(data.message || 'Shift updated successfully');
                
                // Also add an alert message above the shifts section
                $('#shiftToastContainer').html(`
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-edit me-2"></i>
                        ${data.message || 'Shift updated successfully'}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);

                // Update the corresponding row in the table
                updateShiftRow(shiftId, formData);
            })
            .catch(error => {
                console.error('Error:', error);
                const errorMsg = error.message || 'An error occurred while updating the shift. Please try again.';
                
                // Show error message using toastr
                toastr.error(errorMsg);
                
                // Also add an alert message above the shifts section
                $('#shiftToastContainer').html(`
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        ${errorMsg}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
            });
        });
    }
});
