{{-- filepath: c:\xampp\htdocs\newpetrolpump\resources\views\auth\verify-otp.blade.php --}}
@extends('layouts.master-without-nav')

@section('title')
    Verify OTP
@endsection

@section('content')
<div class="container-fluid" style="background: linear-gradient(to right, #4facfe, #00f2fe); min-height: 100vh;">
    <div class="row align-items-center justify-content-center min-vh-100">
        <div class="col-10 col-md-6 col-lg-4 col-xxl-3">
            <div class="card mb-0">
                <div class="card-body">
                    <div class="text-center">
                        <h4 class="font-size-18 mt-4">Verify OTP</h4>
                        <p class="text-muted">Enter the OTP sent to your email.</p>
                    </div>
                    <div class="p-2 mt-5">
                        <form method="POST" action="{{ route('password.otp.verify') }}">
                            @csrf
                            <input type="hidden" name="email" value="{{ $email }}">
                            <div class="input-group auth-form-group-custom mb-3">
                                <span class="input-group-text bg-primary bg-opacity-10 fs-16"><i
                                        class="mdi mdi-numeric auti-custom-input-icon"></i></span>
                                <input type="text" name="otp" class="form-control" placeholder="Enter OTP" required>
                            </div>
                            <div class="text-center pt-3">
                                <button class="btn btn-primary w-xl waves-effect waves-light" type="submit">Verify OTP</button>
                            </div>
                        </form>
                    </div>
                    <div class="mt-5 text-center">
                        <p>© <script>document.write(new Date().getFullYear())</script>FuelFlow</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection