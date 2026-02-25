@extends('partial.master')
@section('title', 'Show Vendor')

@section('content')
<div>
    @php
        $workingTime = is_array($vendor->working_time)
            ? $vendor->working_time
            : (json_decode($vendor->working_time ?? '', true) ?: null);

        $workingTimeText = '-';

        if (is_array($workingTime)) {
            $day = isset($workingTime['day']) ? ucfirst($workingTime['day']) : null;
            $from = $workingTime['from'] ?? null;
            $to = $workingTime['to'] ?? null;

            $parts = array_filter([$day, ($from && $to) ? "{$from} - {$to}" : null]);
            if (! empty($parts)) {
                $workingTimeText = implode(' | ', $parts);
            }
        }
    @endphp

    <div class="flex flex-wrap items-center justify-between gap-3">
        <ul class="flex space-x-2 rtl:space-x-reverse">
            <li>
                <a href="{{ route('vendors.index') }}" class="text-primary hover:underline">Vendors</a>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Profile</span>
            </li>
        </ul>

        <div class="flex flex-wrap gap-2">
            @if($vendor->status !== 'approved')
                <form action="{{ route('vendors.approve', $vendor->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success">Approve</button>
                </form>
            @endif

            @if($vendor->status !== 'rejected')
                <form action="{{ route('vendors.reject', $vendor->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger">Reject</button>
                </form>
            @endif

            @if($vendor->status === 'approved')
                <form action="{{ route('vendors.toggleStatus', $vendor->id) }}" method="POST">
                    @csrf
                    <button
                        type="submit"
                        class="btn {{ $vendor->is_active ? 'btn-success' : 'btn-danger' }}"
                        onclick="return confirm('Are you sure you want to change active status?')">
                        {{ $vendor->is_active ? 'Set Inactive' : 'Set Active' }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="pt-5">
        <div class="grid grid-cols-1 lg:grid-cols-3 xl:grid-cols-4 gap-5 mb-5">
            <div class="panel">
                <div class="flex items-center justify-between mb-5">
                    <h5 class="font-semibold text-lg dark:text-white-light">Vendor Profile</h5>
                    <a href="{{ route('vendors.edit', $vendor->id) }}" class="btn btn-primary p-2 rounded-full" title="Edit Vendor">Edit</a>
                </div>

                <div class="mb-5">
                    <div class="flex flex-col justify-center items-center">
                        <img src="{{ $vendor->main_photo ? asset('storage/app/public/' . $vendor->main_photo) : asset('assets/images/profile-placeholder.png') }}"
                            alt="image"
                            class="w-24 h-24 rounded-full object-cover mb-5 shadow-md" />

                        <p class="font-semibold text-primary text-xl">{{ $vendor->restaurant_name ?? '-' }}</p>
                        <p class="text-sm">{{ $vendor->full_name ?? '-' }}</p>
                        <p class="text-sm text-gray-400">{{ $vendor->email ?? 'No Email' }}</p>
                    </div>

                    <ul class="mt-5 flex flex-col max-w-[260px] m-auto space-y-4 font-semibold text-white-dark">
                        <li class="flex items-center gap-2">
                            <span>Phone:</span>
                            <span>{{ $vendor->phone ?? 'Not provided' }}</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span>Address:</span>
                            <span>{{ $vendor->delivery_address ?? 'No address' }}</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span>Working:</span>
                            <span>{{ $workingTimeText }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="panel lg:col-span-2 xl:col-span-3">
                <div class="mb-5">
                    <h5 class="font-semibold text-lg dark:text-white-light">Vendor Details</h5>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 font-semibold text-[#515365] dark:text-white-light">
                    <div>
                        <strong>Status:</strong>
                        <span class="{{ $vendor->status == 'approved' ? 'text-success' : ($vendor->status == 'pending' ? 'text-warning' : 'text-danger') }}">
                            {{ ucfirst($vendor->status) }}
                        </span>
                    </div>
                    <div>
                        <strong>Active:</strong>
                        <span class="{{ $vendor->is_active ? 'text-success' : 'text-danger' }}">
                            {{ $vendor->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div><strong>City:</strong> {{ $vendor->city->name_en ?? '-' }}</div>
                    <div><strong>Area:</strong> {{ $vendor->area->name_en ?? '-' }}</div>
                    <div><strong>Full Name:</strong> {{ $vendor->full_name ?? '-' }}</div>
                    <div><strong>Restaurant Name:</strong> {{ $vendor->restaurant_name ?? '-' }}</div>
                    <div class="md:col-span-2"><strong>Restaurant Info:</strong> {{ $vendor->restaurant_info ?? '-' }}</div>
                    <div>
                        <strong>Location:</strong>
                        @if ($vendor->location)
                            <a href="{{ $vendor->location }}" target="_blank" class="text-primary underline">View Map</a>
                        @else
                            <span>-</span>
                        @endif
                    </div>
                    <div><strong>Created At:</strong> {{ optional($vendor->created_at)->format('d M, Y') }}</div>
                    <div><strong>Last Updated:</strong> {{ optional($vendor->updated_at)->diffForHumans() }}</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                    @foreach ([
                        'ID Front' => $vendor->id_front,
                        'ID Back' => $vendor->id_back,
                        'Kitchen Photo 1' => $vendor->kitchen_photo_1,
                        'Kitchen Photo 2' => $vendor->kitchen_photo_2,
                        'Kitchen Photo 3' => $vendor->kitchen_photo_3,
                    ] as $label => $image)
                        <div class="border rounded p-3">
                            <p class="font-semibold mb-2">{{ $label }}</p>
                            @if($image)
                                <img src="{{ asset('storage/app/public/' . $image) }}" class="w-full h-40 object-cover rounded">
                            @else
                                <p class="text-sm text-gray-400">No image</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
