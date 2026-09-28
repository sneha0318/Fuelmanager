@extends('layouts.master-without-nav')

@section('title')
    Login
@endsection

@section('css')
<style>
    /* =========================================================
       FUELMANAGER LOGIN PAGE
       FRONTEND ONLY - NO BACKEND CHANGE
       ========================================================= */

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 0;
        overflow-x: hidden;
        font-family: "Segoe UI", sans-serif;
    }

    .fuel-login-page {
        min-height: 100vh;
        display: flex;
        background: #f5f8fc;
    }

    /* ================= LEFT ANIMATION ================= */

    .fuel-animation-side {
        width: 58%;
        min-height: 100vh;
        position: relative;
        overflow: hidden;
        background:
            linear-gradient(180deg, #dff4ff 0%, #edf9ff 55%, #f7fbff 100%);
    }

    /* Sky */

    .sky-circle {
        position: absolute;
        width: 110px;
        height: 110px;
        border-radius: 50%;
        background: #ffd76a;
        top: 12%;
        left: 13%;
        box-shadow: 0 0 50px rgba(255, 215, 106, .45);
    }

    .cloud {
        position: absolute;
        width: 110px;
        height: 35px;
        background: rgba(255,255,255,.8);
        border-radius: 50px;
        animation: cloudMove 18s linear infinite;
    }

    .cloud:before,
    .cloud:after {
        content: "";
        position: absolute;
        background: rgba(255,255,255,.8);
        border-radius: 50%;
    }

    .cloud:before {
        width: 45px;
        height: 45px;
        left: 20px;
        top: -22px;
    }

    .cloud:after {
        width: 55px;
        height: 55px;
        right: 15px;
        top: -30px;
    }

    .cloud-1 {
        top: 17%;
        left: 45%;
    }

    .cloud-2 {
        top: 28%;
        left: 75%;
        transform: scale(.7);
        animation-delay: -7s;
    }

    @keyframes cloudMove {
        from {
            transform: translateX(-100px);
        }
        to {
            transform: translateX(180px);
        }
    }

    /* Heading */

    .animation-heading {
        position: absolute;
        top: 7%;
        left: 8%;
        z-index: 5;
    }

    .animation-heading h1 {
        font-size: 38px;
        font-weight: 800;
        color: #123b68;
        margin-bottom: 8px;
    }

    .animation-heading h1 span {
        color: #1688e8;
    }

    .animation-heading p {
        color: #60758c;
        font-size: 15px;
        margin: 0;
    }

    /* ================= FEATURE CARDS ================= */

    .feature-card {
        position: absolute;
        z-index: 8;
        width: 190px;
        padding: 14px;
        border-radius: 15px;
        background: rgba(255,255,255,.92);
        border: 1px solid rgba(255,255,255,.9);
        box-shadow: 0 12px 30px rgba(35, 80, 120, .13);
        opacity: 0;
        animation: featureAppear 7s ease-in-out infinite;
    }

    .feature-card .feature-icon {
        width: 40px;
        height: 40px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #e7f4ff;
        color: #1688e8;
        font-size: 20px;
        margin-bottom: 8px;
    }

    .feature-card strong {
        display: block;
        color: #173d63;
        font-size: 14px;
    }

    .feature-card small {
        color: #7b8da0;
        font-size: 11px;
    }

    .feature-1 {
        top: 24%;
        left: 8%;
        animation-delay: 0s;
    }

    .feature-2 {
        top: 21%;
        right: 9%;
        animation-delay: 2s;
    }

    .feature-3 {
        top: 43%;
        right: 5%;
        animation-delay: 4s;
    }

    @keyframes featureAppear {
        0%, 10% {
            opacity: 0;
            transform: translateY(20px) scale(.9);
        }

        18%, 72% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        82%, 100% {
            opacity: 0;
            transform: translateY(-15px) scale(.95);
        }
    }

    /* ================= LANDSCAPE ================= */

    .hill-back {
        position: absolute;
        bottom: 34%;
        left: -10%;
        width: 120%;
        height: 190px;
        background: #c7e7d5;
        border-radius: 50% 50% 0 0;
    }

    .hill-front {
        position: absolute;
        bottom: 29%;
        left: -15%;
        width: 130%;
        height: 170px;
        background: #9ed4b5;
        border-radius: 50% 50% 0 0;
    }

    /* ================= FUEL STATION ================= */

    .fuel-station {
        position: absolute;
        bottom: 39%;
        left: 13%;
        z-index: 6;
    }

    .station-roof {
        width: 180px;
        height: 18px;
        background: #1688e8;
        border-radius: 5px;
    }

    .station-top {
        width: 165px;
        height: 55px;
        background: #ffffff;
        margin-left: 7px;
        box-shadow: 0 4px 15px rgba(0,0,0,.08);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #1688e8;
        font-size: 18px;
        font-weight: 800;
    }

    .station-pillar {
        width: 12px;
        height: 65px;
        background: #e6edf4;
        display: inline-block;
        margin: 0 32px;
    }

    .fuel-pump {
        position: absolute;
        bottom: 0;
        right: -90px;
        width: 52px;
        height: 78px;
        background: #ffffff;
        border-radius: 7px;
        box-shadow: 0 5px 15px rgba(0,0,0,.1);
        border: 3px solid #1688e8;
    }

    .fuel-pump:before {
        content: "⛽";
        position: absolute;
        top: 13px;
        left: 11px;
        font-size: 20px;
    }

    /* ================= ROAD ================= */

    .road {
        position: absolute;
        z-index: 4;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 31%;
        background: #374552;
        transform: perspective(500px) rotateX(2deg);
        overflow: hidden;
    }

    .road-line {
        position: absolute;
        top: 48%;
        left: -100px;
        width: 150%;
        height: 7px;
        background: repeating-linear-gradient(
            to right,
            #ffffff 0px,
            #ffffff 85px,
            transparent 85px,
            transparent 160px
        );
        animation: roadMove 1.2s linear infinite;
    }

    @keyframes roadMove {
        from {
            transform: translateX(0);
        }
        to {
            transform: translateX(-160px);
        }
    }

    /* ================= TRUCK ================= */

    .vehicle {
        position: absolute;
        z-index: 10;
    }

    .truck {
        bottom: 13%;
        left: -250px;
        width: 260px;
        height: 100px;
        animation: truckDrive 9s linear infinite;
    }

    .truck-body {
        position: absolute;
        left: 0;
        bottom: 20px;
        width: 180px;
        height: 70px;
        background: #1688e8;
        border-radius: 10px 5px 5px 5px;
        box-shadow: 0 8px 15px rgba(0,0,0,.18);
    }

    .truck-body:after {
        content: "FUEL";
        position: absolute;
        top: 20px;
        left: 50px;
        color: #fff;
        font-size: 19px;
        font-weight: 800;
        letter-spacing: 2px;
    }

    .truck-cabin {
        position: absolute;
        right: 0;
        bottom: 20px;
        width: 78px;
        height: 65px;
        background: #0f6fbd;
        border-radius: 5px 14px 5px 5px;
    }

    .truck-window {
        position: absolute;
        top: 9px;
        left: 10px;
        width: 48px;
        height: 25px;
        background: #bce8ff;
        border-radius: 5px;
    }

    .wheel {
        position: absolute;
        bottom: 3px;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #20262b;
        border: 7px solid #4f5a62;
        box-shadow: inset 0 0 0 5px #1c2227;
    }

    .truck-wheel-1 {
        left: 30px;
    }

    .truck-wheel-2 {
        right: 27px;
    }

    @keyframes truckDrive {
        0% {
            left: -280px;
        }

        45% {
            left: 38%;
        }

        65% {
            left: 52%;
        }

        100% {
            left: 110%;
        }
    }

    /* ================= CAR ================= */

    .car {
        bottom: 5%;
        left: -180px;
        width: 170px;
        height: 70px;
        animation: carDrive 7s linear infinite;
        animation-delay: 1.5s;
    }

    .car-body {
        position: absolute;
        bottom: 15px;
        left: 10px;
        width: 150px;
        height: 42px;
        background: #f5a623;
        border-radius: 35px 45px 10px 10px;
        box-shadow: 0 7px 12px rgba(0,0,0,.2);
    }

    .car-top {
        position: absolute;
        bottom: 48px;
        left: 42px;
        width: 75px;
        height: 35px;
        background: #f5a623;
        border-radius: 45px 45px 0 0;
    }

    .car-window {
        position: absolute;
        bottom: 53px;
        left: 53px;
        width: 50px;
        height: 20px;
        background: #bdeaff;
        border-radius: 20px 20px 3px 3px;
    }

    .car .wheel {
        width: 32px;
        height: 32px;
        bottom: 0;
        border-width: 6px;
    }

    .car-wheel-1 {
        left: 30px;
    }

    .car-wheel-2 {
        right: 25px;
    }

    @keyframes carDrive {
        0% {
            left: -200px;
        }

        45% {
            left: 30%;
        }

        100% {
            left: 110%;
        }
    }

    /* ================= RIGHT LOGIN ================= */

    .login-side {
        width: 42%;
        min-height: 100vh;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 45px;
        position: relative;
        z-index: 20;
    }

    .login-box {
        width: 100%;
        max-width: 430px;
    }

    .login-logo {
        text-align: center;
        margin-bottom: 25px;
    }

    .login-logo img {
        max-width: 185px;
        height: auto;
    }

    .login-title {
        text-align: center;
        margin-bottom: 30px;
    }

    .login-title h2 {
        color: #163b61;
        font-weight: 800;
        font-size: 30px;
        margin-bottom: 8px;
    }

    .login-title p {
        color: #8291a0;
        margin: 0;
        font-size: 14px;
    }

    .custom-input {
        margin-bottom: 20px;
    }

    .custom-input .input-group-text {
        width: 50px;
        justify-content: center;
        border: 1px solid #e1e8ef;
        background: #f3f8fc !important;
        color: #35c46a;
    }

    .custom-input .form-control {
        height: 50px;
        border: 1px solid #e1e8ef;
        box-shadow: none;
        font-size: 14px;
    }

.custom-input .form-control:focus {
    border-color: #35c46a;
    box-shadow: 0 0 0 3px rgba(53,196,106,.10);
}
    .login-button {
        width: 100%;
        height: 50px;
        border: 0;
        border-radius: 9px;
       background: #35c46a;
        color: #fff;
        font-size: 15px;
        font-weight: 700;
        box-shadow: 0 8px 20px rgba(22,136,232,.25);
        transition: .25s ease;
    }
.login-button:hover {
    transform: translateY(-2px);
    background: #2fba62;
    box-shadow: 0 12px 25px rgba(53,196,106,.35);
}

    .login-options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 15px 0 25px;
        font-size: 13px;
    }

    .login-options a {
        color: #35c46a;
        text-decoration: none;
        font-weight: 600;
    }

    .register-text {
        text-align: center;
        margin-top: 25px;
        color: #7b8a99;
        font-size: 14px;
    }

    .register-text a {
        color:#35c46a;
        font-weight: 700;
        text-decoration: none;
    }

    .login-footer {
        text-align: center;
        color: #a1acb7;
        font-size: 12px;
        margin-top: 35px;
    }

    /* ================= MOBILE ================= */

    @media (max-width: 991px) {

        .fuel-animation-side {
            display: none;
        }

        .login-side {
            width: 100%;
            padding: 30px 20px;
        }

        .login-box {
            max-width: 450px;
        }
    }

    @media (max-width: 480px) {

        .login-side {
            padding: 25px 16px;
        }

        .login-title h2 {
            font-size: 25px;
        }

        .login-options {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
    }
</style>
@endsection


@section('content')

<div class="fuel-login-page">

    {{-- =====================================================
         LEFT SIDE ANIMATED FUEL SCENE
         ===================================================== --}}

    <div class="fuel-animation-side">

        <div class="sky-circle"></div>

        <div class="cloud cloud-1"></div>
        <div class="cloud cloud-2"></div>

        <div class="animation-heading">
            <h1>Clivax<span>Manager</span></h1>
            <p>Smart fuel station management made simple.</p>
        </div>

        {{-- Feature Card 1 --}}
        <div class="feature-card feature-1">
            <div class="feature-icon">
                <i class="mdi mdi-gas-station"></i>
            </div>
            <strong>Fuel Management</strong>
            <small>Manage pumps, fuel and daily operations.</small>
        </div>

        {{-- Feature Card 2 --}}
        <div class="feature-card feature-2">
            <div class="feature-icon">
                <i class="mdi mdi-chart-line"></i>
            </div>
            <strong>Sales Tracking</strong>
            <small>Monitor sales and business performance.</small>
        </div>

        {{-- Feature Card 3 --}}
        <div class="feature-card feature-3">
            <div class="feature-icon">
                <i class="mdi mdi-account-group"></i>
            </div>
            <strong>Easy Management</strong>
            <small>Manage users, stock and daily activities.</small>
        </div>

        {{-- Landscape --}}
        <div class="hill-back"></div>
        <div class="hill-front"></div>

        {{-- Fuel Station --}}
        <div class="fuel-station">
            <div class="station-roof"></div>

            <div class="station-top">
                FUEL STATION
            </div>

            <span class="station-pillar"></span>
            <span class="station-pillar"></span>

            <div class="fuel-pump"></div>
        </div>

        {{-- Road --}}
        <div class="road">
            <div class="road-line"></div>
        </div>

        {{-- Animated Truck --}}
        <div class="vehicle truck">

            <div class="truck-body"></div>

            <div class="truck-cabin">
                <div class="truck-window"></div>
            </div>

            <div class="wheel truck-wheel-1"></div>
            <div class="wheel truck-wheel-2"></div>

        </div>

        {{-- Animated Car --}}
        <div class="vehicle car">

            <div class="car-body"></div>
            <div class="car-top"></div>
            <div class="car-window"></div>

            <div class="wheel car-wheel-1"></div>
            <div class="wheel car-wheel-2"></div>

        </div>

    </div>


    {{-- =====================================================
         RIGHT SIDE LOGIN FORM
         BACKEND ROUTES / INPUT NAMES KEPT SAME
         ===================================================== --}}

    <div class="login-side">

        <div class="login-box">

            <div class="login-logo">

                <a href="{{ url('/') }}">
                    <img
                        src="{{ URL::asset('build/images/logo-light.png') }}"
                        alt="Logo"
                    >
                </a>

            </div>


            <div class="login-title">

                <h2>Welcome Back!</h2>

                <p>
                    Sign in to continue to your account.
                </p>

            </div>


            {{-- IMPORTANT:
                 Existing backend route remains unchanged
            --}}

            <form method="POST" action="{{ route('login.submit') }}">

                @csrf

                <input
                    type="hidden"
                    name="redirect_to"
                    value="{{ route('dashboard') }}"
                >


                {{-- Login / Email / Mobile --}}

                <div class="input-group custom-input">

                    <span class="input-group-text">

                        <i class="mdi mdi-account-outline"></i>

                    </span>

                    <input
                        type="text"
                        class="form-control @error('login') is-invalid @enderror"
                        name="login"
                        placeholder="Enter email or mobile"
                        aria-label="Login"
                        required
                    >

                    @error('login')

                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- Password --}}

                <div class="input-group custom-input">

                    <span class="input-group-text">

                        <i class="mdi mdi-lock-outline"></i>

                    </span>

                    <input
                        type="password"
                        class="form-control"
                        name="password"
                        id="userpassword"
                        placeholder="Enter password"
                        aria-label="Password"
                        required
                    >

                </div>


                {{-- Remember / Forgot --}}

                <div class="login-options">

                    <div class="form-check">

                        <input
                            type="checkbox"
                            class="form-check-input"
                            id="customControlInline"
                        >

                        <label
                            class="form-check-label"
                            for="customControlInline"
                        >
                            Remember me
                        </label>

                    </div>


                    <a href="{{ route('password.request') }}">

                        <i class="mdi mdi-lock me-1"></i>

                        Forgot your password?

                    </a>

                </div>


                {{-- Login Button --}}

                <button
                    class="login-button waves-effect waves-light"
                    type="submit"
                >

                    <i class="mdi mdi-login me-1"></i>

                    Log In

                </button>


                {{-- Register --}}

                <div class="register-text">

                    Don't have an account?

                    <a href="{{ route('register') }}">
                        Register
                    </a>

                </div>

            </form>


            <div class="login-footer">

                ©
                <script>
                    document.write(new Date().getFullYear())
                </script>

                FuelManager. All rights reserved.

            </div>

        </div>

    </div>

</div>

@endsection


@section('scripts')

<script src="{{ URL::asset('build/js/app.js') }}"></script>

<script>

    /*
     * Password visibility helper
     * Frontend only
     */

    document.addEventListener("DOMContentLoaded", function () {

        const passwordInput =
            document.getElementById("userpassword");

        if (!passwordInput) return;

        passwordInput.addEventListener("dblclick", function () {

            if (this.type === "password") {

                this.type = "text";

            } else {

                this.type = "password";

            }

        });

    });

</script>

@endsection