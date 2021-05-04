@extends('layouts.layout', ['body_class' => 'brand-main-div'])

@section('middle_content')
@include('./partials/subnav')
<div class="brands">
    <div class="brands-main-heading">
        <div class="container">
            <div class="mx-auto text-center loaderImg">
                <img src="{{ asset('/images/Spinner-1s-100px.gif') }}" alt="Spinner">
            </div>
            <div class="js-brand-header">

            </div>
        </div>
    </div>
    <div class="listing-top-controls d-block d-md-none">
        <!-- <div id="page-navigator" class="page-navigator-mobile"></div> -->
        <div class="filter-toggle-mobile">
            <span class="filter-toggle" id="filterToggleBtn">
                <i class="fas fa-filter"></i>
                Filter
            </span>
            <span class="filter-toggle" id="viewItemsBtn">
                <i class="fab fa-buromobelexperte"></i>
                Toggle View
            </span>
            <span class="filter-toggle" id="selectbox-sortmobile">
                <i class="fas fa-sort"></i>
                Sort
            </span>
        </div>


    </div>
    <div class="js-filters filters d-md-block filter-close-btn">
        <div class="d-none filter" data-filter="brand" id="brandFilter">
            <label class="filter-label"><input data-type="hidden" type="checkbox" checked value="{{ Request::route('brand_name') }}" ></label>
        </div>
        <div class="mobile-filter-header d-md-none"><span class="float-left filters-close-btn"><i class="fa fa-times" aria-hidden="true"></i></span><span class="filter-title">Filters</span><span class="float-right"><a class="btn clearall-filter-btn" href="#" id="clearAllFiltersBtn">Clear All</a></span></div>
        <div id="mobile-filters">
        </div>
        <div class="row">
            <ul class="filter-tabs col-sm-9 d-none d-md-block" id="desktop-filters">
                <li class="nav-item">
                    <a class="nav-link active" href="#">Brand</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#">Price</a>
                </li>

                <li>
                    <a class="clearall-filter-btn" href="/filter/clear_filter/all">Clear All</a>
                </li>

            </ul>
            <div class="col-sm-3 d-none d-md-block">
                <div class="total-items float-left"><span id="totalResults">0</span> Results</div>
                <div class="sortby float-right">
                    <select class="form-control" id="sort">
                        <option>Price : Low to High</option>
                        <option>Price : High to Low</option>
                        <option>Popularity</option>
                        <option selected>Recommended</option>
                    </select>
                </div>

            </div>
        </div>
    </div>
    <div class="filters  d-md-none" id="sort-mobile">
        <div class="mobile-filter-header">
            <div class="filters-close-btn d-md-none float-left"><i class="fa fa-times" aria-hidden="true"></i>
            </div>
            <span class="filter-title">Sort</span>
        </div>
        <hr>
        <div class="sort-filter-input"><input type="radio" name="sort-price-filter" value="price_low_to_high">
            Price : Low to High</div>
        <div class="sort-filter-input"><input type="radio" name="sort-price-filter" value="price_high_to_low">
            Price : High to Low</div>
        <div class="sort-filter-input"><input type="radio" name="sort-price-filter" value="popularity">
            Popularity </div>
    </div>
    <div class="brands-prod-container products-container">
        <div class="container ls-prod-container">
            <div id="productsContainerDiv" class="row"></div>
        </div>
        <div class="text-center" style="display:none;" id="noProductsText">Sorry, no more products to show.
        </div>
        <div class="mx-auto text-center"  id="loaderImg">
            <img src="{{ asset('/images/Spinner-1s-100px.gif') }}" alt="Spinner">
        </div>
    </div>
    @component('components.detailOverview')
    @endcomponent
</div>
@endsection
@push('pageSpecificScripts')
    <script type = "text/template" id="brandHeader">
    <div class="row">
        <div class="main-img">
            <img class="img-fluid" src="@{{cover_image}}" alt="">
        </div>
        <div class="col-12 text-center">
            <h1>@{{name}}</h1>
        </div>
        <div class="col-8 col-sm-12">
            <div class="brands-details">
            <p>@{{description}} </p>
            </div>
        </div>
        <div class="col-4 col-sm-12">
            @{{#if isFeaturesVisible}}
            <div class="row">
                <div class="col-3">
                <img class="brands-icon" src="{{ asset('/images/icon/heart-flag-of-united-states-of-america.png') }}" alt="Heart-flag-of-united-states-of-america">
                </div>
                <div class="col-9 brand-icon-text">
                    <h3>Manufactured in USA</h3>
                </div>
                <div class="col-3">
                <img class="brands-icon" src="{{ asset('/images/icon/shipping.png') }}" alt="Free Shipping">
                </div>
                <div class="col-9 brand-icon-text">
                    <h3>Free Shipping</h3>
                </div>

                <div class="col-3">
                <img class="brands-icon" src="{{ asset('/images/icon/exchange.png') }}" alt="Exchange">
                </div>
                <div class="col-9 brand-icon-text">
                    <h3>Free 30 Days Returns</h3>
                </div>

                <div class="col-3">
                <img class="brands-icon" src="{{ asset('/images/icon/guarantee.png') }}" alt="Guarantee">
                </div>
                <div class="col-9 brand-icon-text">
                    <h3>10 years Warranty</h3>
                </div>
            </div>
            @{{/if}}
        </div>
    </div>
    </script>
    <script id="listing-template-mobile" type="text/x-handlebars-template">
        @{{#with this}}
        <div id="@{{id}}" sku="@{{sku}}" site="@{{site}}" class="ls-product-div col-md-3 item-2">
            <a href="/product/@{{sku}}" class="product-detail-modal">
                <div class="ls-product"><img class="prod-img img-fluid" src="@{{main_image}}" alt="@{{name}}">
                    @{{#if wishlisted}}
                    <div class="wishlist-icon marked" sku="@{{sku}}"><i class="far fa-heart -icon"></i></div><img class="variation-img img-fluid" src="@{{main_image}}" alt="variation-img"></div>
                    @{{else}}
                    <div class="wishlist-icon " sku="@{{sku}}"><i class="far fa-heart -icon"></i></div><img class="variation-img img-fluid" src="@{{main_image}}" alt="variation-img"></div>
                    @{{/if}}

            </a>
            <span class="prod-sale-price">
                @{{#if is_new}}
                    <span class="new-tag"><strong>NEW</strong></span>
                @{{/if}}
                @{{formatPrice is_price}}
            </span>
            <span class="prod-discount-tag @{{discountClass}}">@{{percent_discount}}%</span>
        </div>
        @{{/with}}
    </script>
    <script id="listing-template" type="text/x-handlebars-template">
        @{{#with this}}
            <div id="@{{id}}" sku="@{{sku}}" site="@{{site}}" class="ls-product-div col-md-3 item-2">
                <a href="/product/@{{sku}}" class="product-detail-modal js-detail-modal">
                    <div class="ls-product"><img class="prod-img img-fluid" src="@{{main_image}}" alt="@{{name}}">

                        @{{#if wishlisted}}
                            <div class="wishlist-icon marked" sku="@{{sku}}"><i class="far fa-heart -icon"></i></div><img class="variation-img img-fluid" src="@{{main_image}}" alt="variation-img"></div>
                        @{{else}}
                        <div class="wishlist-icon " sku="@{{sku}}"><i class="far fa-heart -icon"></i></div><img class="variation-img img-fluid" src="@{{main_image}}" alt="variation-img"></div>
                        @{{/if}}

                </a>
                <div class="prod-info">
                    <span class="-site">@{{site}}</span>
                    @{{#if reviewExist}}
                    <div class="rating-container float-right">
                        <span class="total-ratings">@{{reviews}}</span><div class="rating  @{{ratingClass}}"></div>
                    </div>
                    @{{/if}}
                </div>
                <div class="-name">
                    @{{#if is_new}}
                        <span class="new-tag"><strong>NEW</strong></span>
                    @{{/if}}
                    @{{name}}
                </div>
                <div class="-prices">
                    <span class="-cprice">@{{formatPrice is_price}}</span>
                    @{{#ifNeq is_price was_price}}
                    <span class="-oldprice">@{{formatPrice was_price}}</span>
                    @{{/ifNeq}}
                </div>

                <div class="responsive slick-slider" style="">
                    @{{#each_upto variations 6}}
                    @{{#with this}}
                        <div class="mini-carousel-item" style="width: 35px; display: inline-block;">
                            @{{#ifNeq swatch_image ''}}
                            <a class="responsive-img-a" href="javaScript:void(0)" tabindex="0">
                                <img class="carousel-img img-fluid" src="@{{swatch_image}}" data-prodImg="@{{image}}" />
                            </a>
                            @{{else}}
                            <a class="responsive-img-a" href="@{{link}}" tabindex="0">
                                <img class="carousel-img img-fluid" src="@{{image}}" data-prodImg="@{{image}}" />
                            </a>
                            @{{/ifNeq}}

                        </div>
                    @{{/with}}
                    @{{/each_upto}}
                    @{{#if showMoreVariations}}
                        <a href="/product/@{{sku}}" class="more-link js-detail-modal">
                            + more
                        </a>
                    @{{/if}}
                </div>
            </div>
        @{{/with}}
    </script>
    <script id="desktop-filter-template" type="text/x-handlebars-template">
        <li class="nav-item dropdown filter" data-filter=@{{name}} id="@{{name}}Filter">
            <a class="nav-link dropdown-toggle @{{#if isApplied}}applied @{{/if}}" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">@{{name}}</a>
            <ul class="dropdown-menu">
                @{{#if isPrice}}
                    <li class="dropdown-item"  href="#"><input class="price-range-slider" id="priceRangeSlider" name="price_range" value="" tabindex="-1" readonly=""></li>
                @{{else}}
                    @{{#each list}}
                    @{{#with this}}
                    @{{#if enabled}}
                        <li class="dropdown-item" href="#"><label class="filter-label"><input type="checkbox" @{{#if checked}}checked@{{/if}} value="@{{value}}" belongsto="@{{name}}"><span class="checkmark"></span><span class="text">@{{name}}</span></label></li>
                    @{{/if}}
                    @{{/with}}
                    @{{/each}}
                @{{/if}}
            </ul>
        </li>
    </script>
    <script id="mobile-filter-template" type="text/x-handlebars-template">
        <div class="filter" data-filter="@{{name}}">
            <hr/>
            <span class="filter-header">@{{name}}</span>
            <label for="@{{name}}" class="clear-filter float-right">Clear</label>
            <ul class="item-list">
                @{{#if isPrice}}
                    <li class="filter-item"  href="#"><input class="price-range-slider" id="priceRangeSlider" name="price_range" value="" tabindex="-1" readonly=""></li>
                @{{else}}
                    @{{#each list}}
                    @{{#with this}}
                    @{{#if enabled}}
                        <li class="filter-item" href="#"><label class="filter-label"><input type="checkbox" @{{#if checked}}checked@{{/if}} value="@{{value}}" belongsto="@{{name}}"><span class="checkmark"></span><span class="text">@{{name}}</span></label></li>
                    @{{/if}}
                    @{{/with}}
                    @{{/each}}
                @{{/if}}
            </ul>
        </li>
    </script>

    <script src="{{ mix('js/brands.js')}}"></script>
    <script src="{{ mix('js/detailOverview.js')}}"></script>


@endpush
