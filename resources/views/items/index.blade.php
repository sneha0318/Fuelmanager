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
        
        // JavaScript to populate the Edit Modal with item data
        document.addEventListener('DOMContentLoaded', function () {
            // Auto-dismiss server-side alerts after 5 seconds
            const serverAlerts = document.querySelectorAll('.alert:not(#mainPageAlerts .alert)');
            serverAlerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
            
            // Function to attach event listeners to newly added rows
            function attachEventListenersToNewRow(row) {
                // Add event listener to the edit button
                const editButton = row.querySelector('.edit-item-btn');
                if (editButton) {
                    editButton.addEventListener('click', function() {
                        const itemId = this.dataset.id;
                        const itemName = this.dataset.name;
                        const itemStatus = this.dataset.status;
                        const pumpId = this.dataset.pumpId;
                        const nozzleId = this.dataset.nozzleId;

                        // Populate the modal fields
                        document.getElementById('editItemForm').action = `/items/${itemId}`;
                        document.getElementById('edit_name').value = itemName;
                        document.getElementById('edit_status').value = itemStatus;
                        document.getElementById('edit_pump_id').value = pumpId;

                        // Load nozzles for the selected pump
                        if (pumpId) {
                            loadAvailableNozzles(pumpId, document.getElementById('edit_nozzle_id'), nozzleId, itemId);
                        }
                    });
                }

                // Add event listener to the delete button
                const deleteButton = row.querySelector('.delete-item-btn');
                if (deleteButton) {
                    deleteButton.addEventListener('click', function() {
                        const itemId = this.dataset.id;
                        const itemName = this.dataset.name;

                        // Update the delete form action and item name in the modal
                        const deleteForm = document.getElementById('deleteItemForm');
                        deleteForm.action = `/items/${itemId}`;

                        const deleteItemName = document.getElementById('deleteItemName');
                        deleteItemName.textContent = itemName;
                    });
                }
            }
            // Item name validation (allow all characters, 50 character limit)
            const itemNameInputs = document.querySelectorAll('#name, #edit_name');
            itemNameInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    // Allow all characters - no character filtering
                    
                    // Show warning if more than 50 characters
                    if (this.value.length > 50) {
                        showCharacterLimitWarning(this, 'Item name cannot exceed 50 characters');
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                    } else {
                        hideCharacterLimitWarning(this);
                        // Add validation feedback
                        if (this.value.length > 0) {
                            this.classList.remove('is-invalid');
                            this.classList.add('is-valid');
                        } else {
                            this.classList.remove('is-valid', 'is-invalid');
                        }
                    }
                });
            });

            // Function to show character limit warning
            function showCharacterLimitWarning(input, message) {
                let warningElement = input.parentNode.querySelector('.character-limit-warning');
                if (!warningElement) {
                    warningElement = document.createElement('div');
                    warningElement.className = 'character-limit-warning text-danger small mt-1';
                    input.parentNode.appendChild(warningElement);
                }
                warningElement.textContent = message;
                warningElement.style.display = 'block';
            }

            // Function to hide character limit warning
            function hideCharacterLimitWarning(input) {
                const warningElement = input.parentNode.querySelector('.character-limit-warning');
                if (warningElement) {
                    warningElement.style.display = 'none';
                }
            }

            const editButtons = document.querySelectorAll('.edit-item-btn');
            editButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const itemId = this.dataset.id;
                    const itemName = this.dataset.name;
                    const itemStatus = this.dataset.status;
                    const pumpId = this.dataset.pumpId;
                    const nozzleId = this.dataset.nozzleId;
                    const itemType = this.dataset.type || 'Litre';

                    // Populate the modal fields
                    document.getElementById('editItemForm').action = `/items/${itemId}`;
                    document.getElementById('edit_name').value = itemName;
                    document.getElementById('edit_status').value = itemStatus;
                    document.getElementById('edit_pump_id').value = pumpId;
                    document.getElementById('edit_item_type').value = itemType;

                    // Load nozzles for the selected pump
                    if (pumpId) {
                        loadAvailableNozzles(pumpId, document.getElementById('edit_nozzle_id'), nozzleId, itemId);
                    }
                });
            });

            // Add pump filter functionality
            const pumpFilter = document.getElementById('pump_filter');
            pumpFilter.addEventListener('change', function() {
                const selectedPumpId = this.value;
                const tableRows = document.querySelectorAll('tbody tr');

                tableRows.forEach(row => {
                    // Skip the "No items found" row
                    if (row.querySelector('td[colspan]')) {
                        return;
                    }

                    if (!selectedPumpId) {
                        row.style.display = ''; // Show all rows when no pump is selected
                        return;
                    }

                    // Get the pump name from the third column (Assigned Pumps)
                    const pumpCell = row.querySelector('td:nth-child(3)');
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

            const pumpSelect = document.getElementById('pump_id');
            const nozzleSelect = document.getElementById('nozzle_id');
            const editPumpSelect = document.getElementById('edit_pump_id');
            const editNozzleSelect = document.getElementById('edit_nozzle_id');

            if (pumpSelect) {
                pumpSelect.addEventListener('change', function () {
                    const pumpId = this.value;
                    loadAvailableNozzles(pumpId, nozzleSelect);
                });
            }

            if (editPumpSelect) {
                editPumpSelect.addEventListener('change', function () {
                    const pumpId = this.value;
                    loadAvailableNozzles(pumpId, editNozzleSelect);
                });
            }

            // Function to load available nozzles for a pump
            function loadAvailableNozzles(pumpId, nozzleSelect, selectedNozzleId = null, currentItemId = null) {
                if (!pumpId) {
                    nozzleSelect.innerHTML = '<option value="">Select Nozzle</option>';
                    return;
                }

                nozzleSelect.innerHTML = '<option value="">Loading...</option>';

                // Build the URL with parameters
                let url = `/nozzles/available-numbers?pump_id=${pumpId}`;
                if (currentItemId) {
                    url += `&current_item_id=${currentItemId}`;
                }

                fetch(url, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Failed to fetch available nozzles: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    nozzleSelect.innerHTML = '<option value="">Select Nozzle</option>';

                    if (data.numbers && data.numbers.length > 0) {
                        data.numbers.forEach(nozzle => {
                            const option = document.createElement('option');
                            option.value = nozzle.value;

                            // Add "(In Use)" text to nozzles that are already assigned
                            if (nozzle.used) {
                                option.textContent = `Nozzle ${nozzle.text} (In Use)`;
                                option.disabled = true;
                            } else {
                                option.textContent = `Nozzle ${nozzle.text}`;
                            }

                            // Select the nozzle if it's the one we're editing
                            if (selectedNozzleId && nozzle.value == selectedNozzleId) {
                                option.selected = true;
                                // If the nozzle is in use but it's the one we're editing, enable it
                                if (nozzle.used) {
                                    option.disabled = false;
                                }
                            }

                            nozzleSelect.appendChild(option);
                        });
                    } else {
                        nozzleSelect.innerHTML = '<option value="">No nozzles available</option>';
                    }
                })
                .catch(error => {
                    console.error('Error loading available nozzles:', error);
                    nozzleSelect.innerHTML = '<option value="">Error loading nozzles</option>';
                });
            }

            // Add form validation before submission
            const addForm = document.getElementById('addItemForm');
            const editForm = document.getElementById('editItemForm');
            
            [addForm, editForm].forEach(form => {
                if (form) {
                    form.addEventListener('submit', function(e) {
                        const itemNameInput = form.querySelector('input[name="name"]');
                        
                        let hasErrors = false;
                        
                        // Validate item name
                        if (itemNameInput) {
                            if (itemNameInput.value.length > 50) {
                                e.preventDefault();
                                itemNameInput.classList.add('is-invalid');
                                alert('Item name cannot exceed 50 characters');
                                itemNameInput.focus();
                                hasErrors = true;
                            }
                        }
                        
                        if (hasErrors) {
                            return false;
                        }
                    });
                }
            });

            // Delete Item Modal functionality
            const deleteButtons = document.querySelectorAll('.delete-item-btn');
            const deleteForm = document.getElementById('deleteItemForm');
            const deleteItemName = document.getElementById('deleteItemName');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const itemId = this.dataset.id;
                    const itemName = this.dataset.name;

                    deleteForm.action = `/items/${itemId}`;
                    deleteItemName.textContent = itemName;
                });
            });
        });
    </script>
@endsection
@section('title')
    Items Management
@endsection

@section('css')

<style>

/* =========================================
   ITEMS MANAGEMENT BACKGROUND
========================================= */

.items-management-page {
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


/* =========================================
   ITEMS MANAGEMENT HEADING
========================================= */

.items-management-page .page-title-box h4 {

    font-size: 30px !important;

    font-weight: 900 !important;

    color: #073b4c !important;

    margin-bottom: 6px !important;

}


/* MANAGEMENT GREEN */

.items-management-page .item-green-text {

    color: #20b486 !important;

}


/* =========================================
   SUBTITLE
========================================= */

.items-management-page .page-title-box p {

    font-size: 16px !important;

    font-weight: 600 !important;

    color: #477080 !important;

    margin-bottom: 0 !important;

}


/* =========================================
   MAIN CARD
========================================= */

.items-management-page .card {

    background: rgba(255, 255, 255, 0.96);

    border: none;

    border-radius: 18px;

    box-shadow: 0 8px 25px rgba(7, 59, 76, 0.08);

}


/* =========================================
   TABLE HEADER
========================================= */

.items-management-page table th {

    color: #073b4c !important;

    font-weight: 600;

}


/* =========================================
   TABLE DATA
========================================= */

.items-management-page table td {

    color: #315466 !important;

}


/* =========================================
   ADD ITEM BUTTON
========================================= */

.items-management-page .btn-primary {

    background: #20b486 !important;

    border-color: #20b486 !important;

    color: #ffffff !important;

    border-radius: 10px;

}


/* =========================================
   FORM SELECT
========================================= */

.items-management-page .form-control,
.items-management-page .form-select {

    border: 1px solid #d7e8e3;

    border-radius: 8px;

}


.items-management-page .form-control:focus,
.items-management-page .form-select:focus {

    border-color: #20b486;

    box-shadow: 0 0 0 0.15rem rgba(32,180,134,0.15);

}


/* =========================================
   STATUS
========================================= */

.items-management-page .badge.bg-success {

    background-color: #3cc5a7 !important;

    color: #ffffff !important;

}


.items-management-page .badge.bg-danger {

    background-color: #ff6268 !important;

    color: #ffffff !important;

}


/* =========================================
   EDIT BUTTON
========================================= */

.items-management-page .btn-warning {

    background: #ffd66b !important;

    border-color: #ffd66b !important;

}


/* =========================================
   DELETE BUTTON
========================================= */

.items-management-page .btn-danger {

    background: #ff6268 !important;

    border-color: #ff6268 !important;

}


/* =========================================
   MODAL
========================================= */

.items-management-page .modal-content {

    border: none;

    border-radius: 18px;

}

</style>


@endsection

@section('content')
<div class="items-management-page">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                 <h4 class="fs-16 fw-semibold mb-1">
    ITEMS <span class="item-green-text">MANAGEMENT :</span>
</h4>

<p class="text-muted mb-0">
    Manage your items here
</p>
                </div>
                <div class="mt-3 mt-sm-0">
                    <!-- Button to trigger Add Item modal -->
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="fas fa-plus me-1"></i> Add New Item
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="mainPageAlerts"></div>

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
    
    <!-- Alert container for main page messages -->
    <div id="mainPageAlerts"></div>

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
                                    <th>Item Name</th>

                                    <th>Assigned Pumps</th>
                                    <th>Assigned Nozzles</th>
                                    {{-- <th>Created On</th> --}}
                                     <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items->reverse() as $key => $item)
                                    <tr 
                                        data-id="{{ $item->id }}"
                                        data-name="{{ $item->name }}"
                                        data-status="{{ $item->status }}"
                                        data-pump-id="{{ $item->activePumps->first()->pump_id ?? '' }}"
                                        data-nozzle-id="{{ $item->nozzles->first()->nozzle_number ?? '' }}"
                                        data-type="{{ $item->item_type }}"
                                    >
                                        <td>{{ $loop->iteration }}</td> <!-- Use $loop->iteration for sequential numbering -->
                                        <td>{{ $item->name }}</td>

                                        <td>
                                            @foreach($item->activePumps as $pump)
                                                <span >{{ $pump->pump_name }}</span>
                                            @endforeach
                                        </td>
                                        <td>
                                            @foreach($item->nozzles as $nozzle)
                                                <span class="badge bg-primary me-1">
                                                    {{ $nozzle->pump->pump_name }} - Nozzle #{{ $nozzle->nozzle_number }}
                                                    @if($nozzle->item_id && $nozzle->item_id != $item->id)
                                                        <span class="badge bg-warning">In Use</span>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </td>
                                        {{-- <td>{{ $item->created_at->format('d M Y') }}</td> --}}
                                         <td>
                                            <span class="badge bg-{{ $item->status === 'Active' ? 'success' : 'danger' }}">
                                                {{ $item->status }}
                                            </span>
                                        </td>
                                        <td>
                                            <!-- Edit Button -->
                                            <button type="button" class="btn btn-sm btn-warning edit-item-btn"
                                                data-id="{{ $item->id }}"
                                                data-name="{{ $item->name }}"
                                                data-status="{{ $item->status }}"
                                                data-pump-id="{{ $item->activePumps->first()?->id }}"
                                                data-nozzle-id="{{ $item->nozzles->first()?->nozzle_number }}"
                                                data-type="{{ $item->item_type }}"
                                                data-bs-toggle="modal" data-bs-target="#editItemModal">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-sm btn-danger delete-item-btn"
                                                data-id="{{ $item->id }}"
                                                data-name="{{ $item->name }}"
                                                data-bs-toggle="modal" data-bs-target="#deleteItemModal">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No items found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title text-dark" id="addItemModalLabel"><i class="fas fa-box me-2"></i>Add New Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addItemForm" action="{{ route('items.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="name" class="form-label">Item Name</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    id="name" name="name" value="{{ old('name') }}" 
                                    placeholder="Enter item name" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="item_type" class="form-label">Item Type</label>
                                    <select class="form-select @error('item_type') is-invalid @enderror" id="item_type" name="item_type" required>
                                        <option value="Litre">Litre</option>
                                        <option value="KG">KG</option>
                                    </select>
                                    @error('item_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="pump_id" class="form-label">Assign to Pump</label>
                                <select class="form-select @error('pump_id') is-invalid @enderror"
                                    id="pump_id" name="pump_id" required>
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
                                <label for="nozzle_id" class="form-label">Assign to Nozzle</label>
                                <select class="form-select @error('nozzle_id') is-invalid @enderror"
                                    id="nozzle_id" name="nozzle_id" required>
                                    <option value="">Select Nozzle</option>
                                    <!-- Nozzles will be dynamically loaded based on the selected pump -->
                                </select>
                                @error('nozzle_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                {{-- <label for="status" class="form-label">Status</label> --}}
                                <select class="form-select @error('status') is-invalid @enderror"
                                    id="status" name="status" required>
                                    <option value="Active" {{ old('status') == 'Active' ? 'selected' : '' }}>Active</option>
                                    <option value="Inactive" {{ old('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Item</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title text-dark" id="editItemModalLabel"><i class="fas fa-box me-2"></i>Edit Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editItemForm" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="edit_name" class="form-label">Item Name</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    id="edit_name" name="name" 
                                    placeholder="Enter item name" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="edit_item_type" class="form-label">Item Type</label>
                                    <select class="form-select @error('item_type') is-invalid @enderror" id="edit_item_type" name="item_type" required>
                                        <option value="Litre" {{ isset($item) && $item->item_type == 'Litre' ? 'selected' : '' }}>Litre</option>
                                        <option value="KG" {{ isset($item) && $item->item_type == 'KG' ? 'selected' : '' }}>KG</option>
                                    </select>
                                    @error('item_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="edit_pump_id" class="form-label">Assign to Pump</label>
                                <select class="form-select @error('pump_id') is-invalid @enderror"
                                    id="edit_pump_id" name="pump_id" required>
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
                                <label for="edit_nozzle_id" class="form-label">Assign to Nozzle</label>
                                <select class="form-select @error('nozzle_id') is-invalid @enderror"
                                    id="edit_nozzle_id" name="nozzle_id" required>
                                    <option value="">Select Nozzle</option>
                                    <!-- Nozzles will be dynamically loaded based on the selected pump -->
                                </select>
                                @error('nozzle_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select @error('status') is-invalid @enderror"
                                    id="edit_status" name="status" required>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning"><i class="fas fa-edit me-2"></i>Update Item</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteItemModal" tabindex="-1" aria-labelledby="deleteItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteItemModalLabel">Delete Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center">
                                                <div class="me-3">

                            <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                            </div>
                        <div>
                            <p class="mb-0">Are you sure you want to delete the item <strong id="deleteItemName"></strong>?
                                </p>
                            <p class="text-muted small mb-0">This action cannot be undone.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteItemForm" method="POST" class="d-inline m-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-delete-hover">Delete
                        </button>
                    </form>
                </div>            
            </div>
        </div>
    </div>
@endsection