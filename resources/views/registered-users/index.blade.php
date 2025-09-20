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
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fs-16 fw-semibold mb-1">Super Admin Management</h4>
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
