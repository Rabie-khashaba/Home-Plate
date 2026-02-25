@extends('partial.master')
@section('title', 'Subcategories')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">All Subcategories</h5>
        <a href="{{ route('subcategories.create') }}" class="btn btn-primary">Add New</a>
    </div>

    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Category</th>
                    <th>Name (EN)</th>
                    <th>Name (AR)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($subcategories as $key => $sub)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $sub->category->name_en }} / {{ $sub->category->name_ar }}</td>
                    <td>{{ $sub->name_en }}</td>
                    <td>{{ $sub->name_ar }}</td>
                    <td class="flex gap-2">
                        <a href="{{ route('subcategories.edit', $sub->id) }}" class="btn btn-info">Edit</a>
                        <form action="{{ route('subcategories.destroy', $sub->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                        <a href="{{ route('subcategories.show', $sub->id) }}" class="btn btn-secondary">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $subcategories->links() }}</div>
    </div>
</div>
@endsection
