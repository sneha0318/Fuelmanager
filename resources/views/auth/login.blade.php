@extends('layouts.master-without-nav')

@section('title')
    Login
@endsection

@section('css')
@endsection

@section('content')
    <div class="container-fluid authentication-bg overflow-hidden" style="background: linear-gradient(to right, #4facfe, #00f2fe);">
        {{-- Removed the background overlay --}}
        <div class="row align-items-center justify-content-center min-vh-100">
            <div class="col-10 col-md-6 col-lg-4 col-xxl-3">
                <div class="card mb-0">
                    <div class="card-body">
                        <div class="text-center">
                            {{-- <a href="index.html" class="logo-dark">
                                <img src="{{ URL::asset('build/images/logo-dark.png') }}" alt="" height="20"
                                    class="auth-logo logo-dark mx-auto">
                            </a> --}}
                            <a href="index.html" class="logo-dark">
                                <img src="{{ URL::asset('build/images/logo-light.png') }}" alt="" height="20"
                                    class="auth-logo logo-light mx-auto">
                            </a>

                            <h4 class="mt-4">Welcome Back !</h4>
                            <p class="text-muted">Sign in to continue Work.</p>
                        </div>

                        <div class="p-2 mt-5">
                            <form method="POST" action="{{ route('login.submit') }}">
                                @csrf
                                <input type="hidden" name="redirect_to" value="{{ route('dashboard') }}">
                                <div class="input-group auth-form-group-custom mb-3">
                                    <span class="input-group-text bg-primary bg-opacity-10 fs-16 " id="basic-addon1"><i
                                            class="mdi mdi-account-outline auti-custom-input-icon"></i></span>
                                    <input type="text" class="form-control @error('login') is-invalid @enderror" name="login" placeholder="Enter email or mobile"
                                        aria-label="Login" aria-describedby="basic-addon1" required>
                                    @error('login')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="input-group auth-form-group-custom mb-3">
                                    <span class="input-group-text bg-primary bg-opacity-10 fs-16" id="basic-addon2"><i
                                            class="mdi mdi-lock-outline auti-custom-input-icon"></i></span>
                                    <input type="password" class="form-control" name="password" id="userpassword"
                                        placeholder="Enter password" aria-label="Password" aria-describedby="basic-addon2" required>
                                </div>

                                <div class="mb-sm-5">
                                    <div class="form-check float-sm-start">
                                        <input type="checkbox" class="form-check-input" id="customControlInline">
                                        <label class="form-check-label" for="customControlInline">Remember me</label>
                                    </div>
                                    <div class="float-sm-end">
                                        <a href="{{ route('password.request') }}" class="text-muted"><i class="mdi mdi-lock me-1"></i> Forgot your password?</a>
                                    </div>
                                </div>

                                <div class="pt-3 text-center">
                                    <button class="btn btn-primary w-xl waves-effect waves-light" type="submit">Log
                                        In</button>
                                </div>

                                <div class="mt-3 text-center">
                                    <p class="mb-0">Don't have an account ? <a href="{{ route('register') }}"
                                            class="fw-medium text-primary"> Register </a> </p>
                                </div>
                            </form>
                        </div>

                        <div class="mt-5 text-center">
                            <p>©
                                <script>
                                    document.write(new Date().getFullYear())
                                </script> Iplex Infotech
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
