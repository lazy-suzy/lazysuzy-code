<!-- <a class="navbar-brand mx-auto" href="#"><img id="logo-navbar-middle" src="{{ asset('/images/dark_logo_transparent.png') }}" alt="LazySuzy" class="logo"></a> -->

<nav class="navbar navbar-expand-lg navbar-light bg-light px-md-75 py-10">

    <button class="navbar-toggler " type="button" onclick="openNav()">
        <i class="fas fa-bars -bars"></i>
    </button>
    <div id="Sidenavbar" class="sidenav">
        <a href="javascript:void(0)" id="Sidenavbarclose" class="closebtn" onclick="closeNav()">&times;</a>
        <ul id="collapsible-dept" class="sidenav-items">


        </ul>

    </div>

    <a class="navbar-brand" href="/">
        <img id="logo-navbar-middle" src="{{ asset('/images/dark_logo_transparent.png') }}" alt="asd"
            class="logo d-none d-md-block">
        <img id="logo-navbar-middle" src="{{ asset('/images/color_logo_transparent.png') }}" alt="asd"
            class="logo d-block d-md-none">

    </a>

    <div class="d-none d-md-block">
        @include('./partials/sbbody')
    </div>

    <form id="searchbarHeader" role="form" class="searchbar card card-sm">
        <div class="card-body row no-gutters align-items-center">
            <div class="col-auto d-none d-md-block">
                <i class="fas fa-search search-icon"></i>
            </div>
            <!--end of col-->
            <div class="col">
                <input class="form-control form-control-lg form-control-borderless" type="search"
                    placeholder="Find your accent">
            </div>
            <!--end of col-->
            <div class="col-auto d-none d-md-block">
                <button class="btn btn-lg btn-success" type="submit">Search</button>
            </div>
            <div class="col-auto d-md-none">
                <button class="btn btn-lg btn-success" type="submit"><i class="fas fa-search search-icon"></i></button>
            </div>
            <!--end of col-->
        </div>
    </form>


    <div class="-right d-none d-md-flex">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="/aboutus">The Lazy Story</a>
            </li>
            <!-- Authentication Links -->
            @if (Auth::guest())
            <li class="nav-item">
                <a class="nav-link wishlist-login-modal" href="#"><i class="far fa-heart -icon"></i> Saved Items</a>
            </li>
            <li class="nav-item">
                <a class="nav-link dropdown-toggle user-icon user-login-modal" href="#" role="button"
                    aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-user-circle -icon"></i> Sign up
                </a>
            </li>

            @else
            <li class="nav-item">
                <a class="nav-link" href="{{ url('/wishlist') }}"><i class="far fa-heart -icon"></i> Saved Items</a>
            </li>
            <li class="dropdown nav-item -username">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                    {{ Auth::user()->name }} <span class="caret"></span>
                </a>

                <ul class="dropdown-menu" role="menu">
                    <li><a href="{{ url('/logout') }}"><i class="fa fa-btn fa-sign-out"></i>Logout</a></li>
                </ul>
            </li>
            @endif
        </ul>
    </div>

    <div class="nav-item mobile-some-icons d-flex d-md-none">
        <a class="nav-link search-icon-mobile" href="#" id="searchIconMobile">
            <i class="fas fa-search"></i>
        </a>
        @if (Auth::guest())
        <a class="nav-link dropdown-toggle user-icon user-login-modal" href="#" role="button" aria-haspopup="true"
            aria-expanded="false">
            <i class="fas fa-user-circle -icon"></i>
        </a>
        <a class="nav-link wishlist-login-modal wishlist-icon" href="#"><i class="far fa-heart -icon"></i></a>
        @else
        <a class="nav-link wishlist-icon" href="{{ url('/wishlist') }}"><i class="far fa-heart -icon"></i></a>
        <a class="nav-link dropdown-toggle user-icon" href="#" id="userDropdown" role="button" data-toggle="dropdown"
            aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-user-circle -icon"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
            <a class="dropdown-item" href="#">{{ Auth::user()->name }}</a>
            <div class="dropdown-divider"></div>
            <a href="{{ url('/logout') }}" class="dropdown-item"><i class="fa fa-btn fa-sign-out"></i>Logout</a></li>
        </div>
        @endif
    </div>
</nav>
