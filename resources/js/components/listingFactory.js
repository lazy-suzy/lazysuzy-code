require("ion-rangeslider");
import makeSelectBox from "./custom-selectbox";
import isMobile from "../app.js";
import { FAV_MARK_API, FAV_UNMARK_API } from "../constants";

export default class ListingFactory {
    constructor(
        base_url,
        filterConfig,
        elements = {},
        listingTemplate,
        filterTemplate
    ) {
        const search = window.location.search.substring(1);
        const filterVars = search
            ? JSON.parse(
                  '{"' +
                      decodeURI(search)
                          .replace(/"/g, '\\"')
                          .replace(/&/g, '","')
                          .replace(/=/g, '":"') +
                      '"}'
              )
            : {};
        this.base_url = base_url;
        this.strFilters = filterVars.filters || "";
        this.iPageNo = parseInt(filterVars.iPageNo) || 0;
        this.iLimit = filterVars.iLimit;
        this.sortType = filterVars.sort_type || "";
        this.filterToIgnore = filterConfig.filterToIgnore;
        this.listingTemplate = listingTemplate || undefined;
        this.filterTemplate = filterTemplate || undefined;
        this.bFetchingProducts = false;
        this.bNoMoreProductsToShow = false;
        this.price_from = 0;
        this.price_to = 0;
        this.$productContainer =
            elements.$productContainer || $("#productsContainerDiv");
        this.$totalResults = elements.$totalResults || $("#totalResults");
        this.$filterContainer =
            elements.$filterContainer || $("#desktop-filters");
        this.$priceRangeSlider =
            elements.$priceRangeSlider || $("#priceRangeSlider");

        this.generateQueryString = this.generateQueryString.bind(this);
        this.fetchProducts = this.fetchProducts.bind(this);
        this.renderFilters = this.renderFilters.bind(this);
        this.updateFromToPrice = this.updateFromToPrice.bind(this);
        this.setSortType = this.setSortType.bind(this);
        this.resetListing = this.resetListing.bind(this);
    }

    setSEO(seo) {
        if (seo) {
            document.title = `${seo.page_title} | Lazysuzy`;
            $(".js-category-text").text(seo.email_title);
            $(".js-pageHeading").text(seo.page_title);
        }
    }

    isFilterApplied(filter, filterItems) {
        return filter === "price"
            ? Math.round(filterItems.from) !== Math.round(filterItems.min) ||
                  Math.round(filterItems.to) !== Math.round(filterItems.max)
            : filterItems.filter(filterItem => filterItem.checked).length > 0;
    }

    updateFromToPrice(price_from, price_to) {
        this.price_from = price_from;
        this.price_to = price_to;
    }

    setSortType(sort_type) {
        this.sortType = sort_type;
    }

    callWishlistAPI($elm) {
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

    renderFilters(filterData) {
        const _self = this;
        this.bNoMoreProductsToShow = false;
        this.$filterContainer.empty();
        for (var filter in filterData) {
            if (_self.filterToIgnore && filter === _self.filterToIgnore) {
                continue;
            }
            const filterItems = filterData[filter];
            const isPrice = filter === "price";
            (isPrice ||
                (filterItems &&
                    filterItems.length &&
                    filterItems.filter(item => item.enabled).length)) &&
                this.$filterContainer.append(
                    this.filterTemplate({
                        name: filter,
                        list: filterItems,
                        isPrice,
                        isApplied: this.isFilterApplied(filter, filterItems)
                    })
                );
            if (isPrice) {
                const data = filterData[filter];
                $("#priceRangeSlider").ionRangeSlider({
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
                        _self.updateFromToPrice(
                            $("#priceRangeSlider").data("from"),
                            $("#priceRangeSlider").data("to")
                        );
                        _self.resetListing();
                    },
                    onUpdate: function(data) {
                        // fired on changing slider with Update method
                    }
                });
            }
        }

        if (!isMobile()) {
            this.$filterContainer.append(
                '<a class="clearall-filter-btn" href="#" id="clearAllFiltersBtn">Clear All</a>'
            );
        }

        this.strFilters
            ? $(".clearall-filter-btn").show()
            : $(".clearall-filter-btn").hide();
    }

    generateQueryString() {
        let strFilters = "";
        const _self = this;
        $(".filter").each(function() {
            if ($(this).attr("id") === "priceFilter") {
                if (_self.price_from) {
                    strFilters += "price_from:" + _self.price_from + ";";
                }
                if (_self.price_to) {
                    strFilters += "price_to:" + _self.price_to + ";";
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
        this.strFilters = strFilters;
        return this.strFilters;
    }

    fetchProducts(bClearPrevProducts) {
        this.bFetchingProducts = true;
        const _self = this;
        var strLimit = this.iLimit === undefined ? "" : "&limit=" + this.iLimit;
        var filterQuery = `?filters=${this.strFilters}&sort_type=${this.sortType}&pageno=${this.iPageNo}${strLimit}`;
        var listingApiPath = this.base_url + filterQuery;

        history.pushState(
            {},
            "",
            window.location.protocol +
                "//" +
                window.location.host +
                window.location.pathname +
                filterQuery
        );

        if (this.iPageNo > 0 && !this.$productContainer.html().trim()) {
            var apiCall = [];
            for (var i = 0; i <= this.iPageNo; i++) {
                var filterQuery = `?filters=${this.strFilters}&sort_type=${this.sortType}&pageno=${i}${strLimit}`;
                var listingApiPath = this.base_url + filterQuery;
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
            this.iPageNo += 1;
        } else {
            this.iPageNo += 1;
            $.ajax({
                type: "GET",
                url: listingApiPath,
                dataType: "json",
                success: function(data) {
                    listingApiRendering(data);
                },
                error: function(jqXHR, exception) {
                    _self.bFetchingProducts = false;
                    console.log(jqXHR);
                    console.log(exception);
                }
            });
        }
        const listingApiRendering = function(data) {
            _self.bFetchingProducts = false;
            let totalResults = 0;
            if (!_self.$productContainer.html().trim()) {
                _self.setSEO(data.seo_data);
            }
            if (bClearPrevProducts) {
                _self.$productContainer.empty();
            }
            //$('#loaderImg').hide();
            if (data == null) {
                return;
            }
            if (data.products && data.products.length) {
                _self.bNoMoreProductsToShow = true;

                totalResults = data.total;
                _self.$totalResults.text(totalResults);

                var anchor = $("<a/>", {
                    href: "#page" + _self.iPageNo,
                    id: "#anchor-page" + _self.iPageNo
                }).appendTo("#productsContainerDiv");

                for (var product of data.products) {
                    product.percent_discount = Math.round(
                        product.percent_discount
                    );
                    product.discountClass =
                        product.percent_discount == 0
                            ? "d-none"
                            : product.percent_discount > 20
                            ? "_20"
                            : "";
                    Math.round(product.percent_discount);
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
                    product.variations = product.variations.map(variation => {
                        variation.swatch_image =
                            variation.swatch_image || variation.swatch || "";
                        if (variation.swatch_image !== "") {
                            product.showMoreVariations =
                                product.variations.length > 6;
                        }
                        return variation;
                    });

                    _self.$productContainer.append(
                        _self.listingTemplate(product)
                    );
                }
            } else {
                _self.bNoMoreProductsToShow = true;
                _self.iPageNo -= 1;
                $("#loaderImg").hide();
                $(".subscriber-container").removeClass("d-none");
                return;
            }
            if (data.filterData) {
                _self.renderFilters(data.filterData);
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
        };
    }

    resetListing() {
        this.iPageNo = 0;
        this.generateQueryString();
        this.fetchProducts(true);
    }
}
