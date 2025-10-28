<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>ParkMate Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Bootstrap 5 CDN -->
  <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
  <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
  <!-- Inter font -->
  <link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
  <!-- Font Awesome -->
  <link href="{{ asset('css/all.min.css') }}" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('bootstrap-icons.css') }}">
  <style>
    body,
    html {
      height: 100%;
      font-family: 'Inter', sans-serif;
      background-color: #dfdfdf;
    }

    .center-box {
      min-width: 350px;
      /* keeps original width on desktop */

      min-height: 240px;
      background-color: #ffffff;
      padding: 20px;
      border-radius: 16px;
    }

    h2 {
      font-weight: 600;
    }

    .offset-up {
      transform: translateY(-140px);
    }

    .btn-signin {
      background-color: #56ca8b;
      color: white;
      border: none;
      padding: 8px 24px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      width: 100%;
      border-radius: 8px;
    }

    .btn-signin:hover {
      background-color: #38b174;
    }

    .text-error {
      color: red;
      text-align: center;
      margin-bottom: 10px;
    }
  </style>
</head>

<body>
  <div class="d-flex flex-column justify-content-center align-items-center vh-100 offset-up">
    <h2 class="mb-4">Welcome to ParkMate</h2>
    <div class="center-box position-relative">
    <a href="{{ url('/') }}" class="position-absolute text-decoration-none text-secondary" style="top: 15px; left: 15px; z-index: 10;">
      <i class="bi bi-arrow-left fs-5"></i>
    </a>
      <p class="text-center mb-4">Sign in as user.</p>


      <!-- Display errors -->
      @if ($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach ($errors->all() as $error)
          {{ $error }}
          @endforeach
        </ul>
      </div>
      @endif

      <form method="POST" action="{{ route('user.login.submit') }}">
        @csrf
        <div class="mb-3">
          <input type="text" name="login" class="form-control" placeholder="User ID / Email" required />

        </div>
        <div class="input-group mb-3">
          <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Password" required />
        <span class="input-group-text bg-white border-start-0 password-toggle" onclick="togglePasswordVisibility()">
          <i class="fas fa-eye text-secondary" id="passwordIcon"></i>
        </span>
        </div>
        <div class="d-flex justify-content-end mt-3">
          <button type="submit" class="btn-signin">Sign In</button>
        </div>
      </form>
    </div>
  </div>
  <script>
  function togglePasswordVisibility() {
    const passwordInput = document.getElementById('passwordInput');
    const passwordIcon = document.getElementById('passwordIcon');
    
    if (passwordInput.type === 'password') {
      passwordInput.type = 'text';
      passwordIcon.classList.remove('fa-eye');
      passwordIcon.classList.add('fa-eye-slash');
    } else {
      passwordInput.type = 'password';
      passwordIcon.classList.remove('fa-eye-slash');
      passwordIcon.classList.add('fa-eye');
    }
  }
</script>
</body>