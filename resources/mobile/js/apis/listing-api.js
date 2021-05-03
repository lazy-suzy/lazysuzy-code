import * as multiCarouselFuncs from "../components/multi-carousel";
import makeSelectBox from "../components/custom-selectbox";
import subscriber from "../../../js/apis/subscriber";
import isMobile from "../../../js/app";
const Handlebars = require("handlebars");
// import strItemsNumClass from '../pages/listing';
// import * as priceSliderContainer from '../pages/listing';
subscriber();

$(document).ready(function() {
    const LISTING_API_PATH = "/api" + location.pathname;
    const FAV_MARK_API = "/api/mark/favourite/";
    const FAV_UNMARK_API = "/api/unmark/favourite/";
    const PRODUCT_URL = "/product/";
    var totalResults = 0;
    var bFiltersCreated = false;
    var objGlobalFilterData;
    var search = window.location.search.substring(1);
    var source = document.getElementById("listing-template").innerHTML;
    var listingTemplate = Handlebars.compile(source);
    Handlebars.registerHelper("ifEq", function(v1, v2, options) {
        if (v1 === v2) {
            return options.fn(this);
        }
        return options.inverse(this);
    });
    Handlebars.registerHelper("ifNeq", function(v1, v2, options) {
        if (v1 !== v2) {
            return options.fn(this);
        }
        return options.inverse(this);
    });
    Handlebars.registerHelper("formatPrice", function(price) {
        if (price.includes("-")) {
            let salepriceRange = price.split("-");
            return `$${Math.round(
                salepriceRange[0]
            ).toLocaleString()} - $${Math.round(
                salepriceRange[1]
            ).toLocaleString()}`;
        }
        return `$${Math.round(price).toLocaleString()}`;
    });
    Handlebars.registerHelper("printDiscount", function(discount) {
        if (Math.ceil(discount) > 0) {
            return new Handlebars.SafeString(
                `<span class="prod-discount-tag d-md-none ${
                    discount >= 20 ? "_20" : ""
                }">${Math.ceil(discount)}%</span>`
            );
        }
        return null;
    });

    var queryObject = search
        ? JSON.parse(
              '{"' +
                  decodeURI(search)
                      .replace(/"/g, '\\"')
                      .replace(/&/g, '","')
                      .replace(/=/g, '":"') +
                  '"}'
          )
        : {};
    var strFilters = queryObject.filters || "";
    var strSortType = queryObject.sort_type || "";
    var iPageNo = parseInt(queryObject.pageno) || 0,
        iLimit;
    var price_from, price_to;
    var bNoMoreProductsToShow = false;
    var bFetchingProducts = false;

    $(window).scroll(function() {
        if (!bNoMoreProductsToShow) {
            if ($("#loaderImg") && isScrolledIntoView($("#loaderImg")[0])) {
                fetchProducts(false);
            } else if ($("#loaderImg") === null) {
                fetchProducts(false);
            }
        }
    });

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

    function fetchProducts(bClearPrevProducts) {
        if (!bFetchingProducts) {
            bFetchingProducts = true;
            var strLimit = iLimit === undefined ? "" : "&limit=" + iLimit;
            var filterQuery = `?filters=${strFilters}&sort_type=${strSortType}&pageno=${iPageNo}${strLimit}`;
            var listingApiPath = LISTING_API_PATH + filterQuery;
            resultButton();
            history.pushState(
                {},
                "",
                window.location.protocol +
                    "//" +
                    window.location.host +
                    window.location.pathname +
                    filterQuery
            );
            $("#noProductsText").hide();

            if (
                iPageNo > 0 &&
                !$("#productsContainerDiv")
                    .html()
                    .trim()
            ) {
                var apiCall = [];
                for (var i = 0; i <= iPageNo; i++) {
                    var filterQuery = `?filters=${strFilters}&sort_type=${strSortType}&pageno=${i}${strLimit}`;
                    var listingApiPath = LISTING_API_PATH + filterQuery;
                    apiCall.push(
                        $.ajax({
                            type: "GET",
                            url: listingApiPath,
                            dataType: "json"
                        })
                    );
                }
                var productsarry = [];
                $.when.apply(undefined, apiCall).then(function(...results) {
                    results.map(data => {
                        productsarry = [...productsarry, ...data[0].products];
                    });
                    results[0][0].products = productsarry;
                    listingApiRendering(results[0][0]);
                });
                iPageNo += 1;
            } else {
                iPageNo += 1;
                $.ajax({
                    type: "GET",
                    url: listingApiPath,
                    dataType: "json",
                    success: function(data) {
                        listingApiRendering(data);
                    },
                    error: function(jqXHR, exception) {
                        bFetchingProducts = false;
                        console.log(jqXHR);
                        console.log(exception);
                    }
                });
            }
        }
        window.listingApiRendering = function(data) {
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
                !$("#productsContainerDiv")
                    .html()
                    .trim()
            ) {
                const { seo_data } = data;
                document.title = `${seo_data.page_title} | Lazysuzy`;
                $(".js-category-text").text(seo_data.email_title);
            }

            if (data.products && data.products.length) {
                bNoMoreProductsToShow = true;

                totalResults = data.total;
                $("#totalResults").text(totalResults);

                var anchor = $("<a/>", {
                    href: "#page" + iPageNo,
                    id: "#anchor-page" + iPageNo
                }).appendTo("#productsContainerDiv");
                for (var product of data.products) {
                    if (
                        product.reviews != null &&
                        parseInt(product.reviews) != 0
                    ) {
                        product.reviewExist = true;
                        product.ratingClass = `rating-${parseFloat(
                            product.rating
                        )
                            .toFixed(1)
                            .toString()
                            .replace(".", "_")}`;
                    }
                    $("#productsContainerDiv").append(listingTemplate(product));
                }
                scrollToAnchor();
                multiCarouselFuncs.makeMultiCarousel();
                filterData();
            } else {
                // if (!bClearPrevProducts) {
                totalResults = data.total;
                bNoMoreProductsToShow = true;
                iPageNo -= 1;
                $("#loaderImg").hide();
                $(".subscriber-container").removeClass("d-none");
                filterData();
                resultButton();
                return;
                // }
            }

            function filterData() {
                if (data.filterData) {
                    objGlobalFilterData = data.filterData;
                    createUpdateFilterData(data.filterData);
                }
            }

            if (data.sortType) {
                $("#sort").empty();
                data.sortType.forEach(element => {
                    var sortElm = jQuery("<option />", {
                        value: element.value,
                        selected: element.enabled,
                        text: element.name
                    }).appendTo("#sort");
                    if (element.enabled) {
                        strSortType = element.value;
                    }
                });
                makeSelectBox();
            }

            //     $("#anchor-page"+iPageNo)[0].click()
        };
    }

    var mainProductDiv;

    function createUpdateFilterData(filterData) {
        bNoMoreProductsToShow = false;
        $("#filters").empty();
        var mobileFilterHeader = jQuery("<div/>", {
            class: "mobile-filter-header d-md-none "
        }).appendTo("#filters");
        jQuery("<span/>", {
            class: "float-left filters-close-btn",
            html: '<i class="fa fa-arrow-left" aria-hidden="true"></i>'
        }).appendTo(mobileFilterHeader);
        jQuery("<span/>", {
            class: "filter-title",
            text: "Filters"
        }).appendTo(mobileFilterHeader);
        jQuery("<span/>", {
            class: "clear-all",
            html:
                '<a class="btn clearall-filter-btn" href="#" id="clearAllFiltersBtn">Clear All</a>'
        }).appendTo(mobileFilterHeader);
        var row = jQuery("<div/>", {
            class: "row  filters-height"
        }).appendTo("#filters");
        var col3 = jQuery("<div/>", {
            class: "col-3 tab-column"
        }).appendTo(row);
        var col9 = jQuery("<div/>", {
            class: "col-9 bg-white"
        }).appendTo(row);
        var filterTabs = jQuery("<div/>", {
            class: "filter-tabs"
        }).appendTo(col3);
        var filterList = jQuery("<ul/>", {
            class: "nav flex-column"
        }).appendTo(filterTabs);
        var totalResult = jQuery("<div/>", {
            class: "total-results",
            id: "filter-options"
        }).appendTo(col9);
        resultButton();

        Object.keys(filterData).forEach((key, index) => {
            const data = filterData[key];
            if (data === null) {
                return;
            }
            var filterItem = jQuery("<li/>", {
                class: "nav-item"
            }).appendTo(filterList);
            if (
                !data ||
                data.length == 0 ||
                (data.length &&
                    data.filter(filterData => filterData.enabled).length == 0)
            ) {
                var filterLink = jQuery("<a/>", {
                    class: "nav-link flex-column disabled-link",
                    href: "javascript:void(0)",
                    text: key
                }).appendTo(filterItem);
            } else {
                var filterLink = jQuery("<a/>", {
                    class: "nav-link flex-column",
                    href: key,
                    text: key
                }).appendTo(filterItem);
            }

            var filterDiv = jQuery("<div/>", {
                class: "filter",
                "data-filter": key,
                id: key
            }).appendTo(col9);
            var clear = jQuery("<div/>", {
                class: "clear-btn"
            }).appendTo(filterDiv);
            $(clear).append(
                '<label for="' + key + '" class="clear-filter">Clear</label>'
            );

            if (key != "price") {
                var filterUl = jQuery("<ul/>", {
                    class: "item-list"
                }).appendTo(filterDiv);
                if (
                    !data ||
                    data.length == 0 ||
                    (data.length &&
                        data.filter(filterData => filterData.enabled).length ==
                            0)
                ) {
                    return;
                }
                const isChecked =
                    data.filter(element => element.checked).length > 0;
                data.forEach(element => {
                    if (element.enabled) {
                        var filterLi = jQuery("<li/>", {
                            class: "filter-item"
                        }).appendTo(filterUl);
                        var filterLabel = jQuery("<label/>", {
                            class: "filter-label"
                        }).appendTo(filterLi);
                        var filterCheckbox = jQuery("<input />", {
                            type: "checkbox",
                            checked: element.checked,
                            value: element.value,
                            disabled: !element.enabled,
                            belongsTo: key,
                            class: "list-checkbox"
                        }).appendTo(filterLabel);
                        $(filterLabel).append(
                            '<span class="checkmark"></span>'
                        );
                        $(filterLabel).append(
                            '<span class="text">' + element.name + "</span>"
                        );
                    }
                });
                isChecked &&
                    $(clear).append(
                        '<label for="' +
                            key +
                            '" class="clear-filter visible">Clear</label>'
                    );
            } else {
                $(clear).append(
                    '<label for="' +
                        key +
                        '" class="clear-filter visible">Clear</label>'
                );
                $(filterDiv).attr("id", "price");
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
        if (!isMobile()) {
            $("#filters").append(
                '<a class="btn clearall-filter-btn" href="#" id="clearAllFiltersBtn">Clear All</a>'
            );
        }

        const tab = localStorage.getItem("tab") || "brand";
        $("[href$=" + tab + "]").addClass("selected");
        $("#" + tab).addClass("selected");

        // $('#filters').append('<hr/>')
    }

    function resultButton() {
        var result = jQuery("<button/>", {
            class: "filter-results filters-close-btn"
        }).appendTo("#filter-options");

        if (bFetchingProducts === true) {
            $(".filter-results ").html(
                "<div class='lds-ellipsis'><div></div><div></div><div></div><div></div></div>"
            );
        } else {
            if (totalResults === 1) {
                $(".filter-results ").html(`See ${totalResults} product`);
            } else {
                $(".filter-results ").html(`See ${totalResults} products`);
            }
        }
    }

    fetchProducts(false);

    function scrollToAnchor() {
        var aTag = $("a[href='#page" + iPageNo + "']");
        iPageNo == 1
            ? $("html,body").scrollTop(0)
            : $("html,body").scrollTop(aTag.position().top);
    }

    $("body").on("click", ".clear-filter", function() {
        iPageNo = 0;

        var $filter = $(this).closest(".filter");
        if ($filter.attr("id") === "price") {
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

    $("body").on("click", "#clearAllFiltersBtn", function() {
        iPageNo = 0;

        strFilters = "";
        $(".filter").each(function() {
            if ($(this).attr("id") === "price") {
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
        strSortType = $("#selectbox-sort").attr("active");
        iPageNo = 0;
        updateFilters();
        fetchProducts(true);
    });
    $('input[name="sort-price-filter"]').click(function() {
        strSortType = $('input[name="sort-price-filter"]:checked').val();
        iPageNo = 0;
        updateFilters();
        fetchProducts(true);
        $("#sort-mobile").toggleClass("show");
    });

    function updateFilters() {
        strFilters = "";
        $(".filter").each(function() {
            if ($(this).attr("id") === "price") {
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

        //  window.location.search = strFilters;
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
        e.stopPropagation();
        if ($("#isLoggedIn").val() == 0) {
            $("#modalLoginForm").modal();
        } else {
            var iSku = $(this).attr("sku");
            callWishlistAPI($(this));
        }
    });
    $(".filter").on("click", ".filter-label .list-checkbox", function(e) {
        e.preventDefault();
        e.stopPropagation();
        if ($(this).is(":checked")) {
            $(".clear-filter").removeClass("d-none");
        }
    });
    $("body").on("click", ".filter-tabs .nav-link", function(e) {
        e.preventDefault();
        e.stopPropagation();

        if ($(this).hasClass("disabled-link")) {
            return;
        }

        $(".filter.selected").removeClass("selected");
        $(".filter-tabs .nav-link.selected").removeClass("selected");

        $(this).addClass("selected");
        const target = $(this).attr("href");
        $("#" + target).addClass("selected");
        if (typeof localStorage !== "undefined") {
            localStorage.setItem("tab", target);
        }
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
