<!-- ========== Left Sidebar Start ========== -->
<div class="sidebar-left">

    <div data-simplebar class="h-100">

        <!--- Sidebar-menu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="left-menu list-unstyled" id="side-menu">
                <li>
                    <a href="{{ route('dashboard') }}" class="">
                        <i class="fas fa-desktop"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                @if(auth()->user()->user_type === 'ultra_super_admin')
                <li>
                    <a href="{{ route('registered-users.index') }}" class="{{ request()->routeIs('registered-users.*') ? 'active' : '' }}">
                        <i class="fas fa-users-cog"></i>
                        <span>Registered Users</span>
                    </a>
                </li>
                @elseif(auth()->user()->user_type === 'operator')
                <li>
                    <a href="{{ route('new-sales.index') }}" class="{{ request()->routeIs('new-sales.*') ? 'active' : '' }}">
                        <i class="fas fa-shopping-cart"></i>
                        <span>New Sales</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('loans.index') }}" class="{{ request()->routeIs('loans.*') ? 'active' : '' }}">
                        <i class="fa fa-credit-card"></i>
                        <span>Credit Sale</span>
                    </a>
                </li>
                @else
                <li class="menu-title">Pump Items Manage</li>

                @if(auth()->user()->user_type === 'super_admin')
                <li>
                    <a href="{{ route('pumps.index') }}" class="{{ request()->routeIs('pumps.*') ? 'active' : '' }}">
                        <i class="fas fa-gas-pump"></i>
                        <span>Pump Manage</span>
                    </a>
                </li>
                @endif

                <li>
                    <a href="{{ route('pump-users.index') }}" class="{{ request()->routeIs('pump-users.*') ? 'active' : '' }}">
                        <i class="fas fa-users"></i>
                        <span>User Manage</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('items.index') }}" class="{{ request()->routeIs('items.*') ? 'active' : '' }}">
                        <i class="fas fa-box"></i>
                        <span>Item & Nozzle</span>
                    </a>
                </li> 

                <li>
                    <a href="{{ route('attendance.index') }}" class="{{ request()->routeIs('attendance.*') ? 'active' : '' }}">
                        <i class="fas fa-user-check"></i>
                        <span>Attendance</span>
                    </a>
                </li>

                <li class="menu-title">Rate/Shift & Sales</li>
                <li>
                    <a href="{{ route('shifts.index') }}" class="{{ request()->routeIs('shifts.*') ? 'active' : '' }}">
                        <i class="fas fa-clock"></i>
                        <span>Shift & Rate</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('new-sales.index') }}" class="{{ request()->routeIs('new-sales.*') ? 'active' : '' }}">
                        <i class="fas fa-shopping-cart"></i>
                        <span>New Sales</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('loans.index') }}" class="{{ request()->routeIs('loans.*') ? 'active' : '' }}">
                        <i class="fa fa-credit-card"></i>
                        <span>Credit Sale</span>
                    </a>
                </li>

                <li class="menu-title">Summary Details</li>

                <li>
                    <a href="{{ url('report') }}">
                        <i class="fa fa-chart-bar"></i>
                        <span>Check Summary</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('daily-check-report.index') }}" class="{{ request()->routeIs('daily-check-report.*') ? 'active' : '' }}">
                        <i class="fa fa-clipboard-check"></i>
                        <span>Daily Report</span>
                    </a>
                </li>
                @if(auth()->user()->user_type === 'super_admin' || auth()->user()->user_type === 'admin')
                <li>
                    <a href="{{ route('payment.index') }}" class="{{ request()->routeIs('payment.*') ? 'active' : '' }}">
                        <i class="fa fa-credit-card"></i>
                        <span>Payment</span>
                    </a>
                </li>

                <li class="menu-title">Party Management</li>

                <li>
                    <a href="{{ route('party-list.index') }}" class="{{ request()->routeIs('party-list.*') ? 'active' : '' }}">
                        <i class="fas fa-users"></i>
                        <span>Party List</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('stock-management.index') }}" class="{{ request()->routeIs('stock-management.*') ? 'active' : '' }}">
                        <i class="fas fa-warehouse"></i>
                        <span>Stock Counter</span>
                    </a>
                </li>
                
                <li>
                    <a href="{{ route('expenses.index') }}" class="{{ request()->routeIs('expenses.*') ? 'active' : '' }}">
                        <i class="fas fa-wallet"></i>
                        <span>Expenses</span>
                    </a>
                </li>
                
                @endif
                @endif
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- Left Sidebar End -->
