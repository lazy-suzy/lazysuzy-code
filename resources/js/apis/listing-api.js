import * as multiCarouselFuncs from '../components/multi-carousel';
import makeSelectBox from '../components/custom-selectbox';
// import * as priceSliderContainer from '../pages/listing';

$(document).ready(function () {
    const LISTING_API_PATH = '/api' + location.pathname;
    const LISTING_FILTER_API_PATH = '/api/filter/products';
    var totalResults = 0;
    var UrlSearchParams = new Object();
    var objGlobalFilterData;
    var bFiltersCreated = false;
    var strFilters = '';
    var strSortType = '';
    var iPageNo = 0, iLimit;
    var price_from, price_to;
    var bNoMoreProductsToShow = false;

    $(window).scroll(function () {
        if (!bNoMoreProductsToShow) {
            var position = $(window).scrollTop();
            var bottom = $(document).height() - $(window).height();

            if (position == bottom) {
                fetchProducts(false);
            }
        }
    });

    function fetchProducts(bClearPrevProducts) {
        var strLimit = iLimit === undefined ? '' : '&limit=' + iLimit;
        var listingApiPath = LISTING_API_PATH + '?filters=' + strFilters + '&sort_type=' + strSortType + '&pageno=' + iPageNo + strLimit;
        console.log(listingApiPath);
        $('#loaderImg').show();
        $('#noProductsText').hide();
        iPageNo += 1;
        $.ajax({
            type: "GET",
            url: listingApiPath,
            dataType: "json",
            success: function (data) {
                console.log(data);
                if (bClearPrevProducts) {
                    $('#productsContainerDiv').empty()
                    totalResults = 0;
                };
                $('#loaderImg').hide();
                if (data == null) {
                    return;
                }
                if (data.products != undefined && data.products.length != 0) {
                    bNoMoreProductsToShow = true;

                    totalResults = data.total;
                    $('#totalResults').text(totalResults);

                    for (var i = 0; i < data.products.length; i++) {
                        createProductDiv(data.products[i]);
                    }
                    multiCarouselFuncs.makeMultiCarousel();
                }
                else {
                    // if (!bClearPrevProducts) {
                        bNoMoreProductsToShow = true;
                        iPageNo -= 1;
                        $('#noProductsText').show();
                        return;
                    // }
                }
                if (data.filterData) {
                    objGlobalFilterData = data.filterData;
                    createUpdateFilterData(data.filterData);
                }
                if(data.sortType) {
                    $('#sort').empty();
                    data.sortType.forEach(element => {
                        var sortElm = jQuery('<option />', {
                            value: element.value,
                            selected: element.enabled,
                            text: element.name
                        }).appendTo('#sort');
                        if( element.enabled ){
                            strSortType = element.value;
                        }
                    });
                    makeSelectBox();
                }

            },
            error: function (jqXHR, exception) {
                console.log(jqXHR);
                console.log(exception);
            }
        });
    }

    function createProductDiv(productDetails) {
        //Make product main div
        var mainProductDiv = jQuery('<div/>', {
            id: productDetails.id,
            sku: productDetails.sku,
            site: productDetails.site,
            class: 'ls-product-div col-md-3 item-3'
        }).appendTo('#productsContainerDiv');

        var anchor = $('<a/>', {
            href: '#page' + iPageNo
        }).appendTo(mainProductDiv);

        var productLink = jQuery('<a/>', {
            href: productDetails.product_url
        }).appendTo(mainProductDiv);

        var product = jQuery('<div/>', {
            class: 'ls-product'
        }).appendTo(productLink);

        jQuery('<img />', {
            class: 'img-fluid',
            src: productDetails.main_image,
            alt: productDetails.name
        }).appendTo(product);

        //Product information
        var prodInfo = jQuery('<div/>', {
            class: 'prod-info'
        }).appendTo(product);
        var catDetails = jQuery('<span/>', {
            class: '-cat-name',
        }).appendTo(prodInfo);
        $(catDetails).text(productDetails.site)
        var prices = jQuery('<span/>', {
            class: '-prices float-right',
        }).appendTo(prodInfo);
        var currPrice = jQuery('<span/>', {
            class: '-cprice',
        }).appendTo(prices);
        $(currPrice).text('$' + productDetails.is_price);
        if (productDetails.is_price < productDetails.was_price) {
            var oldPrice = jQuery('<span/>', {
                class: '-oldprice',
            }).appendTo(prices);
            $(oldPrice).text('$' + productDetails.was_price);
        }

        $(product).append('<div class="wishlist-icon"><i class="far fa-heart -icon"></i></div>');

        var productInfoNext = jQuery('<div/>', {
            class: 'd-none d-md-block',
        }).appendTo(mainProductDiv);
        $(productInfoNext).append('<div class="-name">' + productDetails.name + '</div>');

        var carouselMainDiv = jQuery('<div/>', {
            class: 'responsive',
        }).appendTo(productInfoNext);

        var variationImages = productDetails.variations.map( variation => variation.image );

        variationImages.forEach(img => {
            var responsiveImgDiv = jQuery('<div/>', {
                class: 'mini-carousel-item',
            }).appendTo(carouselMainDiv);
            var responsiveImg = jQuery('<img/>', {
                class: 'carousel-img img-fluid',
                src: img
            }).appendTo(responsiveImgDiv);

        });

        if (parseInt(productDetails.reviews) != 0) {

            var reviewValue = parseInt(productDetails.reviews);
            var ratingValue = parseFloat(productDetails.rating).toFixed(1);
            var ratingClass = ratingValue.toString().replace('.', "_");
            $(productInfoNext).append('<div class="rating-container"><div class="rating  rating-' + ratingClass + '"></div><span class="total-ratings">' + reviewValue + '</span></div>');
        }

        scrollToAnchor();
    }

    function createUpdateFilterData(filterData) {
        bNoMoreProductsToShow = false;
        if (!bFiltersCreated) {
            bFiltersCreated = true;
            $('#filters').empty();
            Object.keys(filterData).forEach((key, index) => {
                const data = filterData[key];
                var filterDiv = jQuery('<div/>', {
                    class: 'filter',
                    "data-filter": key
                }).appendTo('#filters');
                $(filterDiv).append('<hr/>');

                $(filterDiv).append('<span class="filter-header">' + key.replace('_', ' ') + '</span>')
                $(filterDiv).append('<label for="' + key + '" class="clear-filter float-right">Clear</label>')

                if (key != "price") {
                    var filterUl = jQuery('<ul/>', {
                    }).appendTo(filterDiv);
                    data.forEach(element => {
                        var filterLi = jQuery('<li/>', {
                        }).appendTo(filterUl);
                        var filterLabel = jQuery('<label/>', {
                            class: 'container'
                        }).appendTo(filterLi);
                        var filterCheckbox = jQuery('<input />', {
                            type: "checkbox",
                            checked: element.checked,
                            value: element.value,
                            disabled: !element.enabled,
                            belongsTo: key
                        }).appendTo(filterLabel);
                        $(filterLabel).append('<span class="checkmark"></span>')
                        $(filterLabel).append('<span class="text">' + element.name + '</span>');

                    });
                }
                else {
                    $(filterDiv).attr('id', 'priceFilter');
                    var priceInput = jQuery('<input/>', {
                        class: 'price-range-slider',
                        id: 'priceRangeSlider',
                        name: 'price_range',
                        value: ''
                    }).appendTo(filterDiv);

                    // $("#priceRangeSlider").change(function () {
                    //     $("#priceInfo").find('.low').text($(this).attr('min'));
                    //     $("#priceInfo").find('.high').text($(this).val());
                    // });

                    $priceRangeSlider = $("#priceRangeSlider");

                    $priceRangeSlider.ionRangeSlider({
                        skin: "sharp",
                        type: "double",
                        min: data.min ? data.min : 0,
                        max: data.max ? data.max : 10000,
                        from: data.from ? data.from : data.min,
                        to: data.to ? data.to : data.max,
                        prefix: "$",
                        prettify_separator: ",",
                        onStart: function (data) {
                            // fired then range slider is ready
                        },
                        onChange: function (data) {
                            // fired on every range slider update
                        },
                        onFinish: function (data) {
                            // fired on pointer release

                            var $inp = $('#priceRangeSlider');
                            price_from = $inp.data("from"); // reading input data-from attribute
                            price_to = $inp.data("to"); // reading input data-to attribute

                            // console.log(price_from, price_to);
                            iPageNo = 0;
                            updateFilters();
                            fetchProducts(true);
                        },
                        onUpdate: function (data) {
                            // fired on changing slider with Update method
                        }
                    });

                }

                if (index == Object.keys(filterData).length - 1) {
                    $(filterDiv).append('<hr/>');
                }
            });

            // $(filterDiv).append('<hr/>');
            $('#filters').append('<a class="btn clearall-filter-btn" href="#" id="clearAllFiltersBtn">Clear All</a>');

            $('#filters').append('<hr/>');
        }
        else {
            Object.keys(filterData).forEach((key, index) => {
                const data = filterData[key];
                if (key != 'price') {
                    data.forEach(element => {
                        $('input[type="checkbox"][value=' + element.value + ']').attr('checked', element.checked);
                        $('input[type="checkbox"][value=' + element.value + ']').attr('disabled', !element.enabled);
                    });
                }
                else {
                    var instance = $('#priceRangeSlider').data("ionRangeSlider");
                    instance.update({
                        from: data.from ? data.from : data.min,
                        to: data.to ? data.to : data.max,
                        min: data.min,
                        max: data.max
                    });
                }
            });
        }
    }

    fetchProducts(false);

    function scrollToAnchor() {
        var aTag = $("a[href='#page" + iPageNo + "']");
        $('html,body').scrollTop(aTag.offset().top);
    }

    $('body').on('click', '.clear-filter', function () {
        iPageNo = 0;

        var $filter = $(this).closest('.filter');
        if ($filter.attr('id') === 'priceFilter') {
            var $inp = $(this);
            price_from = $inp.data("from");
            price_to = $inp.data("to");
        }
        else {
            $filter.find('input[type="checkbox"]').each(function () {
                if (this.checked) {
                    this.checked = false;
                }
            });
        }

        updateFilters();
        fetchProducts(true);
    });
    // var instance = $('#priceRangeSlider').data("ionRangeSlider");
    // $('body').on("mouseup", instance, function () {
    //     var $inp = $(this);
    //     price_from = $inp.data("from"); // reading input data-from attribute
    //     price_to = $inp.data("to"); // reading input data-to attribute

    //     console.log(price_from, price_to);
    //     updateFilters();
    //     fetchProducts(true);
    // });

    $('body').on('click', '#clearAllFiltersBtn', function () {
        iPageNo = 0;

        strFilters = '';
        $('.filter').each(function () {
            if ($(this).attr('id') === 'priceFilter') {
                var $inp = $(this);
                price_from = $inp.data("from");
                price_to = $inp.data("to");
            }
            else {
                $(this).find('input[type="checkbox"]').each(function () {
                    if (this.checked) {
                        this.checked = false;
                    }
                });
            }
        })
        fetchProducts(true);
    })

    /***************Implementation of filter changes **************/
    $('body').on('change', '.filter input[type="checkbox"]', function () {
        iPageNo = 0;
        updateFilters();
        fetchProducts(true);
    });

    function updateFilters() {

        strFilters = '';
        $('.filter').each(function () {

            if ($(this).attr('id') === 'priceFilter') {
                if (price_from) {
                    strFilters += 'price_from:' + price_from + ';';
                }
                if (price_to) {
                    strFilters += 'price_to:' + price_to + ";";
                }
            }
            else {
                var currFilter = $(this).attr('data-filter');
                strFilters += currFilter + ':';
                var bFirstChecked = false;
                $(this).find('input[type="checkbox"]').each(function (idx) {
                    if (this.checked) {
                        var delim;
                        if (!bFirstChecked) {
                            delim = '';
                            bFirstChecked = true;
                        }
                        else {
                            delim = ',';
                        }
                        strFilters += delim + $(this).attr('value');
                    }
                });
                strFilters += ';'
            }
        });
    }
});