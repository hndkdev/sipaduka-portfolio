<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=4.0">
    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpg') }}">
    <title>Login | SIPADUKA PANBAR</title>

    {{-- Bootstrap via Vite --}}
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <style>
        body {
            background: linear-gradient(135deg, #2642bdff 0%, #100575ff 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        .login-container {
            display: flex;
            justify-content: center;    
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
            border: none;
        }
        .card-header {
            background: linear-gradient(135deg, #281fdaff 0%, #21064fff 100%);
            color: white;
            text-align: center;
            padding: 30px 20px;
            border-bottom: none;
        }
        .card-header h3 {
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 1.8rem;
        }
        .card-header p {
            opacity: 0.9;
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        .card-body {
            padding: 30px;
        }
        .logo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }
        .logo {
            width: 90px;
            height: 90px;
            object-fit: cover;
            background: white;
            border-radius: 20%;
            padding: 0px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            border: 3px solid rgba(255, 255, 255, 0.3);
        }
        .form-label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        .form-control {
            border-radius: 12px;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        .form-control:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .btn-login {
            background: linear-gradient(135deg, #181570ff 0%, #1c2dc5ff 100%);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }
        .support-text {
            text-align: center;
            color: #718096;
            font-size: 0.85rem;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .input-icon {
            position: relative;
        }
        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            z-index: 10;
        }
        .input-icon .form-control {
            padding-left: 45px;
        }
        .alert {
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.9rem;
        }
        .brand-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }
        .brand-subtitle {
            font-size: 0.9rem;
            color: #718096;
            margin-bottom: 25px;
        }
    </style>
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="login-container">
    <div class="card login-card">
        <!-- Card Header -->
        <div class="card-header">
            <div class="logo-container">
                <img 
                    src="{{ asset('images/logo.jpg') }}" 
                    alt="Logo SIPADUKA"
                    class="logo"
                >
            </div>
            <h3>SIPADUKA</h3>
            <p>Sistem Pendataan Penduduk & Keluarga</p>
        </div>

        <!-- Card Body -->
        <div class="card-body">
            <!-- Error Message -->
            @if ($errors->any())
                <div class="alert alert-danger small mb-4">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <!-- Brand Info -->
            <div class="text-center mb-4">
                <div class="brand-title">Sistem Pendataan Warga</div>
                <div class="brand-subtitle">Silakan login untuk melanjutkan</div>
            </div>

            <!-- Login Form -->
            <form action="{{ url('/login') }}" method="POST">
                @csrf

                <!-- Username Field -->
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <input
                            type="text"
                            name="username"
                            class="form-control"
                            placeholder="Masukkan username"
                            value="{{ old('username') }}"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            placeholder="Masukkan password"
                            required
                        >
                    </div>
                </div>

                <!-- Login Button -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-login text-white">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </div>
            </form>

            <!-- Support Text -->
            <div class="support-text">
                <i class="fas fa-info-circle me-2"></i>
                Jika Anda mengalami masalah login, silakan hubungi Administrator.
            </div>
            <footer class="mt-4 text-center text-muted" style="font-size: 0.8rem;">
                &copy; {{ date('Y') }} SIPADUKA PANBAR. All rights reserved.
        </div>
    </div>
</div>

</body>
</html>