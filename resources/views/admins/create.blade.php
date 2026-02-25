@extends('partial.master')
@section('title', 'Create Admin')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Create Admin</h5>
        <a href="{{ route('admins.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <form class="grid grid-cols-1 md:grid-cols-2 gap-5" method="POST" action="{{ route('admins.store') }}">
        @csrf

        <div>
            <label for="name">Name</label>
            <input id="name" name="name" type="text" class="form-input" required />
        </div>

        <div>
            <label for="phone">Phone</label>
            <input id="phone" name="phone" type="text" class="form-input" required />
        </div>

        <div>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" class="form-input" required />
        </div>

        <div>
            <label for="password_confirmation">Confirm Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="form-input" required />
        </div>

        <div>
            <label for="type">Type</label>
            <select id="type" name="type" class="form-select text-white-dark" required>
                <option value="admin">Admin</option>
                <option value="user">User</option>
            </select>
        </div>

        <div class="md:col-span-2">
            <button type="submit" class="btn btn-primary w-full md:w-auto !mt-6">Save</button>
        </div>
    </form>
</div>
@endsection
