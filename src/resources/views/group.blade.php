@extends('laravel-localization::layouts.master')

@push('styles')
    <link href="/vendor/laravel-localization/vendor/bootstrap-editable/css/bootstrap-editable.css" rel="stylesheet"/>

    <style>

        .editable-empty {

            color: red !important;
        }
    </style>
@endpush

@push('scripts')

    <script src="/vendor/laravel-localization/vendor/bootstrap-editable/js/bootstrap-editable.min.js"></script>
    <script>
        $(document).ready(function() {

            $('.editable').editable({
                url: "{{ route('laravel-localization.post-key') }}",
                save: 'always',
                ajaxOptions: {
                    type: 'POST',
                    dataType: 'json',
                    headers: {

                        'X-CSRF-TOKEN': document.head.querySelector( 'meta[name="csrf-token"]' ).content,
                    }
                }
            });

        })
    </script>
@endpush

@section('content')

    <div style="margin-top: 1rem;">

        <form method="post" action="{{ route('laravel-localization.add-new-keys') }}">

            @csrf
            <input type="hidden" name="group" value="{{ $group_name }}">
            <label for="keys">{{ __('Add and edit keys in this group') }}</label>
            <textarea class="form-control" id="keys" name="keys" rows="4" placeholder="{{ __('Add 1 key per line, without the group prefix') }}"></textarea>

            <button style="margin-top: 1rem;" class="btn btn-primary" type="submit">{{ __('Add keys') }}</button>
        </form>
    </div>

    <div style="margin-top: 1rem;">

        <table class="table table-striped" data-toggle="table">

            <thead class="thead-dark">

            <tr>
                <th scope="col">{{ __('Key') }}</th>
                @foreach($languages as $language)
                        <th scope="col"> {{ $language }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>

            @foreach($group as $group_key => $locales)
                    <tr>
                        <td>{{ htmlentities(($group_key), ENT_QUOTES, 'UTF-8', false) }}</td>
                        @foreach($languages as $language)
                            @if(isset($locales[$language]) && !is_array($locales[$language]))
                                <td> <a href="#" class="editable" data-type="text" data-name="{{ $group_name . '/' . $group_key }}" data-pk="{{ $language }}">{{ htmlentities($locales[$language], ENT_QUOTES, 'UTF-8', false) }}</a></td>
                            @else
                                <td> <a href="#" class="editable" data-name="{{ $group_name . '/' . $group_key }}" data-pk="{{ $language }}">@isset($locales[$language][$group_key]){{ $locales[$language][$group_key] }}@endisset</a></td>
                            @endisset
                        @endforeach
                    </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection