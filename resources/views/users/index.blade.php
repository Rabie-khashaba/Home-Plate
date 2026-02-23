@extends('partial.master')

@section('title')

@endsection

@section('content')

<!-- captions -->
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Captions</h5>
        <a
            class="font-semibold hover:text-gray-400 dark:text-gray-400 dark:hover:text-gray-600"
            href="javascript:;"
            @click="toggleCode('code5')"
            ><span class="flex items-center">
                <svg
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 ltr:mr-2 rtl:ml-2"
                >
                    <path
                        d="M17 7.82959L18.6965 9.35641C20.239 10.7447 21.0103 11.4389 21.0103 12.3296C21.0103 13.2203 20.239 13.9145 18.6965 15.3028L17 16.8296"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                    />
                    <path
                        opacity="0.5"
                        d="M13.9868 5L10.0132 19.8297"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                    />
                    <path
                        d="M7.00005 7.82959L5.30358 9.35641C3.76102 10.7447 2.98975 11.4389 2.98975 12.3296C2.98975 13.2203 3.76102 13.9145 5.30358 15.3028L7.00005 16.8296"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                    />
                </svg>
                Code</span
            ></a
        >
    </div>
    <div class="mb-5">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th class="text-center">Register</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="data in tableData" :key="data.id">
                        <tr>
                            <td x-text="data.id"></td>
                            <td x-text="data.name" class="whitespace-nowrap"></td>
                            <td x-text="data.email"></td>
                            <td>
                                <span
                                    class="badge whitespace-nowrap"
                                    :class="{'badge-outline-primary': data.status === 'Complete', 'badge-outline-secondary': data.status === 'Pending', 'badge-outline-info': data.status === 'In Progress', 'badge-outline-danger': data.status === 'Canceled'}"
                                    x-text="data.status"
                                ></span>
                            </td>
                            <td class="text-center" x-text="data.register"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection


@section('scripts')


@endsection
