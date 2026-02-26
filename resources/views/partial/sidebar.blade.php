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

                                            <span class="ltr:pl-3 rtl:pr-3 dark:text-[#506690] group-hover:text-primary">Items</span>
                                        </div>
                                        <svg :class="{'rotate-90': activeDropdown === 'items'}" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>

                                    <ul x-show="activeDropdown === 'items'" x-collapse class="sub-menu text-gray-500 ml-6">
                                        <li><a href="{{ route('items.index', ['approval_status' => 'pending']) }}">Requests </a></li>
                                        <li><a href="{{ route('items.index' , ['approval_status' => 'pending']) }}">All Items</a></li>
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
                                            <span class="ltr:pl-3 rtl:pr-3">Orders</span>
                                        </div>
                                    </a>
                                </li>

                                <!-- Rates -->
                                <li class="menu nav-item">
                                    <a href="#"
                                    @class([
                                        'nav-link group flex w-full items-center justify-start dark:text-[#506690] hover:text-primary',
                                        'active' => request()->routeIs('rates.*')
                                    ])>
                                        <div class="flex items-center">
                                            <span class="ltr:pl-3 rtl:pr-3">Rates</span>
                                        </div>
                                    </a>
                                </li>

                                <!-- Support -->
                                <li class="menu nav-item">
                                    <a href="#"
                                    @class([
                                        'nav-link group flex w-full items-center justify-start dark:text-[#506690] hover:text-primary',
                                        'active' => request()->routeIs('support.*')
                                    ])>
                                        <div class="flex items-center">
                                            <span class="ltr:pl-3 rtl:pr-3">Support</span>
                                        </div>
                                    </a>
                                </li>


                                <!-- Notifications -->
                                <li class="menu nav-item">
                                    <a href="#"
                                    @class([
                                        'nav-link group flex w-full items-center justify-start dark:text-[#506690] hover:text-primary',
                                        'active' => request()->routeIs('notifications.*')
                                    ])>
                                        <div class="flex items-center">
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

                                            <span class="ltr:pl-3 rtl:pr-3 dark:text-[#506690] group-hover:text-primary">App Settings</span>
                                        </div>
                                        <svg :class="{'rotate-90': activeDropdown === 'appSetting'}" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>

                                    <ul x-show="activeDropdown === 'appSetting'" x-collapse class="sub-menu text-gray-500 ml-6">
                                        <li><a href="#">Slider / Banners </a></li>
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




                            </ul>
                        </div>




                    </div>
                </nav>
            </div>
            <!-- end sidebar section -->
