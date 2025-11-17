<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <title>Reset Password - TambaQ</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        *::-webkit-scrollbar {
            display: none;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 50%;
            top: -250px;
            right: -250px;
            opacity: 0.5;
            animation: float 6s ease-in-out infinite;
        }

        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border-radius: 50%;
            bottom: -200px;
            left: -200px;
            opacity: 0.5;
            animation: float 8s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 1;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 25px;
            text-align: center;
            position: relative;
        }

        .header i {
            font-size: 40px;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .header p {
            font-size: 14px;
            opacity: 0.95;
        }

        .body {
            padding: 30px 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .password-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 2px solid #e0e7ff;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            background-color: #f8faff;
            color: #333;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #667eea;
            font-size: 18px;
            padding: 5px;
        }

        .password-toggle:hover {
            color: #764ba2;
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background-color: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .info-box {
            background-color: #e8f4fd;
            border-left: 4px solid #667eea;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #555;
        }

        .info-box i {
            margin-right: 8px;
            color: #667eea;
        }

        .password-requirements {
            background-color: #fff9e6;
            border-left: 4px solid #ffa726;
            padding: 10px 12px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 12px;
            color: #555;
        }

        .password-requirements ul {
            margin: 5px 0 0 18px;
            padding: 0;
        }

        .password-requirements li {
            margin: 3px 0;
        }

        @media (max-width: 480px) {
            .container {
                margin: 10px;
            }
            
            .header {
                padding: 30px 20px;
            }

            .header h1 {
                font-size: 26px;
            }

            .body {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <i class="bi bi-shield-lock-fill"></i>
            <h1>Reset Password</h1>
            <p>Masukkan password baru Anda</p>
        </div>

        <div class="body">
            @if(session('error'))
                <div class="alert alert-error">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="info-box">
                <i class="bi bi-check-circle-fill"></i>
                Verifikasi berhasil! Silakan buat password baru Anda.
            </div>

            <form action="{{ route('password.reset') }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="bi bi-lock-fill"></i> Password Baru
                    </label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            class="form-input" 
                            id="password" 
                            name="password" 
                            placeholder="Masukkan password baru"
                            required
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                    @error('password')
                        <span style="color: #c33; font-size: 12px; margin-top: 5px; display: block;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password_confirmation">
                        <i class="bi bi-lock-fill"></i> Konfirmasi Password
                    </label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            class="form-input" 
                            id="password_confirmation" 
                            name="password_confirmation" 
                            placeholder="Konfirmasi password baru"
                            required
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation', this)">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                </div>

                <div class="password-requirements">
                    <strong><i class="bi bi-info-circle-fill"></i> Persyaratan Password:</strong>
                    <ul>
                        <li>Minimal 8 karakter</li>
                        <li>Kedua password harus sama</li>
                    </ul>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="bi bi-check-circle-fill"></i> Reset Password
                </button>
            </form>
        </div>
    </div>

    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye-fill');
                icon.classList.add('bi-eye-slash-fill');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash-fill');
                icon.classList.add('bi-eye-fill');
            }
        }
    </script>
</body>
</html>
