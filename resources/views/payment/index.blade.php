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
        
        // Character limit warning functions
        function showCharacterLimitWarning(inputId, warningId, message) {
            const warningElement = document.getElementById(warningId);
            if (warningElement) {
                warningElement.textContent = message;
                warningElement.style.display = 'block';
            }
        }

        function hideCharacterLimitWarning(warningId) {
            const warningElement = document.getElementById(warningId);
            if (warningElement) {
                warningElement.style.display = 'none';
            }
        }

        // JavaScript to populate the Edit Modal with payment data
        document.addEventListener('DOMContentLoaded', function () {
            // Edit Payment Modal functionality
            const editButtons = document.querySelectorAll('.edit-payment-btn');
            editButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const paymentId = this.dataset.id;
                    const itemName = this.dataset.item_name;
                    const petrolPump = this.dataset.petrol_pump;
                    const status = this.dataset.status;

                    // Populate the modal fields
                    document.getElementById('editPaymentForm').action = `/payment/update/${paymentId}`;
                    document.getElementById('editItemName').value = itemName;
                    document.getElementById('editPetrolPump').value = petrolPump;
                    document.getElementById('editStatus').value = status;
                });
            });

            // Delete Payment Modal functionality
            const deleteButtons = document.querySelectorAll('.delete-payment-btn');
            const deletePaymentName = document.getElementById('deletePaymentName');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            let currentPaymentId = null;

            console.log('Delete buttons found:', deleteButtons.length);
            console.log('Delete payment name element:', deletePaymentName);
            console.log('Confirm delete button:', confirmDeleteBtn);

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    currentPaymentId = this.dataset.id;
                    const itemName = this.dataset.itemName; // This corresponds to data-item-name
                    deletePaymentName.textContent = itemName;
                    
                    console.log('Delete button clicked for payment ID:', currentPaymentId);
                    console.log('Item name:', itemName);
                });
            });

            // Handle the actual delete with AJAX
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', function() {
                    console.log('Confirm delete button clicked');
                    
                    if (!currentPaymentId) {
                        alert('No payment selected for deletion.');
                        console.error('No currentPaymentId set');
                        return;
                    }

                    const deleteUrl = `{{ url('/') }}/payment/destroy/${currentPaymentId}`;
                    console.log('Sending DELETE request to:', deleteUrl);

                    // Disable button to prevent double clicks
                    this.disabled = true;
                    this.textContent = 'Deleting...';

                    // Create form data with CSRF token
                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('_method', 'DELETE');

                    fetch(deleteUrl, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        console.log('Response headers:', response.headers);
                        
                        if (response.ok) {
                            // Close modal and reload page
                            const modal = bootstrap.Modal.getInstance(document.getElementById('deletePaymentModal'));
                            if (modal) {
                                modal.hide();
                            }
                            window.location.reload();
                        } else {
                            return response.text().then(text => {
                                console.error('Response text:', text);
                                throw new Error(`HTTP error! status: ${response.status}, response: ${text}`);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Delete error:', error);
                        alert('Error deleting payment: ' + error.message);
                        
                        // Re-enable button
                        this.disabled = false;
                        this.textContent = 'Delete';
                    });
                });
            } else {
                console.error('confirmDeleteBtn not found');
            }

            // Add Payment Form - Item Name Validation
            const addItemNameInput = document.getElementById('item_name');
            if (addItemNameInput) {
                addItemNameInput.addEventListener('input', function() {
                    let value = this.value;
                    
                    // Remove numbers and special characters, keep only alphabets and spaces
                    const filteredValue = value.replace(/[^a-zA-Z\s]/g, '');
                    if (value !== filteredValue) {
                        this.value = filteredValue;
                        value = filteredValue;
                    }
                    
                    // Check character limit
                    if (value.length > 50) {
                        showCharacterLimitWarning('item_name', 'item_name_warning', 'Item name cannot exceed 50 characters');
                        this.classList.add('is-invalid');
                        this.classList.remove('is-valid');
                    } else {
                        hideCharacterLimitWarning('item_name_warning');
                        if (value.length > 0) {
                            this.classList.add('is-valid');
                            this.classList.remove('is-invalid');
                        } else {
                            this.classList.remove('is-valid', 'is-invalid');
                        }
                    }
                });
            }

            // Edit Payment Form - Item Name Validation
            const editItemNameInput = document.getElementById('editItemName');
            if (editItemNameInput) {
                editItemNameInput.addEventListener('input', function() {
                    let value = this.value;
                    
                    // Remove numbers and special characters, keep only alphabets and spaces
                    const filteredValue = value.replace(/[^a-zA-Z\s]/g, '');
                    if (value !== filteredValue) {
                        this.value = filteredValue;
                        value = filteredValue;
                    }
                    
                    // Check character limit
                    if (value.length > 50) {
                        showCharacterLimitWarning('editItemName', 'edit_item_name_warning', 'Item name cannot exceed 50 characters');
                        this.classList.add('is-invalid');
                        this.classList.remove('is-valid');
                    } else {
                        hideCharacterLimitWarning('edit_item_name_warning');
                        if (value.length > 0) {
                            this.classList.add('is-valid');
                            this.classList.remove('is-invalid');
                        } else {
                            this.classList.remove('is-valid', 'is-invalid');
                        }
                    }
                });
            }

            // Add Payment Form Submission Validation
            const addPaymentForm = document.getElementById('addPaymentForm');
            if (addPaymentForm) {
                addPaymentForm.addEventListener('submit', function(e) {
                    const itemName = document.getElementById('item_name').value.trim();
                    
                    if (itemName.length > 50) {
                        e.preventDefault();
                        alert('Item name cannot exceed 50 characters. Please shorten the name.');
                        document.getElementById('item_name').focus();
                        return false;
                    }
                    
                    if (!/^[a-zA-Z\s]+$/.test(itemName)) {
                        e.preventDefault();
                        alert('Item name can only contain alphabets and spaces.');
                        document.getElementById('item_name').focus();
                        return false;
                    }
                });
            }

            // Edit Payment Form Submission Validation
            const editPaymentForm = document.getElementById('editPaymentForm');
            if (editPaymentForm) {
                editPaymentForm.addEventListener('submit', function(e) {
                    const itemName = document.getElementById('editItemName').value.trim();
                    
                    if (itemName.length > 50) {
                        e.preventDefault();
                        alert('Item name cannot exceed 50 characters. Please shorten the name.');
                        document.getElementById('editItemName').focus();
                        return false;
                    }
                    
                    if (!/^[a-zA-Z\s]+$/.test(itemName)) {
                        e.preventDefault();
                        alert('Item name can only contain alphabets and spaces.');
                        document.getElementById('editItemName').focus();
                        return false;
                    }
                });
            }
        });
    </script>
@endsection
@section('title')
    Payment Management
@endsection

@section('css')
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fs-16 fw-semibold mb-1">Payment Management</h4>
                    <p class="text-muted mb-0">Manage your payments here</p>
                </div>
                <div class="mt-3 mt-sm-0">
                    <!-- Button to trigger Add Payment modal -->
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                        <i class="fas fa-plus me-1"></i> Add New Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Page Alerts Container -->
    <div id="mainPageAlerts" class="container-fluid">
        <!-- Alerts will be injected here by JavaScript -->
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
    
    <!-- Alert container for main page messages -->
    <div id="mainPageAlerts"></div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Item Name</th>
                                    <th>Petrol Pump</th>
                                    <th>Status</th>
                                    <th>Created On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $key => $payment)
                                    @if(auth()->user()->user_type === 'super_admin' || $payment->pump && $payment->pump->pumpUsers->contains('email', auth()->user()->email))
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $payment->item_name }}</td>
                                            <td>{{ $payment->pump->pump_name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $payment->status === 'active' ? 'success' : 'danger' }}">
                                                    {{ ucfirst($payment->status) }}
                                                </span>
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($payment->created_on)->format('d M Y') }}</td>
                                            <td>
                                                <!-- Edit Button -->
                                                <button type="button" class="btn btn-sm btn-warning edit-payment-btn"
                                                    data-id="{{ $payment->id }}"
                                                    data-item_name="{{ $payment->item_name }}"
                                                    data-petrol_pump="{{ $payment->petrol_pump }}"
                                                    data-status="{{ $payment->status }}"
                                                    data-bs-toggle="modal" data-bs-target="#editPaymentModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <!-- Delete Button -->
                                                <button type="button" class="btn btn-sm btn-danger delete-payment-btn" data-bs-toggle="modal" data-bs-target="#deletePaymentModal" data-id="{{ $payment->id }}" data-item-name="{{ $payment->item_name }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No payments found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title text-dark" id="addPaymentModalLabel"><i class="fas fa-credit-card me-2"></i>Add New Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addPaymentForm" action="{{ route('payment.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <!-- Item Name -->
                            <div class="col-md-12 mb-3">
                                <label for="item_name" class="form-label">Item Name</label>
                                <input type="text" class="form-control @error('item_name') is-invalid @enderror" id="item_name" name="item_name" value="{{ old('item_name') }}" placeholder="Enter item name" pattern="[A-Za-z\s]+" required>
                                <div id="item_name_warning" class="text-danger mt-1" style="display: none;"></div>
                                @error('item_name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Petrol Pump Dropdown -->
                            <div class="col-md-12 mb-3">
                                <label for="petrol_pump" class="form-label">Pump name</label>
                                <select class="form-select @error('petrol_pump') is-invalid @enderror" id="petrol_pump" name="petrol_pump" required>
                                    <option value="" disabled selected>Select Pump</option>
                                    @foreach($pumps as $pump)
                                        @if(auth()->user()->user_type === 'super_admin' || $pump->pumpUsers->contains('email', auth()->user()->email))
                                            <option value="{{ $pump->id }}">{{ $pump->pump_name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('petrol_pump')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Status -->
                            <div class="col-md-12 mb-3">
                                {{-- <label for="status" class="form-label">Status</label> --}}
                                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Payment Modal -->
    <div class="modal fade" id="editPaymentModal" tabindex="-1" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title text-dark" id="editPaymentModalLabel"><i class="fas fa-edit me-2"></i>Edit Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editPaymentForm" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <!-- Item Name -->
                            <div class="col-md-12 mb-3">
                                <label for="editItemName" class="form-label">Item Name</label>
                                <input type="text" class="form-control @error('item_name') is-invalid @enderror" id="editItemName" name="item_name" placeholder="Enter item name" pattern="[A-Za-z\s]+" required>
                                <div id="edit_item_name_warning" class="text-danger mt-1" style="display: none;"></div>
                                @error('item_name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Petrol Pump Dropdown -->
                            <div class="col-md-12 mb-3">
                                <label for="editPetrolPump" class="form-label">Pump name</label>
                                <select class="form-select @error('petrol_pump') is-invalid @enderror" id="editPetrolPump" name="petrol_pump" required>
                                    <option value="" disabled>Select Pump</option>
                                    @foreach($pumps as $pump)
                                        @if(auth()->user()->user_type === 'super_admin' || $pump->pumpUsers->contains('email', auth()->user()->email))
                                            <option value="{{ $pump->id }}">{{ $pump->pump_name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('petrol_pump')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Status -->
                            <div class="col-md-12 mb-3">
                                <label for="editStatus" class="form-label">Status</label>
                                <select class="form-select @error('status') is-invalid @enderror" id="editStatus" name="status" required>
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
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning"><i class="fas fa-edit me-2"></i>Update Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Payment Modal -->
    <div class="modal fade" id="deletePaymentModal" tabindex="-1" aria-labelledby="deletePaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deletePaymentModalLabel">Delete Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                        </div>
                        <div>
                            <p class="mb-0">Are you sure you want to delete payment for <strong id="deletePaymentName"></strong>?</p>
                            <p class="text-muted small mb-0">This action cannot be undone.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Initialize toasts and auto-dismiss server-side alerts
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize toasts
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
            var addPaymentModal = new bootstrap.Modal(document.getElementById('addPaymentModal'));
            addPaymentModal.show();
        </script>
    @endif
@endsection