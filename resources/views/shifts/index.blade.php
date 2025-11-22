@extends('layouts.master-without-page-title')
@section('scripts')
    <!-- Include Toastr for notifications -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.13.18/jquery.timepicker.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.13.18/jquery.timepicker.min.css">
    <!-- Bootstrap datepicker -->
    <link href="{{ URL::asset('build/libs/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
    <script src="{{ URL::asset('build/libs/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
    <script>
    $(document).ready(function() {
        // Configuration for toastr notifications
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
            timeOut: 5000
        };
        
        // Override toastr success function to prevent shift and rate related success notifications
        const originalToastrSuccess = toastr.success;
        toastr.success = function(message, title, options) {
            // Check if the message is related to shift or rate operations
            if (message && (
                message.includes('Shift') || 
                message.includes('shift') ||
                message.includes('Rate') ||
                message.includes('rate') ||
                message.includes('rates') ||
                message.includes('Successfully saved') ||
                message.includes('deleted successfully') ||
                message.includes('created successfully') ||
                message.includes('updated successfully')
            )) {
                console.log('Blocked shift/rate-related toastr notification:', message);
                return; // Don't show the notification
            }
            // For non-shift/rate messages, use the original function
            return originalToastrSuccess.call(this, message, title, options);
        };
        
        // Fallback function to ensure rates load
        console.log('Page loaded, checking if rate-management.js is loaded...');
        
        // We'll defer to the external JS, but provide a safety check
        setTimeout(function() {
            if ($('#pumpsAccordion').find('.accordion-item').length === 0) {
                console.log('No accordion items found, trying to reload...');
                // Try to load rates directly in the page
                $.ajax({
                    url: '/api/all-pumps-with-items-rates',
                    type: 'GET',
                    success: function(response) {
                        console.log('Fallback response:', response);
                        if (response.pumps && response.pumps.length > 0) {
                            $('#pumpsAccordion').html('<div class="alert alert-info">Rates loaded, but display script failed. Please check console for errors and refresh the page.</div>');
                        } else {
                            $('#pumpsAccordion').html('<div class="alert alert-info">No pumps found.</div>');
                        }
                    },
                    error: function(xhr) {
                        $('#pumpsAccordion').html('<div class="alert alert-danger">Error loading pump data.</div>');
                        console.error('Error loading pump data in fallback:', xhr.responseText);
                    }
                });
            }
        }, 3000);
        
        // Initialize bootstrap datepicker for shifts filter
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
        
        // Time input handling
        const startTimeInput = document.getElementById('start_time');
        const endTimeInput = document.getElementById('end_time');
        const editStartTimeInput = document.getElementById('edit_start_time');
        const editEndTimeInput = document.getElementById('edit_end_time');

        // Function to format time in MySQL format (HH:mm:ss)
        function formatTime(timeInput) {
            if (!timeInput || !timeInput.value) return '00:00:00';
            const time = timeInput.value;

            // If time doesn't include seconds, add them
            if (time.length === 5) {
                return time + ':00';
            }
            return time;
        }

        // Set default time values with seconds
        if (startTimeInput) startTimeInput.value = '00:00:00';
        if (endTimeInput) endTimeInput.value = '00:00:00';
        if (editStartTimeInput) editStartTimeInput.value = '00:00:00';
        if (editEndTimeInput) editEndTimeInput.value = '00:00:00';

        // Rate management functions
        function initializeRateManagement() {
            // Handle pump selection for rate creation
            $('#pump_id').change(function() {
                var pumpId = $(this).val();
                if (pumpId) {
                    $.get('/api/pumps/' + pumpId + '/items', function(items) {
                        var options = '<option value="">Select an item</option>';
                        items.forEach(function(item) {
                            options += '<option value="' + item.id + '">' + item.name + '</option>';
                        });
                        $('#item_id').html(options).prop('disabled', false);
                    });
                } else {
                    $('#item_id').html('<option value="">First select a pump</option>').prop('disabled', true);
                }
            });

            // Handle edit rate modal
            $('.edit-rate').click(function() {
                var rate = $(this).data('rate');
                var pump = $(this).data('pump');
                var item = $(this).data('item');
                var rateValue = $(this).data('rate-value');
                var active = $(this).data('active');
                var itemName = $(this).closest('tr').find('td:nth-child(3)').text().trim();

                $('#editRateForm').attr('action', '/rates/' + rate);
                $('#edit_pump_id').val(pump);
                $('#edit_rate').val(rateValue);
                $('#edit_is_active').prop('checked', active == 1);

                // Set item name and id (readonly, not dropdown)
                $('#edit_item_name').val(itemName);
                $('#edit_item_id').val(item);

                $('#editRateModal').modal('show');
            });

            // Intercept Edit Rate form submit to use AJAX and update in-place
            $('#editRateForm').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);
                var url = $form.attr('action');
                var formData = $form.serialize();

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'X-HTTP-Method-Override': 'PUT'
                    },
                    success: function(response) {
                        // Display success message and hide modal
                        $('#editRateModal').modal('hide');
                        // Show success alert
                        var successAlert = '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                            'Rate updated successfully' +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                            '</div>';
                        $('.alert-container').html(successAlert);
                        // Reload the page after a short delay
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    },
                    error: function(xhr) {
                        // Show error message
                        var errorMessage = 'Failed to update rate. Please check your input.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        alert(errorMessage);
                    }
                });
            });

            // Add rotation animation to collapse icons
            $('.card-header[data-bs-toggle="collapse"]').on('click', function() {
                $(this).find('.fa-chevron-down').toggleClass('fa-rotate-180');
            });
            
            // Handle bulk form submissions with AJAX
            $(document).on('submit', '.pump-rate-form', function(e) {
                e.preventDefault();
                var $form = $(this);
                var formData = $form.serialize();
                var pumpName = $form.closest('.accordion-item').find('.accordion-button').text().trim();
                
                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        // Show success message
                        var successAlert = '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                            'Rates updated successfully for ' + pumpName + '.' +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                            '</div>';
                        $('.alert-container').html(successAlert);
                        
                        // Reload pump data after successful update
                        if (typeof loadAllPumpsWithRates === 'function') {
                            loadAllPumpsWithRates();
                        }
                    },
                    error: function(xhr) {
                        var errorMessage = 'Failed to update rates. Please check your input.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        
                        // Show error message
                        var errorAlert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                            errorMessage +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                            '</div>';
                        $('.alert-container').html(errorAlert);
                    }
                });
            });
        }

        // Initialize rate management
        initializeRateManagement();

        // Show/Hide pump user fields based on pump selection
        function handlePumpSelection() {
            const checkboxes = document.querySelectorAll('input[name="pumps[]"]:checked');
            const selectedPumpIds = Array.from(checkboxes).map(cb => cb.value);
            
            // Show/hide pump user checkboxes
            document.querySelectorAll('.pump-user-group').forEach(group => {
                const pumpId = group.dataset.pump;
                if (selectedPumpIds.includes(pumpId)) {
                    group.style.display = 'block';
                } else {
                    group.style.display = 'none';
                    // Uncheck checkboxes for hidden pumps
                    group.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
                }
            });
        }

        // Add event listeners to pump checkboxes
        document.querySelectorAll('input[name="pumps[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', handlePumpSelection);
        });

        // Initial call to set proper state
        handlePumpSelection();

    });
    </script>
    <!-- Load app.js after vendor scripts -->
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script src="{{ URL::asset('js/rate-management.js') }}"></script>
    <script src="{{ URL::asset('js/shift-management.js') }}"></script>
@endsection

@section('title', 'Shift & Rate Management')
@section('topbar-title', 'Management')

@section('content')
<style>
    #pumpsAccordion .accordion-button:not(.collapsed) {
        background-color: #f0f7ff;
        color: #0d6efd;
    }
    
    #pumpsAccordion .accordion-button:focus {
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    
    #pumpsAccordion .table {
        margin-bottom: 0;
    }
    
    #pumpsAccordion .accordion-item {
        margin-bottom: 10px;
        border-radius: 0.25rem;
        overflow: hidden;
    }
    
    #allPumpsContainer {
        margin-top: 15px;
    }
    
    /* Animation for highlighting updates */
    @keyframes highlightFade {
        0%   { background-color: rgba(25, 135, 84, 0.3); }
        100% { background-color: transparent; }
    }
    
    .highlight-update {
        animation: highlightFade 1.5s ease-out;
    }
    
    /* Also apply highlighting to table rows */
    tr.highlight-update td {
        animation: highlightFade 1.5s ease-out;
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

    /* Mobile-specific styles for action buttons */
    @media (max-width: 768px) {
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

        /* Table responsive adjustments for mobile */
        .table-responsive {
            overflow-x: auto;
        }

        /* Additional mobile text adjustments */
        .table tbody td .fw-bold,
        .table tbody td .text-muted,
        .table tbody td .badge,
        .table tbody td a,
        .table tbody td button {
            font-size: 14px !important;
        }
    }
</style>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fs-16 fw-semibold mb-1">Rate Management</h4>
                    <p class="text-muted mb-0">Manage your fuel rates here</p>
                </div>
                <div class="mt-3 mt-sm-0">
                    {{-- <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRateModal">
                        <i class="fas fa-plus me-1"></i> Add New Rate
                    </button> --}}
                </div>
            </div>
        </div>
    </div>

    <div class="alert-container">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
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
    </div>

     <!-- Rate Management Table -->
    <div class="row">
        <div class="col-12">
            <div id="rateToastContainer" class="mb-2"></div>
            <div class="card">
                <div class="card-header bg-primary">
                    <h4 class="card-title text-white mb-0">All Rates</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Pump</th>
                                    <th>Item</th>
                                    <th>Rate</th>
                                    <th>Rate Date</th>
                                    <th>Status</th>
                                    <th>Edited On</th>
                                    {{-- <th>Actions</th> --}}
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rates as $key => $rate)
                                    @if(auth()->user()->user_type === 'super_admin' || $rate->pump->pumpUsers->contains('email', auth()->user()->email))
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $rate->pump->pump_name }}</td>
                                        <td>{{ $rate->item->name }}</td>
                                        <td>₹{{ number_format($rate->rate, 2) }}</td>
                                        <td>{{ $rate->rate_date ? \Carbon\Carbon::parse($rate->rate_date)->format('d M Y') : '-' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $rate->is_active ? 'success' : 'danger' }}">
                                                {{ $rate->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>{{ $rate->updated_at->format('d M Y') }}</td>
                                        {{-- <td>
                                            <button class="btn btn-sm btn-warning edit-rate"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editRateModal"
                                                data-rate="{{ $rate->id }}"
                                                data-pump="{{ $rate->pump_id }}"
                                                data-item="{{ $rate->item_id }}"
                                                data-rate-value="{{ $rate->rate }}"
                                                data-active="{{ $rate->is_active }}">
                                                Edit
                                            </button>
                                            <form action="{{ route('rates.destroy', $rate->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this rate?')">Delete</button>
                                            </form>
                                        </td> --}}
                                    </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No rates found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bulk Rate Creation -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary d-flex justify-content-between align-items-center" role="button" data-bs-toggle="collapse" data-bs-target="#bulkRateCollapse" aria-expanded="true" aria-controls="bulkRateCollapse" style="cursor: pointer;">
                    <h4 class="card-title text-white mb-0">Create & Update Rates</h4>
                    <i class="fas fa-chevron-down text-white"></i>
                </div>
                <div class="collapse show" id="bulkRateCollapse">
                    <div class="card-body">
                        <div class="row mb-3 mobile-header-controls">
                            <div class="col-md-2 col-12">
                                <form method="GET" action="" id="ratesDateFilterForm">
                                    @php
                                        // Format the displayed date in dd/mm/yyyy format for datepicker
                                        $displayRateDate = request('rate_date') ? \Carbon\Carbon::parse(request('rate_date'))->format('d/m/Y') : \Carbon\Carbon::now()->format('d/m/Y');
                                        
                                        // Keep the backend date value in YYYY-MM-DD format
                                        $backendRateDate = request('rate_date') ?? \Carbon\Carbon::now()->format('Y-m-d');
                                    @endphp
                                    <div class="input-group date">
                                        <input type="text" name="display_rate_date" id="rates_date_picker" class="form-control" 
                                              value="{{ $displayRateDate }}" placeholder="Select date" readonly>
                                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                    </div>
                                    <input type="hidden" name="rate_date" id="formatted_rate_date" value="{{ $backendRateDate }}">
                                    <input type="hidden" id="rate_date_global" value="{{ $backendRateDate }}">
                                </form>
                            </div>
                            <div class="col-md-10 text-end d-flex align-items-end justify-content-end">
                                <button type="button" id="saveAllRatesBtn" class="btn btn-success">
                                    <i class="fas fa-save me-2"></i> Save All Rates
                                </button>
                            </div>
                        </div>
                        
                        <div id="allPumpsContainer">
                            <div class="accordion" id="pumpsAccordion">
                                <!-- Pumps will be loaded here dynamically -->
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="mt-2">Loading pumps and rates...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!-- Shift Management Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fs-16 fw-semibold mb-1">Shift Management</h4>
                    <p class="text-muted mb-0">Manage your shifts here</p>
                </div>
                <div class="mt-3 mt-sm-0">
                    {{-- <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createShiftModal">
                        <i class="fas fa-plus me-1"></i> Add New Shift
                    </button> --}}
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Shift Creation -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary d-flex justify-content-between align-items-center" role="button" data-bs-toggle="collapse" data-bs-target="#bulkShiftCollapse" aria-expanded="false" aria-controls="bulkShiftCollapse">
                    <h4 class="card-title text-white mb-0">Create Shift</h4>
                    <i class="fas fa-chevron-down text-white"></i>
                </div>
                <div class="collapse" id="bulkShiftCollapse">
                    <div class="card-body">
                        <form id="bulkShiftForm" action="{{ route('shifts.bulk-store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="created_date" id="created_date" value="{{ now()->format('Y-m-d') }}">
                            <div class="table-responsive mb-3">
                                <table class="table table-bordered">
                                    <thead class="bg-light">
                                        <tr>
                                            <th width="5%">S.No</th>
                                            <th width="25%">Pump Name</th>
                                            <th width="35%">Assigned Users</th>
                                            <th width="35%">Available Nozzles</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pumps as $index => $pump)
                                            @if(auth()->user()->user_type === 'super_admin' || $pump->pumpUsers->contains('email', auth()->user()->email))
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="form-check">
                                                        <input type="radio" name="pumps[]" id="pump_{{ $pump->id }}" value="{{ $pump->id }}" class="form-check-input">
                                                        <label class="form-check-label" for="pump_{{ $pump->id }}">
                                                            {{ $pump->pump_name }}
                                                        </label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="pump-users-{{ $pump->id }}">
                                                        @foreach($pumpUsers as $user)
                                                            @if($user->pump_id == $pump->id)
                                                            <div class="form-check">
                                                                <input type="radio" name="pump_users[]" id="user_{{ $user->id }}" value="{{ $user->id }}" class="form-check-input">
                                                                <label class="form-check-label" for="user_{{ $user->id }}">
                                                                    {{ $user->user_name }} ({{ $user->mobile_number }})
                                                                </label>
                                                            </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="nozzles-{{ $pump->id }}">
                                                        @foreach($nozzles as $nozzle)
                                                            @if($nozzle->pump_id == $pump->id)
                                                            <div class="form-check">
                                                                <input type="radio" name="nozzles[]" id="nozzle_{{ $nozzle->id }}" value="{{ $nozzle->id }}" class="form-check-input">
                                                                <label class="form-check-label" for="nozzle_{{ $nozzle->id }}">
                                                                    {{ $nozzle->item->name }} ({{ $nozzle->nozzle_number }})
                                                                </label>
                                                            </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </td>
                                            </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="row">
                                <div class="col-md-2 mb-3"><label for="shift_name" class="form-label">Shift Name</label><select name="shift_name" id="shift_name" class="form-select" required><option value="">Select Shift</option><option value="First Shift">First Shift</option><option value="Second Shift">Second Shift</option><option value="Night Shift">Night Shift</option></select></div>
                                <div class="col-md-2 mb-3"><label for="start_date" class="form-label">Start Date</label><input type="date" name="start_date" id="start_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required></div>
                                <div class="col-md-2 mb-3"><label for="end_date" class="form-label">End Date</label><input type="date" name="end_date" id="end_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required></div>
                                <div class="col-md-2 mb-3"><label for="start_time" class="form-label">Start Time (24h)</label><input type="time" name="start_time" id="start_time" class="form-control" step="1" required></div>
                                <div class="col-md-2 mb-3"><label for="end_time" class="form-label">End Time (24h)</label><input type="time" name="end_time" id="end_time" class="form-control" step="1" required></div>
                                <div class="col-md-2 mb-3"><label for="is_active" class="form-label">Status</label><select name="is_active" id="is_active" class="form-select"><option value="1" selected>Active</option><option value="0">Inactive</option></select></div>
                                <div class="text-end mt-3"><button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Create Shift</button></div>
                            </div>                        
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- All Shifts -->
    <div class="row">
        <div class="col-12">
            <div id="shiftToastContainer" class="mb-2"></div>
            <div class="card">
                <div class="card-header bg-primary">
                    <h4 class="card-title text-white mb-0">All Shifts</h4>
                </div>
                <div class="card-body">
                    <!-- Date Filter for Shifts -->
                    <div class="row mb-3 mobile-header-controls">
                        <div class="col-md-2 col-12">
                            <form method="GET" action="" id="shiftsDateFilterForm">
                                @php
                                    // Format the displayed date in dd/mm/yyyy format for datepicker
                                    $displayDate = request('shift_date') ? \Carbon\Carbon::parse(request('shift_date'))->format('d/m/Y') : \Carbon\Carbon::now()->format('d/m/Y');
                                    
                                    // Keep the backend date value in YYYY-MM-DD format
                                    $backendDate = request('shift_date') ?? \Carbon\Carbon::now()->format('Y-m-d');
                                @endphp
                                <div class="input-group date">
                                    <input type="text" name="display_date" id="shifts_date_picker" class="form-control" 
                                          value="{{ $displayDate }}" placeholder="Select date" readonly>
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                </div>
                                <input type="hidden" name="shift_date" id="formatted_shift_date" value="{{ $backendDate }}">
                            </form>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>S.No</th>
                                    <th>Pump Name</th>
                                    <th>Pump User</th>
                                    <th>Nozzle</th>
                                    <th>Shift Name</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    {{-- <th>Edited On</th> --}}
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($shifts as $index => $shift)
                                    @if(auth()->user()->user_type === 'super_admin' || $shift->pump->pumpUsers->contains('email', auth()->user()->email))
                                    <tr id="shift-row-{{ $shift->id }}" class="{{ $shift->is_active ? '' : 'table-secondary' }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $shift->pump->pump_name ?? 'N/A' }}</td>
                                        <td>{{ $shift->pumpUser->user_name ?? 'N/A' }} ({{ $shift->pumpUser->mobile_number ?? 'N/A' }})</td>
                                        <td>{{ $shift->nozzle->item->name ?? 'N/A' }} ({{ $shift->nozzle->nozzle_number ?? 'N/A' }})</td>
                                        <td>{{ $shift->shift_name }}</td>
                                        <td>{{ $shift->start_time ? \Carbon\Carbon::parse($shift->start_time)->format('H:i:s') : 'N/A' }}</td>
                                        <td>{{ $shift->end_time ? \Carbon\Carbon::parse($shift->end_time)->format('H:i:s') : 'N/A' }}</td>
                                        <td>{{ $shift->start_date ? \Carbon\Carbon::parse($shift->start_date)->format('d-m-Y') : 'N/A' }}</td>
                                        <td>{{ $shift->end_date ? \Carbon\Carbon::parse($shift->end_date)->format('d-m-Y') : 'N/A' }}</td>
                                        <td>{{ $shift->is_active ? 'Active' : 'Inactive' }}</td>
                                        {{-- <td>{{ $shift->updated_at->format('d M Y') }}</td> --}}
                                        <td>
                                            <button class="btn btn-sm btn-warning edit-shift"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editShiftModal"
                                                data-shift="{{ $shift->id }}"
                                                data-pump="{{ $shift->pump_id }}"
                                                data-pump-user="{{ $shift->pump_user_id }}"
                                                data-nozzle="{{ $shift->nozzle_id }}"
                                                data-shift-name="{{ $shift->shift_name }}"
                                                data-start-time="{{ $shift->start_time ? \Carbon\Carbon::parse($shift->start_time)->format('H:i') : '' }}"
                                                data-end-time="{{ $shift->end_time ? \Carbon\Carbon::parse($shift->end_time)->format('H:i') : '' }}"
                                                data-start-date="{{ $shift->start_date ? \Carbon\Carbon::parse($shift->start_date)->format('Y-m-d') : '' }}"
                                                data-end-date="{{ $shift->end_date ? \Carbon\Carbon::parse($shift->end_date)->format('Y-m-d') : '' }}"
                                                data-active="{{ $shift->is_active }}">
                                                <i class="fas fa-edit"></i>
                                            </button>                                           
                                             <button type="button" class="btn btn-sm btn-danger delete-shift-btn" 
                                                data-shift-id="{{ $shift->id }}"
                                                data-shift-name="{{ $shift->shift_name }}"
                                                data-pump-name="{{ $shift->pump->pump_name ?? 'Unknown' }}"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteShiftModal">
                                                <i class="fas fa-trash"></i>
                                             </button>
                                        </td>
                                    </tr>
                                    @endif
                                @empty
                                <tr>
                                    <td colspan="12" class="text-center">No shifts available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Rate Modal -->
<div class="modal fade" id="createRateModal" tabindex="-1" aria-labelledby="createRateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title text-dark" id="createRateModalLabel"><i class="fas fa-money-bill me-2"></i>Add New Rate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('rates.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="pump_id" class="form-label">Select Pump</label>
                            <select name="pump_id" id="pump_id" class="form-select @error('pump_id') is-invalid @enderror" required>
                                <option value="">Select a pump</option>
                                @foreach($pumps as $pump)
                                    @if(auth()->user()->user_type === 'super_admin' || $pump->pumpUsers->contains('email', auth()->user()->email))
                                    <option value="{{ $pump->id }}" {{ old('pump_id') == $pump->id ? 'selected' : '' }}>
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
                            <label for="item_id" class="form-label">Select Item</label>
                            <select name="item_id" id="item_id" class="form-select @error('item_id') is-invalid @enderror" required disabled>
                                <option value="">First select a pump</option>
                            </select>
                            @error('item_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="rate" class="form-label">Rate</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" name="rate" id="rate" class="form-control @error('rate') is-invalid @enderror" value="{{ old('rate') }}" required>
                            </div>
                            @error('rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="rate_date_display" class="form-label">Rate Date</label>
                            <div class="input-group date">
                                <input type="text" id="rate_date_display" class="form-control date-picker-input @error('rate_date') is-invalid @enderror" 
                                       value="{{ \Carbon\Carbon::parse(old('rate_date', date('Y-m-d')))->format('d/m/Y') }}" placeholder="Select date" readonly>
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            </div>
                            <input type="hidden" name="rate_date" id="rate_date" value="{{ old('rate_date', date('Y-m-d')) }}" required>
                            @error('rate_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Rate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Rate Modal -->
<div class="modal fade" id="editRateModal" tabindex="-1" aria-labelledby="editRateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title text-dark" id="editRateModalLabel"><i class="fas fa-edit me-2"></i>Edit Rate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editRateForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="edit_pump_id" class="form-label">Select Pump</label>
                            <select name="pump_id" id="edit_pump_id" class="form-select @error('pump_id') is-invalid @enderror" required>
                                <option value="">Select a pump</option>
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
                            <label for="edit_item_id" class="form-label">Item</label>
                            <input type="text" id="edit_item_name" class="form-control" readonly>
                            <input type="hidden" name="item_id" id="edit_item_id">
                            @error('item_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="edit_rate" class="form-label">Rate</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" name="rate" id="edit_rate" class="form-control @error('rate') is-invalid @enderror" required>
                            </div>
                            @error('rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="edit_rate_date" class="form-label">Rate Date</label>
                            <input type="date" name="rate_date" id="edit_rate_date" class="form-control @error('rate_date') is-invalid @enderror" required>
                            @error('rate_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="edit_is_active" class="form-check-input" value="1">
                                <label class="form-check-label" for="edit_is_active">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-edit me-2"></i>Update Rate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Shift Modal -->
<div class="modal fade" id="editShiftModal" tabindex="-1" aria-labelledby="editShiftModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title text-dark" id="editShiftModalLabel"><i class="fas fa-edit me-2"></i>Edit Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editShiftForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="edit_pump_name" class="form-label">Pump Name</label>
                            <input type="text" id="edit_pump_name" class="form-control" readonly>
                            <input type="hidden" name="pump_id" id="edit_pump_id">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="edit_pump_user_id" class="form-label">Pump User</label>
                            <select name="pump_user_id" id="edit_pump_user_id" class="form-select" required>
                                <option value="">Select a pump user</option>
                                @foreach($pumpUsers as $user)
                                    @if(auth()->user()->user_type === 'super_admin' || $user->pump->pumpUsers->contains('email', auth()->user()->email))
                                    <option value="{{ $user->id }}">{{ $user->user_name }} ({{ $user->mobile_number }})</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="edit_nozzle_id" class="form-label">Nozzle</label>
                            <select name="nozzle_id" id="edit_nozzle_id" class="form-select" required>
                                <option value="">Select a nozzle</option>
                                @foreach($nozzles as $nozzle)
                                    @if(auth()->user()->user_type === 'super_admin' || $nozzle->pump->pumpUsers->contains('email', auth()->user()->email))
                                    <option value="{{ $nozzle->id }}">{{ $nozzle->item->name }} ({{ $nozzle->nozzle_number }})</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="edit_shift_name" class="form-label">Shift Name</label>
                            <select name="shift_name" id="edit_shift_name" class="form-select" required>
                                <option value="">Select Shift</option>
                                <option value="First Shift">First Shift</option>
                                <option value="Second Shift">Second Shift</option>
                                <option value="Night Shift">Night Shift</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_start_date" class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="edit_start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_end_date" class="form-label">End Date</label>
                            <input type="date" name="end_date" id="edit_end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_start_time" class="form-label">Start Time (24h)</label>
                            <input type="time" name="start_time" id="edit_start_time" class="form-control" step="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_end_time" class="form-label">End Time (24h)</label>
                            <input type="time" name="end_time" id="edit_end_time" class="form-control" step="1" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="edit_is_active" class="form-label">Status</label>
                            <select name="is_active" id="edit_is_active" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-edit me-2"></i>Update Shift</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Save Rate Confirmation Modal -->
<div class="modal fade" id="saveRateConfirmModal" tabindex="-1" aria-labelledby="saveRateConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="saveRateConfirmModalLabel">Confirm Save Rates</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-question-circle text-primary fa-2x"></i>
                    </div>
                    <div>
                        <p class="mb-0">Are you sure you want to save rates for <strong id="saveRatePumpName"></strong>?</p>
                        <p class="text-muted small mb-0">Current rates will be updated with new values.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmSaveRatesBtn">Save Rates</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Shift Confirmation Modal -->
<div class="modal fade" id="deleteShiftModal" tabindex="-1" aria-labelledby="deleteShiftModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteShiftModalLabel">Delete Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                    </div>
                    <div>
                        <p class="mb-0">Are you sure you want to delete the shift <strong id="deleteShiftName"></strong> from pump <strong id="deleteShiftPumpName"></strong>?</p>
                        <p class="text-muted small mb-0">This action cannot be undone and may affect related records.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteShiftForm" method="POST" class="d-inline m-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Duplicate Create Rate Modal removed - was causing JavaScript errors -->
@endsection


