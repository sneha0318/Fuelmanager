@extends('layouts.master-without-page-title')

@section('title')
    Super Admin Management
@endsection
@section('scripts')
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // User status change modal functionality
        const statusChangeButtons = document.querySelectorAll('.status-change-btn');
        const statusChangeForm = document.getElementById('statusChangeForm');
        const statusChangeUserName = document.getElementById('statusChangeUserName');
        const statusChangeAction = document.getElementById('statusChangeAction');
        const statusChangeIcon = document.getElementById('statusChangeIcon');
        const statusChangeButton = document.getElementById('statusChangeSubmitBtn');

        statusChangeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.id;
                const userName = this.dataset.name;
                const currentStatus = this.dataset.status;
                const action = currentStatus === '1' ? 'deactivate' : 'activate';
                
                // Update form action based on current status
                if(action === 'deactivate') {
                    statusChangeForm.action = `/registered-users/${userId}/deactivate`;
                    statusChangeAction.textContent = 'deactivate';
                    statusChangeIcon.className = 'fas fa-ban text-warning fa-2x';
                    statusChangeButton.className = 'btn btn-warning';
                    statusChangeButton.innerHTML = '<i class="fas fa-ban me-1"></i> Deactivate';
                } else {
                    statusChangeForm.action = `/registered-users/${userId}/activate`;
                    statusChangeAction.textContent = 'activate';
                    statusChangeIcon.className = 'fas fa-check text-success fa-2x';
                    statusChangeButton.className = 'btn btn-success';
                    statusChangeButton.innerHTML = '<i class="fas fa-check me-1"></i> Activate';
                }
                
                // Set the user name in the modal
                statusChangeUserName.textContent = userName;
            });
        });
    });
</script>
@endsection
@section('css')
<style>

/* =========================================================
   REGISTERED USERS / SUPER ADMIN MANAGEMENT
   PUMP MANAGEMENT JAISE DESIGN
   ========================================================= */

.super-admin-management-page {
    min-height: calc(100vh - 70px);

    width: calc(100% + 40px);
    margin-left: -20px;
    margin-right: -20px;
    margin-top: -30px;

    padding: 25px 25px 50px 25px;

 background:
    linear-gradient(
        rgba(220, 250, 242, 0.60),
        rgba(220, 250, 242, 0.60)
    ),
    url("{{ asset('build/images/dashboard-bg.png') }}");

background-size: cover;
background-position: center;
background-repeat: no-repeat;

    box-sizing: border-box;

    font-family: "Segoe UI", Arial, sans-serif;
}


.super-admin-title {
    font-family: "Segoe UI", Arial, sans-serif !important;

    font-size: 30px !important;
    font-weight: 900 !important;

    color: #20b486 !important;

    margin: 0 0 6px 0 !important;

    letter-spacing: 0.2px;
}

.super-admin-title span {
    color: #073b4c !important;
}

.super-admin-management-page .page-title-box p {
    font-family: "Segoe UI", Arial, sans-serif !important;

    font-size: 16px !important;
    font-weight: 600 !important;

    color: #477080 !important;

    margin: 0 !important;
}


/* =========================
   MAIN CARD
   ========================= */

.super-admin-management-page .card {
    background: rgba(255, 255, 255, 0.94) !important;

    border: none !important;

    border-radius: 18px !important;

    box-shadow:
        0 12px 35px rgba(32, 180, 134, 0.15) !important;

    overflow: hidden;
}


/* =========================
   CARD BODY
   ========================= */

.super-admin-management-page .card-body {
    padding: 28px !important;
}


/* =========================
   TABLE
   ========================= */

.super-admin-management-page .table {
    margin-bottom: 0 !important;

    font-family: "Segoe UI", Arial, sans-serif !important;
}


/* TABLE HEADER */

.super-admin-management-page .table thead th {
    background: #32b99b !important;

    color: #ffffff !important;

    font-size: 14px !important;
    font-weight: 700 !important;

    padding: 16px 14px !important;

    border: none !important;

    white-space: nowrap;
}


/* HEADER CORNERS */

.super-admin-management-page .table thead th:first-child {
    border-top-left-radius: 14px;
}

.super-admin-management-page .table thead th:last-child {
    border-top-right-radius: 14px;
}


/* TABLE BODY */

.super-admin-management-page .table tbody td {
    color: #315466 !important;

    font-size: 14px !important;
    font-weight: 500 !important;

    padding: 17px 14px !important;

    vertical-align: middle !important;

    border-bottom: 1px solid rgba(50, 185, 155, 0.12) !important;

    background: rgba(255, 255, 255, 0.65) !important;
}


/* ROW HOVER */

.super-admin-management-page .table tbody tr:hover td {
    background: rgba(226, 249, 242, 0.85) !important;
}


/* =========================
   STATUS BADGE
   ========================= */

.super-admin-management-page .badge {
    border-radius: 8px !important;

    padding: 6px 12px !important;

    font-size: 12px !important;
    font-weight: 700 !important;
}

.super-admin-management-page .badge.bg-success {
    background: #d9f5ec !important;

    color: #12a982 !important;
}

.super-admin-management-page .badge.bg-danger {
    background: #ffe1e3 !important;

    color: #e5484d !important;
}


/* =========================
   DEACTIVATE / ACTIVATE
   ========================= */

.super-admin-management-page .btn-warning {
    background: #ffd166 !important;

    border: none !important;

    color: #ffffff !important;

    border-radius: 6px !important;

    font-size: 12px !important;
    font-weight: 700 !important;

    padding: 7px 13px !important;

    box-shadow: none !important;
}

.super-admin-management-page .btn-warning:hover {
    background: #f5bd3d !important;

    color: #ffffff !important;
}

.super-admin-management-page .btn-success {
    background: #20b486 !important;

    border: none !important;

    color: #ffffff !important;

    border-radius: 6px !important;

    font-size: 12px !important;
    font-weight: 700 !important;

    padding: 7px 13px !important;
}


/* =========================
   TABLE RESPONSIVE
   ========================= */

.super-admin-management-page .table-responsive {
    border-radius: 14px;
}


/* =========================
   MOBILE
   ========================= */

@media (max-width: 768px) {

    .super-admin-management-page {
        width: calc(100% + 20px);

        margin-left: -10px;
        margin-right: -10px;

        padding: 20px 15px 40px 15px;
    }

    .super-admin-management-page .page-title-box h4 {
        font-size: 24px !important;
    }

    .super-admin-management-page .page-title-box p {
        font-size: 14px !important;
    }

    .super-admin-management-page .card-body {
        padding: 18px !important;
    }

    .super-admin-management-page .table thead th,
    .super-admin-management-page .table tbody td {
        font-size: 12px !important;

        padding: 12px 10px !important;
    }
}

</style>
@endsection

@section('content')

<div class="super-admin-management-page">

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                   <h4 class="super-admin-title">
    <span>SUPER ADMIN</span> MANAGEMENT :
</h4>
                    <p class="text-muted mb-0">Manage registered super admin users here</p>
                </div>
            </div>
        </div>
    </div>

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

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Status</th>
                                    <th>Registered On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $key => $user)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->mobile }}</td>
                                        <td>
                                            <span class="badge bg-{{ $user->is_active ? 'success' : 'danger' }}">
                                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>{{ $user->created_at->format('d M Y') }}</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-{{ $user->is_active ? 'warning' : 'success' }} status-change-btn" 
                                                data-bs-toggle="modal" data-bs-target="#statusChangeModal" 
                                                data-id="{{ $user->id }}" data-name="{{ $user->name }}" data-status="{{ $user->is_active }}">
                                                @if($user->is_active)
                                                    <i class="fas fa-ban"></i> Deactivate
                                                @else
                                                    <i class="fas fa-check"></i> Activate
                                                @endif
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No super admin users found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Change Confirmation Modal -->
    <div class="modal fade" id="statusChangeModal" tabindex="-1" aria-labelledby="statusChangeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusChangeModalLabel">Change User Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i id="statusChangeIcon" class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                        </div>
                        <div>
                            <p class="mb-0">Are you sure you want to <span id="statusChangeAction">deactivate</span> the super admin <strong id="statusChangeUserName"></strong>?</p>
                            <p class="text-muted small mb-0">This will change the user's access to the system.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                    <form id="statusChangeForm" method="POST" class="d-inline m-0">
                        @csrf
                        @method('PUT')
                        <button type="submit" id="statusChangeSubmitBtn" class="btn btn-warning">Deactivate</button>
                    </form>
                </div>            
            </div>
        </div>
    </div>
@endsection
