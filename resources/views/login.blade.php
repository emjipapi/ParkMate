<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Login - ParkMate</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Bootstrap 5 CDN -->
  <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
  <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
  <!-- Inter font -->
  <link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
  <!-- Font Awesome -->
  <link href="{{ asset('css/all.min.css') }}" rel="stylesheet">

  <style>
    body,
    html {
      height: 100%;
      font-family: 'Inter', sans-serif;
      background-color: #CDD1D9;
    }

    .center-box {
      max-width: 440px;
      /* keeps original width on desktop */
      x;
      min-height: 240px;
      background-color: #ffffff;
      padding: 20px;
    }

    h2 {
      font-weight: 600;
    }

    .offset-up {
      transform: translateY(-140px);
    }

    .btn-signin {
      background-color: #3481B4;
      color: white;
      border: none;
      padding: 8px 24px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .btn-signin:hover {
      background-color: rgb(110, 172, 213);
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
    <div class="center-box">

      <p class="text-center mb-4">Sign in to start your session.</p>


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

      <form action="{{ url('/login') }}" method="POST">
        @csrf
        <div class="mb-3">
          <input type="text" name="username" class="form-control" placeholder="Username" required />
        </div>
        <div class="input-group mb-3">
          <input type="password" name="password" class="form-control" placeholder="Password" required />
          <span class="input-group-text bg-white border-start-0">
            <i class="fas fa-lock text-secondary"></i>
          </span>
        </div>
        <div class="d-flex justify-content-end mt-3">
          <button type="submit" class="btn-signin">Sign In</button>
        </div>
      </form>
    </div>
  </div>