@extends('partial.master')
@section('title', 'Create Role')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Create Role</h5>
        <a href="{{ route('roles.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <form class="grid grid-cols-1 md:grid-cols-2 gap-5" method="POST" action="{{ route('roles.store') }}">
        @csrf

        <div class="md:col-span-2">
            <label for="name">Role Name</label>
            <input id="name" name="name" type="text" class="form-input" value="{{ old('name') }}" required>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="md:col-span-2">
            <label>Permissions</label>
            <div class="grid grid-cols-2 gap-3 mt-2">
                @foreach($permissions as $permission)
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="form-checkbox">
                        <span>{{ $permission->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="md:col-span-2">
            <button type="submit" class="btn btn-primary w-full md:w-auto !mt-6">Save Role</button>
        </div>
    </form>
</div>
@endsection
