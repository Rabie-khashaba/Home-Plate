@extends('partial.master')
@section('title', 'Show Delivery')

@section('content')
<div>
    <ul class="flex space-x-2 rtl:space-x-reverse">
        <li>
            <a href="{{ route('deliveries.index') }}" class="text-primary hover:underline">Deliveries</a>
        </li>
        <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
            <span>Profile</span>
        </li>
    </ul>

    <div class="pt-5">
        <div class="grid grid-cols-1 lg:grid-cols-3 xl:grid-cols-4 gap-5 mb-5">

            <!-- ===== Profile Panel ===== -->
            <div class="panel">
                <div class="flex items-center justify-between mb-5">
                    <h5 class="font-semibold text-lg dark:text-white-light">Delivery Profile</h5>
                    <a href="{{ route('deliveries.edit', $delivery->id) }}"
                        class="btn btn-primary p-2 rounded-full" title="Edit Delivery">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M4 21h4l11-11a2.828 2.828 0 10-4-4L4 17v4z" />
                        </svg>
                    </a>
                </div>

                <div class="mb-5">
                    <div class="flex flex-col justify-center items-center">
                        <img src="{{ $delivery->photo ? asset('storage/app/public/'.$delivery->photo) : asset('assets/images/profile-placeholder.png') }}"
                            alt="photo"
                            class="w-24 h-24 rounded-full object-cover mb-5 shadow-md" />

                        <p class="font-semibold text-primary text-xl">{{ $delivery->first_name }}</p>
                        <p class="text-sm text-gray-400">{{ $delivery->email ?? 'No Email' }}</p>
                    </div>

                    <ul class="mt-5 flex flex-col max-w-[200px] m-auto space-y-4 font-semibold text-white-dark">
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2 5.5A2.5 2.5 0 014.5 3h2A2.5 2.5 0 019 5.5v1a2.5 2.5 0 01-2.5 2.5H6a11 11 0 0011 11v-.5A2.5 2.5 0 0119.5 17h1a2.5 2.5 0 012.5 2.5v2A2.5 2.5 0 0120.5 24h-1A18.5 18.5 0 012 5.5z" />
                            </svg>
                            <span>{{ $delivery->phone ?? 'Not provided' }}</span>
                        </li>

                        <li class="flex items-center gap-2"><strong>Vehicle Type:</strong> {{ $delivery->vehicle_type }}</li>
                        <li class="flex items-center gap-2"><strong>City:</strong> {{ $delivery->city->name_en ?? '-' }}</li>
                        <li class="flex items-center gap-2"><strong>Area:</strong> {{ $delivery->area->name_en ?? '-' }}</li>
                    </ul>
                </div>
            </div>

            <!-- ===== Details Panel ===== -->
            <div class="panel lg:col-span-2 xl:col-span-3">
                <div class="mb-5">
                    <h5 class="font-semibold text-lg dark:text-white-light">Delivery Details</h5>
                </div>

                <div class="grid grid-cols-2 gap-4 font-semibold text-[#515365] dark:text-white-light">
                    <div>
                        <strong>Status:</strong>
                        <span class="{{ $delivery->status == 'approved' ? 'text-success' : ($delivery->status == 'pending' ? 'text-warning' : 'text-danger') }}">
                            {{ ucfirst($delivery->status) }}
                        </span>
                    </div>
                    <div>
                        <strong>Active:</strong>
                        <span class="{{ $delivery->is_active ? 'text-success' : 'text-danger' }}">
                            {{ $delivery->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div><strong>Created At:</strong> {{ $delivery->created_at->format('d M, Y') }}</div>
                    <div><strong>Last Updated:</strong> {{ $delivery->updated_at->diffForHumans() }}</div>
                </div>

                <hr class="my-6">

                <!-- ===== Documents Section ===== -->
                <h6 class="font-semibold text-lg mb-4">Documents</h6>

                <div class="overflow-x-auto py-2">
                    <div class="flex gap-4">
                        @php
                            $docs = [
                                'Driver License' => $delivery->drivers_license,
                                'National ID' => $delivery->national_id,
                                'Vehicle Photo' => $delivery->vehicle_photo,
                                'Vehicle License Front' => $delivery->vehicle_license['front'] ?? null,
                                'Vehicle License Back' => $delivery->vehicle_license['back'] ?? null,
                            ];
                        @endphp

                        @foreach ($docs as $label => $path)
                            @if ($path)
                                <div class="flex flex-col items-center text-center w-30 flex-shrink-0">
                                    <p class="font-semibold mb-1 text-xs">{{ $label }}</p>
                                    <a href="{{ asset('storage/app/public/'.$path) }}" target="_blank">
                                        <img src="{{ asset('storage/app/public/'.$path) }}" alt="{{ $label }}"
                                            class="w-16 h-16 object-cover rounded-md shadow-sm mb-1 transition-transform duration-200 hover:scale-110" />
                                    </a>
                                    <span class="text-primary underline text-xs cursor-pointer">View</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
