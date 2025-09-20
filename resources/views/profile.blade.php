@extends('layouts.master-without-page-title')

@section('content')
<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">User Profile</h3>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th scope="row">Name</th>
                        <td>{{ $user->name }}</td>
                    </tr>
                    <tr>
                        <th scope="row">Email</th>
                        <td>{{ $user->email }}</td>
                    </tr>
                    <tr>
                        <th scope="row">Mobile Number</th>
                        <td>{{ $user->mobile_number ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th scope="row">User Type</th>
                        <td>{{ $user->user_type ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th scope="row">Created At</th>
                        <td>{{ $user->created_at }}</td>
                    </tr>
                    <tr>
                        <th scope="row">Updated At</th>
                        <td>{{ $user->updated_at }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
