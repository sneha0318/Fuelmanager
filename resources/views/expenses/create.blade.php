@extends('layouts.master-without-page-title')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <div>
                <h4 class="fs-16 fw-semibold mb-1">Add Expense</h4>
                <p class="text-muted mb-0">Add a new expense entry</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary flex-fill flex-md-grow-0" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                    <i class="fas fa-plus me-1"></i> Add Expense Entry
                </button>
                <a href="{{ route('expenses.index') }}" class="btn btn-secondary flex-fill flex-md-grow-0">
                    <i class="fas fa-arrow-left me-1"></i> Back to Category
                </a>
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

{{-- Expense entries table --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-12">
                        @php
                            $totalExpense = $expenses->sum('amount');
                        @endphp
                        <div class="card border-0 shadow-sm bg-primary text-white">
                            <div class="card-body d-flex align-items-center">
                                <div>
                                    <h5 class="mb-1">Total Expense</h5>
                                    <h2 class="mb-0 fw-bold">
                                        &#8377;{{ number_format($totalExpense, 2) }}
                                    </h2>
                                </div>
                                <div class="ms-auto">
                                    <i class="fas fa-wallet fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-centered table-nowrap mb-0">
                        <thead>
                            <tr>
                                <th>Sr</th>
                                <th>Name</th>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenses as $expense)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $expense->name }}</td>
                                    <td>{{ $expense->date }}</td>
                                    <td>{{ $expense->description }}</td>
                                    <td>{{ number_format($expense->amount, 2) }}</td>
                                    <td>{{ ucfirst($expense->payment_method) }}</td>
                                    <td>
                                        {{-- <a href="#" class="btn btn-info btn-sm" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a> --}}
                                        <button type="button" class="btn btn-warning btn-sm" title="Edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editExpenseModal{{ $expense->id }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" title="Delete" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteExpenseModal{{ $expense->id }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- Edit Expense Modal -->
                                <div class="modal fade" id="editExpenseModal{{ $expense->id }}" tabindex="-1" aria-labelledby="editExpenseModalLabel{{ $expense->id }}" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form action="{{ route('expenses.update', $expense->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editExpenseModalLabel{{ $expense->id }}">Edit Expense Entry</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Name</label>
                                                        <div class="position-relative">
                                                            <input type="text" name="name" class="form-control edit-expense-name" value="{{ $expense->name }}" required pattern="[A-Za-z0-9\s]+" title="Only alphabets, numbers and spaces are allowed" data-expense-id="{{ $expense->id }}">
                                                            <div id="edit_expense_success_icon_{{ $expense->id }}" class="position-absolute top-50 end-0 translate-middle-y me-3" style="display: {{ strlen($expense->name) > 0 && strlen($expense->name) <= 200 ? 'block' : 'none' }};">
                                                                <i class="fas fa-check text-success"></i>
                                                            </div>
                                                        </div>
                                                        <div id="edit_expense_warning_{{ $expense->id }}" class="text-danger small" style="display: {{ strlen($expense->name) > 200 ? 'block' : 'none' }};">
                                                            Character limit exceeded! Only 200 characters allowed.
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Date</label>
                                                        <input type="date" name="date" class="form-control" value="{{ $expense->date }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Amount</label>
                                                        <input type="number" step="0.01" name="amount" class="form-control" value="{{ $expense->amount }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Payment Method</label>
                                                        <select name="payment_method" class="form-control" required>
                                                            <option value="">Select</option>
                                                            <option value="cash" {{ $expense->payment_method == 'cash' ? 'selected' : '' }}>Cash</option>
                                                            <option value="bank" {{ $expense->payment_method == 'bank' ? 'selected' : '' }}>Bank</option>
                                                            <option value="cheque" {{ $expense->payment_method == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Description (Optional)</label>
                                                        <input type="text" name="description" class="form-control" value="{{ $expense->description }}">
                                                    </div>
                                                    <input type="hidden" name="category_id" value="{{ $expense->category_id }}">
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Update</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Delete Expense Modal -->
                                <div class="modal fade" id="deleteExpenseModal{{ $expense->id }}" tabindex="-1" aria-labelledby="deleteExpenseModalLabel{{ $expense->id }}" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteExpenseModalLabel{{ $expense->id }}">Delete Expense</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                                                    </div>
                                                    <div>
                                                        <p class="mb-0">Are you sure you want to delete expense <strong>{{ $expense->name }}</strong>?</p>
                                                        <p class="text-muted small mb-0">This action cannot be undone.</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                                                <form action="{{ route('expenses.destroy', $expense->id) }}" method="POST" class="d-inline m-0">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-delete-hover">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No expenses found for this category.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('expenses.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addExpenseModalLabel">Add Expense Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="expense_name" class="form-label">Name</label>
                        <div class="position-relative">
                            <input type="text" name="name" id="expense_name" class="form-control" required pattern="[A-Za-z0-9\s]+" title="Only alphabets, numbers and spaces are allowed">
                            <div id="expense_success_icon" class="position-absolute top-50 end-0 translate-middle-y me-3" style="display: none;">
                                <i class="fas fa-check text-success"></i>
                            </div>
                        </div>
                        <div id="expense_warning" class="text-danger small" style="display: none;">
                            Character limit exceeded! Only 200 characters allowed.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="expense_date" class="form-label">Date</label>
                        <input type="date" name="date" id="expense_date" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label for="expense_amount" class="form-label">Amount</label>
                        <input type="number" step="0.01" name="amount" id="expense_amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="expense_payment_method" class="form-label">Payment Method</label>
                        <select name="payment_method" id="expense_payment_method" class="form-control" required>
                            <option value="">Select</option>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="expense_description" class="form-label">Description (Optional)</label>
                        <input type="text" name="description" id="expense_description" class="form-control">
                    </div>
                    <input type="hidden" name="category_id" value="{{ $category->id ?? '' }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add Expense name validation
            const expenseNameInput = document.getElementById('expense_name');
            const expenseWarning = document.getElementById('expense_warning');
            const expenseSuccessIcon = document.getElementById('expense_success_icon');

            if (expenseNameInput) {
                // Check initial state
                if (expenseNameInput.value.length > 200) {
                    expenseWarning.style.display = 'block';
                    expenseNameInput.classList.add('is-invalid');
                    expenseSuccessIcon.style.display = 'none';
                } else if (expenseNameInput.value.length > 0) {
                    expenseSuccessIcon.style.display = 'block';
                }

                expenseNameInput.addEventListener('input', function() {
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
                        expenseWarning.style.display = 'block';
                        this.classList.add('is-invalid');
                        expenseSuccessIcon.style.display = 'none';
                    } else if (finalLength > 0) {
                        expenseWarning.style.display = 'none';
                        this.classList.remove('is-invalid');
                        expenseSuccessIcon.style.display = 'block';
                    } else {
                        expenseWarning.style.display = 'none';
                        this.classList.remove('is-invalid');
                        expenseSuccessIcon.style.display = 'none';
                    }
                });

                // Reset form when modal is opened
                const addExpenseModal = document.getElementById('addExpenseModal');
                if (addExpenseModal) {
                    addExpenseModal.addEventListener('show.bs.modal', function() {
                        expenseNameInput.value = '';
                        expenseWarning.style.display = 'none';
                        expenseSuccessIcon.style.display = 'none';
                        expenseNameInput.classList.remove('is-invalid');
                    });
                }

                // Prevent form submission if over limit
                const expenseForm = expenseNameInput.closest('form');
                if (expenseForm) {
                    expenseForm.addEventListener('submit', function(e) {
                        if (expenseNameInput.value.length > 200) {
                            e.preventDefault();
                            expenseWarning.style.display = 'block';
                            expenseNameInput.classList.add('is-invalid');
                            expenseSuccessIcon.style.display = 'none';
                            return false;
                        }
                    });
                }
            }

            // Edit Expense name validation for all edit modals
            const editExpenseInputs = document.querySelectorAll('.edit-expense-name');
            
            editExpenseInputs.forEach(function(input) {
                const expenseId = input.dataset.expenseId;
                const warningElement = document.getElementById('edit_expense_warning_' + expenseId);
                const successIconElement = document.getElementById('edit_expense_success_icon_' + expenseId);
                
                input.addEventListener('input', function() {
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
                        if (warningElement) {
                            warningElement.style.display = 'block';
                        }
                        this.classList.add('is-invalid');
                        if (successIconElement) {
                            successIconElement.style.display = 'none';
                        }
                    } else if (finalLength > 0) {
                        if (warningElement) {
                            warningElement.style.display = 'none';
                        }
                        this.classList.remove('is-invalid');
                        if (successIconElement) {
                            successIconElement.style.display = 'block';
                        }
                    } else {
                        if (warningElement) {
                            warningElement.style.display = 'none';
                        }
                        this.classList.remove('is-invalid');
                        if (successIconElement) {
                            successIconElement.style.display = 'none';
                        }
                    }
                });

                // Prevent form submission if over limit
                const editForm = input.closest('form');
                if (editForm) {
                    editForm.addEventListener('submit', function(e) {
                        if (input.value.length > 200) {
                            e.preventDefault();
                            if (warningElement) {
                                warningElement.style.display = 'block';
                            }
                            input.classList.add('is-invalid');
                            if (successIconElement) {
                                successIconElement.style.display = 'none';
                            }
                            return false;
                        }
                    });
                }
            });
        });
    </script>
@endsection
