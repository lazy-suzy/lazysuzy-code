@extends('layouts.layout', ['body_class' => 'listing-main-div'])

@section('middle_content')
@include('../partials/subnav')
<div class="listing">
    <div class="listing-container main-container container ">
        <div class="d-none d-md-block">
        </div>
        <div class="row">

            <div class="filters d-md-block filter-close-btn" id="filters">

                <a class="btn clearall-filter-btn" href="/filter/clear_filter/all">Clear All</a>
            </div>
            <div class="filters sort-container  d-md-none" id="sort-mobile">
                <div class="mobile-sort-header">
                    <div class="filters-close-btn d-md-none float-left"><i class="fa fa-arrow-left" aria-hidden="true"></i>
                    </div>
                    <span class="filter-title">Sort</span>
                </div>
                <div class="sort-filter-input">
                    <label class="sort-label">Recommended
                        <span class="float-right">
                            <input type="radio" checked name="sort-price-filter" value="recommended">
                        </span>
                    </label>
                </div>
                <div class="sort-filter-input">
                    <label class="sort-label">Price : Low to High
                        <span class="float-right">
                            <input type="radio" name="sort-price-filter" value="price_low_to_high">
                        </span>
                    </label>
                </div>
                <div class="sort-filter-input">
                    <label class="sort-label">Price : High to Low
                        <span class="float-right">
                            <input type="radio" name="sort-price-filter" value="price_high_to_low">
                        </span>
                    </label>
                </div>
                <div class="sort-filter-input">
                    <label class="sort-label">Popularity
                        <span class="float-right">
                            <input type="radio" name="sort-price-filter" value="popularity">
                        </span>
                    </label>
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

            <div class="products-container col-md-10">
                <div class="ls-prod-container container-fluid text-center">
                    <div class="top-button d-md-none"><i class="fa fa-arrow-up" aria-hidden="true"></i></div>
                    <div class="row" id="productsContainerDiv">

                    </div>
                    <div class="mx-auto" id="loaderImg">
                        <img src="{{ asset('/images/Spinner-1s-100px.gif') }}" alt="Spinner">
                    </div>
                </div>
            </div>
        </div>
    </div>
    @component('components.subscriber')
    @endcomponent
</div>
@endsection
@push('pageSpecificScripts')
<script src="{{ mix('js/listing.mobile.js')}}"></script>
<script id="listing-template" type="text/x-handlebars-template">
    @{{#with this}}
            <div id="@{{id}}" sku="@{{sku}}" site="@{{site}}" class="ls-product-div col-md-3 item-2">
                <a href="/product/@{{sku}}" class="product-detail-modal">
                    <div class="ls-product"><img class="prod-img img-fluid" src="@{{main_image}}" alt="@{{name}}">
                        <div class="prod-info d-none d-md-block">
                            <span class="-cat-name">@{{site}}</span>
                            <span class="-prices float-right">
                                <span class="-cprice">@{{formatPrice is_price}}</span>
                                @{{#ifNeq is_price was_price}}
                                <span class="-oldprice">@{{formatPrice was_price}}</span>
                                @{{/ifNeq}}
                            </span>
                        </div>
                        @{{#if is_new}}
                            <span class="new-tag"><strong>NEW</strong></span>
                        @{{/if}}
                        @{{#if wishlisted}}
                            <div class="tile-icon wishlist-icon marked" sku="@{{sku}}"><i class="far fa-heart -icon"></i></div>
                        @{{else}}
                            <div class="tile-icon wishlist-icon " sku="@{{sku}}"><i class="far fa-heart -icon"></i></div>
                        @{{/if}}
                        @{{#if variations}}
                            <div class="tile-icon multiple-variants-icon" sku="@{{sku}}"><i class="far fa-clone -icon"></i></div>
                        @{{/if}}
                        <div><img class="variation-img img-fluid" src="@{{main_image}}" alt="variation-img"></div>

                </a><span class="prod-sale-price d-md-none @{{#ifNeq is_price was_price}}sale@{{/ifNeq}}">@{{formatPrice is_price}}</span>
                @{{printDiscount percent_discount}}
                <div class="d-none d-md-block">
                    <h5 class="-name">@{{name}}</h5>
                    <div class="responsive slick-initialized slick-slider" style="">
                        <div class="slick-list draggable">
                            <div class="slick-track" style="opacity: 1; width: 100px; transform: translate3d(0px, 0px, 0px);">
                            @{{#each variations}}
                            @{{#with this}}
                                <div class="mini-carousel-item" style="width: 30px; display: inline-block;"><a class="responsive-img-a" href="/product/@{{variation_sku}}" tabindex="0"><img class="carousel-img img-fluid" src="@{{image}}" data-prodImg="@{{image}}" /></a></div>
                            @{{/with}}
                            @{{/each}}
                        </div>
                        </div>
                    </div>
                    @{{#if reviewExist}}
                    <div class="rating-container">
                        <div class="rating  @{{ratingClass}}"></div><span class="total-ratings">@{{reviews}}</span></div>
                </div>
                @{{/if}}
            </div>
        @{{/with}}
    </script>
@endpush
@push('pageSpecificScripts')

