@extends('layouts.master-without-nav')

@section('title')
    Dashboard
@endsection

@section('css')
@endsection

@section('content')
    <div class="">
        <div class="container">
            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-lg-5">
                    <div class="text-center">
                        <img src="{{ URL::asset('build/images/error-404.png') }}" alt="404 error" class="img-fluid">
                        <div class="mt-5 pt-5 text-center">
                            <a class="btn btn-primary waves-effect waves-light" href="{{ url('index') }}">Back to
                                Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- apexcharts -->
    <script src="{{ URL::asset('build/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/dashboard.init.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
