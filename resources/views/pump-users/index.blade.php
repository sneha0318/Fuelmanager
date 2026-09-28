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
        
        // JavaScript to populate the Edit Modal with user data
        document.addEventListener('DOMContentLoaded', function () {
            // User name validation (alphabets only, 50 character limit)
            const userNameInputs = document.querySelectorAll('#user_name, #editUserName');
            userNameInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    // Remove any non-alphabetic characters and spaces
                    this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
                    
                    // Show warning if more than 50 characters
                    if (this.value.length > 50) {
                        showCharacterLimitWarning(this, 'User name cannot exceed 50 characters');
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

            // Email validation (50 character limit)
            const emailInputs = document.querySelectorAll('#email, #editEmail');
            emailInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    // Show warning if more than 50 characters
                    if (this.value.length > 50) {
                        showCharacterLimitWarning(this, 'Email cannot exceed 50 characters');
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

            // Password validation (minimum 8 character requirement)
            const passwordInputs = document.querySelectorAll('#password, #editPassword');
            passwordInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    // Show warning if less than 8 characters
                    if (this.value.length > 0 && this.value.length < 8) {
                        showCharacterLimitWarning(this, 'Password must be at least 8 characters');
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                    } else {
                        hideCharacterLimitWarning(this);
                        // Add validation feedback
                        if (this.value.length >= 8) {
                            this.classList.remove('is-invalid');
                            this.classList.add('is-valid');
                        } else if (this.value.length === 0) {
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

            // Add mobile number validation
            const mobileInputs = document.querySelectorAll('#mobile_number, #editMobileNumber');
            mobileInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    // Remove any non-digit characters
                    this.value = this.value.replace(/[^0-9]/g, '');

                    // Limit to 10 digits
                    if (this.value.length > 10) {
                        this.value = this.value.slice(0, 10);
                    }

                    // Add validation feedback
                    if (this.value.length === 10) {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    } else if (this.value.length > 0) {
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-valid', 'is-invalid');
                    }
                });

                // Add paste validation
                input.addEventListener('paste', function(e) {
                    setTimeout(() => {
                        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
                        this.dispatchEvent(new Event('input'));
                    }, 0);
                });
            });

            // Edit user functionality
            const editButtons = document.querySelectorAll('.edit-user-btn');
            editButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const userId = this.dataset.id;
                    const userName = this.dataset.name;
                    const email = this.dataset.email;
                    const mobileNumber = this.dataset.mobile;
                    const userType = this.dataset.type;
                    const pumpId = this.dataset.pumpId;
                    const status = this.dataset.status;

                    // Populate the modal fields
                    document.getElementById('editUserForm').action = `/pump-users/${userId}`;
                    document.getElementById('editUserName').value = userName;
                    document.getElementById('editEmail').value = email;
                    document.getElementById('editMobileNumber').value = mobileNumber;
                    document.getElementById('editUserType').value = userType;
                    document.getElementById('editPumpId').value = pumpId;
                    document.getElementById('editStatus').value = status;
                    document.getElementById('editPassword').value = ''; // Clear password field
                });
            });

            // Delete user confirmation
            const deleteButtons = document.querySelectorAll('.delete-user-btn');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.dataset.id;
                    const userName = this.dataset.name;

                    // Update the delete form action and user name in the modal
                    const deleteForm = document.getElementById('deleteUserForm');
                    deleteForm.action = `/pump-users/${userId}`;

                    const deleteUserName = document.getElementById('deleteUserName');
                    deleteUserName.textContent = userName;
                });
            });

            // Add form validation before submission
            const addForm = document.getElementById('addUserForm');
            const editForm = document.getElementById('editUserForm');

            [addForm, editForm].forEach(form => {
                if (form) {
                    form.addEventListener('submit', function(e) {
                        const mobileInput = form.querySelector('input[name="mobile_number"]');
                        const userNameInput = form.querySelector('input[name="user_name"]');
                        const emailInput = form.querySelector('input[name="email"]');
                        const passwordInput = form.querySelector('input[name="password"]');
                        
                        let hasErrors = false;
                        
                        // Validate mobile number
                        if (mobileInput && mobileInput.value.length !== 10) {
                            e.preventDefault();
                            mobileInput.classList.add('is-invalid');
                            alert('Mobile number must be exactly 10 digits');
                            mobileInput.focus();
                            hasErrors = true;
                        }
                        
                        // Validate user name
                        if (userNameInput) {
                            if (userNameInput.value.length > 50) {
                                e.preventDefault();
                                userNameInput.classList.add('is-invalid');
                                alert('User name cannot exceed 50 characters');
                                userNameInput.focus();
                                hasErrors = true;
                            } else if (!/^[a-zA-Z\s]+$/.test(userNameInput.value)) {
                                e.preventDefault();
                                userNameInput.classList.add('is-invalid');
                                alert('User name can only contain alphabets and spaces');
                                userNameInput.focus();
                                hasErrors = true;
                            }
                        }
                        
                        // Validate email
                        if (emailInput && emailInput.value.length > 50) {
                            e.preventDefault();
                            emailInput.classList.add('is-invalid');
                            alert('Email cannot exceed 50 characters');
                            emailInput.focus();
                            hasErrors = true;
                        }
                        
                        // Validate password (only for add form or if password is provided in edit form)
                        if (passwordInput && passwordInput.value.length > 0) {
                            if (passwordInput.value.length < 8) {
                                e.preventDefault();
                                passwordInput.classList.add('is-invalid');
                                alert('Password must be at least 8 characters');
                                passwordInput.focus();
                                hasErrors = true;
                            }
                        }
                        
                        if (hasErrors) {
                            return false;
                        }
                    });
                }
            });
        });
    </script>

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

            // Delete User Modal functionality
            const deleteButtons = document.querySelectorAll('.delete-user-btn');
            const deleteForm = document.getElementById('deleteUserForm');
            const deleteUserName = document.getElementById('deleteUserName');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.dataset.id;
                    const userName = this.dataset.name;

                    deleteForm.action = `/pump-users/${userId}`;
                    deleteUserName.textContent = userName;
                });
            });
        });
    </script>

    @if ($errors->any())
        <script>
            var addUserModal = new bootstrap.Modal(document.getElementById('addUserModal'));
            addUserModal.show();
        </script>
    @endif
@endsection

@section('title')
    User Management
@endsection

@section('css')
@section('css')

<style>

/* =========================================
   USER MANAGEMENT FULL BACKGROUND
========================================= */

.user-management-page {
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
   USER MANAGEMENT HEADING
========================================= */
/* USER MANAGEMENT HEADING */
.user-management-page .page-title-box h4 {

    font-size: 30px !important;

    font-weight: 900 !important;

    color: #073b4c !important;

    margin-bottom: 6px !important;

}

.user-management-page .user-green-text {

    color: #20b486 !important;

}

.user-management-page .page-title-box p {

    font-size: 16px !important;

    font-weight: 600 !important;

    color: #477080 !important;

    margin-bottom: 0 !important;

}

/* =========================================
   SUBTITLE
========================================= */

.user-management-page p.text-muted,
.user-management-page p {
    color: #477080 !important;
}


/* =========================================
   MAIN CARD
========================================= */

.user-management-page .card {
    background: rgba(255, 255, 255, 0.96);

    border: none;

    border-radius: 18px;

    box-shadow: 0 8px 25px rgba(7, 59, 76, 0.08);
}


/* =========================================
   TABLE HEADER
========================================= */

.user-management-page table th {
    color: #073b4c !important;
    font-weight: 600;
}


/* =========================================
   TABLE DATA
========================================= */

.user-management-page table td {
    color: #315466 !important;
}


/* =========================================
   ADD USER BUTTON
========================================= */

.user-management-page .btn-primary {
    background: #20b486 !important;

    border-color: #20b486 !important;

    color: #ffffff !important;

    border-radius: 10px;
}


/* =========================================
   ACTIVE STATUS
========================================= */

.user-management-page .badge.bg-success {
    background-color: #3cc5a7 !important;
    color: #ffffff !important;
}


/* =========================================
   INACTIVE STATUS
========================================= */

.user-management-page .badge.bg-danger {
    background-color: #ff6268 !important;
    color: #ffffff !important;
}


/* =========================================
   EDIT BUTTON
========================================= */

.user-management-page .btn-warning {
    background: #ffd66b !important;

    border-color: #ffd66b !important;
}


/* =========================================
   DELETE BUTTON
========================================= */

.user-management-page .btn-danger {
    background: #ff6268 !important;

    border-color: #ff6268 !important;
}


/* =========================================
   MODAL
========================================= */

.user-management-page .modal-content {
    border: none;

    border-radius: 18px;
}

</style>


@endsection

@section('content')
<div class="user-management-page">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fs-16 fw-semibold mb-1">
    USER <span class="user-green-text">MANAGEMENT :</span>
</h4>

<p class="text-muted mb-0">
    Manage your pump users here
</p>

                </div>
                <div class="mt-3 mt-sm-0">
                    @if(auth()->user()->user_type === 'super_admin' || auth()->user()->user_type === 'admin')
                        <!-- Button to trigger Add User modal -->
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-plus me-1"></i> Add New User
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Main Page Alerts Container -->
    <div id="mainPageAlerts" class="container-fluid">
        <!-- Success, warning, or error messages will be injected here by showMainPageAlert function -->
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
                                    <th>User Name</th>
                                    <th>Email</th>
                                    <th>Mobile Number</th>
                                    <th>User Type</th>
                                    <th>Pump Name</th>
                                    <th>Status</th>
                                    <th>Created On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users->reverse() as $key => $user)
                                    <tr>
                                        <td>{{ $users->count() - $key }}</td>
                                        <td>{{ $user->user_name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->mobile_number }}</td>
                                        <td>{{ $user->user_type }}</td>
                                        <td>{{ $user->pump_name }}</td>
                                        <td>
                                            <span class="badge bg-{{ $user->status === 'active' ? 'success' : 'danger' }}">
                                                {{ ucfirst($user->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $user->created_at->format('d M Y') }}</td>
                                        <td>
                                            @if(auth()->user()->user_type === 'super_admin')
                                                <!-- Super Admin can manage all users -->
                                                <button type="button" class="btn btn-sm btn-warning edit-user-btn"
                                                    data-id="{{ $user->id }}"
                                                    data-name="{{ $user->user_name }}"
                                                    data-email="{{ $user->email }}"
                                                    data-mobile="{{ $user->mobile_number }}"
                                                    data-type="{{ $user->user_type }}"
                                                    data-pump-id="{{ $user->pump_id }}"
                                                    data-status="{{ $user->status }}"
                                                    data-bs-toggle="modal" data-bs-target="#editUserModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <button type="button" class="btn btn-sm btn-danger delete-user-btn"
                                                    data-bs-toggle="modal" data-bs-target="#deleteUserModal"
                                                    data-id="{{ $user->id }}"
                                                    data-name="{{ $user->user_name }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @elseif(auth()->user()->user_type === 'admin' && $user->user_type === 'operator')
                                                <!-- Admin can only manage operator users -->
                                                <button type="button" class="btn btn-sm btn-warning edit-user-btn"
                                                    data-id="{{ $user->id }}"
                                                    data-name="{{ $user->user_name }}"
                                                    data-email="{{ $user->email }}"
                                                    data-mobile="{{ $user->mobile_number }}"
                                                    data-type="{{ $user->user_type }}"
                                                    data-pump-id="{{ $user->pump_id }}"
                                                    data-status="{{ $user->status }}"
                                                    data-bs-toggle="modal" data-bs-target="#editUserModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <button type="button" class="btn btn-sm btn-danger delete-user-btn"
                                                    data-bs-toggle="modal" data-bs-target="#deleteUserModal"
                                                    data-id="{{ $user->id }}"
                                                    data-name="{{ $user->user_name }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @else
                                                <span class="text-muted">Not authorized</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No users found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title text-dark" id="addUserModalLabel"><i class="fas fa-user-plus me-2"></i>Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm" action="{{ route('pump-users.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <!-- User Name -->
                            <div class="col-md-12 mb-3">
                                <label for="user_name" class="form-label">User Name</label>
                                <input type="text" class="form-control @error('user_name') is-invalid @enderror"
                                    id="user_name" name="user_name" placeholder="Enter user name" 
                                    pattern="[a-zA-Z\s]+" title="Only alphabets and spaces allowed" required>
                                @error('user_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Email -->
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                    id="email" name="email" placeholder="Enter email" 
                                    required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Mobile Number -->
                            <div class="col-md-6 mb-3">
                                <label for="mobile_number" class="form-label">Mobile Number</label>
                                <input type="tel" class="form-control @error('mobile_number') is-invalid @enderror"
                                    id="mobile_number" name="mobile_number" placeholder="Enter 10-digit mobile number"
                                    pattern="[0-9]{10}" maxlength="10" title="Please enter exactly 10 digits" required>
                                @error('mobile_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- User Type -->
                            <div class="col-md-6 mb-3">
                                <label for="user_type" class="form-label">User Type</label>
                                <select class="form-select @error('user_type') is-invalid @enderror"
                                    id="user_type" name="user_type" required>
                                    <option value="operator">Operator</option>
                                    <option value="admin">Admin</option>
                                </select>
                                @error('user_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Password -->
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                    id="password" name="password" placeholder="Minimum 8 characters required" 
                                    title="Password must be at least 8 characters" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Pump Name -->
                            <div class="col-md-6 mb-3">
                                <label for="pump_id" class="form-label">Pump Name</label>
                                <select class="form-select @error('pump_id') is-invalid @enderror"
                                    id="pump_id" name="pump_id" required>
                                    <option value="">Select pump</option>
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

                            <!-- Status -->
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label"></label>
                                <select class="form-select @error('status') is-invalid @enderror"
                                    id="status" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title text-dark" id="editUserModalLabel"><i class="fas fa-edit me-2"></i>Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <!-- User Name -->
                            <div class="col-md-12 mb-3">
                                <label for="editUserName" class="form-label">User Name</label>
                                <input type="text" class="form-control @error('user_name') is-invalid @enderror"
                                    id="editUserName" name="user_name" placeholder="Enter user name"
                                    pattern="[a-zA-Z\s]+" title="Only alphabets and spaces allowed" required>
                                @error('user_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Email -->
                            <div class="col-md-6 mb-3">
                                <label for="editEmail" class="form-label">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                    id="editEmail" name="email" placeholder="Enter email"
                                    required readonly>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Mobile Number -->
                            <div class="col-md-6 mb-3">
                                <label for="editMobileNumber" class="form-label">Mobile Number</label>
                                <input type="tel" class="form-control @error('mobile_number') is-invalid @enderror"
                                    id="editMobileNumber" name="mobile_number" placeholder="Enter 10-digit mobile number"
                                    pattern="[0-9]{10}" maxlength="10" title="Please enter exactly 10 digits" required>
                                @error('mobile_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- User Type -->
                            <div class="col-md-6 mb-3">
                                <label for="editUserType" class="form-label">User Type</label>
                                <select class="form-select @error('user_type') is-invalid @enderror"
                                    id="editUserType" name="user_type" required>
                                    <option value="admin">Admin</option>
                                    <option value="operator">Operator</option>
                                    {{-- <option value="manager">Manager</option> --}}
                                </select>
                                @error('user_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Password -->
                            <div class="col-md-6 mb-3">
                                <label for="editPassword" class="form-label">New Password </label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                    id="editPassword" name="password" placeholder="Minimum 8 characters required" 
                                    title="Password must be at least 8 characters">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Pump Name -->
                            <div class="col-md-6 mb-3">
                                <label for="editPumpId" class="form-label">Pump Name</label>
                                <select class="form-select @error('pump_id') is-invalid @enderror"
                                    id="editPumpId" name="pump_id" required>
                                    <option value="">Select pump</option>
                                    @foreach($pumps as $pump)
                                        <option value="{{ $pump->id }}">
                                            {{ $pump->pump_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('pump_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div class="col-md-6 mb-3">
                                <label for="editStatus" class="form-label">Status</label>
                                <select class="form-select @error('status') is-invalid @enderror"
                                    id="editStatus" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning"><i class="fas fa-edit me-2"></i>Update User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
                    <button type="button" class="btn-close btn-close-black" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                        </div>
                        <div>
                            <p class="mb-0">Are you sure you want to delete user <strong id="deleteUserName"></strong>?</p>
                            <p class="text-muted small mb-0">This action cannot be undone.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteUserForm" method="POST" class="d-inline m-0">
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


