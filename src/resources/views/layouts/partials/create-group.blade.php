<div style="margin-top: 1rem;">

    <form action="{{ route('laravel-localization.add-new-group') }}" method="post">

        @csrf
        <div class="form-group">

            <label for="new-group">{{ __('Enter group name') }}</label>
            <input class="form-control" id="new-group" name="new-group" value="{{ old('new-group') }}">
        </div>

        <div class="form-group">

            <button type="submit" class="btn btn-outline-primary">{{ __('Create new group') }}</button>
        </div>
    </form>
</div>