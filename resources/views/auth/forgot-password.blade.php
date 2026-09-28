@extends('layouts.master-without-nav')

@section('title')
    Forgot Password
@endsection

@section('content')
<style>
    .forgot-bg {
        position: relative;
        min-height: 100vh;
        background-image: url('{{ asset('build/images/fuel-bg.png') }}');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        overflow: hidden;
    }

    .forgot-bg::before {
        content: "";
        position: absolute;
        inset: -15px;
        background-image: inherit;
        background-size: cover;
        background-position: center;
        filter: blur(5px);
        transform: scale(1.05);
        z-index: 0;
    }

    .forgot-bg > .row {
        position: relative;
        z-index: 2;
    }

    .forgot-bg .card {
        background: rgba(255, 255, 255, 0.96);
        border: none;
        box-shadow: 0 15px 45px rgba(0, 0, 0, 0.15);
    }
</style>
<div class="container-fluid forgot-bg" style="min-height: 100vh;">
    <div class="row align-items-center justify-content-center min-vh-100">
        <div class="col-10 col-md-6 col-lg-4 col-xxl-3">
            <div class="card mb-0">
                <div class="card-body">
                    <div class="text-center">
                        <h4 class="font-size-18 mt-4">Reset Password</h4>
                        <p class="text-muted">Enter your email to receive an OTP for resetting your password.</p>
                    </div>
                    <div class="p-2 mt-5">
                        <form id="send-otp-form" method="POST" action="{{ route('password.otp.send') }}">
                            @csrf
                            <div class="input-group auth-form-group-custom mb-3">
                                <span class="input-group-text bg-primary bg-opacity-10 fs-16"><i
                                        class="mdi mdi-email-outline auti-custom-input-icon"></i></span>
                                <input type="email" name="email" id="email" class="form-control" placeholder="Enter email" required>
                            </div>
                            <div class="text-center pt-3">
                                <button class="btn btn-primary w-xl waves-effect waves-light" type="submit" id="send-otp-button">Send OTP</button>
                            </div>
                        </form>

                        <form id="verify-otp-form" method="POST" action="{{ route('password.otp.verify') }}" style="margin-top: 20px;">
                            @csrf
                            <input type="hidden" name="email" id="hidden-email">
                            <div class="input-group auth-form-group-custom mb-3">
                                <span class="input-group-text bg-primary bg-opacity-10 fs-16"><i
                                        class="mdi mdi-numeric auti-custom-input-icon"></i></span>
                                <input type="text" name="otp" id="otp" class="form-control" placeholder="Enter OTP" required>
                            </div>
                            <div class="text-center pt-3">
                                <button class="btn btn-primary w-xl waves-effect waves-light" type="submit" id="verify-otp-button" disabled>Verify OTP</button>
                            </div>
                        </form>
                    </div>
                    <div class="mt-5 text-center">
                        <p>© <script>document.write(new Date().getFullYear())</script> FuelFlow</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('send-otp-form').addEventListener('submit', function (e) {
        e.preventDefault();
        const email = document.getElementById('email').value;

        fetch('{{ route('password.otp.send') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ email })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('OTP sent to your email.');
                document.getElementById('hidden-email').value = email;
                document.getElementById('verify-otp-button').disabled = false;
            } else {
                alert(data.message || 'Failed to send OTP.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while sending OTP.');
        });
    });

    document.getElementById('verify-otp-form').addEventListener('submit', function (e) {
        e.preventDefault();
        const email = document.getElementById('hidden-email').value;
        const otp = document.getElementById('otp').value;

        fetch('{{ route('password.otp.verify') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ email, otp })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('OTP verified successfully.');
                window.location.href = data.redirect_url; // Redirect to reset password page
            } else {
                alert(data.message || 'Invalid or expired OTP.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while verifying OTP.');
        });
    });
</script>
@endsection