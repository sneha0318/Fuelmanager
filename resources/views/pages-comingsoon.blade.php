@extends('layouts.master-without-nav')

@section('title')
    Coming Soon
@endsection

@section('css')
@endsection

@section('content')
    <div class="container-fluid coming-bg overflow-hidden">
        <div class="row align-items-center justify-content-center min-vh-100">
            <div class="col-10 col-md-6 col-lg-5 col-xxl-3">
                <h2 class="text-white text-center display-1 fw-semibold mb-5 coming-title">Coming soon</h2>
                <div class="card bg-info bg-opacity-10 mb-0">
                    <div class="card-body">
                        <div class="text-center">
                            <a href="index.html" class="">
                                <img src="{{ URL::asset('build/images/logo-dark.png') }}" alt="" class="d-inline-block" height="20"
                                    class="auth-logo logo-dark mx-auto">
                            </a>
                            <h4 class="mt-4 text-black">Let's get started with FuleBunk</h4>
                            <p class="text-black-50 mb-0">Form automation of people peocesses to creating an engaged and
                                driven culture.</p>
                        </div>
                        <div class="p-2 mt-5">
                            <div data-countdown="2025/01/01" class="counter-number row"></div>
                        </div>
                        <div class="input-section mt-5">
                            <div class="row justify-content-center">
                                <div class="col-10">
                                    <div class="position-relative">
                                        <input type="email" class="form-control bg-light bg-opacity-50"
                                            placeholder="Please enter your email address">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mt-3 text-center">
                                        <button type="submit"
                                            class="btn btn-primary w-md waves-effect waves-light">Subscribe</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- Plugins js-->
    <script src="{{ URL::asset('build/libs/jquery-countdown/dist/jquery.countdown.min.js') }}"></script>

    <!-- Countdown js -->
    <script src="{{ URL::asset('build/js/pages/coming-soon.init.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
