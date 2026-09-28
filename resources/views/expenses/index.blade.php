{{-- filepath: c:\xampp\htdocs\fuelmanager\resources\views\expenses\index.blade.php --}}
@extends('layouts.master-without-page-title')

@section('css')
    <style>
        .expenses-management-page {
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

        /* Page Title */
        .expenses-management-page .page-title-box h4 {
            font-size: 30px !important;
            font-weight: 900 !important;
            color: #073b4c !important;
            margin-bottom: 6px !important;
        }

        .expenses-management-page .expenses-green-text {
            color: #20b486 !important;
        }

        .expenses-management-page .page-title-box p {
            font-size: 16px !important;
            font-weight: 600 !important;
            color: #477080 !important;
            margin-bottom: 0 !important;
        }

        /* Card */
        .expenses-management-page .card {
            background: rgba(255, 255, 255, 0.96) !important;
            border: none !important;
            border-radius: 18px !important;
            box-shadow: 0 8px 25px rgba(7, 59, 76, 0.08);
        }

        /* Table */
        .expenses-management-page table th {
            background-color: #f8fdfb !important;
            color: #073b4c !important;
            font-weight: 600 !important;
        }

        .expenses-management-page table td {
            color: #315466 !important;
            vertical-align: middle;
        }

        /* Add Category */
        .expenses-management-page .btn-primary {
            background-color: #20b486 !important;
            border-color: #20b486 !important;
            color: #ffffff !important;
            border-radius: 8px;
        }

        /* Add Expense */
        .expenses-management-page .btn-success {
            background-color: #20b486 !important;
            border-color: #20b486 !important;
            color: #ffffff !important;
        }

        /* Delete */
        .expenses-management-page .btn-danger {
            background-color: #ff6268 !important;
            border-color: #ff6268 !important;
            color: #ffffff !important;
        }

        /* Form Labels */
        .expenses-management-page .form-label {
            color: #073b4c !important;
            font-weight: 600 !important;
        }

        /* Alerts */
        .expenses-management-page .alert {
            border-radius: 10px;
        }

        /* Modals */
        .expenses-management-page .modal-content {
            border: none !important;
            border-radius: 18px !important;
            box-shadow: 0 10px 35px rgba(7, 59, 76, 0.15);
        }

        .expenses-management-page .modal-header {
            border-bottom: 1px solid #e8eeee;
        }

        .expenses-management-page .modal-title {
            color: #073b4c !important;
            font-weight: 700;
        }

        .expenses-management-page .modal-footer {
            border-top: 1px solid #e8eeee;
        }
    </style>
@endsection
@section('content')

<div class="expenses-management-page">

    <div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <div>
              <h4 class="fs-16 fw-semibold mb-1">
    EXPENSES <span class="expenses-green-text">MANAGEMENT :</span>
</h4>
<p class="text-muted mb-0">
    Manage your expenses here
</p>
            </div>
            <div class="mt-3 mt-sm-0">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus me-1"></i> Add Category
                </button>
            </div>
        </div>
    </div>
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
                                <th>Category  Name</th>
                                <th>Pump Name</th>
                                <th>Total Expenses</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $category)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        @php
                                            // Extract just the category name from "CategoryName (PumpName)" format
                                            $displayName = $category->category_name;
                                            if (preg_match('/^(.+) \((.+)\)$/', $category->category_name, $matches)) {
                                                $displayName = $matches[1];
                                            }
                                        @endphp
                                        {{ $displayName }}
                                        @if($category->pump)
                                            <small class="text-muted d-block">{{ $category->pump->pump_name }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $category->pump->pump_name ?? 'N/A' }}</td>
                                    <td>
                                        {{-- Calculate and display total expenses for this category --}}
                                        &#8377;{{ number_format($category->expenses->sum('amount'), 2) }}
                                    </td>
                                    <td>
                                        <a href="{{ route('expenses.create', ['category_id' => $category->id]) }}" class="btn btn-success btn-sm" title="Add Expense">
                                            <i class="fas fa-plus"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger btn-sm delete-category-btn" 
                                            data-bs-toggle="modal" data-bs-target="#deleteCategoryModal"
                                            data-id="{{ $category->id }}"
                                            data-name="{{ $category->name }}"
                                            title="Delete Category">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">No expenses found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('categories.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name</label>
                        <div class="position-relative">
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="category_name" name="name" value="{{ old('name') }}" required pattern="[A-Za-z0-9\s]+" title="Only alphabets, numbers and spaces are allowed">
                            <div id="category_success_icon" class="position-absolute top-50 end-0 translate-middle-y me-3" style="display: none;">
                                <i class="fas fa-check text-success"></i>
                            </div>
                        </div>
                        <div id="category_warning" class="text-danger small" style="display: none;">
                            Character limit exceeded! Only 200 characters allowed.
                        </div>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="pump_id" class="form-label">Pump</label>
                        <select class="form-select @error('pump_id') is-invalid @enderror" id="pump_id" name="pump_id">
                            <option value="">Select Pump</option>
                            @foreach($pumps as $pump)
                                <option value="{{ $pump->id }}" {{ old('pump_id') == $pump->id ? 'selected' : '' }}>{{ $pump->pump_name }}</option>
                            @endforeach
                        </select>
                        @error('pump_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCategoryModalLabel">Delete Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                    </div>
                    <div>
                        <p class="mb-0">Are you sure you want to delete category <strong id="deleteCategoryName"></strong>?</p>
                        <p class="text-muted small mb-0">This action cannot be undone.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteCategoryForm" method="POST" class="d-inline m-0">
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
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Delete Category Modal functionality
            const deleteButtons = document.querySelectorAll('.delete-category-btn');
            const deleteForm = document.getElementById('deleteCategoryForm');
            const deleteCategoryName = document.getElementById('deleteCategoryName');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const categoryId = this.dataset.id;
                    const categoryName = this.dataset.name;

                    // Set the form action
                    deleteForm.action = `/categories/${categoryId}`;
                    // Update the category name in the modal
                    deleteCategoryName.textContent = categoryName;
                });
            });

            // Category name validation
            const categoryInput = document.getElementById('category_name');
            const categoryWarning = document.getElementById('category_warning');
            const categorySuccessIcon = document.getElementById('category_success_icon');

            if (categoryInput) {
                // Check if already over limit on load
                if (categoryInput.value.length > 200) {
                    categoryWarning.style.display = 'block';
                    categoryInput.classList.add('is-invalid');
                    categorySuccessIcon.style.display = 'none';
                } else if (categoryInput.value.length > 0) {
                    categorySuccessIcon.style.display = 'block';
                }

                categoryInput.addEventListener('input', function() {
                    const value = this.value;
                    const length = value.length;
                    
                    // Remove invalid characters first (keep only alphanumeric and spaces)
                    const cleanValue = value.replace(/[^A-Za-z0-9\s]/g, '');
                    if (cleanValue !== value) {
                        this.value = cleanValue;
                    }
                    
                    const finalLength = this.value.length;
                    
                    // Show/hide warning and success icon based on character count
                    if (finalLength > 200) {
                        categoryWarning.style.display = 'block';
                        this.classList.add('is-invalid');
                        categorySuccessIcon.style.display = 'none';
                    } else if (finalLength > 0) {
                        categoryWarning.style.display = 'none';
                        this.classList.remove('is-invalid');
                        categorySuccessIcon.style.display = 'block';
                    } else {
                        categoryWarning.style.display = 'none';
                        this.classList.remove('is-invalid');
                        categorySuccessIcon.style.display = 'none';
                    }
                });

                // Reset form when modal is opened
                const addCategoryModal = document.getElementById('addCategoryModal');
                if (addCategoryModal) {
                    addCategoryModal.addEventListener('show.bs.modal', function() {
                        categoryInput.value = '';
                        categoryWarning.style.display = 'none';
                        categorySuccessIcon.style.display = 'none';
                        categoryInput.classList.remove('is-invalid');
                    });
                }

                // Prevent form submission if over limit
                const categoryForm = categoryInput.closest('form');
                if (categoryForm) {
                    categoryForm.addEventListener('submit', function(e) {
                        if (categoryInput.value.length > 200) {
                            e.preventDefault();
                            categoryWarning.style.display = 'block';
                            categoryInput.classList.add('is-invalid');
                            categorySuccessIcon.style.display = 'none';
                            return false;
                        }
                    });
                }
            }
        });
    </script>
@endsection
