@extends('layouts.layout', ['body_class' => 'detailspage-main-div'])

@section('middle_content')
<div class="detailspage" id="detailPage">
    @include('./partials/subnav')
    <div id="detailspageNavigator"></div>
    <div class="-images-container" id="scroll-bar-img">
        <div class="-images"></div>
    </div>

    <div class="row container box">
        <div class="prod-desc">
            <div class="-variations-carousel col-12"></div>
            <div class="prod-main-img col-12 col-md-5 order-md-last">
            </div>
            <div class="col-12 col-md-7">
                <div class="wishlist-icon float-right" id="wishlistBtnDesktop">
                    <i class="far fa-heart -icon"></i>
                </div>
                <h2 class="-name"></h2>
                <hr class="modal-hr"/>
                <!-- <a target="_blank" class="rating-container"><div class="rating "></div><span class="total-ratings"></span></a> -->
                <div class="variation-container " >
                    <div class="variation-options horizontal-scroll" id="container">
                        <div class="swatch-images horizontal-scroll-contents"></div>
                    </div>
                    <!-- <button
                        id="left-slide"
                        class="canvas-scroll js-left-scroll canvas-scroll-left"
                        type="button"
                        >
                        <i class="fas fa-arrow-left"></i>
                        </button>
                        <button
                        id="right-slide"
                        class="canvas-scroll selected-right-scroll canvas-scroll-right"
                        type="button"
                        >
                        <i class="fas fa-arrow-right"></i>
                    </button>    -->
                </div>
                <p class="-desc d-none d-md-block"></p>
                <div class="d-none d-md-block">
                    <div class="accordion" id="accordionExample">
                        <div class="card d-none">
                            <!-- <div class="card-header" id="headingOne"> -->
                            <div id="headingOne">
                                <h2 class="mb-0">
                                    <button class="btn btn-link" type="button" data-toggle="collapse"
                                        data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                        <span class="-text">Description</span>
                                    </button>
                                </h2>
                            </div>

                            <div id="collapseOne" class="collapse show" aria-labelledby="headingOne"
                                data-parent="#accordionExample">
                                <div class="card-body">
                                    <p class="-desc"></p>
                                </div>
                            </div>
                        </div>
                        <!-- <div class="card"> -->
                        <div>
                            <!-- <div class="card-header" id="headingTwo"> -->
                            <div id='headingTwo'>
                                <h2 class="mb-0">
                                    <button class="btn btn-link  collapsed" type="button" data-toggle="collapse"
                                        data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        <span class="-text">▼ Features </span>
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo"
                                data-parent="#accordionExample">
                                <div class="card-body">
                                    <div class="-features"></div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <!-- <div class="card"> -->
                            <!-- <div class="card-header" id="headingThree"> -->
                            <div id="headingThree">
                                <h2 class="mb-0">
                                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse"
                                        data-target="#collapseThree" aria-expanded="false"
                                        aria-controls="collapseThree">
                                        <span class="-text">▼ Dimensions</span>
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseThree" class="collapse" aria-labelledby="headingThree"
                                data-parent="#accordionExample">
                                <div class="card-body">
                                    <div class="-dimension row"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-sm-block d-md-none">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link detail-nav-link active" data-toggle="tab" href="#descp">Description</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link detail-nav-link" data-toggle="tab" href="#feat">Features</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link detail-nav-link" data-toggle="tab" href="#dimen">Dimensions</a>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content">
                        <div class="tab-pane container active" id="descp"></div>
                        <div class="tab-pane container fade" id="feat"></div>
                        <div class="tab-pane container fade" id="dimen"></div>
                    </div>
                </div>
            </div>

        </div>

        <div class="prod-price-card col-3 d-none">
        </div>
    </div>
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/slick-lightbox/0.2.12/slick-lightbox.css">
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/slick-lightbox/0.2.12/slick-lightbox.js">
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/slick-lightbox/0.2.12/slick-lightbox.min.js">
    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/slick-lightbox/0.2.12/slick-lightbox.min.js.map">

    @include('./partials/brandassociation')
</div>
@endsection

@push('pageSpecificScripts')
<script src="{{ mix('js/detailspage.js')}}"></script>
@endpush
