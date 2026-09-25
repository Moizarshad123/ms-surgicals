<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MS Surgicals POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            min-height:100vh; display:flex; align-items:center; justify-content:center;
            background: linear-gradient(135deg,#1e2a38,#2d4256);
            font-family:'Segoe UI', system-ui, sans-serif;
        }
        .login-card { width:100%; max-width:400px; border:none; border-radius:1rem; box-shadow:0 10px 30px rgba(0,0,0,.25); }
        .login-header { text-align:center; padding-top:2rem; }
        .login-header i { font-size:2.5rem; color:#2d4256; }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="login-header">
            <img src="{{asset('admin/images/logo-2.png')}}" alt="" width="220px" height="200px" style="margin-bottom: -30px">
            {{-- <div class="text-muted small">Point of Sale System</div> --}}
        </div>
        <div class="card-body p-4">
            @if ($errors->any())
                <div class="alert alert-danger py-2 small">
                    @foreach ($errors->all() as $error)
                        {{ $error }}
                    @endforeach
                </div>
            @endif
            <form method="POST" action="{{ route('login.attempt') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
         
                <button type="submit" class="btn btn-dark w-100">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
