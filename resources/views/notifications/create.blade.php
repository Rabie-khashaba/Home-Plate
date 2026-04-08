@extends('partial.master')
@section('title', 'Create Notification')

@section('content')
<div class="panel max-w-5xl mx-auto" x-data="{ type: '{{ old('type', 'immediate') }}' }">
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h5 class="text-lg font-semibold dark:text-white-light">Create Notification</h5>
            <p class="text-sm text-gray-500 mt-1">Send immediately, schedule once, or configure recurring delivery.</p>
        </div>
        <a href="{{ route('notifications.index') }}" class="btn btn-outline-primary">Back</a>
    </div>

    <form method="POST" action="{{ route('notifications.store') }}">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="md:col-span-2">
                <label class="form-label">Title</label>
                <input type="text" name="title" value="{{ old('title') }}" class="form-input @error('title') border-danger @enderror" required>
                @error('title')
                    <p class="mt-1 text-danger text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="form-label">Body</label>
                <textarea name="body" rows="5" class="form-textarea @error('body') border-danger @enderror" required>{{ old('body') }}</textarea>
                @error('body')
                    <p class="mt-1 text-danger text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="form-label">Target Audience</label>
                <select name="target_audience" class="form-select @error('target_audience') border-danger @enderror" required>
                    @foreach(['all' => 'All Users', 'users' => 'App Users', 'vendors' => 'Vendors', 'riders' => 'Riders'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('target_audience') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('target_audience')
                    <p class="mt-1 text-danger text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="form-label">Type</label>
                <select name="type" x-model="type" class="form-select @error('type') border-danger @enderror" required>
                    @foreach(['immediate' => 'Immediate', 'scheduled' => 'Scheduled', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly_day' => 'Monthly (Day of Week)', 'monthly_date' => 'Monthly (Date)'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('type')
                    <p class="mt-1 text-danger text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div x-show="type === 'scheduled'" x-cloak class="md:col-span-2">
                <label class="form-label">Scheduled At</label>
                <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" class="form-input @error('scheduled_at') border-danger @enderror">
                @error('scheduled_at')
                    <p class="mt-1 text-danger text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div x-show="['daily', 'weekly', 'monthly_day', 'monthly_date'].includes(type)" x-cloak>
                <label class="form-label">Recurrence Time</label>
                <input type="time" name="recurrence_time" value="{{ old('recurrence_time') }}" class="form-input @error('recurrence_time') border-danger @enderror">
                @error('recurrence_time')
                    <p class="mt-1 text-danger text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div x-show="type === 'weekly' || type === 'monthly_day'" x-cloak>
                <label class="form-label">Day Of Week</label>
                <select name="recurrence_day_of_week" class="form-select @error('recurrence_day_of_week') border-danger @enderror">
                    @foreach([0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'] as $value => $label)
                        <option value="{{ $value }}" @selected((string) old('recurrence_day_of_week') === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('recurrence_day_of_week')
                    <p class="mt-1 text-danger text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div x-show="type === 'monthly_day'" x-cloak>
                <label class="form-label">Week Of Month</label>
                <select name="recurrence_week_of_month" class="form-select @error('recurrence_week_of_month') border-danger @enderror">
                    @foreach([1 => 'First', 2 => 'Second', 3 => 'Third', 4 => 'Fourth'] as $value => $label)
                        <option value="{{ $value }}" @selected((string) old('recurrence_week_of_month') === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('recurrence_week_of_month')
                    <p class="mt-1 text-danger text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div x-show="type === 'monthly_date'" x-cloak>
                <label class="form-label">Date Of Month</label>
                <input type="number" min="1" max="31" name="recurrence_date" value="{{ old('recurrence_date') }}" class="form-input @error('recurrence_date') border-danger @enderror">
                @error('recurrence_date')
                    <p class="mt-1 text-danger text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex items-center gap-3 mt-6">
            <button type="submit" class="btn btn-primary">Save Notification</button>
            <a href="{{ route('notifications.index') }}" class="btn btn-outline-danger">Cancel</a>
        </div>
    </form>
</div>
@endsection
