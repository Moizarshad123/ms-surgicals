
       <!-- header -->
       <div class="header">
        <div class="menu-toggle-btn">
            <!-- Menu close button for mobile devices -->
            <a href="#">
                <i class="bi bi-list"></i>
            </a>
        </div>
        <!-- Logo -->
        <a href="{{ route('dashboard') }}" class="logo">
            <img width="120" src="{{ asset('admin/images/logo-2.png') }}" alt="MS Surgicals">
        </a>
        <!-- ./ Logo -->
        <div class="page-title">@yield('page-title', 'Overview')</div>
           
            
        <!-- Header mobile buttons -->
        <div class="header-mobile-buttons">
            <a href="#" class="search-bar-btn">
                <i class="bi bi-search"></i>
            </a>
            <a href="#" class="actions-btn">
                <i class="bi bi-three-dots"></i>
            </a>
        </div>
        <!-- ./ Header mobile buttons -->
    </div>
    <!-- ./ header -->