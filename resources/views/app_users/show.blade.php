@extends('partial.master')
@section('title', 'Show App User')

@section('content')
<div>
    <ul class="flex space-x-2 rtl:space-x-reverse">
        <li>
            <a href="{{ route('app_users.index') }}" class="text-primary hover:underline">Users</a>
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
                    <h5 class="font-semibold text-lg dark:text-white-light">Profile</h5>
                    <a href="{{ route('app_users.edit', $app_user->id) }}"
                        class="btn btn-primary p-2 rounded-full" title="Edit Profile">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M4 21h4l11-11a2.828 2.828 0 10-4-4L4 17v4z" />
                        </svg>
                    </a>
                </div>

                <div class="mb-5">
                    <div class="flex flex-col justify-center items-center">
                        <img src="{{ $app_user->photo ? asset('storage/'.$app_user->photo) : asset('assets/images/profile-placeholder.png') }}"
                            alt="image"
                            class="w-24 h-24 rounded-full object-cover mb-5 shadow-md" />

                        <p class="font-semibold text-primary text-xl">{{ $app_user->name }}</p>
                    </div>

                    <ul class="mt-5 flex flex-col max-w-[200px] m-auto space-y-4 font-semibold text-white-dark">

                        <!-- Email -->
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 8l9 6 9-6M4 6h16a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2z" />
                            </svg>
                            <span>{{ $app_user->email ?? 'No Email' }}</span>
                        </li>

                        <!-- Phone -->
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2 5.5A2.5 2.5 0 014.5 3h2A2.5 2.5 0 019 5.5v1a2.5 2.5 0 01-2.5 2.5H6a11 11 0 0011 11v-.5A2.5 2.5 0 0119.5 17h1a2.5 2.5 0 012.5 2.5v2A2.5 2.5 0 0120.5 24h-1A18.5 18.5 0 012 5.5z" />
                            </svg>
                            <span>{{ $app_user->phone ?? 'Not provided' }}</span>
                        </li>

                        <!-- Address -->
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 2a7 7 0 017 7c0 5.25-7 13-7 13S5 14.25 5 9a7 7 0 017-7z" />
                                <circle cx="12" cy="9" r="2.5" />
                            </svg>
                            <span>{{ $app_user->delivery_addresses ?? 'No address' }}</span>
                        </li>

                    </ul>
                </div>
            </div>

            <!-- ===== Details Panel ===== -->
            <div class="panel lg:col-span-2 xl:col-span-3">
                <div class="mb-5">
                    <h5 class="font-semibold text-lg dark:text-white-light">User Details</h5>
                </div>

                <div class="grid grid-cols-2 gap-4 font-semibold text-[#515365] dark:text-white-light">
                    <div>
                        <strong>Status:</strong>
                        <span
                            class="{{ $app_user->is_active == '1' ? 'text-success' : 'text-danger' }}">
                            {{ $app_user->is_active == '1' ? 'نشط' : 'غير نشط' }}
                        </span>
                    </div>
                    <div><strong>City:</strong> {{ $app_user->city->name_en }}</div>
                    <div><strong>Area</strong> {{ $app_user->area->name_en }}</div>
                    <div><strong>Address:</strong> {{ $app_user->delivery_addresses }}</div>
                    <div><strong>Birth Day:</strong> {{ $app_user->dob->format('d M, Y') }}</div>
                    <div><strong>Gender:</strong> {{ $app_user->gender }}</div>
                    <div><strong>Location:</strong>
                        @if ($app_user->location)
                            <a href="{{ $app_user->location }}" target="_blank" class="text-primary underline">View Map</a>
                        @else
                            <span>-</span>
                        @endif
                    </div>
                    <div><strong>Joined:</strong> {{ $app_user->created_at->format('d M, Y') }}</div>
                    <div><strong>Last Update:</strong> {{ $app_user->updated_at->diffForHumans() }}</div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
