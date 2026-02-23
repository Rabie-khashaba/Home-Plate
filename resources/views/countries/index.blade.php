@extends('partial.master')
@section('title', 'Countries')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">All Countries</h5>
        <a href="{{ route('countries.create') }}" class="btn btn-primary">Add New</a>
    </div>

    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name (EN)</th>
                    <th>Name (AR)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($countries as $key => $country)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $country->name_en }}</td>
                    <td>{{ $country->name_ar }}</td>
                    <td class="flex gap-2">
                        <a href="{{ route('countries.edit', $country->id) }}" class="btn btn-info">Edit</a>
                        <form action="{{ route('countries.destroy', $country->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $countries->links() }}</div>
    </div>
</div>
@endsection
