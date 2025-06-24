<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            /* Primary Colors */
            --primary-green: #256B36;
            --primary-pink: #E1A6AD;
            --primary-brown: #4B4058;

            /* Secondary Colors */
            --light-green: #65D1B5;
            --aqua: #659DAB;
            --blue: #315470;
            --purple: #47566A0;
            --orange: #BB864E;
        }

        body {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 30%, var(--aqua) 60%, var(--primary-pink) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        /* Animated Background Elements */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg,
                    transparent 0%,
                    rgba(255, 255, 255, 0.05) 25%,
                    transparent 50%,
                    rgba(255, 255, 255, 0.08) 75%,
                    transparent 100%);
            animation: shimmer 8s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes shimmer {

            0%,
            100% {
                transform: rotate(0deg) scale(1);
            }

            50% {
                transform: rotate(180deg) scale(1.1);
            }
        }

        /* Floating Circles */
        .floating-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
            pointer-events: none;
        }

        .floating-circle:nth-child(1) {
            width: 100px;
            height: 100px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-circle:nth-child(2) {
            width: 60px;
            height: 60px;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }

        .floating-circle:nth-child(3) {
            width: 80px;
            height: 80px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        .login-card {
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow:
                0 20px 40px rgba(37, 107, 54, 0.2),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 10;
            transition: all 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow:
                0 30px 60px rgba(37, 107, 54, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.15);
        }

        .logo-container {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow:
                0 10px 30px rgba(37, 107, 54, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .logo-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: logoShine 3s ease-in-out infinite;
        }

        @keyframes logoShine {

            0%,
            100% {
                transform: rotate(0deg);
            }

            50% {
                transform: rotate(180deg);
            }
        }

        .logo-container i {
            font-size: 2.5rem;
            color: white;
            position: relative;
            z-index: 2;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .form-control {
            border: 2px solid rgba(37, 107, 54, 0.1);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }

        .form-control:focus {
            border-color: var(--light-green);
            box-shadow: 0 0 0 0.25rem rgba(101, 209, 181, 0.25);
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-1px);
        }

        .input-group-text {
            border: 2px solid rgba(37, 107, 54, 0.1);
            border-right: none;
            border-radius: 12px 0 0 12px;
            background: linear-gradient(135deg, var(--light-green) 0%, var(--aqua) 100%);
            color: white;
            font-weight: 500;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(37, 107, 54, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--light-green) 0%, var(--primary-green) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 107, 54, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(225, 166, 173, 0.15) 0%, rgba(220, 53, 69, 0.15) 100%);
            color: #721c24;
            border-left: 4px solid var(--primary-pink);
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(101, 209, 181, 0.15) 0%, rgba(37, 107, 54, 0.15) 100%);
            color: var(--primary-green);
            border-left: 4px solid var(--light-green);
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-brown);
            margin-bottom: 8px;
        }

        h3 {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            text-shadow: none;
        }

        .copyright-text {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 8px 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .floating-circle {
                display: none;
            }

            .login-card {
                margin: 20px;
                border-radius: 16px;
            }

            .logo-container {
                width: 40px;
                height: 40px;
            }

            .logo-container i {
                font-size: 2rem;
            }
        }

        /* Loading Animation */
        .btn-primary.loading {
            pointer-events: none;
        }

        .btn-primary.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
        }

        @keyframes spin {
            to {
                transform: translateY(-50%) rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <!-- Floating Background Elements -->
    <div class="floating-circle"></div>
    <div class="floating-circle"></div>
    <div class="floating-circle"></div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card login-card border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <!-- Custom Logo -->
                            <div class="logo-container">
                                <i class="bi bi-bell-fill"></i>
                            </div>
                            <img src="{{ asset('images/logo.png') }}" alt="Logo" height="40" class="me-2"
                                style="border-radius: 4px;"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                            <h4 class="mt-3 mb-2">{{ config('app.name') }}</h4>
                            {{-- <p class="text-muted mb-0">เข้าสู่ระบบด้วย LDAP</p> --}}
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}" id="loginForm">
                            @csrf

                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="bi bi-person me-1"></i>ชื่อผู้ใช้
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" class="form-control @error('username') is-invalid @enderror"
                                        id="username" name="username" value="{{ old('username') }}" required autofocus
                                        placeholder="กรอกชื่อผู้ใช้ LDAP">
                                    @error('username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="bi bi-lock me-1"></i>รหัสผ่าน
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                        id="password" name="password" required placeholder="กรอกรหัสผ่าน">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg" id="loginBtn">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    <span class="btn-text">เข้าสู่ระบบ</span>
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <small class="text-muted">
                                <i class="bi bi-shield-check me-1"></i>
                                ใช้ชื่อผู้ใช้และรหัสผ่านเดียวกับระบบขององค์กร
                            </small>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <div class="copyright-text">
                        <small class="text-white">
                            <i class="bi bi-c-circle me-1"></i>
                            {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Login form enhancement
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const btnText = btn.querySelector('.btn-text');

            btn.classList.add('loading');
            btnText.textContent = 'กำลังเข้าสู่ระบบ...';
            btn.disabled = true;

            // Re-enable after 10 seconds as fallback
            setTimeout(() => {
                btn.classList.remove('loading');
                btnText.textContent = 'เข้าสู่ระบบ';
                btn.disabled = false;
            }, 10000);
        });

        // Input focus effects
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });

            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Keyboard accessibility
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                const form = e.target.closest('form');
                if (form) {
                    form.submit();
                }
            }
        });
    </script>
</body>

</html>
