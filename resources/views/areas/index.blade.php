@extends('partial.master')
@section('title', 'Areas')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">All Areas</h5>
        <a href="{{ route('areas.create') }}" class="btn btn-primary">Add New</a>
    </div>

    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>City</th>
                    <th>Name (EN)</th>
                    <th>Name (AR)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($areas as $key => $area)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $area->city->name_en ?? '-' }}</td>
                    <td>{{ $area->name_en }}</td>
                    <td>{{ $area->name_ar }}</td>
                    <td class="flex gap-2">
                        <a href="{{ route('areas.show', $area->id) }}" class="btn btn-success">Show</a>
                        <a href="{{ route('areas.edit', $area->id) }}" class="btn btn-info">Edit</a>
                        <form action="{{ route('areas.destroy', $area->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $areas->links() }}</div>
    </div>
</div>
@endsection
