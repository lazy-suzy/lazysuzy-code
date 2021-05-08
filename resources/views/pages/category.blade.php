@extends('layouts.layout', ['body_class' => 'categorypage-main-div'])
@section('middle_content')
@section('title', $departmentName.' | LazySuzy')
@include('./partials/subnav')

<div class="category-page" id="categorypage">
    <div class="listing-container category-img-container">
        <div class="listing-top-controls d-block">

            <!-- <span class="filter-toggle float-right" id="filterToggleBtn">
                        <i class="fas fa-filter"></i>
                    </span>
                    <span class="view-items-toggle float-right" id="viewItemsBtn">
                        <i class="fab fa-buromobelexperte"></i>
                    </span> -->
            <!-- <div class="wishlist-icon float-right m-10" id="wishlistBtn">
                <i class="far fa-heart -icon"></i>
            </div>
            <div class="filter-toggle float-right m-10" id="filterToggleBtn">
                <i class="fas fa-filter -icon"></i>
            </div> -->
        </div>
        <div class="category-listing">
            <div class="row">
                @foreach ($listedCategories as $category)
                <div class="col-sm-6 col-4 category-text">
                    <a class="category-text" href="{{$category['link']}}">
                        <img data-LSID="{{$category['LS_ID']}}" class="category-img" src="{{$category['image']}}?v=1.0.0" alt="{{$category['category']}}">
                        <div><span>{{$category['category']}}</span></div>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @include('./partials/brandassociation')
</div>
@endsection