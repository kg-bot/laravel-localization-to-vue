@extends('laravel-localization::layouts.master')


@section('content')

    <div style="margin-top: 1rem;">

        <h5>{{ __('Current supported locales') }}</h5>

        <hr style="margin-top: 0.5rem;"/>

        <form method="post" role="form" action="{{ route('laravel-localization.delete-locale') }}" data-confirm="Are you sure to remove this locale and all of data?">
            @csrf
            <ul>
                @foreach($languages as $language)
                    <li>
                        <div class="form-group">
                            <button type="submit" name="remove-locale[{{ $language }}]" class="btn btn-danger btn-sm" data-disable-with="...">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            {{ $language }}
                        </div>
                    </li>
                @endforeach
            </ul>
        </form>

        <div>

            <form action="{{ route('laravel-localization.add-new-locale') }}" method="post">

                @csrf

                <div class="form-group">
                    <p>
                        {{ __('Enter new locale key') }}:
                    </p>
                    <div class="row">
                        <div class="col-sm-3">
                            <input type="text" name="locale" value="{{ old('locale') }}" class="{{ $errors->has('locale') ? 'is-invalid' : '' }} form-control">
                            @if($errors->has('locale'))
                            <div class="invalid-feedback">
                                {{ $errors->first('locale') }}
                            </div>
                                @endif
                        </div>
                        <div class="col-sm-2">
                            <button type="submit" class="btn btn-primary btn-block" data-disable-with="Adding..">{{ __('Add new locale') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

    </div>

@endsection
