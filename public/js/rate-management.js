$(document).ready(function() {
    console.log('Rate management script loaded');
    
    // Initialize bootstrap datepicker for rates filter
    if ($("#rates_date_picker").length > 0) {
        $("#rates_date_picker").datepicker({
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
            
            // Set formatted date in hidden fields
            document.getElementById('formatted_rate_date').value = formattedDate;
            document.getElementById('rate_date_global').value = formattedDate;
            
            // Submit the form when a date is selected
            $("#ratesDateFilterForm").submit();
        });
    }
      
    // Debug helper function
    function debugResponse(response) {
        console.log('API Response:', response);
        
        if (!response) {
            console.error('Response is null or undefined!');
            toastr.error('Error loading rates: Empty response received');
            return false;
        }
        
        if (!response.pumps) {
            console.error('Response has no "pumps" property:', response);
            toastr.error('Error loading rates: Invalid response format');
            return false;
        }
        
        if (!Array.isArray(response.pumps)) {
            console.error('Response.pumps is not an array:', response.pumps);
            toastr.error('Error loading rates: Invalid pumps data format');
            return false;
        }
        
        if (response.pumps.length === 0) {
            console.warn('Response.pumps is an empty array');
            // This is not necessarily an error - might just be no pumps in the system
            return false;
        }
        
        // Log the structure of the first pump
        console.log('First pump structure:', response.pumps[0]);
        
        if (response.pumps[0].items && response.pumps[0].items.length > 0) {
            console.log('First item structure:', response.pumps[0].items[0]);
        }
        
        console.log('Total pumps loaded:', response.pumps.length);
        
        // Count total items across all pumps
        let totalItems = 0;
        let totalRatesFound = 0;
        response.pumps.forEach(pump => {
            if (pump.items && Array.isArray(pump.items)) {
                totalItems += pump.items.length;
                pump.items.forEach(item => {
                    if (item.rate) totalRatesFound++;
                });
            }
        });
        console.log(`Total items: ${totalItems}, Items with rates: ${totalRatesFound}`);
        
        return true;
    }
    
    // Load all pumps with their items and rates on page load
    loadAllPumpsWithRates();

    // Handle date change for rates
    $('#rate_date_global').change(function() {
        loadAllPumpsWithRates();
    });    // Function to load all pumps with their items and rates
    function loadAllPumpsWithRates(highlightUpdated = false) {
        const rateDate = $('#rate_date_global').val() || new Date().toISOString().split('T')[0];
        
        // Show loading indicator
        $('#pumpsAccordion').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading pumps and rates...</p></div>');
        
        // Add a random cache-busting parameter to avoid cached responses
        const cacheBuster = new Date().getTime();
        
        $.ajax({
            url: '/api/all-pumps-with-items-rates',
            type: 'GET',
            data: { 
                date: rateDate,
                _: cacheBuster  // Cache-busting parameter
            },
            cache: false,  // Disable AJAX caching
            success: function(response) {
                // Debugging the API response
                const isValidResponse = debugResponse(response);
                
                if (isValidResponse && response.pumps && response.pumps.length > 0) {
                    displayPumpsAndRates(response.pumps);
                    
                    // Highlight the updated section if requested
                    if (highlightUpdated) {
                        $('#pumpsAccordion').addClass('highlight-update');
                        setTimeout(() => {
                            $('#pumpsAccordion').removeClass('highlight-update');
                        }, 1500);
                    }
                    
                    console.log('Rates display updated successfully');
                } else {
                    $('#pumpsAccordion').html('<div class="alert alert-info">No pumps or items found. Please add pumps and items first.</div>');
                    console.warn('No pumps or rates found in the response');
                }
            },            error: function(xhr) {
                $('#pumpsAccordion').html(`
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Error loading data</h5>
                        <p>There was a problem loading the rate data. Please try again.</p>
                        <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="loadAllPumpsWithRates()">
                            <i class="fas fa-sync-alt me-1"></i> Retry
                        </button>
                    </div>
                `);
                console.error('Error loading pumps data:', xhr.responseText);
                toastr.error('Failed to load rate data. Please try again.');
            }
        });
    }

    // Function to display pumps and rates in a table format
    function displayPumpsAndRates(pumps) {
        let html = '';
        
        pumps.forEach(function(pump, index) {
            const pumpId = pump.id;
            const pumpName = pump.pump_name || pump.name; // fallback if pump_name doesn't exist
            const headingId = 'heading' + pumpId;
            const collapseId = 'collapse' + pumpId;
            const isFirst = index === 0;
            
            html += `
            <div class="accordion-item">
                <h2 class="accordion-header" id="${headingId}">
                    <button class="accordion-button ${isFirst ? '' : 'collapsed'}" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#${collapseId}" aria-expanded="${isFirst}" aria-controls="${collapseId}">
                        <strong>${pumpName}</strong> <span class="ms-2 badge bg-primary">${pump.items.length} Items</span>
                    </button>
                </h2>
                <div id="${collapseId}" class="accordion-collapse collapse ${isFirst ? 'show' : ''}" 
                     aria-labelledby="${headingId}" data-bs-parent="#pumpsAccordion">
                    <div class="accordion-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 50px">S.No</th>
                                        <th style="min-width: 150px">Item</th>
                                        <th style="min-width: 150px">Current Rate (₹)</th>
                                        <th style="min-width: 180px">New Rate (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>`;
            
            if (pump.items && pump.items.length > 0) {
                pump.items.forEach(function(item, itemIndex) {
                    const rateId = item.rate ? item.rate.id : 'new';
                    const currentRate = item.rate ? item.rate.rate : '—';
                    const rateDate = item.rate && item.rate.rate_date ? 
                        new Date(item.rate.rate_date).toLocaleDateString('en-GB') : '';
                    const currentRateDisplay = item.rate ? 
                        `₹${currentRate}${rateDate ? ' (' + rateDate + ')' : ''}` : '—';
                    
                    html += `
                    <tr>
                        <td>${itemIndex + 1}</td>
                        <td>${item.name}</td>
                        <td>${currentRateDisplay}</td>
                        <td>
                            <div class="input-group" style="min-width: 150px">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" class="form-control new-rate-input" 
                                       data-pump-id="${pumpId}" data-item-id="${item.id}" data-rate-id="${rateId}" 
                                       value="${item.rate ? item.rate.rate : ''}"
                                       style="min-width: 100px">
                            </div>
                        </td>
                    </tr>`;
                });
            } else {
                html += `<tr><td colspan="4" class="text-center">No items found for this pump</td></tr>`;
            }
            
            html += `
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 bg-light">
                            <button type="button" class="btn btn-primary save-pump-rates" data-pump-id="${pumpId}">
                                <i class="fas fa-save me-2"></i> Save ${pumpName} Rates
                            </button>
                        </div>
                    </div>
                </div>
            </div>`;
        });
        
        $('#pumpsAccordion').html(html);
        
        // Add event listener for save buttons after rendering
        $('.save-pump-rates').click(function() {
            const pumpId = $(this).data('pump-id');
            const pumpName = $(this).closest('.accordion-item').find('.accordion-button strong').text();
            
            // Set pump name in confirmation modal
            $('#saveRatePumpName').text(pumpName);
            
            // Store the pump ID for later use
            $('#confirmSaveRatesBtn').data('pump-id', pumpId);
            
            // Show the confirmation modal
            $('#saveRateConfirmModal').modal('show');
        });
    }

    // Function to save rates for a specific pump
    function saveRatesForPump(pumpId) {
        const rateDate = $('#rate_date_global').val();
        const rates = [];
        
        // Collect all rates for this pump
        $(`input.new-rate-input[data-pump-id="${pumpId}"]`).each(function() {
            const $input = $(this);
            const itemId = $input.data('item-id');
            const rateId = $input.data('rate-id');
            const rateValue = $input.val();

            
            // Only add rates that have a value
            if (rateValue) {
                rates.push({
                    rate_id: rateId === 'new' ? null : rateId,
                    pump_id: pumpId,
                    item_id: itemId,
                    rate: parseFloat(rateValue),

                    is_active: 1,
                    date: rateDate
                });
            }
        });
        
        // If no rates to save, show message
        if (rates.length === 0) {
            alert('No rates entered to save');
            return;
        }
        
        // Show saving indicator
        const $saveBtn = $(`.save-pump-rates[data-pump-id="${pumpId}"]`);
        const originalText = $saveBtn.html();
        $saveBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
        $saveBtn.prop('disabled', true);
        
        // Send AJAX request to save rates
        $.ajax({
            url: '/api/bulk-save-rates',
            type: 'POST',
            data: {
                rates: rates,
                _token: $('meta[name="csrf-token"]').attr('content')
            },            success: function(response) {
                // Hide the confirmation modal if it's open
                $('#saveRateConfirmModal').modal('hide');
                
                // Show success message
                toastr.success(response.message || 'Rates saved successfully');
                
                // Also add an alert message above the rates section
                $('#rateToastContainer').html(`
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        ${response.message || 'Rates saved successfully'}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
                
                // Reload only the rates section instead of full page and highlight the update
                loadAllPumpsWithRates(true);
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON && xhr.responseJSON.message 
                    ? xhr.responseJSON.message 
                    : 'Error saving rates. Please try again.';
                    
                // Show error message
                toastr.error(errorMsg);
                
                // Also add an alert message above the rates section
                $('#rateToastContainer').html(`
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        ${errorMsg}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
                toastr.error(errorMsg);
                console.error('Error saving rates:', xhr.responseText);
            },
            complete: function() {
                // Restore button state
                $saveBtn.html(originalText);
                $saveBtn.prop('disabled', false);
            }
        });
    }
      // Global save button functionality
    $('#saveAllRatesBtn').click(function() {
        // Set pump name in confirmation modal to "All Pumps"
        $('#saveRatePumpName').text('All Pumps');
        
        // Set a special indicator for saving all rates
        $('#confirmSaveRatesBtn').data('pump-id', 'all');
        
        // Show the confirmation modal
        $('#saveRateConfirmModal').modal('show');
    });
    
    // Handle confirmation button click in the modal
    $('#confirmSaveRatesBtn').click(function() {
        const pumpId = $(this).data('pump-id');
        
        if (pumpId === 'all') {
            const pumpIds = [];
            
            // Collect all unique pump IDs
            $('.save-pump-rates').each(function() {
                pumpIds.push($(this).data('pump-id'));
            });
            
            // Disable the confirm button and show loading state
            $(this).prop('disabled', true);
            $(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
            
            // Hide modal
            $('#saveRateConfirmModal').modal('hide');
            
            // Show a saving message
            toastr.info('Saving rates for all pumps. Please wait...');
            
            // Save rates for each pump sequentially using a counter
            let savedCount = 0;
            
            // Define a function to save rates one by one
            function saveNextPump(index) {
                if (index >= pumpIds.length) {
                    // All done - show final success message and refresh the view
                    toastr.success('All rates saved successfully!');
                    loadAllPumpsWithRates(true);
                    return;
                }
                
                const currentPumpId = pumpIds[index];
                const rateDate = $('#rate_date_global').val();
                const rates = [];
                
                // Collect all rates for this pump
                $(`input.new-rate-input[data-pump-id="${currentPumpId}"]`).each(function() {
                    const $input = $(this);
                    const itemId = $input.data('item-id');
                    const rateId = $input.data('rate-id');
                    const rateValue = $input.val();
                    
                    // Only add rates that have a value
                    if (rateValue) {
                        rates.push({
                            rate_id: rateId === 'new' ? null : rateId,
                            pump_id: currentPumpId,
                            item_id: itemId,
                            rate: parseFloat(rateValue),
                            is_active: 1,
                            date: rateDate
                        });
                    }
                });
                
                // Skip if no rates to save for this pump
                if (rates.length === 0) {
                    saveNextPump(index + 1);
                    return;
                }
                
                // Show saving indicator for this pump
                const $saveBtn = $(`.save-pump-rates[data-pump-id="${currentPumpId}"]`);
                const pumpName = $saveBtn.closest('.accordion-item').find('.accordion-button strong').text();
                $saveBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
                $saveBtn.prop('disabled', true);
                
                // Send AJAX request to save rates
                $.ajax({
                    url: '/api/bulk-save-rates',
                    type: 'POST',
                    data: {
                        rates: rates,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        savedCount++;
                        // Restore button state
                        $saveBtn.html(`<i class="fas fa-save me-2"></i> Save ${pumpName} Rates`);
                        $saveBtn.prop('disabled', false);
                        
                        // Move to the next pump
                        saveNextPump(index + 1);
                    },
                    error: function(xhr) {
                        const errorMsg = xhr.responseJSON && xhr.responseJSON.message 
                            ? xhr.responseJSON.message 
                            : `Error saving rates for ${pumpName}. Please try again.`;
                        toastr.error(errorMsg);
                        console.error('Error saving rates:', xhr.responseText);
                        
                        // Restore button state
                        $saveBtn.html(`<i class="fas fa-save me-2"></i> Save ${pumpName} Rates`);
                        $saveBtn.prop('disabled', false);
                        
                        // Continue with next pump despite error
                        saveNextPump(index + 1);
                    }
                });
            }
            
            // Start the sequential saving process
            saveNextPump(0);
            
        } else {
            // Save rates for specific pump
            saveRatesForPump(pumpId);
        }
    });
});
