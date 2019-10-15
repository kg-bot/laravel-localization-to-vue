<div style="margin-top: 1rem;">

    <form method="post" role="form" action="{{ route('laravel-localization.open-group') }}">

        @csrf
        <label for="select-group">{{ __('Select group to edit') }}</label>
        <select name="group" id="select-group" class="form-control group-select">

            <option value=""></option>
            @foreach($groups as $group)
                <option value="{{ $group }}" @if(old('group') === $group) selected @endif>{{ $group }}</option>
            @endforeach
        </select>
    </form>
</div>