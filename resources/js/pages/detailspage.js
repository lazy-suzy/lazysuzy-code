import * as multiCarouselFuncs from "../components/multi-carousel";
import makeSelectBox from "../components/custom-selectbox";
import makeHorizontalScroll from "../components/horizontal-scrollbar";
import Drift from "drift-zoom";
import isMobile from "../app.js";
require("slick-lightbox");
var md = require("markdown-it")({
    html: true,
    breaks: true
});

$(document).ready(function() {
    const PDP_API = "/api" + window.location.pathname;
    const VARIATION_API = "/api/variation" + window.location.pathname;
    const SWATCH_API = "/api/filters/variation" + window.location.pathname;
    const FAV_MARK_API = "/api/mark/favourite/";
    const FAV_UNMARK_API = "/api/unmark/favourite/";
    const $product = $("#detailPage");
    const $prodPriceCard = $product.find(".prod-price-card");
    var $filtersDiv = "";
    var $filtersDivMobile = "";
    var variationDrift = "";
    var variationImgEl = "";
    var arrFilters = [];

    $("#features")
        .find(".nav-link")
        .on("click", function() {
            if (!$("#collapseB").hasClass("show")) {
                $("#collapseB").collapse("toggle");
            }
        });
    $(".prod-desc").addClass("d-none");
    $.ajax({
        type: "GET",
        url: PDP_API,
        dataType: "json",
        success: function(data) {
            document.title = data.name + " | LazySuzy";
            var $imagesContainer = $product.find(".-images-container");
            var $images = $imagesContainer.find(".-images");
            var $variationsContainer = $product.find(".variation-options");
            var $swatchImages = $variationsContainer.find(".swatch-images");
            var imgContainerWidth = 0;
            $(".wishlist-icon").attr("sku", data.sku);
            if (data.wishlisted) {
                $(".wishlist-icon").addClass("marked");
            }
            data.on_server_images.forEach(img => {
                var lightbox = jQuery("<a/>", {
                    href: img,
                    // text: 'hello',
                    "data-caption": ""
                }).appendTo($images);
                var responsiveImg = jQuery("<img/>", {
                    class: "-prod-img img-fluid",
                    src: img,
                    alt: "product image"
                }).appendTo(lightbox);
            });
            $swatchImages.empty();
            $(".prod-desc.d-none").removeClass("d-none");
            if (data.variations && data.variations.length > 0) {
                $(".variation-container.d-none").removeClass("d-none");
                data.variations.forEach(img => {
                    var div = jQuery("<div/>", {
                        class: "single"
                    }).appendTo($swatchImages);
                    var a = jQuery("<a/>", {
                        href: img.link,
                        "data-caption": ""
                    }).appendTo(div);
                    if (img.swatch_image) {
                        var responsiveImg = jQuery("<img/>", {
                            class: "variant-img",
                            src: img.swatch_image,
                            alt: "product image",
                            "data-parent": img.has_parent_sku,
                            "data-image": img.image
                        }).appendTo(a);
                    } else {
                        $(".variation-container").addClass("d-none");
                    }
                });
            } else {
                $(".variation-container").addClass("d-none");
            }
            var $prodDetails = $("<div />", {
                class: "-product-details"
            }).appendTo($prodPriceCard);
            var site = $("<a/>", {
                text: data.name + " ",
                href: data.product_url,
                target: "_blank",
                class: " -name tr-productName"
            }).appendTo($prodDetails);

            var site = $("<div/>", {
                text: data.site + " "
            }).appendTo($prodDetails);
            site.append(
                ' <a target="_blank" class="rating-container float-right"><div class="rating "></div><span class="total-ratings"></span></a>'
            );
            var priceCont = $("<div/>").appendTo($prodDetails);
            $("<span/>", {
                text: " $" + data.is_price.replace("-", " - $"),
                class: "offer-price"
            }).appendTo(priceCont);
            var buyBtn = $("<a/>", {
                class: "btn pdp-buy-btn float-right tr-viewDetails",
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
            let detailsNavigator = data.department_info[0].category_url.split(
                "/"
            );
            $("<span/>", {
                class: "product-navigator",
                text: detailsNavigator[2] + " >"
            }).appendTo("#detailspageNavigator");
            $("<span/>", {
                class: "product-navigator",
                text: detailsNavigator[3] + " >"
            }).appendTo("#detailspageNavigator");
            $("<span/>", {
                class: "product-navigator",
                text: data.name
            }).appendTo("#detailspageNavigator");

            $("<div />", {
                class: "clearfix"
            }).appendTo($prodDetails);

            $("<div />", {
                class: "clearfix"
            }).appendTo($prodDetails);
            $filtersDivMobile = jQuery("<div/>", {
                id: "filtersDivMobile",
                class: "filters filters-mobile"
            }).insertBefore($imagesContainer);

            $filtersDiv = jQuery("<div/>", {
                id: "filtersDiv",
                class: "filters"
            }).appendTo($prodPriceCard);

            // if (data.variations != null) {
            //     makeVariationCarousel(data.variations)
            //     if (data.filters == null && $.isEmptyObject(data.filters)) {
            //         $('#filterToggleBtn').hide();
            //     }
            // } else {
            //     fetchVariations();
            // }

            //Product description

            var $desc = $product.find(".prod-desc");
            $desc.find(".-name");

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

            $desc.find(".rating-container").attr("href", data.product_url);
            $desc.find(".-desc").html(md.render(data.description.join("\n")));
            $desc.find(".-dimen").html(data.dimension);
            $("#descp").html(md.render(data.description.join("\n")));
            $("#dimen").html(data.dimension);

            // $desc.find('.-desc').html(md.render(data.description.join('\n')))
            // $desc.find('.-dimen').html(data.dimension)
            // $('#desc').html(data.description)

            var $featuresList = $desc.find(".-features");

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

            // data.features.forEach(feature => {
            //     var li = $('<li>', {
            //         html: feature
            //     }).appendTo($featuresList)
            // })

            $($featuresList)
                .clone()
                .appendTo("#feat");

            var $dimension = $desc.find(".-dimension");
            $dimension.empty();

            if (data.dimension !== null && data.dimension !== undefined) {
                data.dimension.forEach(dimension => {
                    dimensionList(dimension);
                });
            } else {
                return null;
            }
            function dimensionList(dimension) {
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
            }
            $($dimension)
                .clone()
                .appendTo("#dimen");
        },
        error: function(jqXHR, exception) {
            console.log(jqXHR);
            console.log(exception);
        }
    });
    // $('.-images').slick({
    //     infinite: true,
    //     slidesToShow: 3,
    //     slidesToScroll: 1,
    //     mobileFirst: true
    // })
    $(".-images").slickLightbox({
        itemSelector: "a",
        navigateByKeyboard: true,
        captionPosition: "dynamic",
        layouts: {
            closeButton:
                '<button type="button" class="slick-lightbox-close"></button>'
        }
    });

    $(document).on("click", ".variant-img", function(e) {
        $(".variant-img.active").removeClass("active");
        const has_parent = $(this).data("parent");
        const imgSrc = this.attributes["data-image"].value;
        if (!has_parent) {
            e.preventDefault();
            e.stopPropagation();
            $(".-images")
                .find("img:first")
                .remove();
            var prependImg = jQuery("<img/>", {
                class: "-prod-img img-fluid",
                src: imgSrc,
                alt: "product image"
            }).prependTo(".-images");
            $(this).addClass("active");
        }
        $(".-images").animate(
            { scrollLeft: $(".-prod-img").position().left },
            0
        );
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
                console.log(data);
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
        console.log(arrDupes);
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
    $(document).on("click", 'a[data-toggle="lightbox"]', function(event) {
        event.preventDefault();
        $(this).ekkoLightbox({ alwaysShowClose: true });
    });
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

    $("body").on("click touchstart", "#closeMainImgBtn", function() {
        $(".prod-main-img").hide();
    });

    $("#left-slide").on("click", function() {
        makeHorizontalScroll("left", "left-slide");
    });

    $("#right-slide").on("click", function() {
        makeHorizontalScroll("right", "right-slide");
    });

    $("body").on("click touchstart", ".responsive-img-a", function() {
        $("#variationImg").attr("src", $(this).attr("data-image"));
        $(".prod-main-img").show();
        $(".select-styled").each(function() {
            $(this).attr("active", "unselected-value");
        });

        if (isMobile()) {
            $("html, body")
                .delay(1000)
                .animate(
                    {
                        scrollTop: $(this).offset().top - 15
                    },
                    1000
                );
        }

        var triggerEl = document.querySelector("#variationImg");
        variationDrift.setZoomImageURL($(this).attr("data-image"));
        triggerEl.setAttribute("data-zoom", $(this).attr("data-image"));

        //    arrFilters.forEach(filter => {
        //        var filterId = 'selectbox-attr-'+filter;
        //        var filterValue = $(this).attr(filter);
        //        var strSelectedValue = $('#'+filterId).next().find('li[rel="'+filterValue+'"]').text();
        //        $('#'+filterId).text(strSelectedValue)
        //        $('#'+filterId).attr('active', filterValue);
        //    });
        onSwatchChange(
            $(this)
                .find(".carousel-img")
                .attr("data-zoom")
        );
    });

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

    $("body").on("click", ".wishlist-icon:not(.nav-link)", function(e) {
        e.preventDefault();
        if ($("#isLoggedIn").val() == 0) {
            $("#modalLoginForm").modal();
        } else {
            var iSku = $(this).attr("sku");
            callWishlistAPI($(this));
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
                console.log(data);
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
