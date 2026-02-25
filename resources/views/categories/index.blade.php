@extends('partial.master')
@section('title', 'Categories')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">All Categories</h5>
        <a href="{{ route('categories.create') }}" class="btn btn-primary">Add New</a>
    </div>

    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name (EN)</th>
                    <th>Name (AR)</th>
                    <th>Photo</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $key => $category)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $category->name_en }}</td>
                    <td>{{ $category->name_ar }}</td>
                    <td>
                        @if($category->photo)
                            <img src="{{ asset('storage/app/public/'.$category->photo) }}" class="w-12 h-12 rounded" />
                        @else
                            <span class="text-gray-400">No Photo</span>
                        @endif
                    </td>
                    <td class="flex gap-2">
                        <a href="{{ route('categories.edit', $category->id) }}" class="btn btn-info">Edit</a>
                        <form action="{{ route('categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $categories->links() }}</div>
    </div>
</div>
@endsection
