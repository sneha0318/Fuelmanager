@extends('layouts.master-without-page-title')
@section('css')
    <!-- DataTables -->
    <link href="{{ URL::asset('/assets/libs/datatables/datatables.min.css') }}" rel="stylesheet" type="text/css" />

    <style>
        .stock-management-page {
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
        .stock-management-page .page-title-box h4 {
            font-size: 30px !important;
            font-weight: 900 !important;
            color: #073b4c !important;
            margin-bottom: 6px !important;
        }

        .stock-management-page .stock-green-text {
            color: #20b486 !important;
        }

        .stock-management-page .page-title-box p {
            font-size: 16px !important;
            font-weight: 600 !important;
            color: #477080 !important;
            margin-bottom: 0 !important;
        }

        /* Cards */
        .stock-management-page .card {
            background: rgba(255, 255, 255, 0.96) !important;
            border: none !important;
            border-radius: 18px !important;
            box-shadow: 0 8px 25px rgba(7, 59, 76, 0.08);
        }

        /* Table */
        .stock-management-page table th {
            background-color: #f8fdfb !important;
            color: #073b4c !important;
            font-weight: 600 !important;
        }

        .stock-management-page table td {
            color: #315466 !important;
            vertical-align: middle;
        }

        /* Add New Stock */
        .stock-management-page .btn-primary {
            background-color: #20b486 !important;
            border-color: #20b486 !important;
            color: #ffffff !important;
            border-radius: 8px;
        }

        /* Add to Stock */
        .stock-management-page .btn-success {
            background-color: #20b486 !important;
            border-color: #20b486 !important;
            color: #ffffff !important;
        }

        /* Edit */
        .stock-management-page .btn-warning {
            background-color: #ffd66b !important;
            border-color: #ffd66b !important;
            color: #073b4c !important;
        }

        /* View */
        .stock-management-page .btn-info {
            background-color: #6ec6e8 !important;
            border-color: #6ec6e8 !important;
            color: #ffffff !important;
        }

        /* Form Labels */
        .stock-management-page .form-label {
            color: #073b4c !important;
            font-weight: 600 !important;
        }

        /* Alerts */
        .stock-management-page .alert {
            border-radius: 10px;
        }

        /* Modals */
        .stock-management-page .modal-content {
            border: none !important;
            border-radius: 18px !important;
            box-shadow: 0 10px 35px rgba(7, 59, 76, 0.15);
        }

        .stock-management-page .modal-header {
            border-bottom: 1px solid #e8eeee;
        }

        .stock-management-page .modal-title {
            color: #073b4c !important;
            font-weight: 700;
        }

        .stock-management-page .modal-footer {
            border-top: 1px solid #e8eeee;
        }

        /* DataTable text */
        .stock-management-page .dataTables_wrapper {
            color: #315466;
        }

        .stock-management-page .dataTables_wrapper .dataTables_filter input,
        .stock-management-page .dataTables_wrapper .dataTables_length select {
            border: 1px solid #d9e5e5;
            border-radius: 8px;
        }
    </style>
@endsection

@section('content')

    <div class="stock-management-page">

        <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                 <h4 class="fs-16 fw-semibold mb-1">
    STOCK <span class="stock-green-text">MANAGEMENT :</span>
</h4>
<p class="text-muted mb-0">
    Manage your stock here
</p>
                </div>
                <div class="mt-3 mt-sm-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStockModal">
                        <i class="fas fa-plus me-1"></i> Add New Stock
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
                        <table id="datatable" class="table table-centered table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Item</th>
                                    <th>Pump</th>
                                    <th>Initial Quantity</th>
                                    <th>Sold Quantity</th>
                                    <th>Remaining Quantity</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stocks as $key => $stock)
                                    @php
                                        // Get total stock for this pump and item
                                        $totalStock = \App\Models\Stock::where('pump_id', $stock->pump_id)
                                            ->where('item_id', $stock->item_id)
                                            ->sum('quantity');

                                        // Get today's sales for display (sold quantity)
                                        $todaySales = \App\Models\PaymentItemEntry::whereHas('nozzle', function($query) use ($stock) {
                                            $query->where('item_id', $stock->item_id)
                                                  ->where('pump_id', $stock->pump_id);
                                        })
                                        ->whereDate('created_at', $stock->date)
                                        ->sum('litre');

                                        // Calculate remaining quantity by subtracting sold quantity from total stock
                                        $remainingQuantity = $totalStock - $todaySales;
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $stock->item->name }}</td>
                                        <td>{{ $stock->pump->pump_name }}</td>
                                        <td>{{ number_format($stock->quantity, 2) }} {{ $stock->item->item_type === 'KG' ? 'KG' : 'L' }}</td>
                                        <td>{{ number_format($todaySales, 2) }} {{ $stock->item->item_type === 'KG' ? 'KG' : 'L' }}</td>
                                        <td>{{ number_format($remainingQuantity, 2) }} {{ $stock->item->item_type === 'KG' ? 'KG' : 'L' }}</td>
                                        <td>{{ date('d-m-Y', strtotime($stock->date)) }}</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-success add-stock-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#addToStockModal{{ $stock->id }}"
                                                data-stock-id="{{ $stock->id }}"
                                                data-stock-quantity="{{ $stock->quantity }}">

                                                <i class="fas fa-plus"></i> Add to Stock
                                            </button>
                                            <a href="{{ route('stock-management.show', $stock->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-warning edit-stock-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editStockModal{{ $stock->id }}"
                                                data-stock-id="{{ $stock->id }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="{{ route('stock-management.destroy', $stock->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                {{-- <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this stock?')">
                                                    <i class="fas fa-trash"></i>
                                                </button> --}}
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Sales Details Modal -->
                                    <div class="modal fade" id="showSalesModal{{ $stock->id }}" tabindex="-1" aria-labelledby="showSalesModalLabel{{ $stock->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header bg-light">
                                                    <h5 class="modal-title text-dark" id="showSalesModalLabel{{ $stock->id }}">
                                                        <i class="fas fa-list me-2"></i>Sales Details - {{ $stock->item->name }} ({{ $stock->pump->pump_name }})
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    @php
                                                        $sales = \App\Models\PaymentItemEntry::whereHas('nozzle', function($query) use ($stock) {
                                                            $query->where('item_id', $stock->item_id)
                                                                  ->where('pump_id', $stock->pump_id);
                                                        })
                                                        ->whereDate('created_at', $stock->date)
                                                        ->with(['paymentItem', 'vehicle'])
                                                        ->get();
                                                    @endphp


                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Edit Stock Modal -->
                                    <div class="modal fade" id="editStockModal{{ $stock->id }}" tabindex="-1" aria-labelledby="editStockModalLabel{{ $stock->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-md">
                                            <div class="modal-content">
                                                <div class="modal-header bg-light">
                                                    <h5 class="modal-title text-dark" id="editStockModalLabel{{ $stock->id }}">
                                                        <i class="fas fa-edit me-2"></i>Edit Stock
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('stock-management.update', $stock->id) }}" method="POST" id="editStockForm{{ $stock->id }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <!-- Item -->
                                                            <div class="col-md-12 mb-3">
                                                                <label for="edit_item_id{{ $stock->id }}" class="form-label">Item</label>
                                                                <select class="form-select" id="edit_item_id{{ $stock->id }}" name="item_id" required>
                                                                    <option value="">Select Item</option>
                                                                    @foreach($items as $item)
                                                                        <option value="{{ $item->id }}" {{ $stock->item_id == $item->id ? 'selected' : '' }}>
                                                                            {{ $item->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <!-- Pump -->
                                                            <div class="col-md-12 mb-3">
                                                                <label for="edit_pump_id{{ $stock->id }}" class="form-label">Pump</label>
                                                                <select class="form-select" id="edit_pump_id{{ $stock->id }}" name="pump_id" required>
                                                                    <option value="">Select Pump</option>
                                                                    @foreach($pumps as $pump)
                                                                        <option value="{{ $pump->id }}" {{ $stock->pump_id == $pump->id ? 'selected' : '' }}>
                                                                            {{ $pump->pump_name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <!-- Quantity -->
                                                            <div class="col-md-12 mb-3">
                                                                <label for="edit_quantity{{ $stock->id }}" class="form-label">Initial Quantity ({{ $stock->item->item_type === 'KG' ? 'KG' : 'L' }})</label>
                                                                <input type="number" step="0.01" class="form-control" id="edit_quantity{{ $stock->id }}" name="quantity" value="{{ $stock->quantity }}" required>
                                                            </div>
                                                            <!-- Sold Quantity -->
                                                            <div class="col-md-12 mb-3">
                                                                <label class="form-label">Sold Quantity ({{ $stock->item->item_type === 'KG' ? 'KG' : 'L' }})</label>
                                                                @php
                                                                    $soldQuantity = \App\Models\PaymentItemEntry::whereHas('nozzle', function($query) use ($stock) {
                                                                        $query->where('item_id', $stock->item_id)
                                                                              ->where('pump_id', $stock->pump_id);
                                                                    })
                                                                    ->whereDate('created_at', $stock->date)
                                                                    ->sum('litre');
                                                                @endphp
                                                                <input type="text" class="form-control" value="{{ number_format($soldQuantity, 2) }}" readonly>
                                                            </div>
                                                            <!-- Remaining Quantity -->
                                                            <div class="col-md-12 mb-3">
                                                                <label class="form-label">Remaining Quantity ({{ $stock->item->item_type === 'KG' ? 'KG' : 'L' }})</label>
                                                                @php
                                                                    $totalStockForItem = \App\Models\Stock::where('pump_id', $stock->pump_id)
                                                                        ->where('item_id', $stock->item_id)
                                                                        ->sum('quantity');
                                                                @endphp
                                                                <input type="text" class="form-control" value="{{ number_format($totalStockForItem - $soldQuantity, 2) }}" readonly>
                                                            </div>
                                                            <!-- Date -->
                                                            <div class="col-md-12 mb-3">
                                                                <label for="edit_date{{ $stock->id }}" class="form-label">Date</label>
                                                                <input type="date" class="form-control" id="edit_date{{ $stock->id }}" name="date" value="{{ date('Y-m-d', strtotime($stock->date)) }}" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-warning"><i class="fas fa-edit me-2"></i>Update Stock</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Add to Stock Modal -->
                                    <div class="modal fade" id="addToStockModal{{ $stock->id }}" tabindex="-1" aria-labelledby="addToStockModalLabel{{ $stock->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-md">
                                            <div class="modal-content">
                                                <div class="modal-header bg-light">
                                                    <h5 class="modal-title text-dark" id="addToStockModalLabel{{ $stock->id }}">
                                                        <i class="fas fa-plus me-2"></i>Add to Existing Stock
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('stock-management.store') }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        @if($errors->any())
                                                            <div class="alert alert-danger">
                                                                <ul class="mb-0">
                                                                    @foreach($errors->all() as $error)
                                                                        <li>{{ $error }}</li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        @endif

                                                        <input type="hidden" name="item_id" value="{{ $stock->item_id }}">
                                                        <input type="hidden" name="pump_id" value="{{ $stock->pump_id }}">
                                                        <input type="hidden" name="date" value="{{ $stock->date->format('Y-m-d') }}">

                                                        <div class="row">
                                                            <div class="col-md-12 mb-3 alert alert-info">
                                                                <p class="mb-0"><strong>Note:</strong> Adding stock here will increase the quantity of the existing stock entry.</p>
                                                                <p class="mb-0 small" id="current-quantity-display">Current quantity: {{ number_format($stock->quantity, 2) }} {{ $stock->item->item_type === 'KG' ? 'KG' : 'L' }}</p>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <!-- Quantity -->
                                                            <div class="col-md-12 mb-3">
                                                                <label for="quantity" class="form-label" id="add-quantity-label">Additional Quantity</label>
                                                                <input type="number" step="0.01" class="form-control @error('quantity') is-invalid @enderror" id="quantity" name="quantity" required>
                                                                @error('quantity')
                                                                    <span class="invalid-feedback" role="alert">
                                                                        <strong>{{ $message }}</strong>
                                                                    </span>
                                                                @enderror
                                                                <small class="text-muted" id="add-quantity-help">Enter the quantity you want to add to the current stock</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-success"><i class="fas fa-plus me-1"></i>Add to Stock</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                                @if($stocks->isEmpty())
                                    <tr>
                                        <td colspan="6" class="text-center">No stock found</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Stock Modal -->
    <div class="modal fade" id="addStockModal" tabindex="-1" aria-labelledby="addStockModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title text-dark" id="addStockModalLabel"><i class="fas fa-plus me-2"></i>Add New Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('stock-management.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">
                            <!-- Pump -->
                            <div class="col-md-12 mb-3">
                                <label for="pump_id" class="form-label">Pump</label>
                                <select class="form-select @error('pump_id') is-invalid @enderror" id="pump_id" name="pump_id" required>
                                    <option value="">Select Pump</option>
                                    @foreach($pumps as $pump)
                                        <option value="{{ $pump->id }}">{{ $pump->pump_name }}</option>
                                    @endforeach
                                </select>
                                @error('pump_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <!-- Item -->
                            <div class="col-md-12 mb-3">
                                <label for="item_id" class="form-label">Item</label>
                                <select class="form-select @error('item_id') is-invalid @enderror" id="item_id" name="item_id" required>
                                    <option value="">Select Item</option>
                                    @foreach($items as $item)
                                        <option value="{{ $item->id }}" class="item-option">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                                @error('item_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <!-- Quantity -->
                            <div class="col-md-12 mb-3">
                                <label for="quantity" class="form-label" id="quantity-label">Quantity</label>
                                <input type="number" step="0.01" class="form-control @error('quantity') is-invalid @enderror" id="quantity" name="quantity" required>
                                @error('quantity')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                <small class="text-muted" id="quantity-help">Enter the quantity</small>
                            </div>
                        </div>
                        <div class="row">
                            <!-- Date -->
                            <div class="col-md-12 mb-3">
                                <label for="date" class="form-label">Date</label>
                                <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ date('Y-m-d') }}" required>
                                @error('date')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <!-- Required datatable js -->
    <script src="{{ URL::asset('/assets/libs/datatables/datatables.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/libs/jszip/jszip.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/libs/pdfmake/pdfmake.min.js') }}"></script>

    <!-- Datatable init js -->
    <script>
        $(document).ready(function() {
            $('#datatable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });

            // Reset form when modal is closed
            $('#addStockModal').on('hidden.bs.modal', function () {
                $(this).find('form')[0].reset();
                // Set current date when modal is opened
                $('#date').val(new Date().toISOString().slice(0, 10));
            });
            
            // Filter items based on selected pump
            $('#pump_id').on('change', function() {
                const pumpId = $(this).val();
                
                // Clear item dropdown
                $('#item_id').empty().append('<option value="">Loading items...</option>');
                
                if (!pumpId) {
                    $('#item_id').empty().append('<option value="">Select Item</option>');
                    return;
                }
                
                // Fetch items for the selected pump via AJAX
                $.ajax({
                    url: '/get-pump-items/' + pumpId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        $('#item_id').empty().append('<option value="">Select Item</option>');
                        
                        if (response.items && response.items.length > 0) {
                            $.each(response.items, function(key, item) {
                                $('#item_id').append('<option value="' + item.id + '" data-type="' + (item.item_type || 'Litre') + '">' + item.name + '</option>');
                            });
                        } else {
                            $('#item_id').append('<option value="" disabled>No items available for this pump</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching pump items:', error);
                        $('#item_id').empty().append('<option value="">Error loading items</option>');
                    }
                });
            });

            // Update UI labels based on selected item type
            $('#item_id').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const itemType = selectedOption.data('type') || 'Litre';
                const unit = itemType === 'KG' ? 'KG' : 'L';
                
                // Update labels and help text
                $('#quantity-label').text('Quantity (' + unit + ')');
                $('#quantity-help').text('Enter the quantity in ' + unit);
            });

            // Handle edit form submission
            $('[id^="editStockForm"]').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const formData = form.serialize();

                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        // Close the modal
                        form.closest('.modal').modal('hide');
                        // Reload the page to show updated data
                        location.reload();
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            // Validation errors
                            const errors = xhr.responseJSON.errors;
                            let errorHtml = '<div class="alert alert-danger"><ul>';
                            for (let field in errors) {
                                errorHtml += '<li>' + errors[field][0] + '</li>';
                            }
                            errorHtml += '</ul></div>';
                            form.find('.modal-body').prepend(errorHtml);
                        } else {
                            alert('An error occurred while updating the stock.');
                        }
                    }
                });
            });
        });
    </script>
@endsection
