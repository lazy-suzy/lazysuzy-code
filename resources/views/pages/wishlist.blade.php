@extends('layouts.layout', ['body_class' => 'listing-main-div'])
@section('title','LazySuzy | Search & Discover Furniture for your Home')
@section('middle_content')

    <div class="wishlist listing">
        @include('./partials/subnav')
        <div class="listing-container main-container container">
            <div class="d-none d-md-block">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">My saved items</li>
                </ol>
            </div>
            <div class="row">
                <div class="listing-top-controls d-block d-md-none">
                    <div class="dropdown show float-left">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMobileListing" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-angle-down"></i>
                        </a>
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
                    {{ Breadcrumbs::render('products') }}
                    <span class="view-items-toggle float-right" id="viewItemsBtn">
                        <i class="fab fa-buromobelexperte"></i>
                    </span>
                </div>
                <div class="products-container col-md-12">
                    <div class="float-right d-none">
                        <div class="sortby">
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
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
@push('pageSpecificScripts')
    <script src="{{ mix('js/wishlist.js')}}"></script>
    <script id="listing-template" type="text/x-handlebars-template">
        @{{#with this}}
            <div id="@{{id}}" sku="@{{sku}}" site="@{{site}}" class="ls-product-div col-md-3 item-2">
                <a href="https://lazysuzy.com/product/@{{sku}}" class="product-detail-modal js-detail-modal" data-href="/product/@{{sku}}">
                    <div class="ls-product"><img class="prod-img img-fluid" src="@{{main_image}}" alt="@{{name}}">

                        @{{#if wishlisted}}
                            <div class="wishlist-icon marked" sku="@{{sku}}"><i class="far fa-heart -icon"></i></div><img class="variation-img img-fluid" src="@{{main_image}}" alt="variation-img"></div>
                        @{{else}}
                        <div class="wishlist-icon " sku="@{{sku}}"><i class="far fa-heart -icon"></i></div><img class="variation-img img-fluid" src="@{{main_image}}" alt="variation-img"></div>
                        @{{/if}}

                </a>
                <div class="prod-info">
                    <span class="-site">
                        @{{#if is_new}}
                            <span class="new-tag"><strong>NEW</strong></span>
                        @{{/if}}

                        @{{site}}
                    </span>
                    @{{#if reviewExist}}
                    <div class="rating-container float-right">
                        <span class="total-ratings">@{{reviews}}</span><div class="rating  @{{ratingClass}}"></div>
                    </div>
                    @{{/if}}
                </div>
                <div class="-name">@{{name}}</div>
                <div class="-prices">
                    <span class="-cprice">@{{formatPrice is_price}}</span>
                    @{{#ifNeq is_price was_price}}
                    <span class="-oldprice">@{{formatPrice was_price}}</span>
                    @{{/ifNeq}}
                </div>

                <div class="responsive slick-slider" style="">
                    @{{#each_upto variations 6}}
                    @{{#with this}}
                    @{{#ifNeq swatch_image ''}}
                        <div class="mini-carousel-item" style="width: 35px; display: inline-block;">
                            <a class="responsive-img-a js-detail-modal" href="https://lazysuzy.com/@{{link}}" data-href="@{{link}}" tabindex="0">
                                <img class="carousel-img img-fluid" src="@{{swatch_image}}" data-prodImg="@{{image}}" />
                            </a>
                        </div>
                    @{{/ifNeq}}

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
@endpush
