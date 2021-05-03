import * as multiCarouselFuncs from "../components/multi-carousel";
import makeSelectBox from "../components/custom-selectbox";
import isMobile from "../app.js";
// import * as priceSliderContainer from '../pages/listing';

function getQueryStringParameters(url) {
    var urlParams = {},
        match,
        additional = /\+/g, // Regex for replacing additional symbol with a space
        search = /([^&=]+)=?([^&]*)/g,
        decode = function(s) {
            return decodeURIComponent(s.replace(additional, " "));
        },
        query;
    if (url) {
        if (url.split("?").length > 0) {
            query = url.split("?")[1];
        }
    } else {
        url = window.location.href;
        query = window.location.search.substring(1);
    }
    while ((match = search.exec(query))) {
        urlParams[decode(match[1])] = decode(match[2]);
    }
    return urlParams;
}

$(document).ready(function() {
    const LISTING_API_PATH = "https://www.lazysuzy.com:9200/products/_search";
    const LISTING_FILTER_API_PATH = location.origin + "/api/products/living";
    const DEPT_API = "/api/all-departments";

    const FAV_MARK_API = "/api/mark/favourite/";
    const FAV_UNMARK_API = "/api/unmark/favourite/";
    const PRODUCT_URL = location.origin + "/product/";
    const PRODUCT_URL_LOCATION = "/product/";
    var totalResults = 0;
    var UrlSearchParams = new Object();
    var objGlobalFilterData;
    var bFiltersCreated = false;
    var strFilters = "";
    var strSortType = "";
    var iPageNo = 0,
        iLimit = 12;
    var price_from, price_to;
    var bNoMoreProductsToShow = false;
    var bFetchingProducts = false;
    $(window).scroll(function() {
        // if (!bNoMoreProductsToShow) {
        if ($("#loaderImg") && isScrolledIntoView($("#loaderImg")[0])) {
            fetchProducts(false);
        } else if ($("#loaderImg") === null) {
            fetchProducts(false);
        }
        // }
    });
    let searchNavigator = window.location.href.split("=");

    var span = $("<span/>", {
        text: "Search results for " + '"'
    }).appendTo("#search-navigator");
    var span = $("<span/>", {
        text: searchNavigator[1],
        class: "search-navigator"
    }).appendTo("#search-navigator");
    var span = $("<span/>", {
        text: '"'
    }).appendTo("#search-navigator");

    function isScrolledIntoView(el) {
        var rect = el.getBoundingClientRect();
        var elemTop = rect.top;
        var elemBottom = rect.bottom;

        // Only completely visible elements return true:
        var isVisible = elemTop >= 0 && elemBottom <= window.innerHeight;
        // Partially visible elements return true:
        //isVisible = elemTop < window.innerHeight && elemBottom >= 0;
        return isVisible;
    }

    fetchProducts(false);

    function fetchProducts(bClearPrevProducts) {
        if (!bFetchingProducts) {
            bFetchingProducts = true;
            var strLimit = iLimit === undefined ? "" : "&limit=" + iLimit;
            var listingApiPath = LISTING_API_PATH;
            console.log(listingApiPath);
            //$('#loaderImg').show();
            $("#noProductsText").hide();

            var strUrlParams = getQueryStringParameters(location.href);

            var strQuery = JSON.stringify({
                from: iPageNo * iLimit,
                size: iLimit,
                query: {
                    match: {
                        name: {
                            query: strUrlParams.query + "*"
                        }
                    }
                }
            });
            iPageNo += 1;
            $.ajax({
                type: "GET",
                url: listingApiPath,
                dataType: "json",
                crossDomain: true,
                data: {
                    source: strQuery,
                    source_content_type: "application/json"
                },
                contentType: "application/json; charset=utf-8",
                success: function(data) {
                    bFetchingProducts = false;

                    if (bClearPrevProducts) {
                        $("#productsContainerDiv").empty();
                        totalResults = 0;
                    }
                    //$('#loaderImg').hide();
                    if (data == null) {
                        return;
                    }
                    if (
                        data.hits.hits != undefined &&
                        data.hits.hits.length != 0
                    ) {
                        bNoMoreProductsToShow = true;

                        totalResults += data.hits.hits.length;
                        $("#totalResults").text(totalResults);

                        var anchor = $("<a/>", {
                            href: "#page" + iPageNo,
                            id: "#anchor-page" + iPageNo
                        }).appendTo("#productsContainerDiv");
                        for (var i = 0; i < data.hits.hits.length; i++) {
                            createProductDiv(data.hits.hits[i]);
                        }
                        // scrollToAnchor();
                        multiCarouselFuncs.makeMultiCarousel();
                    } else {
                        // if (!bClearPrevProducts) {
                        bNoMoreProductsToShow = true;
                        iPageNo -= 1;
                        $("#noProductsText").show();
                        return;
                        // }
                    }

                    //     $("#anchor-page"+iPageNo)[0].click()
                },
                error: function(jqXHR, exception) {
                    bFetchingProducts = false;
                    console.log(jqXHR);
                    console.log(exception);
                }
            });
        }
    }

    function createProductDiv(productDetails) {
        //Make product main div
        var mainProductDiv = jQuery("<div/>", {
            id: productDetails._id,
            sku: productDetails._source.product_sku,
            site: productDetails._source.site_name,
            class: "ls-product-div col-md-3 item-3"
        }).appendTo("#productsContainerDiv");

        var productLink = jQuery("<a/>", {
            href: PRODUCT_URL_LOCATION + productDetails._source.product_sku,
            class: "product-detail-modal js-detail-modal",
            "data-href": "/product/" + productDetails._source.product_sku
        }).appendTo(mainProductDiv);

        // var productLink = jQuery('<a/>', {
        //     href: PRODUCT_URL + productDetails.sku,
        //     class: 'product-detail-modal'
        // }).appendTo(mainProductDiv)

        var product = jQuery("<div/>", {
            class: "ls-product"
        }).appendTo(productLink);

        jQuery("<img />", {
            class: "prod-img img-fluid",
            src:
                "//www.lazysuzy.com" +
                productDetails._source.main_product_images,
            alt: productDetails._source.name
        }).appendTo(product);

        //Product information
        var prodInfo = jQuery("<div/>", {
            class: "prod-info d-none d-md-block"
        }).appendTo(mainProductDiv);
        var catDetails = jQuery("<span/>", {
            class: "-site"
        }).appendTo(prodInfo);
        $(mainProductDiv).append(
            '<div class="-name">' + productDetails._source.name + "</div>"
        );
        $(catDetails).text(productDetails._source.site_name);
        var prices = jQuery("<span/>", {
            class: "-prices"
        }).appendTo(mainProductDiv);
        var currPrice = jQuery("<span/>", {
            class: "-cprice"
        }).appendTo(prices);
        $(currPrice).text("$" + productDetails._source.price);
        if (productDetails.is_price < productDetails._source.was_price) {
            var oldPrice = jQuery("<span/>", {
                class: "-oldprice"
            }).appendTo(prices);
            $(oldPrice).text("$" + productDetails._source.was_price);
        }

        var strMarked = productDetails.wishlisted ? "marked" : "";
        $(product).append(
            '<div class="wishlist-icon tile-icon' +
                strMarked +
                '" sku=' +
                productDetails._source.product_sku +
                '><i class="far fa-heart -icon"></i></div>'
        );

        var carouselMainDiv = jQuery("<div/>", {
            class: "responsive"
        }).appendTo(mainProductDiv);

        if (productDetails._source.variations) {
            var variationImages = productDetails._source.variations.map(
                variation => variation.image
            );
            var variationSwatchImages = productDetails._source.variations.map(
                (variation, idx) => {
                    return variation.swatch_image || variationImages[idx];
                }
            );
            var variationLinks = productDetails._source.variations.map(
                variation => variation.link
            );
        }

        if (productDetails._source.main_product_images != null) {
            jQuery("<img />", {
                class: "variation-img img-fluid",
                src:
                    "//www.lazysuzy.com" +
                    productDetails._source.main_product_images,
                alt: "variation-img"
            }).appendTo(product);
        }

        if (variationSwatchImages && variationSwatchImages.length > 0) {
            variationSwatchImages.forEach((img, idx) => {
                var responsiveImgDiv = jQuery("<div/>", {
                    class: "mini-carousel-item"
                }).appendTo(carouselMainDiv);
                var anchor = jQuery("<a/>", {
                    class: "responsive-img-a",
                    href: variationLinks[idx]
                }).appendTo(responsiveImgDiv);
                var responsiveImg = jQuery("<img/>", {
                    class: "carousel-img img-fluid",
                    src: img,
                    "data-prodimg": variationImages[idx]
                }).appendTo(anchor);
            });
        } else {
            carouselMainDiv.addClass("d-none");
        }

        if (parseInt(productDetails._source.reviews) != 0) {
            var reviewValue = parseInt(productDetails._source.reviews);
            var ratingValue = parseFloat(productDetails._source.rating).toFixed(
                1
            );
            var ratingClass = ratingValue.toString().replace(".", "_");
            $(prodInfo).append(
                '<div class="rating-container float-right"><div class="rating  rating-' +
                    ratingClass +
                    '"></div><span class="total-ratings">' +
                    reviewValue +
                    "</span></div>"
            );
        }
    }

    function createUpdateFilterData(filterData) {
        bNoMoreProductsToShow = false;
        if (!bFiltersCreated) {
            bFiltersCreated = true;
            $("#filters").empty();
            Object.keys(filterData).forEach((key, index) => {
                const data = filterData[key];
                var filterDiv = jQuery("<div/>", {
                    class: "filter",
                    "data-filter": key
                }).appendTo("#filters");
                $(filterDiv).append("<hr/>");

                $(filterDiv).append(
                    '<span class="filter-header">' +
                        key.replace("_", " ") +
                        "</span>"
                );
                $(filterDiv).append(
                    '<label for="' +
                        key +
                        '" class="clear-filter float-right">Clear</label>'
                );

                if (key != "price") {
                    var filterUl = jQuery("<ul/>", {}).appendTo(filterDiv);
                    data.forEach(element => {
                        var filterLi = jQuery("<li/>", {}).appendTo(filterUl);
                        var filterLabel = jQuery("<label/>", {
                            class: "container"
                        }).appendTo(filterLi);
                        var filterCheckbox = jQuery("<input />", {
                            type: "checkbox",
                            checked: element.checked,
                            value: element.value,
                            disabled: !element.enabled,
                            belongsTo: key
                        }).appendTo(filterLabel);
                        $(filterLabel).append(
                            '<span class="checkmark"></span>'
                        );
                        $(filterLabel).append(
                            '<span class="text">' + element.name + "</span>"
                        );
                    });
                } else {
                    $(filterDiv).attr("id", "priceFilter");
                    var priceInput = jQuery("<input/>", {
                        class: "price-range-slider",
                        id: "priceRangeSlider",
                        name: "price_range",
                        value: ""
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
                        onStart: function(data) {
                            // fired then range slider is ready
                        },
                        onChange: function(data) {
                            // fired on every range slider update
                        },
                        onFinish: function(data) {
                            // fired on pointer release

                            var $inp = $("#priceRangeSlider");
                            price_from = $inp.data("from"); // reading input data-from attribute
                            price_to = $inp.data("to"); // reading input data-to attribute

                            // console.log(price_from, price_to);
                            iPageNo = 0;
                            updateFilters();
                            fetchProducts(true);
                        },
                        onUpdate: function(data) {
                            // fired on changing slider with Update method
                        }
                    });
                }

                if (index == Object.keys(filterData).length - 1) {
                    $(filterDiv).append("<hr/>");
                }
            });

            // $(filterDiv).append('<hr/>');
            $("#filters").append(
                '<a class="btn clearall-filter-btn" href="#" id="clearAllFiltersBtn">Clear All</a>'
            );

            $("#filters").append("<hr/>");
        } else {
            Object.keys(filterData).forEach((key, index) => {
                const data = filterData[key];
                if (key != "price") {
                    data.forEach(element => {
                        $(
                            'input[type="checkbox"][value=' +
                                element.value +
                                "]"
                        ).attr("checked", element.checked);
                        $(
                            'input[type="checkbox"][value=' +
                                element.value +
                                "]"
                        ).attr("disabled", !element.enabled);
                    });
                } else {
                    var instance = $("#priceRangeSlider").data(
                        "ionRangeSlider"
                    );
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

    function scrollToAnchor() {
        var aTag = $("a[href='#page" + iPageNo + "']");
        iPageNo == 1
            ? $("html,body").scrollTop(0)
            : $("html,body").scrollTop(aTag.position().top);
    }

    $("body").on("click", ".clear-filter", function() {
        iPageNo = 0;

        var $filter = $(this).closest(".filter");
        if ($filter.attr("id") === "priceFilter") {
            var $inp = $(this);
            price_from = $inp.data("from");
            price_to = $inp.data("to");
        } else {
            $filter.find('input[type="checkbox"]').each(function() {
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

    $("body").on("click", "#clearAllFiltersBtn", function() {
        iPageNo = 0;

        strFilters = "";
        $(".filter").each(function() {
            if ($(this).attr("id") === "priceFilter") {
                var $inp = $(this);
                price_from = $inp.data("from");
                price_to = $inp.data("to");
            } else {
                $(this)
                    .find('input[type="checkbox"]')
                    .each(function() {
                        if (this.checked) {
                            this.checked = false;
                        }
                    });
            }
        });
        fetchProducts(true);
    });

    /***************Implementation of filter changes **************/
    $("body").on("change", '.filter input[type="checkbox"]', function() {
        iPageNo = 0;
        updateFilters();
        fetchProducts(true);
    });

    $(document).on("select-value-changed", function() {
        strSortType = $("#selectbox-sort").text();
        iPageNo = 0;
        updateFilters();
        fetchProducts(true);
    });

    function updateFilters() {
        strFilters = "";
        $(".filter").each(function() {
            if ($(this).attr("id") === "priceFilter") {
                if (price_from) {
                    strFilters += "price_from:" + price_from + ";";
                }
                if (price_to) {
                    strFilters += "price_to:" + price_to + ";";
                }
            } else {
                var currFilter = $(this).attr("data-filter");
                strFilters += currFilter + ":";
                var bFirstChecked = false;
                $(this)
                    .find('input[type="checkbox"]')
                    .each(function(idx) {
                        if (this.checked) {
                            var delim;
                            if (!bFirstChecked) {
                                delim = "";
                                bFirstChecked = true;
                            } else {
                                delim = ",";
                            }
                            strFilters += delim + $(this).attr("value");
                        }
                    });
                strFilters += ";";
            }
        });
    }

    $("body").on("mouseover", ".slick-slide", function() {
        $(this)
            .closest(".ls-product-div")
            .find(".variation-img")
            .attr(
                "src",
                $(this)
                    .find(".carousel-img")
                    .attr("data-prodimg")
            );
        $(this)
            .closest(".ls-product-div")
            .find(".prod-img")
            .css("visibility", "hidden");
        $(this)
            .closest(".ls-product-div")
            .find(".variation-img")
            .show();
    });

    $("body").on("mouseleave", ".slick-slide", function() {
        $(this)
            .closest(".ls-product-div")
            .find(".variation-img")
            .hide();
        $(this)
            .closest(".ls-product-div")
            .find(".prod-img")
            .css("visibility", "unset");
    });

    $("body").on("click", ".dropdown-submenu a", function(e) {
        if (isMobile()) {
            // early return if the parent has no hover-class
            if (!$(this).hasClass("hover")) return;

            // prevent click when delay is too small
            var delay = Date.now() - $(this).data("hovered");
            if (delay < 100) e.preventDefault();
        }
    });

    $("body").on("mouseover", ".dropdown-submenu a", function(e) {
        if (isMobile()) {
            var time = Date.now();
            $(this).data("hovered", time);
        }
    });

    $("body").on("click", ".wishlist-icon:not(.nav-link)", function(e) {
        e.preventDefault();
        var iSku = $(this).attr("sku");
        callWishlistAPI($(this));
    });

    function callWishlistAPI($elm) {
        var strApiToCall = "";
        if (!$elm.hasClass("marked")) {
            strApiToCall = FAV_MARK_API + $elm.attr("sku");
        } else {
            strApiToCall = FAV_UNMARK_API + $elm.attr("sku");
        }
        $.ajax({
            type: "GET",
            url: strApiToCall,
            dataType: "json",
            success: function(data) {
                if (!$elm.hasClass("marked")) {
                    $elm.addClass("marked");
                } else {
                    $elm.removeClass("marked");
                }
            },
            error: function(jqXHR, exception) {
                console.log(jqXHR);
                console.log(exception);
            }
        });
    }
});
