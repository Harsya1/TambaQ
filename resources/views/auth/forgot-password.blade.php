<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <title>Lupa Password - TambaQ</title>
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
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }

        .header i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.9;
        }

        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .header p {
            font-size: 15px;
            opacity: 0.95;
        }

        .body {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
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

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #764ba2;
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
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #555;
        }

        .info-box i {
            margin-right: 8px;
            color: #667eea;
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
            <i class="bi bi-key-fill"></i>
            <h1>Lupa Password?</h1>
            <p>Verifikasi email dan nomor telepon Anda</p>
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
                <i class="bi bi-info-circle-fill"></i>
                Masukkan email dan nomor telepon yang terdaftar untuk mereset password Anda.
            </div>

            <form action="{{ route('password.verify') }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label class="form-label" for="email">
                        <i class="bi bi-envelope-fill"></i> Email
                    </label>
                    <input 
                        type="email" 
                        class="form-input" 
                        id="email" 
                        name="email" 
                        placeholder="Masukkan email Anda"
                        value="{{ old('email') }}"
                        required
                    >
                    @error('email')
                        <span style="color: #c33; font-size: 12px; margin-top: 5px; display: block;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone_number">
                        <i class="bi bi-phone-fill"></i> Nomor Telepon
                    </label>
                    <input 
                        type="text" 
                        class="form-input" 
                        id="phone_number" 
                        name="phone_number" 
                        placeholder="Masukkan nomor telepon Anda"
                        value="{{ old('phone_number') }}"
                        required
                    >
                    @error('phone_number')
                        <span style="color: #c33; font-size: 12px; margin-top: 5px; display: block;">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn-primary">
                    <i class="bi bi-check-circle-fill"></i> Verifikasi
                </button>
            </form>

            <a href="{{ route('login') }}" class="back-link">
                <i class="bi bi-arrow-left"></i> Kembali ke Login
            </a>
        </div>
    </div>
</body>
</html>
