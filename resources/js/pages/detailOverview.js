import * as multiCarouselFuncs from "../components/multi-carousel";
import makeSelectBox from "../components/custom-selectbox";
import Drift from "drift-zoom";
import isMobile from "../app.js";
require("slick-lightbox");
var md = require("markdown-it")({
    html: true,
    breaks: true
});

$(document).ready(function() {
    const PDP_API = "/api" + window.location.pathname;
    const FAV_MARK_API = "/api/mark/favourite/";
    const FAV_UNMARK_API = "/api/unmark/favourite/";
    const VARIATION_API = "/api/variation" + window.location.pathname;
    const SWATCH_API = "/api/filters/variation" + window.location.pathname;

    const $product = $("#detailPage");
    const $modal = $("#modalProduct");
    const $prodPriceCard = $product.find(".prod-price-card");
    let LISTING_URL = "";
    var $filtersDiv = "";
    var $filtersDivMobile = "";
    var variationDrift = "";
    var variationImgEl = "";
    var arrFilters = [];
    var search = window.location.search.substring(1);
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
    if (isMobile()) {
        return;
    }
    const openProductModal = detail_url => {
        $("#modalProduct").modal();
        $("#modalProduct").on("hidden.bs.modal", function() {
            window.history.pushState(
                "",
                "",
                LISTING_URL + window.location.search
            );
        });
        $prodPriceCard.empty();
        $(".prod-desc").addClass("d-none");
        $.ajax({
            type: "GET",
            url: detail_url,
            dataType: "json",
            success: function(data) {
                LISTING_URL =
                    data.department_info &&
                    (data.department_info[0].category_url ||
                        data.department_info[0].department_url);
                var $imagesContainer = $product.find(".-images-container");
                var $images = $imagesContainer.find(".-images");
                var $variationsContainer = $product.find(".variation-options");
                var $swatchImages = $variationsContainer.find(".swatch-images");
                var imgContainerWidth = 0;
                $("#wishlistBtnDesktop").attr("sku", data.sku);
                if (data.wishlisted) {
                    $("#wishlistBtnDesktop").addClass("marked");
                }
                $images.empty();
                data.on_server_images.forEach(img => {
                    var div = jQuery("<div/>", {
                        class: "single"
                    }).appendTo($images);
                    var a = jQuery("<a/>", {
                        href: img,
                        "data-caption": ""
                    }).appendTo(div);
                    var responsiveImg = jQuery("<img/>", {
                        class: "-prod-img img-fluid",
                        src: img,
                        alt: "product image"
                    }).appendTo(a);
                });
                $swatchImages.empty();
                $(".prod-desc.d-none").removeClass("d-none");
                if (data.variations && data.variations.length) {
                    let hasSwatch = false;
                    $(".variation-container.d-none").removeClass("d-none");
                    data.variations.forEach(img => {
                        var div = jQuery("<div/>", {
                            class: "single"
                        }).appendTo($swatchImages);

                        var a = jQuery("<a/>", {
                            "data-caption": ""
                        }).appendTo(div);

                        var span = jQuery("<span/>", {
                            "data-title": img.name
                        }).appendTo(a);
                        if (img.swatch_image) {
                            hasSwatch = true;
                            var responsiveImg = jQuery("<img/>", {
                                class: "variant-img",
                                src: img.swatch_image,
                                alt: "product image",
                                "data-href": img.link,
                                "data-parent": img.has_parent_sku,
                                "data-image": img.image || ""
                            }).appendTo(span);
                        }
                    });
                    $(".products-modal-details").removeClass("extended");
                    if (!hasSwatch) {
                        $(".variation-container").addClass("d-none");
                        $(".products-modal-details").addClass("extended");
                    }
                } else {
                    $(".variation-container").addClass("d-none");
                }

                $(".js-site").text(data.site);
                var $prodDetails = $("<div />", {
                    class: "-product-details"
                }).appendTo($prodPriceCard);

                var priceCont = $("<div/>").appendTo($prodDetails);
                $("<span/>", {
                    text: " $" + data.is_price.replace("-", " - $"),
                    class: "offer-price"
                }).appendTo(priceCont);
                var buyBtn = $("<a/>", {
                    class: "btn pdp-buy-btn float-right  tr-viewDetails",
                    href: data.product_url,
                    text: "View Details",
                    target: "_blank"
                }).appendTo(priceCont);
                if (data.is_price !== data.was_price) {
                    $("<span/>", {
                        text: " $" + data.was_price.replace("-", " -$"),
                        class: "price"
                    }).appendTo(priceCont);
                }

                //Product description

                var $desc = $product.find(".prod-desc");

                $desc.find(".-name").text(data.name);
                if (isMobile()) {
                    var $mobileProdDetails = $(".-product-details").clone();
                    $mobileProdDetails.insertAfter(".-name");
                }

                var ratingValue = parseFloat(data.rating).toFixed(1);
                var ratingClass =
                    "rating-" + ratingValue.toString().replace(".", "_");
                $desc.find(".rating").addClass(ratingClass);
                $desc.find(".total-ratings").text(data.reviews);
                if (data.reviews <= 0) {
                    $desc.find(".rating-container").hide();
                }

                $desc
                    .find(".-desc")
                    .html(md.render(data.description.join("\n")));
                $desc.find(".-dimen").html(data.dimension);
                $("#desc").html(data.description);

                var $featuresList = document.createElement("div");

                if (data.site.replace(/\s+/g, "").toLowerCase() == "westelm") {
                    var div = $("<div/>", {
                        html: md.render(data.features.join("\n"))
                    }).appendTo($featuresList);
                } else {
                    data.features &&
                        data.features.map(features => {
                            // $featuresList.empty()
                            var li = $("<li>", {
                                html: features
                            }).appendTo($featuresList);
                        });
                }

                $($featuresList)
                    .clone()
                    .appendTo($("#feat").empty());

                var $dimension = $desc.find(".-dimension");
                $dimension.empty();

                if (data.dimension && Array.isArray(data.dimension)) {
                    data.dimension.forEach(dimension => {
                        var div = $("<div/>", {
                            class: " col-6"
                        }).appendTo($dimension);
                        dimension.description &&
                            $("<h5/>", {
                                class: "description-title",
                                html: `${dimension.description}`
                            }).appendTo(div);
                        dimension.width &&
                            $("<div/>", {
                                class: "description-data",
                                html: `Width: ${dimension.width}"`
                            }).appendTo(div);
                        dimension.height &&
                            $("<div/>", {
                                class: "description-data",
                                html: `Height: ${dimension.height}"`
                            }).appendTo(div);
                        dimension.depth &&
                            $("<div/>", {
                                class: "description-data",
                                html: `Depth: ${dimension.depth}"`
                            }).appendTo(div);
                    });
                }
            },
            error: function(jqXHR, exception) {
                console.log(jqXHR);
                console.log(exception);
            }
        });
    };

    if (queryObject.model_sku) {
        openProductModal(`/api/product/${queryObject.model_sku}`);
        window.GLOBAL_LISTING_API_PATH = `/api${window.location.pathname}`;
        window.history.pushState(
            {},
            "",
            `/product/${queryObject.model_sku}${window.location.search}`
        );
    }
    $(document).on("click", ".js-detail-modal", function(e) {
        e.preventDefault();
        e.stopPropagation();
        const product_sku = this.attributes["data-href"].value;
        openProductDetailModal(product_sku);
    });
    $(document).on("click", ".variant-img", function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(".variant-img.active").removeClass("active");
        const has_parent = $(this).data("parent");
        const imgSrc = this.attributes["data-image"].value;
        if (has_parent) {
            const product_sku = this.attributes["data-href"].value;
            openProductDetailModal(product_sku);
        } else {
            $(".-images")
                .find("img:first")
                .remove();
            var prependImg = jQuery("<img/>", {
                class: "-prod-img img-fluid",
                src: imgSrc,
                alt: "product image"
            }).prependTo(".-images");
            $(this).addClass("active");
            prependImg.on("load", () => {
                $(".-images").animate(
                    { scrollTop: $(".-prod-img").position().top },
                    0
                );
            });
        }
        $(".-images").scrollTop($(".-prod-img").position().top);
    });

    function openProductDetailModal(href) {
        openProductModal(`/api${href}`);
        window.history.pushState("", "", `${href}${window.location.search}`);
    }
    $(".-images").slick({
        infinite: true,
        slidesToShow: 3,
        slidesToScroll: 1,
        mobileFirst: true
    });
    $(".-images").slickLightbox({
        itemSelector: "a",
        navigateByKeyboard: true,
        captionPosition: "dynamic",
        layouts: {
            closeButton:
                '<button type="button" class="slick-lightbox-close"></button>'
        }
    });

    $("#wishlistBtnDesktop").on("click", function(e) {
        e.preventDefault();
        // callWishlistAPI($(this))
        if ($("#isLoggedIn").val() == 0) {
            $("#modalLoginForm").modal();
        } else {
            var iSku = $(this).attr("sku");
            $(".alert-warning").addClass("show");
            // $('.wishlist-icon-modal').addClass('marked-icon')
            callWishlistAPI($(this));
            // alert('is added to wishlist successfully.')
        }
    });
    function callWishlistAPI($elm) {
        var strApiToCall = "";
        if (!$elm.hasClass("marked-icon")) {
            strApiToCall = FAV_MARK_API + $elm.attr("sku");
        } else {
            strApiToCall = FAV_UNMARK_API + $elm.attr("sku");
        }
        $.ajax({
            type: "GET",
            url: strApiToCall,
            dataType: "json",
            success: function(data) {
                console.log(data, "success");
                if (!$elm.hasClass("marked-icon")) {
                    $elm.addClass("marked-icon");
                    $elm.find("span").text("Saved");
                } else {
                    $elm.removeClass("marked-icon");
                    $elm.find("span").text("Save");
                }
            },
            error: function(jqXHR, exception) {
                console.log(jqXHR);
                console.log(exception);
            }
        });
    }

    $("#features")
        .find(".nav-link")
        .on("click", function() {
            if (!$("#collapseB").hasClass("show")) {
                $("#collapseB").collapse("toggle");
            }
        });

    function fetchVariations(queryParams = null) {
        updateFiltersAndVariations(queryParams, VARIATION_API, true);
    }

    function fetchFilters(queryParams = null) {
        updateFiltersAndVariations(queryParams, SWATCH_API, false);
    }

    function makeFilters(data, isMobile) {
        var currFilterDiv = isMobile ? $filtersDivMobile : $filtersDiv;
        Object.keys(data.filters).forEach(function(filter) {
            // data.filters.filter.forEach(options => {
            var transformedLabel = data.filters[filter].label
                .toLowerCase()
                .replace(" ", "_");
            var $singleFilter = jQuery("<div/>", {
                class: "single-filter" + (isMobile ? " text-center" : "")
            }).appendTo(currFilterDiv);
            var $filterLabel = jQuery("<label/>", {
                text: data.filters[filter].label + ":",
                for: "selectbox-attr-" + transformedLabel,
                class: "select-label",
                value: data.filters[filter].label
            }).appendTo($singleFilter);
            var $filterSelectBox = jQuery("<select/>", {
                class: "form-control",
                id: "attr-" + transformedLabel
            }).appendTo($singleFilter);

            var bFilterEnabled = false;
            data.filters[filter].options.forEach((element, idx) => {
                if (!bFilterEnabled) {
                    bFilterEnabled = element.in_request;
                }
                var attrElm = jQuery("<option />", {
                    value: element.value,
                    selected: element.in_request,
                    text: element.name
                }).appendTo($filterSelectBox);
                if (idx == data.filters[filter].options.length - 1) {
                    var attrElm2 = jQuery("<option />", {
                        value: "unselected-value",
                        selected: !bFilterEnabled,
                        text: "Please select a value"
                    }).appendTo($filterSelectBox);
                    bFilterEnabled = false;
                }
            });
            // });
        });
    }

    function updateFiltersAndVariations(
        queryParams,
        apiPath,
        bUpdateVariations = true
    ) {
        $.ajax({
            type: "GET",
            url: apiPath,
            data: queryParams,
            dataType: "json",
            success: function(data) {
                $filtersDiv.empty();
                $filtersDivMobile.empty();

                if (data.filters != null && !$.isEmptyObject(data.filters)) {
                    arrFilters = Object.keys(data.filters);
                    makeFilters(data, isMobile());
                    makeSelectBox();
                } else {
                    $("#filterToggleBtn").hide();
                }

                if (data.variations != null && bUpdateVariations) {
                    makeVariationCarousel(data.variations);

                    var $prodMainImgDiv = $product.find(".prod-main-img");
                    $prodMainImgDiv.empty();
                    var carouselMainDiv = jQuery("<i/>", {
                        id: "closeMainImgBtn",
                        class: "far fa-times-circle close-main-btn"
                    }).appendTo($prodMainImgDiv);
                    var carouselMainDiv = jQuery("<img/>", {
                        id: "variationImg",
                        class: "zoom-img-variation img-fluid"
                    }).appendTo($prodMainImgDiv);
                    variationImgEl = document.querySelector("#variationImg");
                    variationDrift = new Drift(variationImgEl, {});
                }

                $(".zoom-img").each(function() {
                    var options = { namespace: "carousel" };
                    new Drift(this, options);
                });
            },
            error: function(jqXHR, exception) {
                console.log(jqXHR);
                console.log(exception);
            }
        });
    }

    function makeVariationCarousel(variationData) {
        var variationImages = variationData.map(variation => variation.image);
        var variationSwatchImages = variationData.map((variation, idx) => {
            return variation.swatch_image || variationImages[idx];
        });
        var arrDupes = [];
        var variationSwatchImagesNew = variationSwatchImages.filter(function(
            item,
            index,
            self
        ) {
            if (self.indexOf(item) === index) {
                arrDupes.push(index);
            }
            return self.indexOf(item) === index;
        });
        var variationImagesNew = variationImages.filter(function(item, index) {
            return arrDupes.indexOf(index) >= 0;
        });
        // for( var i=0; i< arrDupes.length ; i++){
        //     variationImages.splice(i,1);
        // }
        var variationLinks = variationData.map(variation => variation.link);

        var $variationsCarousel = $product.find(".-variations-carousel");
        $variationsCarousel.empty();
        var carouselMainDiv = jQuery("<div/>", {
            class: "responsive"
        }).appendTo($variationsCarousel);

        variationSwatchImagesNew.forEach((img, idx) => {
            var responsiveImgDiv = jQuery("<div/>", {
                class: "mini-carousel-item"
            }).appendTo(carouselMainDiv);
            var anchor = jQuery("<a/>", {
                class: "responsive-img-a",
                "data-image": variationImagesNew[idx]
                    ? variationImagesNew[idx]
                    : ""
            }).appendTo(responsiveImgDiv);
            var responsiveImg = jQuery("<img/>", {
                class: "zoom-img carousel-img img-fluid",
                src: img,
                "data-zoom": img
            }).appendTo(anchor);

            arrFilters.forEach(filter => {
                anchor.attr(filter, variationData[idx][filter].value);
            });
        });
        multiCarouselFuncs.makeMultiCarousel(10, 10);
    }

    $(document).on("select-value-changed", function(e, changedElm) {
        $(".select-styled")
            .not(changedElm)
            .each(function() {
                if (
                    $(this).attr("active") == "" ||
                    $(this).attr("active") == "unselected-value"
                ) {
                    $(this).attr("active", "unselected-value");
                }
            });
        onFilterChange();
    });

    // $('body').on('click touchstart', '#closeMainImgBtn', function() {
    //     $('.prod-main-img').hide();
    // });

    // $('body').on('click touchstart', '.responsive-img-a', function() {
    //     $('#variationImg').attr('src', $(this).attr('data-image'));
    //     $('.prod-main-img').show();
    //     $('.select-styled').each(function() {
    //         $(this).attr('active', 'unselected-value');
    //     });

    //     if (isMobile()) {
    //         $('html, body')
    //             .delay(1000)
    //             .animate(
    //                 {
    //                     scrollTop: $(this).offset().top - 15
    //                 },
    //                 1000
    //             );
    //     }

    //     var triggerEl = document.querySelector('#variationImg');
    //     variationDrift.setZoomImageURL($(this).attr('data-image'));
    //     triggerEl.setAttribute('data-zoom', $(this).attr('data-image'));

    //     onSwatchChange(
    //         $(this)
    //             .find('.carousel-img')
    //             .attr('data-zoom')
    //     );
    // });

    function onFilterChange(swatchUrl = null) {
        var oQueryParams = new Object();
        $(".select-styled").each(function(idx) {
            // var strLabelText = $filtersDiv.find('label[for="'+$(this).attr('id')+'"]').attr('value');
            var currFilter = $(this).attr("active");
            if (currFilter != "unselected-value") {
                oQueryParams["attribute_" + (idx + 1)] = currFilter;
            }
        });
        fetchVariations(oQueryParams);
    }

    function onSwatchChange(swatchUrl) {
        var oQueryParams = new Object();
        var arrSwatchUrl = swatchUrl.split("//");
        var arrNewPathname = arrSwatchUrl[1].split("/");
        var newPathname = "";
        for (var i = 1; i < arrNewPathname.length; i++) {
            newPathname += "/";
            newPathname += arrNewPathname[i];
        }
        oQueryParams["swatch"] = decodeURIComponent(newPathname);
        fetchFilters(oQueryParams);
    }

    $("#filterToggleBtn").on("click", function() {
        $("#filtersDivMobile").toggle();
    });

    // $('body').on('click', '.wishlist-icon:not(.nav-link)', function(e) {
    //     e.preventDefault()
    //     if ($('#isLoggedIn').val() == 0) {
    //         $('#modalLoginForm').modal()
    //     } else {
    //         var iSku = $(this).attr('sku')
    //         callWishlistAPI($(this))
    //     }
    // })

    // function callWishlistAPI($elm) {
    //     var strApiToCall = ''
    //     if (!$elm.hasClass('marked')) {
    //         strApiToCall = FAV_MARK_API + $elm.attr('sku')
    //     } else {
    //         strApiToCall = FAV_UNMARK_API + $elm.attr('sku')
    //     }
    //     $.ajax({
    //         type: 'GET',
    //         url: strApiToCall,
    //         dataType: 'json',
    //         success: function(data) {
    //             console.log(data)
    //             if (!$elm.hasClass('marked')) {
    //                 $elm.addClass('marked')
    //             } else {
    //                 $elm.removeClass('marked')
    //             }
    //         },
    //         error: function(jqXHR, exception) {
    //             console.log(jqXHR)
    //             console.log(exception)
    //         }
    //     })
    // }
});
