@extends('layouts.layout', ['body_class' => 'homepage-main-div'])
@section('title', 'LazySuzy | Search & Discover Furniture for your Home')
@section('middle_content')
    <div class="homepage">
        <img class="img-fluid d-md-none" src="{{ asset('/images/homepage-sofa-img.jpg') }}" alt="">
        <div class="d-md-none"> @include('./partials/sbbody') </div>
        <div class="d-none d-md-block">@include('./partials/brandassociation') </div>
        @include('./partials/subnav')
        <div class="d-none">{{ Breadcrumbs::render('/') }}</div>
        <div class="main-container">
            <div class="category-links d-sm-none">
                <div class="-heading float-md-right">Browse by Department</div>
                <div class="-depts row" id="mobileDepartments">
                </div>
            </div>
        </div>
        <div class="main-container">
            <div class="category-links d-sm-none">
                <div class="-heading float-md-right">Browse Top Categories</div>
                <div class="-depts" id="topCategories">
                    <!-- <div id="carouselExampleControls" class="carousel slide" data-ride="carousel" > -->
                    <div id="madinah-carousel" class="carousel slide" data-ride="carousel">
                        <ol id="madinahcarouselindicator" class="carousel-indicators">
                        </ol>
                        <div class="carousel-inner" role="listbox" id="carousel-inner">
                        </div>
                        <!-- <a class="carousel-control-prev" href="#madinah-carousel" role="button" data-slide="prev">
                            <span class=" fa fa-angle-left" aria-hidden="true"></span>
                            <span class="sr-only">Previous</span>
                        </a>
                        <a class="carousel-control-next" href="#madinah-carousel" role="button" data-slide="next">
                            <span class="fa fa-angle-right" aria-hidden="true"></span>
                            <span class="sr-only">Next</span>
                        </a> -->
                    </div>
                </div>
            </div>
        </div>
        <div class="main-container">
            <div class="d-sm-none category-links">
                <div class="-heading float-md-right">Top Trending Designs</div>
                    <div class="-trending " >
                        <div id="carouselTrending" class="carousel slide" data-ride="carousel" >
                            <ol id="toptrendingindicator" class="carousel-indicators">
                            </ol>
                            <div class="carousel-inner" id="carousel-inner-trending">
                            </div>
                            <!-- <a class="carousel-control-prev" href="#carouselTrending" role="button" data-slide="prev">
                                <span class="fa fa-angle-left" aria-hidden="true"></span>
                                <span class="sr-only">Previous</span>
                            </a>
                            <a class="carousel-control-next" href="#carouselTrending" role="button" data-slide="next">
                                <span class="fa fa-angle-right" aria-hidden="true"></span>
                                <span class="sr-only">Next</span>
                            </a> -->
                        </div>
                    </div>
                </div>
        </div>

        <div class="d-md-none">
            @include('./partials/brandassociation') </div>
        </div>
        <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-lightbox/0.2.12/slick-lightbox.css">
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-lightbox/0.2.12/slick-lightbox.js">
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-lightbox/0.2.12/slick-lightbox.min.js">
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-lightbox/0.2.12/slick-lightbox.min.js.map">
    <form method="POST" action="{{ route('login') }}" class="d-none">
            @csrf
         <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" required autofocus>
         <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>
         <button type="submit" class="btn btn-primary">
           {{ __('Login') }}
         </button>
    </form>
@endsection
