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

  <style>
    body,
    html {
      height: 100%;
      font-family: 'Inter', sans-serif;
      background-color: #CDD1D9;
    }

    .center-box {
      min-width: 350px;
      /* keeps original width on desktop */
      height: 240px;
      background-color: #ffffff;
      padding: 20px;
    }

    h2 {
      font-weight: 600;
    }

    .offset-up {
      transform: translateY(-140px);
    }

    .btn {
      background-color: #56ca8b;
      color: white;
      border: none;
      padding: 8px 24px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .btn:hover {
      background-color: #38b174;
      color: white;
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
    <div class="center-box d-flex flex-column justify-content-center align-items-center">
      <div href='/user/login' wire:navigate>
        <button class="btn btn-primary mb-2" style="width: 150px;">Student</button>
      </div>
      <div href='/user/login' wire:navigate>
        <button class="btn btn-primary mb-2" style="width: 150px;">Employee</button>
      </div>
      <div href='/admin/login' wire:navigate>
        <button class="btn btn-primary" style="width: 150px;">Admin</button>
      </div>
    </div>
  </div>
  @livewireScripts
</body>