@extends('layouts.master-without-nav')

@section('title')
    500 Error
@endsection

@section('css')
@endsection

@section('content')
    <div class="">
        <div class="container">
            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-lg-5">
                    <div class="text-center">
                        <img src="{{ URL::asset('build/images/error-500.png') }}" alt="404 error" class="img-fluid">
                        <div class="mt-5 pt-5 text-center">
                            <a class="btn btn-primary waves-effect waves-light" href="index.html">Back to Dashboard</a>
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
