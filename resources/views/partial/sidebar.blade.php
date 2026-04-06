 <!-- start sidebar section -->
            <div :class="{'dark text-white-dark' : $store.app.semidark}">
                <nav
                    x-data="sidebar"
                    class="sidebar fixed bottom-0 top-0 z-50 h-full min-h-screen w-[260px] shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] transition-all duration-300"
                >
                    <div class="h-full bg-white dark:bg-[#0e1726]">
                        <div class="flex items-center justify-between px-4 py-3">
                            <a href="{{ url('/') }}" class="main-logo flex shrink-0 items-center">
                                <img class="ml-[5px] w-8 flex-none" src="{{ asset('assets/images/Home plate.svg')}}" alt="image" />
                                <span class="align-middle text-2xl font-semibold ltr:ml-1.5 rtl:mr-1.5 dark:text-white-light lg:inline">Home Plate</span>
                            </a>
                            <a
                                href="javascript:;"
                                class="collapse-icon flex h-8 w-8 items-center rounded-full transition duration-300 hover:bg-gray-500/10 rtl:rotate-180 dark:text-white-light dark:hover:bg-dark-light/10"
                                @click="$store.app.toggleSidebar()"
                            >
                                <svg class="m-auto h-5 w-5" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M13 19L7 12L13 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path
                                        opacity="0.5"
                                        d="M16.9998 19L10.9998 12L16.9998 5"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </a>
                        </div>

                        <div x-data="{ activeDropdown: null }">
                            <ul class="perfect-scrollbar relative space-y-0.5 overflow-y-auto overflow-x-hidden p-4 py-0 font-semibold">



                                <!-- USERS -->
                                <li class="menu nav-item">
                                    <button
                                        type="button"
                                        class="nav-link group flex w-full items-center justify-between"
                                        :class="{ 'active': activeDropdown === 'users' }"
                                        @click="activeDropdown = activeDropdown === 'users' ? null : 'users'"
                                    >
                                        <div class="flex items-center">
                                            <svg class="shrink-0 group-hover:!text-primary" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.5"/>
                                                <path d="M2 21v-1a7 7 0 0 1 7-7v0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                                <path d="M16 19h6M19 16v6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                            </svg>
                                            <span class="ltr:pl-3 rtl:pr-3 dark:text-[#506690] group-hover:text-primary">Users</span>
                                        </div>
                                        <svg :class="{'rotate-90': activeDropdown === 'users'}" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>

                                    <ul x-show="activeDropdown === 'users'" x-collapse class="sub-menu text-gray-500 ml-6">
                                        <li><a href="{{route('app_users.index')}}">User</a></li>
                                        <li><a href="{{route('vendors.index')}}">Vendor</a></li>
                                        <li><a href="{{route('deliveries.index')}}">Delivery</a></li>
                                    </ul>
                                </li>


                                 <!-- ITEMS -->
                                <li class="menu nav-item">
                                    <button
                                        type="button"
                                        class="nav-link group flex w-full items-center justify-between"
                                        :class="{ 'active': activeDropdown === 'items' }"
                                        @click="activeDropdown = activeDropdown === 'items' ? null : 'items'"
                                    >
                                        <div class="flex items-center">
                                            <svg class="shrink-0 group-hover:!text-primary" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                                <circle cx="7" cy="6" r="1.5" fill="currentColor"/>
                                                <circle cx="7" cy="12" r="1.5" fill="currentColor"/>
                                                <circle cx="7" cy="18" r="1.5" fill="currentColor"/>
                                            </svg>
                                            <span class="ltr:pl-3 rtl:pr-3 dark:text-[#506690] group-hover:text-primary">Items</span>
                                        </div>
                                        <svg :class="{'rotate-90': activeDropdown === 'items'}" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>

                                    <ul x-show="activeDropdown === 'items'" x-collapse class="sub-menu text-gray-500 ml-6">
                                        <li><a href="{{ route('items.index', ['approval_status' => 'pending']) }}">Requests </a></li>
                                        <li><a href="{{ route('items.index', ['approval_status' => 'approved']) }}">All Items</a></li>
                                    </ul>
                                </li>


                                <!-- ORDERS -->
                                <li class="menu nav-item">
                                    <a href="{{ route('orders.index') }}"
                                    @class([
                                        'nav-link group flex w-full items-center justify-start dark:text-[#506690] hover:text-primary',
                                        'active' => request()->routeIs('orders.*')
                                    ])>
                                        <div class="flex items-center">
                                            <svg class="shrink-0 group-hover:!text-primary" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M3 6h18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                                <path d="M16 10a4 4 0 0 1-8 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <span class="ltr:pl-3 rtl:pr-3">Orders</span>
                                        </div>
                                    </a>
                                </li>

                                <!-- Transactions -->
                                <li class="menu nav-item">
                                    <a href="{{ route('transactions.index') }}"
                                    @class([
                                        'nav-link group flex w-full items-center justify-start dark:text-[#506690] hover:text-primary',
                                        'active' => request()->routeIs('transactions.*')
                                    ])>
                                        <div class="flex items-center">
                                            <svg class="shrink-0 group-hover:!text-primary" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                                <path d="M2 10h20" stroke="currentColor" stroke-width="1.5"/>
                                                <path d="M6 15h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                            </svg>
                                            <span class="ltr:pl-3 rtl:pr-3">Transactions</span>
                                        </div>
                                    </a>
                                </li>

                                <!-- Wallets -->
                                <li class="menu nav-item">
                                    <a href="{{ route('wallets.index') }}"
                                    @class([
                                        'nav-link group flex w-full items-center justify-start dark:text-[#506690] hover:text-primary',
                                        'active' => request()->routeIs('wallets.*')
                                    ])>
                                        <div class="flex items-center">
                                            <svg class="shrink-0 group-hover:!text-primary" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z" stroke="currentColor" stroke-width="1.5"/>
                                                <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2" stroke="currentColor" stroke-width="1.5"/>
                                                <circle cx="16" cy="13" r="1.5" fill="currentColor"/>
                                            </svg>
                                            <span class="ltr:pl-3 rtl:pr-3">Wallets</span>
                                        </div>
                                    </a>
                                </li>

                                <!-- Reports -->
                                <li class="menu nav-item">
                                    <a href="{{ route('reports.index') }}"
                                    @class([
                                        'nav-link group flex w-full items-center justify-start dark:text-[#506690] hover:text-primary',
                                        'active' => request()->routeIs('reports.*')
                                    ])>
                                        <div class="flex items-center">
                                            <svg class="shrink-0 group-hover:!text-primary" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M18 20V10M12 20V4M6 20v-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                            </svg>
                                            <span class="ltr:pl-3 rtl:pr-3">Reports</span>
                                        </div>
                                    </a>
                                </li>

                                <!-- Coupons -->
                                <li class="menu nav-item">
                                    <a href="{{ route('coupons.index') }}"
                                    @class([
                                        'nav-link group flex w-full items-center justify-start dark:text-[#506690] hover:text-primary',
                                        'active' => request()->routeIs('coupons.*')
                                    ])>
                                        <div class="flex items-center">
                                            <svg class="shrink-0 group-hover:!text-primary" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M20 12V6a2 2 0 00-2-2H6a2 2 0 00-2 2v6m16 0v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6m16 0H4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                                <path d="M9 12h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                            </svg>
                                            <span class="ltr:pl-3 rtl:pr-3">Coupons</span>
                                        </div>
                                    </a>
                                </li>

                                <!-- Rates -->
                                <li class="menu nav-item">
                                    <a href="{{ route('ratings.index') }}"
                                    @class([
                                        'nav-link group flex w-full items-center justify-start dark:text-[#506690] hover:text-primary',
                                        'active' => request()->routeIs('ratings.*')
                                    ])>
                                        <div class="flex items-center">
                                            <svg class="shrink-0 group-hover:!text-primary" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <span class="ltr:pl-3 rtl:pr-3">Rates</span>
                                        </div>
                                    </a>
                                </li>

                                <!-- Support -->
                                <li class="menu nav-item">
                                    <a href="{{ route('support.index') }}"
                                    @class([
                                        'nav-link group flex w-full items-center justify-start dark:text-[#506690] hover:text-primary',
                                        'active' => request()->routeIs('support.*')
                                    ])>
                                        <div class="flex items-center">
                                            <svg class="shrink-0 group-hover:!text-primary" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <span class="ltr:pl-3 rtl:pr-3">Support</span>
                                        </div>
                                    </a>
                                </li>


                                <!-- Notifications -->
                                <li class="menu nav-item">
                                    <a href="{{ route('notifications.index') }}"
                                    @class([
                                        'nav-link group flex w-full items-center justify-start dark:text-[#506690] hover:text-primary',
                                        'active' => request()->routeIs('notifications.*')
                                    ])>
                                        <div class="flex items-center">
                                            <svg class="shrink-0 group-hover:!text-primary" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M13.73 21a2 2 0 0 1-3.46 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <span class="ltr:pl-3 rtl:pr-3">Notifications</span>
                                        </div>
                                    </a>
                                </li>


                                <!-- APP SETTING -->
                                <li class="menu nav-item">
                                    <button
                                        type="button"
                                        class="nav-link group flex w-full items-center justify-between"
                                        :class="{ 'active': activeDropdown === 'appSetting' }"
                                        @click="activeDropdown = activeDropdown === 'appSetting' ? null : 'appSetting'"
                                    >
                                        <div class="flex items-center">
                                            <svg class="shrink-0 group-hover:!text-primary" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <rect x="2" y="3" width="20" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                                <path d="M8 21h8M12 17v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                            </svg>
                                            <span class="ltr:pl-3 rtl:pr-3 dark:text-[#506690] group-hover:text-primary">App Settings</span>
                                        </div>
                                        <svg :class="{'rotate-90': activeDropdown === 'appSetting'}" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>

                                    <ul x-show="activeDropdown === 'appSetting'" x-collapse class="sub-menu text-gray-500 ml-6">
                                        <li><a href="{{ route('banners.index') }}">Slider / Banners</a></li>
                                        <li><a href="{{ route('delivery_fees.index') }}">Delivery Fees</a></li>
                                        <li><a href="{{ route('general_settings.edit') }}">Maintenance</a></li>
                                    </ul>
                                </li>

                                <!-- SETTINGS -->
                                <li class="menu nav-item">
                                    <button
                                        type="button"
                                        class="nav-link group flex w-full items-center justify-between"
                                        :class="{ 'active': activeDropdown === 'settings' }"
                                        @click="activeDropdown = activeDropdown === 'settings' ? null : 'settings'"
                                    >
                                        <div class="flex items-center">
                                            <svg class="shrink-0 group-hover:!text-primary" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>
                                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" stroke="currentColor" stroke-width="1.5"/>
                                            </svg>
                                            <span class="ltr:pl-3 rtl:pr-3 dark:text-[#506690] group-hover:text-primary">Settings</span>
                                        </div>
                                        <svg :class="{'rotate-90': activeDropdown === 'settings'}" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>

                                    <ul x-show="activeDropdown === 'settings'" x-collapse class="sub-menu text-gray-500 ml-6">
                                        <li><a href="{{route('categories.index')}}">Categories</a></li>
                                        <li><a href="{{route('subcategories.index')}}">SubCategories</a></li>
                                        <li><a href="{{route('countries.index')}}">Countries</a></li>
                                        <li><a href="{{route('cities.index')}}">Cities</a></li>
                                        <li><a href="{{route('areas.index')}}">Areas</a></li>
                                        <li><a href="{{route('admins.index')}}">Admins</a></li>
                                        <li><a href="{{route('roles.index')}}">Roles</a></li>
                                    </ul>
                                </li>

                                <!-- Activity Log -->
                                <li class="menu nav-item">
                                    <a href="{{ route('activity_logs.index') }}"
                                    @class([
                                        'nav-link group flex w-full items-center justify-start dark:text-[#506690] hover:text-primary',
                                        'active' => request()->routeIs('activity_logs.*')
                                    ])>
                                        <div class="flex items-center">
                                            <svg class="shrink-0 group-hover:!text-primary" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke="currentColor" stroke-width="1.5"/>
                                                <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                            </svg>
                                            <span class="ltr:pl-3 rtl:pr-3">Activity Log</span>
                                        </div>
                                    </a>
                                </li>

                            </ul>
                        </div>




                    </div>
                </nav>
            </div>
            <!-- end sidebar section -->
