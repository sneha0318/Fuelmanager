<!-- ========== Left Sidebar Start ========== -->
<div class="sidebar-left">

    <div data-simplebar class="h-100">

        <!--- Sidebar-menu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="left-menu list-unstyled" id="side-menu">
                <li>
                   <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
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
<style>
/* ===== CLIVAX SIDEBAR DESIGN ===== */

.sidebar-left {
    background: #ffffff !important;
    border-right: 1px solid #e8f0ec !important;
    box-shadow: 3px 0 18px rgba(21, 76, 55, 0.05);
}

/* Menu spacing */
.sidebar-left #side-menu {
    padding: 15px 12px 30px !important;
}

/* Normal menu item */
.sidebar-left #side-menu > li > a {
    position: relative;
    display: flex;
    align-items: center;

    min-height: 45px;
    margin: 4px 0;
    padding: 7px 12px !important;

    border-radius: 12px;

    color: #65756e !important;
    font-size: 14px;
    font-weight: 500;

    transition: all 0.2s ease;
}

/* Icons */
.sidebar-left #side-menu > li > a i {
    width: 35px;
    height: 35px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    margin-right: 11px;

    border-radius: 10px;

    background: #f1f8f4;
    color: #71847b;

    font-size: 14px;

    transition: all 0.2s ease;
}

.sidebar-left #side-menu > li > a:hover {
    background: linear-gradient(
        135deg,
        #ffffff 0%,
        #f3fbf7 50%,
        #eef9f4 100%
    ) !important;

    color: #079957 !important;
    transform: translateX(2px);
}

.sidebar-left #side-menu > li > a:hover i {
    background: #ddf5e9;
    color: #079957;
}

.sidebar-left #side-menu > li > a.active {
    position: relative;

    background: linear-gradient(
        135deg,
        #ffffff 0%,
        #eef9f4 50%,
        #e5f5ef 100%
    ) !important;

    color: #079957 !important;

    font-weight: 600;

    box-shadow:
        0 6px 18px rgba(20, 80, 70, 0.08),
        0 2px 6px rgba(32, 180, 134, 0.04);
}

/* Active green line */
.sidebar-left #side-menu > li > a.active::before {
    content: "";

    position: absolute;

    left: -12px;
    top: 8px;
    bottom: 8px;

    width: 4px;

    border-radius: 0 5px 5px 0;

    background: #19b875;
}

/* Active icon */
.sidebar-left #side-menu > li > a.active i {
    background: #d9f5e7;
    color: #079957;
}

/* Section heading */
.sidebar-left #side-menu .menu-title {
    display: flex;
    align-items: center;

    margin: 20px 7px 8px !important;

    color: #98a7a0 !important;

    font-size: 10px !important;
    font-weight: 700 !important;

    letter-spacing: 1.1px;

    text-transform: uppercase;
}

/* Line beside section heading */
.sidebar-left #side-menu .menu-title::after {
    content: "";

    flex: 1;

    height: 1px;

    margin-left: 10px;

    background: #edf2ef;
}

/* Dashboard spacing */
.sidebar-left #side-menu > li:first-child {
    margin-bottom: 14px;
}

/* Scrollbar */
.sidebar-left .simplebar-scrollbar:before {
    background: #b9e7d0;
}

/* Mobile */
@media (max-width: 991px) {
    .sidebar-left #side-menu {
        padding-left: 10px !important;
        padding-right: 10px !important;
    }

    .sidebar-left #side-menu > li > a {
        min-height: 43px;
    }
}
</style>