<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')

    <title>เข้าสู่ระบบ - หอพักสุขสบาย</title>
</head>

<body class="login-page">

    <div class="login-wrapper">

        <div class="login-container">

            {{-- LOGO --}}
            <div class="login-brand">

                <div class="login-logo">
                    <i class="bi bi-buildings-fill"></i>
                </div>

                <h1 class="login-brand-title">
                    หอพักสุขสบาย
                </h1>

                <p class="login-brand-subtitle">
                    ระบบจัดการหอพัก
                </p>

            </div>

            {{-- ACCOUNT TYPE --}}
            <div class="row g-3 login-account-types">

                {{-- ADMIN --}}
                <div class="col-6">

                    <div class="account-card account-card-admin">

                        <div class="account-card-title">
                            <i class="bi bi-person-badge-fill me-1"></i>
                            Admin
                        </div>

                        <div class="account-card-email">
                            admin@dormitory.th
                        </div>

                        <div class="account-card-role">
                            ผู้ดูแลระบบ
                        </div>
                    </div>
                </div>


                {{-- TENANT --}}
                <div class="col-6">

                    <div class="account-card account-card-tenant">

                        <div class="account-card-title">
                            <i class="bi bi-house-fill me-1"></i>
                            ผู้เช่า
                        </div>

                        <div class="account-card-email">
                            TN-000
                        </div>

                        <div class="account-card-role">
                            มานี มานะ
                        </div>

                    </div>

                </div>

            </div>

            {{-- LOGIN CARD --}}
            <div class="login-card">

                {{-- Header --}}
                <div class="login-card-header">

                    <h2>
                        เข้าสู่ระบบ
                    </h2>

                    <p>
                        กรุณาเลือกบัญชีด้านบน หรือกรอกข้อมูลด้วยตนเอง
                    </p>

                </div>


                {{-- ERROR --}}
                @if ($errors->any())
                    <div class="login-error">

                        <i class="bi bi-exclamation-circle-fill"></i>

                        <span>
                            {{ $errors->first() }}
                        </span>

                    </div>
                @endif

                {{-- LOGIN FORM --}}
                <form method="POST" action="{{ route('login.store') }}" class="login-form">

                    @csrf


                    {{-- EMAIL --}}
                    <div class="login-field">

                        <label for="email" class="login-label">
                            User ID หรือ Email
                        </label>

                        <div class="login-input-wrapper">

                            <i class="bi bi-person login-input-icon"></i>

                            <input id="email" name="email" type="email" value="{{ old('email') }}"
                                placeholder="admin@dormitory.th" required autofocus autocomplete="username"
                                class="form-control login-input">

                        </div>

                    </div>


                    {{-- PASSWORD --}}
                    <div class="login-field">

                        <label for="password" class="login-label">
                            รหัสผ่าน
                        </label>

                        <div class="login-input-wrapper">

                            <i class="bi bi-lock login-input-icon"></i>

                            <input id="password" name="password" type="password" placeholder="รหัสผ่าน" required
                                autocomplete="current-password" class="form-control login-input login-password-input">

                            <button type="button" class="login-password-toggle" onclick="togglePassword()"
                                aria-label="แสดงรหัสผ่าน">
                                <i id="eyeIcon" class="bi bi-eye"></i>
                            </button>

                        </div>

                    </div>


                    {{-- REMEMBER --}}
                    <div class="form-check login-remember">

                        <input id="remember" type="checkbox" name="remember" value="1"
                            {{ old('remember') ? 'checked' : '' }} class="form-check-input">

                        <label for="remember" class="form-check-label">
                            จดจำการเข้าสู่ระบบ
                        </label>

                    </div>


                    {{-- LOGIN BUTTON --}}
                    <button type="submit" class="btn login-button">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        เข้าสู่ระบบ
                    </button>

                </form>

            </div>

            {{-- FOOTER --}}
            <div class="login-footer">
                หอพักสุขสบาย · Dormitory Management System v1.0
            </div>

        </div>

    </div>


    <style>
        /* PAGE */
        .login-page {
            min-height: 100vh;
            margin: 0;

            background: #203F67;

            color: #162D4A;
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .login-container {
            width: 100%;
            max-width: 460px;
        }

        /* BRAND */
        .login-brand {
            margin-bottom: 30px;
            text-align: center;
        }

        .login-logo {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            border-radius: 20px;
            background: #2F7CF6;
            color: #ffffff;
            font-size: 38px;
            box-shadow:
                0 12px 28px rgba(0, 0, 0, 0.18);
        }

        .login-brand-title {
            margin: 0;
            color: #ffffff;
            font-size: 30px;
            font-weight: 700;
            line-height: 1.25;
            letter-spacing: -0.02em;
        }

        .login-brand-subtitle {
            margin: 5px 0 0;
            color: #BDD2ED;
            font-size: 14px;
        }


        /* ACCOUNT CARDS */
        .login-account-types {
            margin-bottom: 18px;
        }

        .account-card {
            min-height: 92px;
            padding: 14px 16px;
            border-radius: 14px;
            color: #ffffff;
        }

        .account-card-admin {
            border: 1px solid #D3A900;
            background: #55544B;
            box-shadow:
                0 6px 14px rgba(0, 0, 0, 0.10);
        }

        .account-card-tenant {
            border: 1px solid rgba(96, 165, 250, 0.65);
            background: #285A98;
            box-shadow:
                0 6px 14px rgba(0, 0, 0, 0.10);
        }

        .account-card-title {
            font-size: 15px;
            font-weight: 700;
        }

        .account-card-email {
            margin-top: 5px;
            font-size: 12px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .account-card-role {
            margin-top: 2px;
            font-size: 11px;
        }

        .account-card-admin .account-card-role {
            color: #FFF1B2;
        }

        .account-card-tenant .account-card-role {
            color: #CFE3FF;
        }

        /* LOGIN CARD */
        .login-card {
            padding: 28px;
            border-radius: 20px;
            background: #ffffff;
            box-shadow:
                0 20px 45px rgba(0, 0, 0, 0.18);
        }

        .login-card-header {
            margin-bottom: 24px;
        }

        .login-card-header h2 {
            margin: 0;
            color: #162D4A;
            font-size: 24px;
            font-weight: 700;
            line-height: 1.3;
        }

        .login-card-header p {
            margin: 6px 0 0;
            color: #8CA3C2;
            font-size: 13px;
            line-height: 1.6;
        }


        /* ERROR */
        .login-error {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            margin-bottom: 20px;
            padding: 12px 14px;
            border: 1px solid #FECACA;
            border-radius: 10px;
            background: #FEF2F2;
            color: #DC2626;
            font-size: 13px;
            line-height: 1.5;
        }

        .login-error i {
            margin-top: 2px;
            flex-shrink: 0;
        }


        /* FORM */
        .login-form {
            width: 100%;
        }

        .login-field {
            margin-bottom: 18px;
        }

        .login-label {
            display: block;
            margin-bottom: 8px;
            color: #162D4A;
            font-size: 13px;
            font-weight: 600;
        }


        /* INPUT */
        .login-input-wrapper {
            position: relative;
        }

        .login-input-icon {
            position: absolute;
            top: 50%;
            left: 15px;
            z-index: 2;
            transform: translateY(-50%);
            color: #8CA3C2;
            font-size: 17px;
            pointer-events: none;
        }

        .login-input {
            min-height: 48px;
            padding: 10px 15px 10px 44px;
            border: 1px solid #C8D6E8;
            border-radius: 10px;
            background: #ffffff;
            color: #162D4A;
            font-size: 13px;
            box-shadow: none;
        }

        .login-input::placeholder {
            color: #9AAFC8;
        }

        .login-input:hover {
            border-color: #AABDD3;
        }

        .login-input:focus {
            border-color: #2F7CF6;

            color: #162D4A;

            box-shadow:
                0 0 0 0.2rem rgba(47, 124, 246, 0.12);
        }

        .login-password-input {
            padding-right: 46px;
        }


        /* PASSWORD TOGGLE */

        .login-password-toggle {
            position: absolute;
            top: 50%;
            right: 10px;
            z-index: 3;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            transform: translateY(-50%);
            border: 0;
            border-radius: 6px;
            background: transparent;
            color: #8CA3C2;
            cursor: pointer;
        }

        .login-password-toggle:hover {
            background: #F1F5F9;
            color: #2F7CF6;
        }


        /* REMEMBER */
        .login-remember {
            margin-bottom: 20px;
        }

        .login-remember .form-check-input {
            width: 16px;
            height: 16px;
            margin-top: 2px;
            border-color: #B8C9DD;
            cursor: pointer;
        }

        .login-remember .form-check-input:checked {
            border-color: #2F7CF6;
            background-color: #2F7CF6;
        }

        .login-remember .form-check-input:focus {
            border-color: #2F7CF6;
            box-shadow:
                0 0 0 0.2rem rgba(47, 124, 246, 0.12);
        }

        .login-remember .form-check-label {
            color: #58708F;
            font-size: 13px;
            cursor: pointer;
        }


        /* LOGIN BUTTON */
        .login-button {
            width: 100%;
            min-height: 48px;
            border: 0;
            border-radius: 10px;
            background: #234574;
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            box-shadow:
                0 4px 10px rgba(35, 69, 116, 0.16);
            transition:
                background-color 0.15s ease,
                transform 0.15s ease,
                box-shadow 0.15s ease;
        }

        .login-button:hover {
            background: #1C3B64;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow:
                0 6px 14px rgba(35, 69, 116, 0.22);
        }

        .login-button:active {
            background: #1C3B64;
            color: #ffffff;
            transform: translateY(0);
        }

        .login-button:focus {
            background: #234574;
            color: #ffffff;
            box-shadow:
                0 0 0 0.2rem rgba(47, 124, 246, 0.25);
        }


        /* FOOTER */

        .login-footer {
            margin-top: 22px;
            text-align: center;
            color: #9FC0E4;
            font-size: 12px;
        }


        /* RESPONSIVE */
        @media (max-width: 575.98px) {

            .login-wrapper {
                padding: 28px 16px;
            }

            .login-brand {
                margin-bottom: 24px;
            }

            .login-logo {
                width: 68px;
                height: 68px;
                margin-bottom: 13px;
                font-size: 32px;
                border-radius: 18px;
            }

            .login-brand-title {
                font-size: 25px;
            }

            .login-card {
                padding: 22px 18px;

                border-radius: 17px;
            }

            .account-card {
                min-height: 88px;
                padding: 12px;
            }

            .account-card-title {
                font-size: 14px;
            }

            .account-card-email {
                font-size: 11px;
            }

            .account-card-role {
                font-size: 10px;
            }

        }
    </style>


    {{-- PASSWORD SCRIPT --}}
    <script>
        function togglePassword() {

            const password = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');

            if (!password || !eyeIcon) {
                return;
            }

            if (password.type === 'password') {

                password.type = 'text';

                eyeIcon.classList.remove('bi-eye');
                eyeIcon.classList.add('bi-eye-slash');

            } else {

                password.type = 'password';

                eyeIcon.classList.remove('bi-eye-slash');
                eyeIcon.classList.add('bi-eye');

            }
        }
    </script>

    @fluxScripts

</body>

</html>
