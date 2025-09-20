@extends('layouts.master-without-page-title')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <div>
                <h4 class="fs-16 fw-semibold mb-1">View Expenses</h4>
                <p class="text-muted mb-0">View expenses for category: {{ $category->category_name ?? 'Unknown Category' }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('party-list.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Party List
                </a>
            </div>
        </div>
    </div>
</div>

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
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No expenses found for this category.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
