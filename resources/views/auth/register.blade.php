<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <title>Register - TambaQ</title>
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
            background-color: #0D1117;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .register-container {
            background-color: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }

        .register-header {
            background: linear-gradient(135deg, #58A6FF 0%, #1F6FEB 100%);
            color: white;
            padding: 20px 30px;
            text-align: center;
            flex-shrink: 0;
        }

        .register-header h1 {
            font-size: 24px;
            font-weight: 600;
        }

        .register-body {
            padding: 30px;
            overflow-y: auto;
            flex: 1;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 13px;
        }

        .form-input {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #E1E4E8;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            background-color: #F6F8FA;
            color: #24292F;
        }

        .form-input:focus {
            outline: none;
            border-color: #58A6FF;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(88, 166, 255, 0.1);
        }

        .form-input.error {
            border-color: #F85149;
        }

        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #58A6FF;
            font-size: 18px;
            padding: 5px;
        }

        .password-toggle:hover {
            color: #1F6FEB;
        }

        .error-text {
            color: #F85149;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .error-text.show {
            display: block;
            animation: slideDown 0.3s ease-out;
        }

        .btn-register {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #58A6FF 0%, #1F6FEB 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 5px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(88, 166, 255, 0.4);
        }

        .register-footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #E1E4E8;
        }

        .register-footer p {
            color: #57606A;
            font-size: 13px;
        }

        .register-footer a {
            color: #58A6FF;
            text-decoration: none;
            font-weight: 600;
        }

        .register-footer a:hover {
            text-decoration: underline;
        }

        /* Alert Styles */
        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            animation: slideDown 0.3s ease-out;
        }

        .alert-error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .input-hint {
            font-size: 12px;
            color: #666;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Daftar Akun Baru</h1>
        </div>

        <div class="register-body">
            @if($errors->any())
                <div class="alert alert-error">
                    <ul style="list-style: none; padding: 0;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" id="registerForm">
                @csrf

                <div class="form-group">
                    <label for="name" class="form-label">Nama Lengkap</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-input" 
                        placeholder="Masukkan nama lengkap"
                        value="{{ old('name') }}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="contoh@email.com"
                        value="{{ old('email') }}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="phone_number" class="form-label">Nomor Telepon</label>
                    <input 
                        type="tel" 
                        id="phone_number" 
                        name="phone_number" 
                        class="form-input" 
                        placeholder="08xxxxxxxxxx"
                        value="{{ old('phone_number') }}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Minimal 8 karakter"
                            required
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <span id="toggleIconPassword">
                                <i class="bi bi-eye"></i>
                            </span>
                        </button>
                    </div>
                    <div class="input-hint">Minimal 8 karakter</div>
                </div>

                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="password_confirmation" 
                            name="password_confirmation" 
                            class="form-input" 
                            placeholder="Ulangi password"
                            required
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation')">
                            <span id="toggleIconConfirm">
                                <i class="bi bi-eye"></i>
                            </span>
                        </button>
                    </div>
                    <div class="error-text" id="passwordError">
                        Konfirmasi password harus sama dengan password.
                    </div>
                </div>

                <button type="submit" class="btn-register">Daftar</button>

                <div class="register-footer">
                    <p>Sudah punya akun? <a href="{{ route('login') }}">Login disini</a></p>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const iconId = fieldId === 'password' ? 'toggleIconPassword' : 'toggleIconConfirm';
            const toggleIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.innerHTML = '<i class="bi bi-eye-slash"></i>';
            } else {
                passwordInput.type = 'password';
                toggleIcon.innerHTML = '<i class="bi bi-eye"></i>';
            }
        }

        // Validate password confirmation
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('password_confirmation');
        const passwordError = document.getElementById('passwordError');
        const form = document.getElementById('registerForm');

        function validatePassword() {
            if (confirmPasswordInput.value && passwordInput.value !== confirmPasswordInput.value) {
                confirmPasswordInput.classList.add('error');
                passwordError.classList.add('show');
                return false;
            } else {
                confirmPasswordInput.classList.remove('error');
                passwordError.classList.remove('show');
                return true;
            }
        }

        // Check on input
        confirmPasswordInput.addEventListener('input', validatePassword);
        passwordInput.addEventListener('input', function() {
            if (confirmPasswordInput.value) {
                validatePassword();
            }
        });

        // Validate on submit
        form.addEventListener('submit', function(e) {
            if (!validatePassword() && confirmPasswordInput.value) {
                e.preventDefault();
                confirmPasswordInput.focus();
            }
        });
    </script>
</body>
</html>
