@extends('layouts.layout', ['body_class' => 'listing-main-div'])
@section('title','Search Top Home Brands | LazySuzy')
@section('middle_content')

    <div class="listing">
        @include('./partials/subnav')
        <div class="listing-container main-container container">
            <div class="d-none d-md-block">
                
            </div>
            <div class="row">
                <!-- <div class="filters col-md-2 d-md-block" id="filters">
                    <div class="filter">
                        <hr>
                        <span class="filter-header">Brands</span>
                        <label for="" class="clear-filter float-right">Clear</label>
                        <ul>
                            <li>
                                <label class="container">CB2
                                    <input type="checkbox" checked="checked" value="cb2">
                                    <span class="checkmark"></span>
                                </label>
                            </li>
                            <li>
                                <label class="container">Pier
                                    <input type="checkbox" value="pier1">
                                    <span class="checkmark"></span>
                                </label>
                            </li>
                            <li>
                                <label class="container">Pottery Barn
                                    <input type="checkbox">
                                    <span class="checkmark"></span>
                                </label>
                            </li>
                        </ul>
                        <hr>
                    </div>
                    <div class="filter">
                        <span class="filter-header">Price</span>
                        <label for="" class="clear-filter float-right">Clear</label>
                        <input type="text" class="price-range-slider" id="priceRangeSlider" name="price_range" value="" />
                        <hr>
                    </div>

                    <div class="filter">
                        <span class="filter-header">Type</span>
                        <label for="" class="clear-filter float-right">Clear</label>
                        <ul>
                            <li>
                                <label class="container">Armchair
                                    <input type="checkbox" checked="checked">
                                    <span class="checkmark"></span>
                                </label>
                            </li>
                            <li>
                                <label class="container">Armless
                                    <input type="checkbox">
                                    <span class="checkmark"></span>
                                </label>
                            </li>
                            <li>
                                <label class="container">Recliner
                                    <input type="checkbox">
                                    <span class="checkmark"></span>
                                </label>
                            </li>
                            <li>
                                <label class="container">Ottoman
                                    <input type="checkbox">
                                    <span class="checkmark"></span>
                                </label>
                            </li>
                        </ul>
                        <hr>
                    </div>
                    <a class="btn clearall-filter-btn" href="https://lazysuzy.com/filter/clear_filter/all">Clear All</a>
                </div> -->
                <div class="listing-top-controls d-block d-md-none">
                    <div class="dropdown show float-left">
                        <div id="search-navigator"></div>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMobileListing" rel="dropdownMobileListing">
                            <li><a class="dropdown-item" href="#">Accent</a></li>
                            <li class="dropdown-submenu"><a class="dropdown-item dropdown-toggle" href="#">Living<i class="fas fa-angle-right float-right"></i></a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#">All</a></li>
                                    <li><a class="dropdown-item" href="#">Sofa</a></li>
                                    <li><a class="dropdown-item" href="#">Seating</a></li>
                                    <li><a class="dropdown-item" href="#">Table</a></li>
                                    <li><a class="dropdown-item" href="#">Remaining</a></li>
                                </ul>
                            </li>
                            <li><a class="dropdown-item" href="#">Bed</a></li>
                            <li><a class="dropdown-item" href="#">Kitchen</a></li>
                            <li><a class="dropdown-item" href="#">Remaining</a></li>
                        </ul>
                    </div>
                    
                    <!-- <span class="filter-toggle float-right" id="filterToggleBtn">
                        <i class="fas fa-filter"></i>
                    </span> -->
                    <span class="view-items-toggle float-right" id="viewItemsBtn">
                        <i class="fab fa-buromobelexperte"></i>
                        Toggle view
                    </span>
                </div>
                <div class="products-container col-md-12">
                    <div class="float-right d-none d-md-block">
                        <div class="sortby d-none">
                            <label for="sort">Sort By</label>
                            <select class="form-control" id="sort">
                                <option>Price : Low to High</option>
                                <option>Price : High to Low</option>
                                <option>Popularity</option>
                                <option selected>Recommended</option>
                            </select>
                        </div>
                        <div class="total-items"><span id="totalResults">0</span> Results</div>
                    </div>
                    <div class="ls-prod-container container-fluid text-center">
                        <div class="row" id="productsContainerDiv">
                        </div>
                        <div class="text-center" id="noProductsText">Sorry, no more products to show.</div>
                        <div class="mx-auto" id="loaderImg">
                            <img src="{{ asset('/images/Spinner-1s-100px.gif') }}" alt="Spinner">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    @component('components.detailOverview')
        @endcomponent
@endsection
@push('pageSpecificScripts')
    <script src="{{ mix('js/search.js')}}"></script>
    <script src="{{ mix('js/detailOverview.js')}}"></script>
@endpush