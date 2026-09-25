
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>MS Surgicals - @yield('title')</title>

     <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    {{-- <link rel="shortcut icon" href="../../assets/images/fav-ic-n.png" /> --}}
    @include('layouts.styles')
    <style>
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Firefox */
        input[type=number] {
            -moz-appearance: textfield;
        }
        .toast-success {
            background-color: #51A351 !important; /* Success message background */
            color: #fff !important; /* Success message text color */
        }
        .toast-error {
            background-color: #D9534F !important; /* Error message background */
            color: #fff !important; /* Error message text color */
        }
    </style>

    @yield('css')
    @stack('styles')

</head>

<body class="@yield('body-class')">

    <!-- preloader -->
    <div class="preloader">
        <img src="{{ asset('admin/images/logo-2.png')}}" alt="logo">
        <div class="preloader-icon"></div>
    </div>
    <!-- ./ preloader -->

    @include('layouts.extras')
    @include('layouts.sidebar')


    <!-- layout-wrapper -->
    <div class="layout-wrapper">

        @include('layouts.header')
      

        <!-- content -->
        <div class="content ">

            @yield('content')

        </div>
        <!-- ./ content -->

    </div>
    <!-- ./ layout-wrapper -->

    @include('layouts.scripts')

    

    <script>
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "timeOut": "3000",
            "extendedTimeOut": "1000",
            "positionClass": "toast-top-right", 
        };
    </script>

    @if(session()->has('success'))
    <script type="text/javascript">  toastr.success('{{ session('success')}}');</script>
    @endif
    @if(session()->has('error'))
    <script type="text/javascript"> toastr.error('{{ session('error')}}');</script>
    @endif
    @yield('js')
    @stack('scripts')
</body>

</html>