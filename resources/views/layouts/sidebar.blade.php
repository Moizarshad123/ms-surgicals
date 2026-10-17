<!-- menu -->

<div class="menu">
    <div class="menu-header text-center d-flex flex-column align-items-center justify-content-center">
        <a href="{{ route('dashboard') }}" class="menu-header-logo d-flex justify-content-center align-items-center w-100">
            <img src="{{ asset('admin/images/logo-2.png') }}" alt="logo" style="width: 150px; height: auto; max-height: 100px; object-fit: contain;">
        </a>
        <a href="{{ url('/') }}" class="btn btn-sm menu-close-btn">
            <i class="bi bi-x"></i>
        </a>
    </div>
    <div class="menu-body">
            <div class="dropdown">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center">
                    <div class="avatar me-3">
                        <img src="{{ asset('admin/images/man_avatar3.jpg')}}"
                            class="rounded-circle" alt="image">
                    </div>
                    <div>
                        <div class="fw-bold">TG Surgicals</div>
                    </div>
                </a>
            </div>
        <ul>

            <li>
                <a class="{{ request()->is('dashboard*') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <span class="nav-link-icon">
                        <i class="bi bi-bar-chart"></i>
                    </span>
                    <span>Dashboard</span>
                </a>
            </li>

            <li>
                <a class="{{ request()->is('pos*') ? 'active' : '' }}" href="{{ route('pos.index') }}">
                    <span class="nav-link-icon">
                        <i class="bi bi-cart"></i>
                    </span>
                    <span>POS</span>
                </a>
            </li>

            <li>
                <a class="{{ request()->is('credit-sales*') ? 'active' : '' }}" href="{{ route('credit-sales.index') }}">
                    <span class="nav-link-icon">
                        <i class="bi bi-wallet2"></i>
                    </span>
                    <span>Credit Sales / Installments</span>
                </a>
            </li>

            <li>
                <a class="{{ request()->is('sales-returns*') ? 'active' : '' }}" href="{{ route('sales-returns.index') }}">
                    <span class="nav-link-icon">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </span>
                    <span>Sales Return</span>
                </a>
            </li>

            <li>
                <a class="{{ request()->is('categories*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
                    <span class="nav-link-icon">
                        <i class="bi bi-tags"></i>
                    </span>
                    <span>Categories</span>
                </a>
            </li>
         

            <li>
                <a class="{{ request()->is('products*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                    <span class="nav-link-icon">
                        <i class="bi bi-basket"></i>
                    </span>
                    <span>Products</span>
                </a>
            </li>

            <li>
                <a class="{{ request()->is('purchases*') && !request()->is('purchase-returns*') ? 'active' : '' }}" href="{{ route('purchases.index') }}">
                    <span class="nav-link-icon">
                        <i class="bi bi-credit-card"></i>
                    </span>
                    <span>Purchases</span>
                </a>
            </li>

            <li>
                <a class="{{ request()->is('purchase-returns*') ? 'active' : '' }}" href="{{ route('purchase-returns.index') }}">
                    <span class="nav-link-icon">
                        <i class="bi bi-arrow-return-left"></i>
                    </span>
                    <span>Purchase Return</span>
                </a>
            </li>
          
            <li>
                <a class="{{ request()->is('expenses*') ? 'active' : '' }}" href="{{ route('expenses.index') }}">
                    <span class="nav-link-icon">
                        <i class="bi bi-cash"></i>
                    </span>
                    <span>Expenses</span>
                </a>
            </li>

            <li>
                <a class="{{ request()->is('banks*') ? 'active' : '' }}" href="{{ route('banks.index') }}">
                    <span class="nav-link-icon">
                        <i class="bi bi-bank"></i>
                    </span>
                    <span>Bank Accounts</span>
                </a>
            </li>

            <li class="{{ request()->is('reports*') ? 'open' : '' }}">
                <a href="javascript:;" class="{{ request()->is('reports*') ? 'active' : '' }}">
                    <span class="nav-link-icon">
                        <i class="bi bi-receipt"></i>
                    </span>
                    <span>Reports</span>
                </a>
                <ul>
                    <li>
                        <a class="{{ request()->is('reports/sales*') ? 'active' : '' }}" href="{{ route('reports.sales') }}">Sales Report</a>
                    </li>
                    <li>
                        <a class="{{ request()->is('reports/purchases*') ? 'active' : '' }}" href="{{ route('reports.purchases') }}">Purchase Report</a>
                    </li>
                    <li>
                        <a class="{{ request()->is('reports/expenses*') ? 'active' : '' }}" href="{{ route('reports.expenses') }}">Expense Report</a>
                    </li>
                    <li>
                        <a class="{{ request()->is('reports/bank*') ? 'active' : '' }}" href="{{ route('reports.bank') }}">Bank Statement / Report</a>
                    </li>
                </ul>
            </li>
          
            <li>
                <a href="{{ route('logout') }}"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <span class="nav-link-icon">
                        <i class="bi bi-person-badge"></i>
                    </span>
                    <span>Logout</span>
                </a>

                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </li>
        </ul>
    </div>
</div>
<!-- ./  menu -->