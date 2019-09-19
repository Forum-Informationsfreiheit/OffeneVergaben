<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('page:title')| Ives Lara Admin</title>

    <!-- Custom fonts for this template-->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <!--
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    -->
    <link rel="stylesheet" href="{{ link_to_stylesheet('app_admin',true) }}">
    <link rel="stylesheet" href="{{ link_to_stylesheet('vendor/fontawesome/all.min') }}">

    <script src="{{ link_to_script('app_admin',true) }}"></script>
    <script src="{{ link_to_script('jquery.easing.min') }}"></script>
</head>
<body class="admin @yield('body:class')">
    <div id="wrapper">

        @include('admin.partials.sidebar')

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                @include('admin.partials.topbar')

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">@yield('page:heading')</h1>

                    @include('admin.partials.errors')

                    @include('flash::message')

                    @yield('page:content')
                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            @include('admin.partials.footer')
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    @include('admin.modals.logout')
    @include('admin.modals.publish-page')
    @include('admin.modals.publish-post')
    @include('admin.modals.delete.tag')
    @include('admin.modals.delete.user')
    @include('admin.modals.delete.page')
    @include('admin.modals.delete.post')

    <script src="{{ link_to_script('sb-admin-2') }}"></script>

</body>