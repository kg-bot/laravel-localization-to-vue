<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('laravel-localization::laravel-localization.title') }}</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">

    <style>

        html, body {

            font-size: 14px;
        }
    </style>

    @stack('styles')
    </head>

<body>
<div class="toast toast-success bg-success text-white" style="position: absolute; top: 70px; right: 0;" role="alert" aria-live="assertive" aria-atomic="true">

            <div class="toast-body">
                {{ Session::get('success') }}
            </div>
</div>

<div class="toast toast-error bg-danger text-white" style="position: absolute; top: 70px; right: 0;" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-body">
                {{ Session::get('error') }}
            </div>
</div>


@include('laravel-localization::layouts.partials.navbar')
<div class="container-fluid">
    @include('laravel-localization::layouts.partials.group-select')

    @include('laravel-localization::layouts.partials.create-group')

    @yield('content')
</div>


<script
        src="https://code.jquery.com/jquery-3.4.1.min.js"
        integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
        crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

<script>

    $(document).ready(function() {

        $('.toast').toast({

            delay: 1500,
        });
        @if(Session::has('success'))
            $('.toast-success').toast('show');
        @endif

        @if(Session::has('error'))
        $('.toast-error').toast('show');
        @endif

        $('.group-select').change(function() {

            let group = $(this).val();
            if (group) {
                window.location.href = "{{ route('laravel-localization.get-open-group', 0) }}".replace(0, group);
            } else {
                window.location.href = "{{ route('laravel-localization.web') }}";
            }
        })
    })
</script>

@stack('scripts')
</body>
</html>