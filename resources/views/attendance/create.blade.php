
@extends('layouts.master')

@section('title', 'Add Attendance')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Add Attendance</h4>
                </div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('attendance.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="operator_id" class="form-label">Operator</label>
                                <select name="operator_id" id="operator_id"
                                        class="form-select" required>
                                    <option value="">Select Operator</option>
                                    @foreach ($operators as $operator)
                                        <option value="{{ $operator->id }}"
                                            {{ old('operator_id') == $operator->id ? 'selected' : '' }}>
                                            {{ $operator->user_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="shift_id" class="form-label">Shift</label>
                                <select name="shift_id" id="shift_id"
                                        class="form-select" required>
                                    <option value="">Select Shift</option>
                                    @foreach ($shifts as $shift)
                                        <option value="{{ $shift->id }}"
                                            {{ old('shift_id') == $shift->id ? 'selected' : '' }}>
                                            {{ $shift->shift_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="date" class="form-label">Attendance Date</label>
                                <input type="date" name="date" id="date"
                                       class="form-control"
                                       value="{{ old('date', date('Y-m-d')) }}" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="opening_meter_time" class="form-label">
                                    Opening Meter Time
                                </label>
                                <input type="time" name="opening_meter_time"
                                       id="opening_meter_time" class="form-control"
                                       value="{{ old('opening_meter_time') }}" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="closing_meter_time" class="form-label">
                                    Closing Meter Time
                                </label>
                                <input type="time" name="closing_meter_time"
                                       id="closing_meter_time" class="form-control"
                                       value="{{ old('closing_meter_time') }}">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Save Attendance
                        </button>

                        <a href="{{ route('attendance.index') }}"
                           class="btn btn-secondary">
                            Cancel
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
