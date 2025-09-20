@extends('layouts.master-without-page-title')
@section('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        // JavaScript to populate the Edit Modal with nozzle data
        document.addEventListener('DOMContentLoaded', function () {
            console.log('DOM Content Loaded');

            // Get CSRF token from meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            console.log('CSRF Token:', csrfToken);

            // Test the API endpoint first
            console.log('Testing API endpoint...');
            fetch('/nozzles/test-api', {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    console.log('Test API response:', data);
                })
                .catch(error => {
                    console.error('Error testing API:', error);
                });

            const editButtons = document.querySelectorAll('.edit-nozzle-btn');
            editButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const nozzleId = this.dataset.id;
                    const pumpId = this.dataset.pumpId;
                    const nozzleNumber = this.dataset.nozzleNumber;
                    const itemId = this.dataset.itemId;
                    const status = this.dataset.status;

                    console.log('Edit button clicked:', { nozzleId, pumpId, nozzleNumber, itemId, status });

                    // Populate the modal fields
                    document.getElementById('editNozzleForm').action = `/nozzles/${nozzleId}`;
                    document.getElementById('editPumpId').value = pumpId;
                    document.getElementById('editNozzleNumber').value = nozzleNumber;
                    document.getElementById('editItemId').value = itemId;
                    document.getElementById('editStatus').value = status;

                    // Set the current nozzle number for the edit form
                    document.getElementById('editNozzleNumber').dataset.currentNumber = nozzleNumber;

                    // Load nozzle numbers for the selected pump
                    loadNozzleNumbers(pumpId, document.getElementById('editNozzleNumber'), true);
                });
            });

            // Function to load all nozzle numbers when a pump is selected
            function loadNozzleNumbers(pumpId, nozzleNumberSelect, isEdit = false) {
                console.log('Loading nozzle numbers for pump:', pumpId, 'isEdit:', isEdit);

                if (!pumpId) {
                    console.log('No pump ID provided');
                    nozzleNumberSelect.innerHTML = '<option value="">Select Nozzle Number</option>';
                    return;
                }

                // Show loading indicator
                nozzleNumberSelect.innerHTML = '<option value="">Loading...</option>';

                // Use the test API first to verify fetch is working
                const testUrl = '/nozzles/test-api';
                const url = `/nozzles/available-numbers?pump_id=${pumpId}`;

                console.log('Fetching from URL:', url);

                // First try the test API to verify fetch is working
                fetch(testUrl, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                    .then(response => {
                        console.log('Test API response status:', response.status);
                        return response.json();
                    })
                    .then(testData => {
                        console.log('Test API data:', testData);

                        // Now try the actual API
                        return fetch(url, {
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            }
                        });
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Received data:', data);
                        nozzleNumberSelect.innerHTML = '<option value="">Select Nozzle Number</option>';

                        // Add all numbers
                        if (data.numbers && data.numbers.length > 0) {
                            data.numbers.forEach(number => {
                                const option = document.createElement('option');
                                option.value = number.value;
                                option.textContent = number.text;

                                // If the number is already used, add a visual indicator
                                if (number.used) {
                                    option.textContent += ' (Used)';
                                    option.disabled = !isEdit; // Disable used numbers in add form, but allow in edit form
                                }

                                nozzleNumberSelect.appendChild(option);
                            });
                        } else {
                            console.log('No numbers returned, using fallback');
                            // If no numbers are returned, create options based on total_nozzles
                            const totalNozzles = data.total_nozzles || 10; // Default to 10 if not provided
                            for (let i = 1; i <= totalNozzles; i++) {
                                const option = document.createElement('option');
                                option.value = i;
                                option.textContent = i;
                                nozzleNumberSelect.appendChild(option);
                            }
                        }

                        // If editing, select the current nozzle number
                        if (isEdit && nozzleNumberSelect.dataset.currentNumber) {
                            nozzleNumberSelect.value = nozzleNumberSelect.dataset.currentNumber;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching nozzle numbers:', error);
                        nozzleNumberSelect.innerHTML = '<option value="">Error loading nozzle numbers</option>';

                        // Fallback: Create options based on a default range
                        const totalNozzles = 10; // Default value
                        for (let i = 1; i <= totalNozzles; i++) {
                            const option = document.createElement('option');
                            option.value = i;
                            option.textContent = i;
                            nozzleNumberSelect.appendChild(option);
                        }
                    });
            }

            // Function to load items for a selected pump
            function loadItemsForPump(pumpId, itemSelect) {
                console.log('Loading items for pump:', pumpId);

                if (!pumpId) {
                    console.log('No pump ID provided');
                    itemSelect.innerHTML = '<option value="">Select Item</option>';
                    return;
                }

                // Show loading indicator
                itemSelect.innerHTML = '<option value="">Loading...</option>';

                // Fetch items for the selected pump
                fetch(`/nozzles/items-by-pump/${pumpId}`, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Received items data:', data);
                    itemSelect.innerHTML = '<option value="">Select Item</option>';

                    if (data.length === 0) {
                        const option = document.createElement('option');
                        option.value = "";
                        option.textContent = "No items available for this pump";
                        option.disabled = true;
                        itemSelect.appendChild(option);
                    } else {
                        data.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.id;
                            option.textContent = item.name;
                            itemSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching items:', error);
                    itemSelect.innerHTML = '<option value="">Error loading items</option>';
                });
            }

            // Add event listeners for pump selection in add form
            const addPumpSelect = document.getElementById('pump_id');
            const addNozzleNumberSelect = document.getElementById('nozzle_number');
            const addItemSelect = document.getElementById('item_id');

            if (addPumpSelect) {
                console.log('Add pump select found');
                addPumpSelect.addEventListener('change', function() {
                    console.log('Add pump select changed to:', this.value);
                    loadNozzleNumbers(this.value, addNozzleNumberSelect);
                    loadItemsForPump(this.value, addItemSelect);
                });

                // Trigger change event on page load if a pump is already selected
                if (addPumpSelect.value) {
                    console.log('Add pump already selected:', addPumpSelect.value);
                    loadNozzleNumbers(addPumpSelect.value, addNozzleNumberSelect);
                    loadItemsForPump(addPumpSelect.value, addItemSelect);
                }
            } else {
                console.log('Add pump select not found');
            }

            // Add event listeners for pump selection in edit form
            const editPumpSelect = document.getElementById('editPumpId');
            const editNozzleNumberSelect = document.getElementById('editNozzleNumber');
            const editItemSelect = document.getElementById('editItemId');

            if (editPumpSelect) {
                console.log('Edit pump select found');
                editPumpSelect.addEventListener('change', function() {
                    console.log('Edit pump select changed to:', this.value);
                    loadNozzleNumbers(this.value, editNozzleNumberSelect, true);
                    loadItemsForPump(this.value, editItemSelect);
                });

                // Trigger change event on page load if a pump is already selected
                if (editPumpSelect.value) {
                    console.log('Edit pump already selected:', editPumpSelect.value);
                    loadNozzleNumbers(editPumpSelect.value, editNozzleNumberSelect, true);
                    loadItemsForPump(editPumpSelect.value, editItemSelect);
                }
            } else {
                console.log('Edit pump select not found');
            }

            // Check if we need to load nozzle numbers for any pre-selected pumps
            console.log('Initial check for pre-selected pumps');
            if (addPumpSelect && addPumpSelect.value) {
                loadNozzleNumbers(addPumpSelect.value, addNozzleNumberSelect);
            }
            if (editPumpSelect && editPumpSelect.value) {
                loadNozzleNumbers(editPumpSelect.value, editNozzleNumberSelect, true);
            }

            // Add a direct test button for debugging
    const addNozzleModal = document.getElementById('addNozzleModal');
});
    </script>
@endsection
@section('title')
    Nozzle Management
@endsection

@section('css')
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fs-16 fw-semibold mb-1">Nozzle Management</h4>
                    <p class="text-muted mb-0">Manage your nozzles here</p>
                </div>
                <div class="mt-3 mt-sm-0">
                    <!-- Button to trigger Add Nozzle modal -->
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNozzleModal">
                        <i class="fas fa-plus me-1"></i> Add New Nozzle
                    </button>
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

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Pump Name</th>
                                    <th>Nozzle Number</th>
                                    <th>Type</th>
                                    <th>Created By</th>
                                    <th>Created On</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($nozzles as $key => $nozzle)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $nozzle->pump->pump_name }}</td>
                                        <td>{{ $nozzle->nozzle_number }}</td>
                                        <td>{{ $nozzle->item ? $nozzle->item->name : 'N/A' }}</td>
                                        <td>{{ $nozzle->creator ? $nozzle->creator->name : 'N/A' }}</td>
                                        <td>{{ $nozzle->created_at->format('d M Y') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $nozzle->status === 'active' ? 'success' : 'danger' }}">
                                                {{ ucfirst($nozzle->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <!-- Edit Button -->
                                            <button type="button" class="btn btn-sm btn-info edit-nozzle-btn"
                                                data-id="{{ $nozzle->id }}"
                                                data-pump-id="{{ $nozzle->pump_id }}"
                                                data-nozzle-number="{{ $nozzle->nozzle_number }}"
                                                data-item-id="{{ $nozzle->item_id }}"
                                                data-status="{{ $nozzle->status }}"
                                                data-bs-toggle="modal" data-bs-target="#editNozzleModal">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <!-- Delete Form -->
                                            <form action="{{ route('nozzles.destroy', $nozzle) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this nozzle?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No nozzles found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Nozzle Modal -->
    <div class="modal fade" id="addNozzleModal" tabindex="-1" aria-labelledby="addNozzleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addNozzleModalLabel">Add New Nozzle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addNozzleForm" action="{{ route('nozzles.store') }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="pump_id">Pump Name</label>
                            <select class="form-control @error('pump_id') is-invalid @enderror" id="pump_id" name="pump_id" required>
                                <option value="">Select Pump</option>
                                @foreach($pumps as $pump)
                                    <option value="{{ $pump->id }}" {{ old('pump_id') == $pump->id ? 'selected' : '' }}>
                                        {{ $pump->pump_name }} (Total Nozzles: {{ $pump->total_nozzles }})
                                    </option>
                                @endforeach
                            </select>
                            @error('pump_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="nozzle_number">Nozzle Number</label>
                            <select class="form-control @error('nozzle_number') is-invalid @enderror" id="nozzle_number" name="nozzle_number" required>
                                <option value="">Select Nozzle Number</option>
                            </select>
                            @error('nozzle_number')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="item_id">Nozzle Type</label>
                            <select class="form-control @error('item_id') is-invalid @enderror" id="item_id" name="item_id" required>
                                <option value="">Select Type</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}" {{ old('item_id') == $item->id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('item_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="status"></label>
                            <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="addNozzleForm" class="btn btn-primary">Save Nozzle</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Nozzle Modal -->
    <div class="modal fade" id="editNozzleModal" tabindex="-1" aria-labelledby="editNozzleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editNozzleModalLabel">Edit Nozzle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editNozzleForm" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group mb-3">
                            <label for="editPumpId">Pump Name</label>
                            <select class="form-control" id="editPumpId" name="pump_id" required>
                                <option value="">Select Pump</option>
                                @foreach($pumps as $pump)
                                    <option value="{{ $pump->id }}">{{ $pump->pump_name }} (Total Nozzles: {{ $pump->total_nozzles }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="editNozzleNumber">Nozzle Number</label>
                            <select class="form-control" id="editNozzleNumber" name="nozzle_number" required>
                                <option value="">Select Nozzle Number</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="editItemId">Nozzle Type</label>
                            <select class="form-control" id="editItemId" name="item_id" required>
                                <option value="">Select Type</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}" {{ $nozzle->item_id == $item->id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="editStatus">Status</label>
                            <select class="form-control" id="editStatus" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="editNozzleForm" class="btn btn-primary">Update Nozzle</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @if ($errors->any())
        <script>
            var addNozzleModal = new bootstrap.Modal(document.getElementById('addNozzleModal'));
            addNozzleModal.show();
        </script>
    @endif
@endsection