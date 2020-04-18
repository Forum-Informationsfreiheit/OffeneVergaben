<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('admin::dashboard') }}">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-laugh-wink"></i>
        </div>
        <div class="sidebar-brand-text mx-3">{{ env('APP_NAME','Administration') }}</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item">
        <a class="nav-link" href="{{ route('admin::dashboard') }}">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('admin::datasets') }}">
            <i class="fas fa-fw fa-book"></i>
            <span>Kerndaten</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Interface
    </div>

    <!-- Nav Item - Pages Collapse Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
            <i class="fas fa-fw fa-cog"></i>
            <span>Diverses</span>
        </a>
        <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Custom Components:</h6>
                <a class="collapse-item" href="{{ route('admin::users') }}">Benutzer</a>
                <a class="collapse-item" href="{{ route('admin::pages') }}">Pages</a>
                <a class="collapse-item" href="{{ route('admin::posts') }}">Posts</a>
                <a class="collapse-item" href="{{ route('admin::tags') }}">Tags</a>
            </div>
        </div>
    </li>

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
<!-- End of Sidebar -->
<script>
    var util = __ives.util;

    // before initialization of the map check the local storage for custom zoom/coordinates
    if (util.isLocalStorageAvailable() && util.getStorage('ives_lara_admin_sidebar')) {
        var sidebarSettings = JSON.parse(util.getStorage('ives_lara_admin_sidebar'));

        // sidebar interface is initially loaded as 'open' or 'uncollapsed'
        // check the local storage settings if the user had it actually collapsed (on another page)
        // then we need to restore it here
        if (sidebarSettings.collapsed) {
            // put css class on sidebar itself
            $('#accordionSidebar').addClass('toggled');

            // notify the body
            $("body").toggleClass("sidebar-toggled");

            // collapse all the inner lists of the sidebar
            $('.sidebar .collapse').collapse('hide');
        }
    }
</script>