<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
        <li class="nav-item nav-profile">
            <div class="nav-link">
                <div class="user-wrapper">
                    <div class="profile-image">
                        <img src="{{ asset('admin/images/avatar.png') }}" alt="profile image">
                    </div>
                    <div class="text-wrapper">
                        <p class="profile-name">
                            {{ auth()->user()->full_name }}
                        </p>
                        <div>
                            <small class="designation text-muted">{{ __('Super Admin') }}</small>
                            <span class="status-indicator online"></span>
                        </div>
                    </div>
                </div>
            </div>
        </li>
        <li class="{{ (strpos(Route::current()->uri(),'dashboard') !== false) ? 'active nav-item' : 'nav-item' }}">
            <a class="nav-link" href="{{ route('dashboard') }}">
                <i class="menu-icon icon-screen-desktop"></i>
                <span class="menu-title">{{trans('admin-labels.DASHBOARD_MENU_LABLE_NAME')}}</span>
            </a>
        </li>
        <li class="{{ (strpos(Route::current()->uri(),'users') !== false) ? 'active nav-item' : 'nav-item' }}">
            <a class="nav-link" href="{{ url('admin/users') }}">
                <i class="menu-icon icon-user"></i>
                <span class="menu-title">{{trans('admin-labels.USER_MANAGEMENT_MENU_LABLE_NAME')}}</span>
            </a>
        </li>
        <li class="{{ (strpos(Route::current()->uri(),'user-level') !== false) ? 'active nav-item' : 'nav-item' }}">
            <a class="nav-link" href="{{ url('admin/user-levels') }}">
                <i class="menu-icon icon-badge"></i>
                <span class="menu-title">{{trans('admin-labels.USER_LEVEL_MANAGEMENT_MENU_LABLE_NAME')}}</span>
            </a>
        </li>
        <li class="{{ (strpos(Route::current()->uri(),'video-category') !== false) ? 'active nav-item' : 'nav-item' }}">
                <a class="nav-link" href="{{ url('admin/video-category') }}">
                    <i class="menu-icon icon-list"></i>
                    <span class="menu-title">{{trans('admin-labels.VIDEO_CATEGORY_MANAGEMENT_MENU_LABLE_NAME')}}</span>
                </a>
        </li>
        <li class="{{ (strpos(Route::current()->uri(),'video-books') !== false) ? 'active nav-item' : 'nav-item' }}">
            <a class="nav-link" href="{{ url('admin/video-books') }}">
                <i class="menu-icon icon-notebook"></i>
                <span class="menu-title">{{trans('admin-labels.VIDEO_BOOK_MANAGEMENT_MENU_LABLE_NAME')}}</span>
            </a>
        </li>
        <li class="{{ (strpos(Route::current()->uri(),'pending-video') !== false) ? 'active nav-item' : 'nav-item' }}">
            <a class="nav-link" href="{{ url('admin/pending-video') }}">
                <i class="menu-icon icon-loop"></i>
                <span class="menu-title">{{trans('admin-labels.PENDING_VIDEOS')}}</span>
            </a>
        </li>

        <li class="{{ (strpos(Route::current()->uri(),'purchase-history') !== false) ? 'active nav-item' : 'nav-item' }}">
            <a class="nav-link" href="{{ url('admin/purchase-history') }}">
                <i class="menu-icon icon-clock"></i>
                <span class="menu-title">{{trans('admin-labels.PURCHASE_HISTORY')}}</span>
            </a>
        </li>

        <li class="{{ (strpos(Route::current()->uri(),'video-advertisement') !== false) ? 'active nav-item' : 'nav-item' }}">
            <a class="nav-link" href="{{ url('admin/video-advertisement') }}">
                <i class="menu-icon fa fa-file-video-o"></i>
                <span class="menu-title">{{trans('admin-labels.VIDEO_ADVERTISEMENT_MANAGEMENT_MENU_LABLE_NAME')}}</span>
            </a>
        </li>

        <li class="{{ (strpos(Route::current()->uri(),'img-advertisement') !== false) ? 'active nav-item' : 'nav-item' }}">
            <a class="nav-link" href="{{ url('admin/img-advertisement') }}">
                <i class="menu-icon fa fa-file-image-o"></i>
                <span class="menu-title">{{trans('admin-labels.IMAGE_ADVERTISEMENT_MANAGEMENT_MENU_LABLE_NAME')}}</span>
            </a>
        </li>
        <li class="{{ (strpos(Route::current()->uri(),'cms') !== false) ? 'active nav-item' : 'nav-item' }}">
            <a class="nav-link" href="{{ url('admin/cms') }}">
                <i class="menu-icon icon-book-open"></i>
                <span class="menu-title">{{trans('admin-labels.CMS_MANAGEMENT')}}</span>
            </a>
        </li>

        <li class="{{ (strpos(Route::current()->uri(),'contact-us') !== false) ? 'active nav-item' : 'nav-item' }}">
            <a class="nav-link" href="{{ url('admin/contact-us') }}">
                <i class="menu-icon icon-phone"></i>
                <span class="menu-title">{{trans('admin-labels.CONTACT_US')}}</span>
            </a>
        </li>

        <li class="{{ (strpos(Route::current()->uri(),'settings') !== false) ? 'active nav-item' : 'nav-item' }}">
            <a class="nav-link" href="{{ url('admin/settings') }}">
                <i class="menu-icon  fa fa-gear"></i>
                <span class="menu-title">{{trans('admin-labels.SETTINGS_MANAGEMENT')}}</span>
            </a>
        </li>
        <li class="{{ (strpos(Route::current()->uri(),'appversion') !== false) ? 'active nav-item' : 'nav-item' }}">
            <a class="nav-link" href="{{ url('admin/appversion') }}">
                <i class="menu-icon fa fa-mobile-phone"></i>
                <span class="menu-title">{{trans('admin-labels.APP_VERSION_MANAGEMENT')}}</span>
            </a>
        </li>
    </ul>
</nav>
