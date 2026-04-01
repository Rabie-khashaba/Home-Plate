@extends('partial.master')
@section('title', 'General Settings')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">General Settings</h5>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    <form class="grid grid-cols-1 gap-5" method="POST" action="{{ route('general_settings.update') }}">
        @csrf
        @method('PUT')

        <div class="flex items-center gap-3">
            <input id="maintenance" name="maintenance" type="checkbox" class="form-checkbox" value="1" {{ $setting->maintenance ? 'checked' : '' }} />
            <label for="maintenance" class="mb-0">Maintenance Mode</label>
        </div>

        <div>
            <label for="message">Maintenance Message</label>
            <input id="message" name="message" type="text" class="form-input" value="{{ old('message', $setting->message) }}" />
        </div>

        <div>
            <button type="submit" class="btn btn-primary w-full md:w-auto !mt-2">
                Save
            </button>
        </div>
    </form>
</div>
@endsection
