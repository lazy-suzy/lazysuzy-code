(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["/mobile/js/listing"],{

/***/ "./resources/mobile/js/apis/listing-api.js":
/*!*************************************************!*\
  !*** ./resources/mobile/js/apis/listing-api.js ***!
  \*************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function($, jQuery) {/* harmony import */ var _components_multi_carousel__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../components/multi-carousel */ "./resources/mobile/js/components/multi-carousel.js");

/* harmony import */ var _components_custom_selectbox__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../components/custom-selectbox */ "./resources/mobile/js/components/custom-selectbox.js");
/* harmony import */ var _app_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../app.js */ "./resources/mobile/js/app.js");
/* WEBPACK VAR INJECTION */(function($, jQuery) {/* harmony import */ var 

function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance"); }

function _iterableToArray(iter) { if (Symbol.iterator in Object(iter) || Object.prototype.toString.call(iter) === "[object Arguments]") return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = new Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } }





var Handlebars = __webpack_require__(/*! handlebars */ "./node_modules/handlebars/dist/cjs/handlebars.js"); // import strItemsNumClass from '../pages/listing';
// import * as priceSliderContainer from '../pages/listing';


$(document).ready(function () {
  var LISTING_API_PATH = '/api' + location.pathname;
  var FAV_MARK_API = '/api/mark/favourite/';
  var FAV_UNMARK_API = '/api/unmark/favourite/';
  var PRODUCT_URL = '/product/';
  var totalResults = 0;
  var bFiltersCreated = false;
  var objGlobalFilterData;
  var search = window.location.search.substring(1);
  var source = document.getElementById('listing-template').innerHTML;
  var listingTemplate = Handlebars.compile(source);
  Handlebars.registerHelper('ifEq', function (v1, v2, options) {
    if (v1 === v2) {
      return options.fn(this);
    }

    return options.inverse(this);
  });
  Handlebars.registerHelper('ifNeq', function (v1, v2, options) {
    if (v1 !== v2) {
      return options.fn(this);
    }

    return options.inverse(this);
  });
  var queryObject = search ? JSON.parse('{"' + decodeURI(search).replace(/"/g, '\\"').replace(/&/g, '","').replace(/=/g, '":"') + '"}') : {};
  var strFilters = queryObject.filters || '';
  var strSortType = queryObject.sort_type || '';
  var iPageNo = parseInt(queryObject.pageno) || 0,
      iLimit;
  var price_from, price_to;
  var bNoMoreProductsToShow = false;
  var bFetchingProducts = false;
  $(window).scroll(function () {
    if (!bNoMoreProductsToShow) {
      if ($('#loaderImg') && isScrolledIntoView($('#loaderImg')[0])) {
        fetchProducts(false);
      } else if ($('#loaderImg') === null) {
        fetchProducts(false);
      }
    }
  });

  function isScrolledIntoView(el) {
    var rect = el.getBoundingClientRect();
    var elemTop = rect.top;
    var elemBottom = rect.bottom; // Only completely visible elements return true:

    var isVisible = elemTop >= 0 && elemBottom <= window.innerHeight; // Partially visible elements return true:
    //isVisible = elemTop < window.innerHeight && elemBottom >= 0;

    return isVisible;
  }

  function fetchProducts(bClearPrevProducts) {
    if (!bFetchingProducts) {
      bFetchingProducts = true;
      var strLimit = iLimit === undefined ? '' : '&limit=' + iLimit;
      var filterQuery = "?filters=".concat(strFilters, "&sort_type=").concat(strSortType, "&pageno=").concat(iPageNo).concat(strLimit);
      var listingApiPath = LISTING_API_PATH + filterQuery;
      history.pushState({}, '', window.location.protocol + '//' + window.location.host + window.location.pathname + filterQuery);
      $('#noProductsText').hide();

      if (iPageNo > 0 && !$('#productsContainerDiv').html().trim()) {
        var apiCall = [];

        for (var i = 0; i <= iPageNo; i++) {
          var filterQuery = "?filters=".concat(strFilters, "&sort_type=").concat(strSortType, "&pageno=").concat(i).concat(strLimit);
          var listingApiPath = LISTING_API_PATH + filterQuery;
          apiCall.push($.ajax({
            type: 'GET',
            url: listingApiPath,
            dataType: 'json'
          }));
        }

        var productsarry = [];
        $.when.apply(undefined, apiCall).then(function () {
          for (var _len = arguments.length, results = new Array(_len), _key = 0; _key < _len; _key++) {
            results[_key] = arguments[_key];
          }

          results.map(function (data) {
            productsarry = [].concat(_toConsumableArray(productsarry), _toConsumableArray(data[0].products));
          });
          results[0][0].products = productsarry;
          listingApiRendering(results[0][0]);
        });
        iPageNo += 1;
      } else {
        iPageNo += 1;
        $.ajax({
          type: 'GET',
          url: listingApiPath,
          dataType: 'json',
          success: function success(data) {
            listingApiRendering(data);
          },
          error: function error(jqXHR, exception) {
            bFetchingProducts = false;
            console.log(jqXHR);
            console.log(exception);
          }
        });
      }
    }

    window.listingApiRendering = function (data) {
      bFetchingProducts = false;

      if (bClearPrevProducts) {
        $('#productsContainerDiv').empty();
        totalResults = 0;
      } //$('#loaderImg').hide();


      if (data == null) {
        return;
      }

      if (data.products && data.products.length) {
        bNoMoreProductsToShow = true;
        totalResults = data.total;
        $('#totalResults').text(totalResults);
        var anchor = $('<a/>', {
          href: '#page' + iPageNo,
          id: '#anchor-page' + iPageNo
        }).appendTo('#productsContainerDiv');
        var _iteratorNormalCompletion = true;
        var _didIteratorError = false;
        var _iteratorError = undefined;

        try {
          for (var _iterator = data.products[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
            var product = _step.value;

            if (product.reviews != null && parseInt(product.reviews) != 0) {
              product.reviewExist = true;
              product.ratingClass = "rating-".concat(parseFloat(product.rating).toFixed(1).toString().replace('.', '_'));
            }

            $('#productsContainerDiv').append(listingTemplate(product));
          }
        } catch (err) {
          _didIteratorError = true;
          _iteratorError = err;
        } finally {
          try {
            if (!_iteratorNormalCompletion && _iterator["return"] != null) {
              _iterator["return"]();
            }
          } finally {
            if (_didIteratorError) {
              throw _iteratorError;
            }
          }
        }

        scrollToAnchor();
        _components_multi_carousel__WEBPACK_IMPORTED_MODULE_0__["makeMultiCarousel"]();
      } else {
        // if (!bClearPrevProducts) {
        bNoMoreProductsToShow = true;
        iPageNo -= 1;
        $('#noProductsText').show();
        $('#loaderImg').hide();
        return; // }
      }

      if (data.filterData) {
        objGlobalFilterData = data.filterData;
        createUpdateFilterData(data.filterData);
      }

      if (data.sortType) {
        $('#sort').empty();
        data.sortType.forEach(function (element) {
          var sortElm = jQuery('<option />', {
            value: element.value,
            selected: element.enabled,
            text: element.name
          }).appendTo('#sort');

          if (element.enabled) {
            strSortType = element.value;
          }
        });
        Object(_components_custom_selectbox__WEBPACK_IMPORTED_MODULE_1__["default"])();
      } //     $("#anchor-page"+iPageNo)[0].click()

    };
  }

  var mainProductDiv;

  function createProductDiv(productDetails) {
    //Make product main div
    mainProductDiv = jQuery('<div/>', {
      id: productDetails.id,
      sku: productDetails.sku,
      site: productDetails.site,
      "class": 'ls-product-div col-md-3 ' + strItemsNumClass
    }).appendTo;
    var productLink = jQuery('<a/>', {
      href: PRODUCT_URL + productDetails.sku,
      "class": 'product-detail-modal'
    }).appendTo(mainProductDiv);
    var product = jQuery('<div/>', {
      "class": 'ls-product'
    }).appendTo(productLink);

    if (productDetails.is_price.includes('-')) {
      var salepriceRange = productDetails.is_price.split('-');
      var saleprice = jQuery('<span />', {
        text: "$".concat(Math.round(salepriceRange[0]).toLocaleString(), " - $").concat(Math.round(salepriceRange[1]).toLocaleString()),
        "class": 'prod-sale-price d-md-none'
      }).appendTo(mainProductDiv);
    } else {
      var saleprice = jQuery('<span />', {
        text: "$".concat(Math.round(productDetails.is_price).toLocaleString()),
        "class": 'prod-sale-price d-md-none'
      }).appendTo(mainProductDiv);
    }

    if (Math.ceil(productDetails.percent_discount) > 0) {
      var discounttag = jQuery('<span />', {
        text: "".concat(Math.ceil(productDetails.percent_discount), "%"),
        "class": "prod-discount-tag d-md-none ".concat(productDetails.percent_discount >= 20 ? '_20' : '')
      }).appendTo(mainProductDiv);
    }

    jQuery('<img />', {
      "class": 'prod-img img-fluid',
      src: productDetails.main_image,
      alt: productDetails.name
    }).appendTo(product); //Product information

    var prodInfo = jQuery('<div/>', {
      "class": 'prod-info d-none d-md-block'
    }).appendTo(product);
    var catDetails = jQuery('<span/>', {
      "class": '-cat-name'
    }).appendTo(prodInfo);
    $(catDetails).text(productDetails.site);
    var prices = jQuery('<span/>', {
      "class": '-prices float-right'
    }).appendTo(prodInfo);
    var currPrice = jQuery('<span/>', {
      "class": '-cprice'
    }).appendTo(prices);
    $(currPrice).text('$' + productDetails.is_price);

    if (productDetails.is_price < productDetails.was_price) {
      var oldPrice = jQuery('<span/>', {
        "class": '-oldprice'
      }).appendTo(prices);
      $(oldPrice).text('$' + productDetails.was_price);
    }

    var strMarked = productDetails.wishlisted ? 'marked' : '';
    $(product).append('<div class="wishlist-icon ' + strMarked + '" sku=' + productDetails.sku + '><i class="far fa-heart -icon"></i></div>');
    var productInfoNext = jQuery('<div/>', {
      "class": 'd-none d-md-block'
    }).appendTo(mainProductDiv);
    $(productInfoNext).append('<div class="-name">' + productDetails.name + '</div>');
    var carouselMainDiv = jQuery('<div/>', {
      "class": 'responsive'
    }).appendTo(productInfoNext);
    var variationImages = productDetails.variations.map(function (variation) {
      return variation.image;
    });
    var variationSwatchImages = productDetails.variations.map(function (variation, idx) {
      if (productDetails.site !== 'Westelm') {
        return variation.swatch_image || variationImages[idx];
      } else {
        return variation.swatch_image;
      }
    });
    var variationLinks = productDetails.variations.map(function (variation) {
      return variation.link;
    });

    if (productDetails.main_image != null) {
      jQuery('<img />', {
        "class": 'variation-img img-fluid',
        src: productDetails.main_image,
        alt: 'variation-img'
      }).appendTo(product);
    }

    if (variationSwatchImages.length > 0) {
      variationSwatchImages.forEach(function (img, idx) {
        var responsiveImgDiv = jQuery('<div/>', {
          "class": 'mini-carousel-item'
        }).appendTo(carouselMainDiv);
        var anchor = jQuery('<a/>', {
          "class": 'responsive-img-a',
          href: variationLinks[idx]
        }).appendTo(responsiveImgDiv);
        var responsiveImg = jQuery('<img/>', {
          "class": 'carousel-img img-fluid',
          src: img,
          'data-prodimg': variationImages[idx]
        }).appendTo(anchor);
      });
    } else {
      carouselMainDiv.addClass('d-none');
    }

    if (productDetails.reviews != null && parseInt(productDetails.reviews) != 0) {
      var reviewValue = parseInt(productDetails.reviews);
      var ratingValue = parseFloat(productDetails.rating).toFixed(1);
      var ratingClass = ratingValue.toString().replace('.', '_');
      $(productInfoNext).append('<div class="rating-container"><div class="rating  rating-' + ratingClass + '"></div><span class="total-ratings">' + reviewValue + '</span></div>');
    }
  }

  function createUpdateFilterData(filterData) {
    bNoMoreProductsToShow = false;

    if (!bFiltersCreated) {
      bFiltersCreated = true;
      $('#filters').empty();
      var mobileFilterHeader = jQuery('<div/>', {
        "class": 'mobile-filter-header d-md-none'
      }).appendTo('#filters');
      jQuery('<span/>', {
        "class": 'float-left filters-close-btn',
        html: '<i class="fa fa-times" aria-hidden="true"></i>'
      }).appendTo(mobileFilterHeader);
      jQuery('<span/>', {
        "class": 'filter-title',
        text: 'Filters'
      }).appendTo(mobileFilterHeader);
      jQuery('<span/>', {
        "class": 'float-right',
        html: '<a class="btn clearall-filter-btn" href="#" id="clearAllFiltersBtn">Clear All</a>'
      }).appendTo(mobileFilterHeader);
      Object.keys(filterData).forEach(function (key, index) {
        var data = filterData[key];

        if (data.length == 0) {
          return;
        }

        var filterDiv = jQuery('<div/>', {
          "class": 'filter',
          'data-filter': key
        }).appendTo('#filters');
        $(filterDiv).append('<hr/>');
        $(filterDiv).append('<span class="filter-header">' + key.replace('_', ' ') + '</span>');
        $(filterDiv).append('<label for="' + key + '" class="clear-filter float-right">Clear</label>');

        if (key != 'price') {
          var filterUl = jQuery('<ul/>', {}).appendTo(filterDiv);
          data.forEach(function (element) {
            var filterLi = jQuery('<li/>', {}).appendTo(filterUl);
            var filterLabel = jQuery('<label/>', {
              "class": 'filter-label'
            }).appendTo(filterLi);
            var filterCheckbox = jQuery('<input />', {
              type: 'checkbox',
              checked: element.checked,
              value: element.value,
              disabled: !element.enabled,
              belongsTo: key
            }).appendTo(filterLabel);
            $(filterLabel).append('<span class="checkmark"></span>');
            $(filterLabel).append('<span class="text">' + element.name + '</span>');
          });
        } else {
          $(filterDiv).attr('id', 'price');
          var priceInput = jQuery('<input/>', {
            "class": 'price-range-slider',
            id: 'priceRangeSlider',
            name: 'price_range',
            value: ''
          }).appendTo(filterDiv); // $("#priceRangeSlider").change(function () {
          //     $("#priceInfo").find('.low').text($(this).attr('min'));
          //     $("#priceInfo").find('.high').text($(this).val());
          // });

          $priceRangeSlider = $('#priceRangeSlider');
          $priceRangeSlider.ionRangeSlider({
            skin: 'sharp',
            type: 'double',
            min: data.min ? data.min : 0,
            max: data.max ? data.max : 10000,
            from: data.from ? data.from : data.min,
            to: data.to ? data.to : data.max,
            prefix: '$',
            prettify_separator: ',',
            onStart: function onStart(data) {// fired then range slider is ready
            },
            onChange: function onChange(data) {// fired on every range slider update
            },
            onFinish: function onFinish(data) {
              // fired on pointer release
              var $inp = $('#priceRangeSlider');
              price_from = $inp.data('from'); // reading input data-from attribute

              price_to = $inp.data('to'); // reading input data-to attribute

              iPageNo = 0;
              updateFilters();
              fetchProducts(true);
            },
            onUpdate: function onUpdate(data) {// fired on changing slider with Update method
            }
          });
        }

        if (index == Object.keys(filterData).length - 1) {
          $(filterDiv).append('<hr/>');
        }
      }); // $(filterDiv).append('<hr/>');

      if (!Object(_app_js__WEBPACK_IMPORTED_MODULE_2__["default"])()) {
        $('#filters').append('<a class="btn clearall-filter-btn" href="#" id="clearAllFiltersBtn">Clear All</a>');
      } // $('#filters').append('<hr/>')

    } else {
      Object.keys(filterData).forEach(function (key, index) {
        var data = filterData[key];

        if (key != 'price') {
          data.forEach(function (element) {
            $('input[type="checkbox"][value=' + element.value + ']').attr('checked', element.checked);
            $('input[type="checkbox"][value=' + element.value + ']').attr('disabled', !element.enabled);
          });
        } else {
          var instance = $('#priceRangeSlider').data('ionRangeSlider');
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
    iPageNo == 1 ? $('html,body').scrollTop(0) : $('html,body').scrollTop(aTag.position().top);
  }

  $('body').on('click', '.clear-filter', function () {
    iPageNo = 0;
    var $filter = $(this).closest('.filter');

    if ($filter.attr('id') === 'price') {
      var $inp = $(this);
      price_from = $inp.data('from');
      price_to = $inp.data('to');
    } else {
      $filter.find('input[type="checkbox"]').each(function () {
        if (this.checked) {
          this.checked = false;
        }
      });
    }

    updateFilters();
    fetchProducts(true);
  });
  $('body').on('click', '#clearAllFiltersBtn', function () {
    iPageNo = 0;
    strFilters = '';
    $('.filter').each(function () {
      if ($(this).attr('id') === 'price') {
        var $inp = $(this);
        price_from = $inp.data('from');
        price_to = $inp.data('to');
      } else {
        $(this).find('input[type="checkbox"]').each(function () {
          if (this.checked) {
            this.checked = false;
          }
        });
      }
    });
    fetchProducts(true);
  });
  /***************Implementation of filter changes **************/

  $('body').on('change', '.filter input[type="checkbox"]', function () {
    iPageNo = 0;
    updateFilters();
    fetchProducts(true);
  });
  $(document).on('select-value-changed', function () {
    strSortType = $('#selectbox-sort').attr('active');
    iPageNo = 0;
    updateFilters();
    fetchProducts(true);
  });
  $('input[name="sort-price-filter"]').click(function () {
    strSortType = $('input[name="sort-price-filter"]:checked').val();
    iPageNo = 0;
    updateFilters();
    fetchProducts(true);
    $('#sort-mobile').toggleClass('show');
  });

  function updateFilters() {
    strFilters = '';
    $('.filter').each(function () {
      if ($(this).attr('id') === 'price') {
        if (price_from) {
          strFilters += 'price_from:' + price_from + ';';
        }

        if (price_to) {
          strFilters += 'price_to:' + price_to + ';';
        }
      } else {
        var currFilter = $(this).attr('data-filter');
        strFilters += currFilter + ':';
        var bFirstChecked = false;
        $(this).find('input[type="checkbox"]').each(function (idx) {
          if (this.checked) {
            var delim;

            if (!bFirstChecked) {
              delim = '';
              bFirstChecked = true;
            } else {
              delim = ',';
            }

            strFilters += delim + $(this).attr('value');
          }
        });
        strFilters += ';';
      }
    }); //  window.location.search = strFilters;
  }

  $('body').on('mouseover', '.slick-slide', function () {
    $(this).closest('.ls-product-div').find('.variation-img').attr('src', $(this).find('.carousel-img').attr('data-prodimg'));
    $(this).closest('.ls-product-div').find('.prod-img').css('visibility', 'hidden');
    $(this).closest('.ls-product-div').find('.variation-img').show();
  });
  $('body').on('mouseleave', '.slick-slide', function () {
    $(this).closest('.ls-product-div').find('.variation-img').hide();
    $(this).closest('.ls-product-div').find('.prod-img').css('visibility', 'unset');
  });
  $('body').on('click', '.dropdown-submenu a', function (e) {
    if (Object(_app_js__WEBPACK_IMPORTED_MODULE_2__["default"])()) {
      // early return if the parent has no hover-class
      if (!$(this).hasClass('hover')) return; // prevent click when delay is too small

      var delay = Date.now() - $(this).data('hovered');
      if (delay < 100) e.preventDefault();
    }
  });
  $('body').on('mouseover', '.dropdown-submenu a', function (e) {
    if (Object(_app_js__WEBPACK_IMPORTED_MODULE_2__["default"])()) {
      var time = Date.now();
      $(this).data('hovered', time);
    }
  });
  $('body').on('click', '.wishlist-icon:not(.nav-link)', function (e) {
    e.preventDefault();
    e.stopPropagation();

    if ($('#isLoggedIn').val() == 0) {
      $('#modalLoginForm').modal();
    } else {
      var iSku = $(this).attr('sku');
      callWishlistAPI($(this));
    }
  });

  function callWishlistAPI($elm) {
    var strApiToCall = '';

    if (!$elm.hasClass('marked')) {
      strApiToCall = FAV_MARK_API + $elm.attr('sku');
    } else {
      strApiToCall = FAV_UNMARK_API + $elm.attr('sku');
    }

    $.ajax({
      type: 'GET',
      url: strApiToCall,
      dataType: 'json',
      success: function success(data) {
        if (!$elm.hasClass('marked')) {
          $elm.addClass('marked');
        } else {
          $elm.removeClass('marked');
        }
      },
      error: function error(jqXHR, exception) {
        console.log(jqXHR);
        console.log(exception);
      }
    });
  }
});
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js"), __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js")))

/***/ }),

/***/ "./resources/mobile/js/app.js":
/*!************************************!*\
  !*** ./resources/mobile/js/app.js ***!
  \************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function($, jQuery) {/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return isMobile; });
__webpack_require__(/*! bootstrap */ "./node_modules/bootstrap/dist/js/bootstrap.js");

__webpack_require__(/*! slick-lightbox */ "./node_modules/slick-lightbox/dist/slick-lightbox.js");

__webpack_require__(/*! slick-carousel */ "./node_modules/slick-carousel/slick/slick.js");

__webpack_require__(/*! ./components/multi-carousel */ "./resources/mobile/js/components/multi-carousel.js");

__webpack_require__(/*! ./components/custom-selectbox */ "./resources/mobile/js/components/custom-selectbox.js");

var md = __webpack_require__(/*! markdown-it */ "./node_modules/markdown-it/index.js")({
  html: true,
  breaks: true
});

function slickLightboxcode() {
  $('.topcateLightBox').slick({
    infinite: true,
    slidesToShow: 3,
    slidesToScroll: 1,
    mobileFirst: true
  });
  $('.topcateLightBox').slickLightbox({
    itemSelector: 'a',
    navigateByKeyboard: true,
    captionPosition: 'dynamic',
    layouts: {
      closeButton: '<button type="button" class="slick-lightbox-close"></button>'
    }
  });
}

$(document).ready(function () {
  $('#departmentsNav').on('click', '.dropdown', function (e) {
    console.log('test'); // e.preventDefault()

    $(this).siblings().removeClass('active');
    $(this).addClass('active');
  });
  $('#searchbarHeader').submit(function (e) {
    callSearch(e, this);
  });
  $('.navbar-toggler').click(function () {
    $('#Sidenavbar').css('width', '300px');
  });
  $('.sb-body').submit(function (e) {
    callSearch(e, this);
  });
  $('#Sidenavbarclose').click(function () {
    $('#Sidenavbar').css('width', '0px');
  });
  $('.arrow').on('click', function (event) {
    $('.arrow-img').toggleClass('rotate');
    $('.arrow-img').toggleClass('rotate-reset');
  });
  $(document).on('click', '.collapsible', function () {
    $('.collapsible').removeClass('active');
    this.classList.toggle('active');
    $('.collapse').hide();
    $(this.getAttribute('data-target')).show();
  });

  function callSearch(e, elm) {
    e.preventDefault();
    window.location.href = '/search?query=' + $(elm).find('input').val(); //relative to domain
  }

  var $searchIcon = $('#searchIconMobile');
  var DEPT_API = '/api/all-departments';
  $searchIcon.on('click', function (e) {
    if ($(this).attr('id') == 'searchIconMobile') {
      if ($('#searchbarHeader').hasClass('open')) {
        $('#searchbarHeader').removeClass('open');
      } else {
        $('#searchbarHeader').addClass('open');
      }
    }
  });
  $('.user-login-modal').click(function () {
    $('#modalSignupForm').modal('toggle');
  });
  $('#register-modal').click(function () {
    $('#modalSignupForm').modal('toggle');
    $('#modalLoginForm').modal('toggle');
  });
  $('.user-login-modal1').click(function () {
    $('#modalSignupForm').modal('toggle');
    $('#modalLoginForm').modal('toggle');
  });
  $('.wishlist-login-modal').click(function () {
    $('#modalLoginForm').modal();
  });
  $('body').on('mouseover', '.dropdown-submenu', function (e) {
    var self = this;
    $('.dropdown-submenu').each(function () {
      if ($(this).find('.dropdown-menu')[0] != $(self).next('ul')[0]) {
        $(this).find('.dropdown-menu').hide();
      }
    });
    $(this).find('ul').toggle();

    if (!isMobile()) {
      $(this).find('.dropdown-menu').css('top', $(this).position().top);
    }
  });
  $('#madinah-carousel').carousel({
    interval: false,
    autoplay: false
  });
  $('#carouselTrending').carousel({
    interval: false,
    autoplay: false
  });
  $.ajax({
    type: 'GET',
    url: DEPT_API,
    dataType: 'json',
    success: function success(data) {
      var all_departments = data.all_departments,
          trending_categories = data.trending_categories,
          trending_products = data.trending_products;
      var $carouselInner = $('#carousel-inner');
      var $carouselInnertrend = $('#carousel-inner-trending');
      var deptToAppend = '';

      if (isMobile()) {
        trending_categories.map(function (item, index) {
          var $item = jQuery('<div/>', {
            "class": index == 0 ? 'carousel-item col-sm-12  active' : 'carousel-item col-sm-12'
          }).appendTo($carouselInner);
          var anchor = jQuery('<a/>', {
            href: item.link
          }).appendTo($item);
          var img = jQuery('<img/>', {
            src: "".concat(item.image),
            height: '150px'
          }).appendTo(anchor);
          var div = jQuery('<div/>', {
            "class": 'col-sm-12'
          }).appendTo(anchor);
          var span = jQuery('<span/>', {
            html: "".concat(item.category),
            "class": 'top-trending-text text-center'
          }).appendTo(div);
          var li = jQuery('<li/>', {
            'data-target': '#madinah-carousel',
            'data-slide-to': index,
            "class": index == 0 ? 'active' : ''
          }).appendTo('#madinahcarouselindicator'); // slickLightboxcode()
        });
        slickLightboxcode();
        trending_products.map(function (item, index) {
          var $item = jQuery('<div/>', {
            "class": index == 0 ? 'carousel-item col-sm-12  active' : 'carousel-item col-sm-12'
          }).appendTo($carouselInnertrend);
          var lightbox = jQuery('<a/>', {
            "class": 'light-box',
            href: item.image,
            'data-caption': ''
          }).appendTo($item);
          var img = jQuery('<img/>', {
            src: "".concat(item.main_image)
          }).appendTo(lightbox);
          var div = jQuery('<div/>', {
            html: "".concat(item.site),
            "class": 'top-trending-site text-center'
          }).appendTo($item);
          var nameLink = jQuery('<a/>', {
            target: '_blank',
            href: item.product_url
          }).appendTo($item);
          var div = jQuery('<h3/>', {
            html: "".concat(item.name),
            "class": 'top-trending-text text-center'
          }).appendTo(nameLink);
          var priceLink = jQuery('<a/>', {
            target: '_blank',
            href: item.product_url
          }).appendTo($item);
          var pricediv = jQuery('<div/>', {
            "class": 'prod-price-div'
          }).appendTo(priceLink);
          var li = jQuery('<li/>', {
            'data-target': '#carouselTrending',
            'data-slide-to': index,
            "class": index == 0 ? 'active' : ''
          }).appendTo('#toptrendingindicator');

          if (item.is_price.includes('-')) {
            var salepriceRange = item.is_price.split('-');
            var saleprice = jQuery('<span />', {
              text: "$".concat(Math.round(salepriceRange[0]).toLocaleString(), " - $").concat(Math.round(salepriceRange[1]).toLocaleString()),
              "class": 'prod-sale-price d-md-none'
            }).appendTo(pricediv);
          } else {
            var saleprice = jQuery('<span />', {
              text: "$".concat(Math.round(item.is_price).toLocaleString()),
              "class": 'prod-sale-price d-md-none'
            }).appendTo(pricediv);
          }

          if (item.was_price !== item.is_price) {
            if (item.was_price.includes('-')) {
              var _salepriceRange = item.was_price.split('-');

              var saleprice = jQuery('<span />', {
                text: "$".concat(Math.round(_salepriceRange[0]).toLocaleString(), " - $").concat(Math.round(_salepriceRange[1]).toLocaleString()),
                "class": 'prod-was-price d-md-none'
              }).appendTo(pricediv);
            } else {
              var saleprice = jQuery('<span />', {
                text: "$".concat(Math.round(item.was_price).toLocaleString()),
                "class": 'prod-was-price d-md-none'
              }).appendTo(pricediv);
            }
          }

          var collapseBtnDiv = jQuery('<div/>', {
            "class": 'collapse-btn'
          }).appendTo($item);
          var collapseBtn = jQuery('<a/>', {
            "class": 'load-more-button collapsed',
            'data-toggle': 'collapse',
            href: '#collapseExample' + index + '',
            role: 'button',
            'aria-expanded': 'false',
            'aria-controls': 'collapseExample'
          }).appendTo(collapseBtnDiv);
          var loadMore = jQuery('<span/>', {
            "class": 'more',
            html: '<i class="fa fa-angle-down" aria-hidden="true"></i> More '
          }).appendTo(collapseBtn);
          var loadLess = jQuery('<span/>', {
            "class": 'less',
            html: '<i class="fa fa-angle-up" aria-hidden="true"></i> Less '
          }).appendTo(collapseBtn);
          var collapseMainDiv = jQuery('<div/>', {
            "class": 'collapse',
            id: 'collapseExample' + index + ''
          }).appendTo($item);
          var collapseInnerDiv = jQuery('<div/>', {
            "class": 'class-body top-trending-text text-center',
            html: md.render(item.description.join('\n'))
          }).appendTo(collapseMainDiv);
        }); // $('#carouselTrending').slick({
        //     infinite: true,
        //     slidesToShow: 3,
        //     slidesToScroll: 1,
        //     mobileFirst: true
        // })
        // $('#carouselTrending').slick({
        //     itemSelector: 'a',
        //     navigateByKeyboard: true,
        //     captionPosition: 'dynamic',
        //     layouts: {
        //         closeButton:
        //             '<button type="button" class="slick-lightbox-close"></button>'
        //     }
        // })

        $('#collapsible-dept').empty();
        var deptToAppend = '';

        for (var i = 0; i < all_departments.length; i++) {
          if (all_departments[i].categories.length == 0) {
            deptToAppend += '<li class="department"><a class="link collapsible" href="' + all_departments[i].link + '">' + all_departments[i].department + '</a></li>';
          } else {
            deptToAppend += '<li class="department"><a  class="collapsible" data-toggle="collapse" data-target="#' + all_departments[i].department + '"><span class="link">' + all_departments[i].department + '</span><span  class="side-nav-icon" id="navbarDropdown' + i + '"><i class="fas fa-angle-right arrow"></i></span></a>';
            var catgToAppend = '<ul class="collapse category-list" aria-labelledby="navbarDropdown" id="' + all_departments[i].department + '">';

            for (var j = 0; j < all_departments[i].categories.length; j++) {
              catgToAppend += '<li><a class="link" href="' + all_departments[i].categories[j].link + '">' + all_departments[i].categories[j].category + '</a></li>';
            }

            catgToAppend += '</ul>';
            deptToAppend += catgToAppend;
            deptToAppend += '</li>';
          }
        }

        $('#collapsible-dept').html(deptToAppend);
        var singleDeptMobile = '';

        for (var i = 0; i < all_departments.length; i++) {
          if (all_departments.length != 0) {
            singleDeptMobile = '<div class="col-sm-4  -dept "><a  href="' + all_departments[i].link + '">' + all_departments[i].department + '</a></div>';
          }

          $('#mobileDepartments').append(singleDeptMobile);
        }
      }

      for (var i = 0; i < all_departments.length; i++) {
        if (all_departments[i].categories.length == 0) {
          deptToAppend += '<li><a href="' + all_departments[i].link + '">' + all_departments[i].department + '</a></li>';
        } else {
          var classActive = all_departments[i].link === location.pathname ? 'active' : '';
          deptToAppend += '<li class="dropdown ' + classActive + '"><a  href="' + all_departments[i].link + '" id="navbarDropdown' + i + '" role="button"  aria-haspopup="true" aria-expanded="false">' + all_departments[i].department + '</a>';
          var catgToAppend = '<ul class="dropdown-menu" aria-labelledby="navbarDropdown">';

          for (var j = 0; j < all_departments[i].categories.length; j++) {
            // if (all_departments[i].categories[j].sub_categories.length == 0) {
            catgToAppend += '<li><a href="' + all_departments[i].categories[j].link + '">' + all_departments[i].categories[j].category + '</a></li>'; // }
            // else {
            //   catgToAppend += '<li class="dropdown-submenu">';
            //   catgToAppend += '<a href="'+all_departments[i].categories[j].link+'">' + all_departments[i].categories[j].category + '<span class="mx-2"><i class="fas fa-angle-right"></i></span>';
            //   var subcatToAppend = '<ul class="dropdown-menu">';
            //   for (k = 0; k < all_departments[i].categories[j].sub_categories.length; k++) {
            //     subcatToAppend += '<li><a href="' + all_departments[i].categories[j].sub_categories[k].link + '">' + all_departments[i].categories[j].sub_categories[k].sub_category + '</a></li>'
            //   }
            //   subcatToAppend += '</ul>';
            //   catgToAppend += subcatToAppend;
            //   catgToAppend += '</li>';
            // }
          }

          catgToAppend += '</ul>';
          deptToAppend += catgToAppend;
          deptToAppend += '</li>';
        }
      }

      $('#departmentsNav').append(deptToAppend);
    },
    error: function error(jqXHR, exception) {
      console.log(jqXHR);
      console.log(exception);
    }
  });
});
function isMobile() {
  var isMobile = window.matchMedia('only screen and (max-width: 768px)');
  return isMobile.matches ? true : false;
}
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js"), __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js")))

/***/ }),

/***/ "./resources/mobile/js/components/custom-selectbox.js":
/*!************************************************************!*\
  !*** ./resources/mobile/js/components/custom-selectbox.js ***!
  \************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function($) {/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return makeSelectBox; });
/*
Reference: http://jsfiddle.net/BB3JK/47/
*/
function makeSelectBox() {
  $('select').each(function () {
    var $this = $(this),
        numberOfOptions = $(this).children('option').length; //Remove previously made selectbox

    $('#selectbox-' + $this.attr('id')).remove();
    $this.addClass('select-hidden');
    $this.wrap('<div class="select"></div>');
    $this.after('<div class="select-styled" id="selectbox-' + $this.attr('id') + '"></div>');
    var $styledSelect = $this.next('div.select-styled');
    var strSelectedText = $(this).children('option:selected') ? $(this).children('option:selected').text() : $this.children('option:selected').eq(0).text();
    var strSelectedValue = $(this).children('option:selected') ? $(this).children('option:selected').attr('value') : $this.children('option:selected').eq(0).attr('value');
    $styledSelect.text(strSelectedText);
    $styledSelect.attr('active', strSelectedValue);
    var $list = $('<ul />', {
      "class": 'select-options'
    }).insertAfter($styledSelect);

    for (var i = 0; i < numberOfOptions; i++) {
      $('<li />', {
        text: $this.children('option').eq(i).text(),
        rel: $this.children('option').eq(i).val()
      }).appendTo($list);
    }

    var $listItems = $list.children('li');
    $styledSelect.click(function (e) {
      e.stopPropagation();
      $('div.select-styled.active').not(this).each(function () {
        $(this).removeClass('active').next('ul.select-options').hide();
      });
      $(this).toggleClass('active').next('ul.select-options').toggle();
    });
    $listItems.click(function (e) {
      e.stopPropagation();
      $styledSelect.text($(this).text()).removeClass('active');
      var strSelectedValue = $(this).attr('rel');
      $styledSelect.attr('active', strSelectedValue);
      $(document).trigger('select-value-changed', $styledSelect);
      $this.val($(this).attr('rel'));
      $list.hide(); //console.log($this.val());
    });
    $(document).click(function () {
      $styledSelect.removeClass('active');
      $list.hide();
    });
  });
}
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js")))

/***/ }),

/***/ "./resources/mobile/js/components/multi-carousel.js":
/*!**********************************************************!*\
  !*** ./resources/mobile/js/components/multi-carousel.js ***!
  \**********************************************************/
/*! exports provided: makeMultiCarousel */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function($) {/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "makeMultiCarousel", function() { return makeMultiCarousel; });
function makeMultiCarousel() {
  var slidesShow = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 4;
  var slidesScroll = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 4;
  $('.responsive:not(.slick-slider)').slick({
    infinite: false,
    speed: 300,
    slidesToShow: slidesShow,
    slidesToScroll: slidesScroll,
    arrows: true,
    // centerMode: true,
    responsive: [{
      breakpoint: 1024,
      settings: {
        slidesToShow: 4,
        slidesToScroll: 4
      }
    }, {
      breakpoint: 600,
      settings: {
        slidesToShow: 3,
        slidesToScroll: 3
      }
    }, {
      breakpoint: 480,
      settings: {
        slidesToShow: 3,
        slidesToScroll: 3
      }
    } // You can unslick at a given breakpoint now by adding:
    // settings: "unslick"
    // instead of a settings object
    ]
  });
}
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js")))

/***/ }),

/***/ "./resources/mobile/js/pages/listing.js":
/*!**********************************************!*\
  !*** ./resources/mobile/js/pages/listing.js ***!
  \**********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function($) {__webpack_require__(/*! ion-rangeslider */ "./node_modules/ion-rangeslider/js/ion.rangeSlider.js");

window.GLOBAL_LISTING_API_PATH = '';

__webpack_require__(/*! ../apis/listing-api */ "./resources/mobile/js/apis/listing-api.js");

$(document).ready(function () {
  var iItemsToShow = 2;
  strItemsNumClass = 'item-2';
  $('#priceRangeSlider').change(function () {
    $('#priceInfo').find('.low').text($(this).attr('min'));
    $('#priceInfo').find('.high').text($(this).val());
  });
  $priceRangeSlider = $('#priceRangeSlider');
  $priceRangeSlider.ionRangeSlider({
    skin: 'sharp',
    type: 'double',
    min: 100,
    max: 5000,
    from: 500,
    to: 2500,
    prefix: '$',
    prettify_separator: ','
  }); // var priceSlider = priceSliderContainer.$priceRangeSlider;
  // $('body').on('change', '.price-range-slider', function () {
  //     var $inp = $(this);
  //     var from = $inp.prop("value"); // reading input value
  //     var from2 = $inp.data("from"); // reading input data-from attribute
  //     console.log(from, from2); // FROM value
  // });
  //Top button

  $('.top-button').click(function () {
    window.scrollTo(0, 0);
    $('html, body').animate({
      scrollTop: 0
    }, 800);
  });
  $('#filterToggleBtn').click(function () {
    $('#filters').toggleClass('show');
    $('#sort-mobile').hasClass('show') ? $('#sort-mobile').removeClass('show') : '';
  });
  $('#selectbox-sortmobile').click(function () {
    $('#sort-mobile').toggleClass('show');
    $('#filters').removeClass('show');
  });
  $('#viewItemsBtn').click(function () {
    iItemsToShow = iItemsToShow == 1 ? 3 : iItemsToShow - 1; // if (iItemsToShow !== 1) {
    //     $('#viewItemsBtn')
    //         .children('i')
    //         .removeClass()
    //     $('#viewItemsBtn')
    //         .children('i')
    //         .addClass('fas fa-th-list')
    // } else {
    //     $('#viewItemsBtn')
    //         .children('i')
    //         .removeClass()
    //     $('#viewItemsBtn')
    //         .children('i')
    //         .addClass('fab fa-buromobelexperte')
    // }

    if (iItemsToShow == 3) {
      $('.prod-sale-price').addClass('d-none');
    } else {
      $('.prod-sale-price').removeClass('d-none');
    }

    $('#productsContainerDiv').find('.ls-product-div').each(function () {
      $(this).removeClass(function (index, className) {
        return (className.match(/(^|\s)item-\S+/g) || []).join(' ');
      });
      strItemsNumClass = 'item-' + iItemsToShow;
      $(this).addClass(strItemsNumClass);
    });
  }); //close-btn-filter

  $(document).on('click', '.filters-close-btn', function (e) {
    $('#filters').hasClass('show') ? $('#filters').removeClass('show') : $('#sort-mobile').hasClass('show') ? $('#sort-mobile').removeClass('show') : '';
  });
  $(window).scroll(function (event) {
    if ($(window).scrollTop() > 50) {
      $('.filter-toggle-mobile').addClass('fix-search');
      $('.filters').addClass('fix-search-filter');
    } else {
      $('.filter-toggle-mobile').removeClass('fix-search');
      $('.filters').removeClass('fix-search-filter');
    }
  });
  $('.dropdown-menu a.dropdown-toggle').on('click', function (e) {
    if (!$(this).next().hasClass('show')) {
      $(this).parents('.dropdown-menu').first().find('.show').removeClass('show');
    }

    var $subMenu = $(this).next('.dropdown-menu');
    $subMenu.toggleClass('show');
    $('ul a[href^="/' + location.pathname.split('/')[1] + '"]').addClass('active');
    $(this).parents('li.nav-item.dropdown.show').on('hidden.bs.dropdown', function (e) {
      $('.dropdown-submenu .show').removeClass('show');
    });
    return false;
  });
});
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js")))

/***/ }),

/***/ 6:
/*!****************************************************!*\
  !*** multi ./resources/mobile/js/pages/listing.js ***!
  \****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(/*! /Users/himanshujain/Documents/Himanshu/projects/lazysuzy/lazysuzy-code/resources/mobile/js/pages/listing.js */"./resources/mobile/js/pages/listing.js");


/***/ })

},[[6,"/mobile/js/manifest","/mobile/js/vendor"]]]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9yZXNvdXJjZXMvbW9iaWxlL2pzL2FwaXMvbGlzdGluZy1hcGkuanMiLCJ3ZWJwYWNrOi8vLy4vcmVzb3VyY2VzL21vYmlsZS9qcy9hcHAuanMiLCJ3ZWJwYWNrOi8vLy4vcmVzb3VyY2VzL21vYmlsZS9qcy9jb21wb25lbnRzL2N1c3RvbS1zZWxlY3Rib3guanMiLCJ3ZWJwYWNrOi8vLy4vcmVzb3VyY2VzL21vYmlsZS9qcy9jb21wb25lbnRzL211bHRpLWNhcm91c2VsLmpzIiwid2VicGFjazovLy8uL3Jlc291cmNlcy9tb2JpbGUvanMvcGFnZXMvbGlzdGluZy5qcyJdLCJuYW1lcyI6WyJIYW5kbGViYXJzIiwicmVxdWlyZSIsIiQiLCJkb2N1bWVudCIsInJlYWR5IiwiTElTVElOR19BUElfUEFUSCIsImxvY2F0aW9uIiwicGF0aG5hbWUiLCJGQVZfTUFSS19BUEkiLCJGQVZfVU5NQVJLX0FQSSIsIlBST0RVQ1RfVVJMIiwidG90YWxSZXN1bHRzIiwiYkZpbHRlcnNDcmVhdGVkIiwib2JqR2xvYmFsRmlsdGVyRGF0YSIsInNlYXJjaCIsIndpbmRvdyIsInN1YnN0cmluZyIsInNvdXJjZSIsImdldEVsZW1lbnRCeUlkIiwiaW5uZXJIVE1MIiwibGlzdGluZ1RlbXBsYXRlIiwiY29tcGlsZSIsInJlZ2lzdGVySGVscGVyIiwidjEiLCJ2MiIsIm9wdGlvbnMiLCJmbiIsImludmVyc2UiLCJxdWVyeU9iamVjdCIsIkpTT04iLCJwYXJzZSIsImRlY29kZVVSSSIsInJlcGxhY2UiLCJzdHJGaWx0ZXJzIiwiZmlsdGVycyIsInN0clNvcnRUeXBlIiwic29ydF90eXBlIiwiaVBhZ2VObyIsInBhcnNlSW50IiwicGFnZW5vIiwiaUxpbWl0IiwicHJpY2VfZnJvbSIsInByaWNlX3RvIiwiYk5vTW9yZVByb2R1Y3RzVG9TaG93IiwiYkZldGNoaW5nUHJvZHVjdHMiLCJzY3JvbGwiLCJpc1Njcm9sbGVkSW50b1ZpZXciLCJmZXRjaFByb2R1Y3RzIiwiZWwiLCJyZWN0IiwiZ2V0Qm91bmRpbmdDbGllbnRSZWN0IiwiZWxlbVRvcCIsInRvcCIsImVsZW1Cb3R0b20iLCJib3R0b20iLCJpc1Zpc2libGUiLCJpbm5lckhlaWdodCIsImJDbGVhclByZXZQcm9kdWN0cyIsInN0ckxpbWl0IiwidW5kZWZpbmVkIiwiZmlsdGVyUXVlcnkiLCJsaXN0aW5nQXBpUGF0aCIsImhpc3RvcnkiLCJwdXNoU3RhdGUiLCJwcm90b2NvbCIsImhvc3QiLCJoaWRlIiwiaHRtbCIsInRyaW0iLCJhcGlDYWxsIiwiaSIsInB1c2giLCJhamF4IiwidHlwZSIsInVybCIsImRhdGFUeXBlIiwicHJvZHVjdHNhcnJ5Iiwid2hlbiIsImFwcGx5IiwidGhlbiIsInJlc3VsdHMiLCJtYXAiLCJkYXRhIiwicHJvZHVjdHMiLCJsaXN0aW5nQXBpUmVuZGVyaW5nIiwic3VjY2VzcyIsImVycm9yIiwianFYSFIiLCJleGNlcHRpb24iLCJjb25zb2xlIiwibG9nIiwiZW1wdHkiLCJsZW5ndGgiLCJ0b3RhbCIsInRleHQiLCJhbmNob3IiLCJocmVmIiwiaWQiLCJhcHBlbmRUbyIsInByb2R1Y3QiLCJyZXZpZXdzIiwicmV2aWV3RXhpc3QiLCJyYXRpbmdDbGFzcyIsInBhcnNlRmxvYXQiLCJyYXRpbmciLCJ0b0ZpeGVkIiwidG9TdHJpbmciLCJhcHBlbmQiLCJzY3JvbGxUb0FuY2hvciIsIm11bHRpQ2Fyb3VzZWxGdW5jcyIsInNob3ciLCJmaWx0ZXJEYXRhIiwiY3JlYXRlVXBkYXRlRmlsdGVyRGF0YSIsInNvcnRUeXBlIiwiZm9yRWFjaCIsImVsZW1lbnQiLCJzb3J0RWxtIiwialF1ZXJ5IiwidmFsdWUiLCJzZWxlY3RlZCIsImVuYWJsZWQiLCJuYW1lIiwibWFrZVNlbGVjdEJveCIsIm1haW5Qcm9kdWN0RGl2IiwiY3JlYXRlUHJvZHVjdERpdiIsInByb2R1Y3REZXRhaWxzIiwic2t1Iiwic2l0ZSIsInN0ckl0ZW1zTnVtQ2xhc3MiLCJwcm9kdWN0TGluayIsImlzX3ByaWNlIiwiaW5jbHVkZXMiLCJzYWxlcHJpY2VSYW5nZSIsInNwbGl0Iiwic2FsZXByaWNlIiwiTWF0aCIsInJvdW5kIiwidG9Mb2NhbGVTdHJpbmciLCJjZWlsIiwicGVyY2VudF9kaXNjb3VudCIsImRpc2NvdW50dGFnIiwic3JjIiwibWFpbl9pbWFnZSIsImFsdCIsInByb2RJbmZvIiwiY2F0RGV0YWlscyIsInByaWNlcyIsImN1cnJQcmljZSIsIndhc19wcmljZSIsIm9sZFByaWNlIiwic3RyTWFya2VkIiwid2lzaGxpc3RlZCIsInByb2R1Y3RJbmZvTmV4dCIsImNhcm91c2VsTWFpbkRpdiIsInZhcmlhdGlvbkltYWdlcyIsInZhcmlhdGlvbnMiLCJ2YXJpYXRpb24iLCJpbWFnZSIsInZhcmlhdGlvblN3YXRjaEltYWdlcyIsImlkeCIsInN3YXRjaF9pbWFnZSIsInZhcmlhdGlvbkxpbmtzIiwibGluayIsImltZyIsInJlc3BvbnNpdmVJbWdEaXYiLCJyZXNwb25zaXZlSW1nIiwiYWRkQ2xhc3MiLCJyZXZpZXdWYWx1ZSIsInJhdGluZ1ZhbHVlIiwibW9iaWxlRmlsdGVySGVhZGVyIiwiT2JqZWN0Iiwia2V5cyIsImtleSIsImluZGV4IiwiZmlsdGVyRGl2IiwiZmlsdGVyVWwiLCJmaWx0ZXJMaSIsImZpbHRlckxhYmVsIiwiZmlsdGVyQ2hlY2tib3giLCJjaGVja2VkIiwiZGlzYWJsZWQiLCJiZWxvbmdzVG8iLCJhdHRyIiwicHJpY2VJbnB1dCIsIiRwcmljZVJhbmdlU2xpZGVyIiwiaW9uUmFuZ2VTbGlkZXIiLCJza2luIiwibWluIiwibWF4IiwiZnJvbSIsInRvIiwicHJlZml4IiwicHJldHRpZnlfc2VwYXJhdG9yIiwib25TdGFydCIsIm9uQ2hhbmdlIiwib25GaW5pc2giLCIkaW5wIiwidXBkYXRlRmlsdGVycyIsIm9uVXBkYXRlIiwiaXNNb2JpbGUiLCJpbnN0YW5jZSIsInVwZGF0ZSIsImFUYWciLCJzY3JvbGxUb3AiLCJwb3NpdGlvbiIsIm9uIiwiJGZpbHRlciIsImNsb3Nlc3QiLCJmaW5kIiwiZWFjaCIsImNsaWNrIiwidmFsIiwidG9nZ2xlQ2xhc3MiLCJjdXJyRmlsdGVyIiwiYkZpcnN0Q2hlY2tlZCIsImRlbGltIiwiY3NzIiwiZSIsImhhc0NsYXNzIiwiZGVsYXkiLCJEYXRlIiwibm93IiwicHJldmVudERlZmF1bHQiLCJ0aW1lIiwic3RvcFByb3BhZ2F0aW9uIiwibW9kYWwiLCJpU2t1IiwiY2FsbFdpc2hsaXN0QVBJIiwiJGVsbSIsInN0ckFwaVRvQ2FsbCIsInJlbW92ZUNsYXNzIiwibWQiLCJicmVha3MiLCJzbGlja0xpZ2h0Ym94Y29kZSIsInNsaWNrIiwiaW5maW5pdGUiLCJzbGlkZXNUb1Nob3ciLCJzbGlkZXNUb1Njcm9sbCIsIm1vYmlsZUZpcnN0Iiwic2xpY2tMaWdodGJveCIsIml0ZW1TZWxlY3RvciIsIm5hdmlnYXRlQnlLZXlib2FyZCIsImNhcHRpb25Qb3NpdGlvbiIsImxheW91dHMiLCJjbG9zZUJ1dHRvbiIsInNpYmxpbmdzIiwic3VibWl0IiwiY2FsbFNlYXJjaCIsImV2ZW50IiwiY2xhc3NMaXN0IiwidG9nZ2xlIiwiZ2V0QXR0cmlidXRlIiwiZWxtIiwiJHNlYXJjaEljb24iLCJERVBUX0FQSSIsInNlbGYiLCJuZXh0IiwiY2Fyb3VzZWwiLCJpbnRlcnZhbCIsImF1dG9wbGF5IiwiYWxsX2RlcGFydG1lbnRzIiwidHJlbmRpbmdfY2F0ZWdvcmllcyIsInRyZW5kaW5nX3Byb2R1Y3RzIiwiJGNhcm91c2VsSW5uZXIiLCIkY2Fyb3VzZWxJbm5lcnRyZW5kIiwiZGVwdFRvQXBwZW5kIiwiaXRlbSIsIiRpdGVtIiwiaGVpZ2h0IiwiZGl2Iiwic3BhbiIsImNhdGVnb3J5IiwibGkiLCJsaWdodGJveCIsIm5hbWVMaW5rIiwidGFyZ2V0IiwicHJvZHVjdF91cmwiLCJwcmljZUxpbmsiLCJwcmljZWRpdiIsImNvbGxhcHNlQnRuRGl2IiwiY29sbGFwc2VCdG4iLCJyb2xlIiwibG9hZE1vcmUiLCJsb2FkTGVzcyIsImNvbGxhcHNlTWFpbkRpdiIsImNvbGxhcHNlSW5uZXJEaXYiLCJyZW5kZXIiLCJkZXNjcmlwdGlvbiIsImpvaW4iLCJjYXRlZ29yaWVzIiwiZGVwYXJ0bWVudCIsImNhdGdUb0FwcGVuZCIsImoiLCJzaW5nbGVEZXB0TW9iaWxlIiwiY2xhc3NBY3RpdmUiLCJtYXRjaE1lZGlhIiwibWF0Y2hlcyIsIiR0aGlzIiwibnVtYmVyT2ZPcHRpb25zIiwiY2hpbGRyZW4iLCJyZW1vdmUiLCJ3cmFwIiwiYWZ0ZXIiLCIkc3R5bGVkU2VsZWN0Iiwic3RyU2VsZWN0ZWRUZXh0IiwiZXEiLCJzdHJTZWxlY3RlZFZhbHVlIiwiJGxpc3QiLCJpbnNlcnRBZnRlciIsInJlbCIsIiRsaXN0SXRlbXMiLCJub3QiLCJ0cmlnZ2VyIiwibWFrZU11bHRpQ2Fyb3VzZWwiLCJzbGlkZXNTaG93Iiwic2xpZGVzU2Nyb2xsIiwic3BlZWQiLCJhcnJvd3MiLCJyZXNwb25zaXZlIiwiYnJlYWtwb2ludCIsInNldHRpbmdzIiwiR0xPQkFMX0xJU1RJTkdfQVBJX1BBVEgiLCJpSXRlbXNUb1Nob3ciLCJjaGFuZ2UiLCJzY3JvbGxUbyIsImFuaW1hdGUiLCJjbGFzc05hbWUiLCJtYXRjaCIsInBhcmVudHMiLCJmaXJzdCIsIiRzdWJNZW51Il0sIm1hcHBpbmdzIjoiOzs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7O0FBQUE7QUFDQTtBQUNBOztBQUNBLElBQU1BLFVBQVUsR0FBR0MsbUJBQU8sQ0FBQyxvRUFBRCxDQUExQixDLENBQ0E7QUFDQTs7O0FBRUFDLENBQUMsQ0FBQ0MsUUFBRCxDQUFELENBQVlDLEtBQVosQ0FBa0IsWUFBVztBQUN6QixNQUFNQyxnQkFBZ0IsR0FBRyxTQUFTQyxRQUFRLENBQUNDLFFBQTNDO0FBQ0EsTUFBTUMsWUFBWSxHQUFHLHNCQUFyQjtBQUNBLE1BQU1DLGNBQWMsR0FBRyx3QkFBdkI7QUFDQSxNQUFNQyxXQUFXLEdBQUcsV0FBcEI7QUFDQSxNQUFJQyxZQUFZLEdBQUcsQ0FBbkI7QUFDQSxNQUFJQyxlQUFlLEdBQUcsS0FBdEI7QUFDQSxNQUFJQyxtQkFBSjtBQUNBLE1BQUlD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ImltcG9ydCAqIGFzIG11bHRpQ2Fyb3VzZWxGdW5jcyBmcm9tICcuLi9jb21wb25lbnRzL211bHRpLWNhcm91c2VsJztcbmltcG9ydCBtYWtlU2VsZWN0Qm94IGZyb20gJy4uL2NvbXBvbmVudHMvY3VzdG9tLXNlbGVjdGJveCc7XG5pbXBvcnQgaXNNb2JpbGUgZnJvbSAnLi4vYXBwLmpzJztcbmNvbnN0IEhhbmRsZWJhcnMgPSByZXF1aXJlKCdoYW5kbGViYXJzJyk7XG4vLyBpbXBvcnQgc3RySXRlbXNOdW1DbGFzcyBmcm9tICcuLi9wYWdlcy9saXN0aW5nJztcbi8vIGltcG9ydCAqIGFzIHByaWNlU2xpZGVyQ29udGFpbmVyIGZyb20gJy4uL3BhZ2VzL2xpc3RpbmcnO1xuXG4kKGRvY3VtZW50KS5yZWFkeShmdW5jdGlvbigpIHtcbiAgICBjb25zdCBMSVNUSU5HX0FQSV9QQVRIID0gJy9hcGknICsgbG9jYXRpb24ucGF0aG5hbWU7XG4gICAgY29uc3QgRkFWX01BUktfQVBJID0gJy9hcGkvbWFyay9mYXZvdXJpdGUvJztcbiAgICBjb25zdCBGQVZfVU5NQVJLX0FQSSA9ICcvYXBpL3VubWFyay9mYXZvdXJpdGUvJztcbiAgICBjb25zdCBQUk9EVUNUX1VSTCA9ICcvcHJvZHVjdC8nO1xuICAgIHZhciB0b3RhbFJlc3VsdHMgPSAwO1xuICAgIHZhciBiRmlsdGVyc0NyZWF0ZWQgPSBmYWxzZTtcbiAgICB2YXIgb2JqR2xvYmFsRmlsdGVyRGF0YTtcbiAgICB2YXIgc2VhcmNoID0gd2luZG93LmxvY2F0aW9uLnNlYXJjaC5zdWJzdHJpbmcoMSk7XG4gICAgdmFyIHNvdXJjZSA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdsaXN0aW5nLXRlbXBsYXRlJykuaW5uZXJIVE1MO1xuICAgIHZhciBsaXN0aW5nVGVtcGxhdGUgPSBIYW5kbGViYXJzLmNvbXBpbGUoc291cmNlKTtcbiAgICBIYW5kbGViYXJzLnJlZ2lzdGVySGVscGVyKCdpZkVxJywgZnVuY3Rpb24odjEsIHYyLCBvcHRpb25zKSB7XG4gICAgICAgIGlmICh2MSA9PT0gdjIpIHtcbiAgICAgICAgICAgIHJldHVybiBvcHRpb25zLmZuKHRoaXMpO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBvcHRpb25zLmludmVyc2UodGhpcyk7XG4gICAgfSk7XG4gICAgSGFuZGxlYmFycy5yZWdpc3RlckhlbHBlcignaWZOZXEnLCBmdW5jdGlvbih2MSwgdjIsIG9wdGlvbnMpIHtcbiAgICAgICAgaWYgKHYxICE9PSB2Mikge1xuICAgICAgICAgICAgcmV0dXJuIG9wdGlvbnMuZm4odGhpcyk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIG9wdGlvbnMuaW52ZXJzZSh0aGlzKTtcbiAgICB9KTtcbiAgICB2YXIgcXVlcnlPYmplY3QgPSBzZWFyY2hcbiAgICAgICAgPyBKU09OLnBhcnNlKFxuICAgICAgICAgICAgICAne1wiJyArXG4gICAgICAgICAgICAgICAgICBkZWNvZGVVUkkoc2VhcmNoKVxuICAgICAgICAgICAgICAgICAgICAgIC5yZXBsYWNlKC9cIi9nLCAnXFxcXFwiJylcbiAgICAgICAgICAgICAgICAgICAgICAucmVwbGFjZSgvJi9nLCAnXCIsXCInKVxuICAgICAgICAgICAgICAgICAgICAgIC5yZXBsYWNlKC89L2csICdcIjpcIicpICtcbiAgICAgICAgICAgICAgICAgICdcIn0nXG4gICAgICAgICAgKVxuICAgICAgICA6IHt9O1xuICAgIHZhciBzdHJGaWx0ZXJzID0gcXVlcnlPYmplY3QuZmlsdGVycyB8fCAnJztcbiAgICB2YXIgc3RyU29ydFR5cGUgPSBxdWVyeU9iamVjdC5zb3J0X3R5cGUgfHwgJyc7XG4gICAgdmFyIGlQYWdlTm8gPSBwYXJzZUludChxdWVyeU9iamVjdC5wYWdlbm8pIHx8IDAsXG4gICAgICAgIGlMaW1pdDtcbiAgICB2YXIgcHJpY2VfZnJvbSwgcHJpY2VfdG87XG4gICAgdmFyIGJOb01vcmVQcm9kdWN0c1RvU2hvdyA9IGZhbHNlO1xuICAgIHZhciBiRmV0Y2hpbmdQcm9kdWN0cyA9IGZhbHNlO1xuXG4gICAgJCh3aW5kb3cpLnNjcm9sbChmdW5jdGlvbigpIHtcbiAgICAgICAgaWYgKCFiTm9Nb3JlUHJvZHVjdHNUb1Nob3cpIHtcbiAgICAgICAgICAgIGlmICgkKCcjbG9hZGVySW1nJykgJiYgaXNTY3JvbGxlZEludG9WaWV3KCQoJyNsb2FkZXJJbWcnKVswXSkpIHtcbiAgICAgICAgICAgICAgICBmZXRjaFByb2R1Y3RzKGZhbHNlKTtcbiAgICAgICAgICAgIH0gZWxzZSBpZiAoJCgnI2xvYWRlckltZycpID09PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgZmV0Y2hQcm9kdWN0cyhmYWxzZSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9KTtcblxuICAgIGZ1bmN0aW9uIGlzU2Nyb2xsZWRJbnRvVmlldyhlbCkge1xuICAgICAgICB2YXIgcmVjdCA9IGVsLmdldEJvdW5kaW5nQ2xpZW50UmVjdCgpO1xuICAgICAgICB2YXIgZWxlbVRvcCA9IHJlY3QudG9wO1xuICAgICAgICB2YXIgZWxlbUJvdHRvbSA9IHJlY3QuYm90dG9tO1xuXG4gICAgICAgIC8vIE9ubHkgY29tcGxldGVseSB2aXNpYmxlIGVsZW1lbnRzIHJldHVybiB0cnVlOlxuICAgICAgICB2YXIgaXNWaXNpYmxlID0gZWxlbVRvcCA+PSAwICYmIGVsZW1Cb3R0b20gPD0gd2luZG93LmlubmVySGVpZ2h0O1xuICAgICAgICAvLyBQYXJ0aWFsbHkgdmlzaWJsZSBlbGVtZW50cyByZXR1cm4gdHJ1ZTpcbiAgICAgICAgLy9pc1Zpc2libGUgPSBlbGVtVG9wIDwgd2luZG93LmlubmVySGVpZ2h0ICYmIGVsZW1Cb3R0b20gPj0gMDtcbiAgICAgICAgcmV0dXJuIGlzVmlzaWJsZTtcbiAgICB9XG5cbiAgICBmdW5jdGlvbiBmZXRjaFByb2R1Y3RzKGJDbGVhclByZXZQcm9kdWN0cykge1xuICAgICAgICBpZiAoIWJGZXRjaGluZ1Byb2R1Y3RzKSB7XG4gICAgICAgICAgICBiRmV0Y2hpbmdQcm9kdWN0cyA9IHRydWU7XG4gICAgICAgICAgICB2YXIgc3RyTGltaXQgPSBpTGltaXQgPT09IHVuZGVmaW5lZCA/ICcnIDogJyZsaW1pdD0nICsgaUxpbWl0O1xuICAgICAgICAgICAgdmFyIGZpbHRlclF1ZXJ5ID0gYD9maWx0ZXJzPSR7c3RyRmlsdGVyc30mc29ydF90eXBlPSR7c3RyU29ydFR5cGV9JnBhZ2Vubz0ke2lQYWdlTm99JHtzdHJMaW1pdH1gO1xuICAgICAgICAgICAgdmFyIGxpc3RpbmdBcGlQYXRoID0gTElTVElOR19BUElfUEFUSCArIGZpbHRlclF1ZXJ5O1xuXG4gICAgICAgICAgICBoaXN0b3J5LnB1c2hTdGF0ZShcbiAgICAgICAgICAgICAgICB7fSxcbiAgICAgICAgICAgICAgICAnJyxcbiAgICAgICAgICAgICAgICB3aW5kb3cubG9jYXRpb24ucHJvdG9jb2wgK1xuICAgICAgICAgICAgICAgICAgICAnLy8nICtcbiAgICAgICAgICAgICAgICAgICAgd2luZG93LmxvY2F0aW9uLmhvc3QgK1xuICAgICAgICAgICAgICAgICAgICB3aW5kb3cubG9jYXRpb24ucGF0aG5hbWUgK1xuICAgICAgICAgICAgICAgICAgICBmaWx0ZXJRdWVyeVxuICAgICAgICAgICAgKTtcbiAgICAgICAgICAgICQoJyNub1Byb2R1Y3RzVGV4dCcpLmhpZGUoKTtcblxuICAgICAgICAgICAgaWYgKFxuICAgICAgICAgICAgICAgIGlQYWdlTm8gPiAwICYmXG4gICAgICAgICAgICAgICAgISQoJyNwcm9kdWN0c0NvbnRhaW5lckRpdicpXG4gICAgICAgICAgICAgICAgICAgIC5odG1sKClcbiAgICAgICAgICAgICAgICAgICAgLnRyaW0oKVxuICAgICAgICAgICAgKSB7XG4gICAgICAgICAgICAgICAgdmFyIGFwaUNhbGwgPSBbXTtcbiAgICAgICAgICAgICAgICBmb3IgKHZhciBpID0gMDsgaSA8PSBpUGFnZU5vOyBpKyspIHtcbiAgICAgICAgICAgICAgICAgICAgdmFyIGZpbHRlclF1ZXJ5ID0gYD9maWx0ZXJzPSR7c3RyRmlsdGVyc30mc29ydF90eXBlPSR7c3RyU29ydFR5cGV9JnBhZ2Vubz0ke2l9JHtzdHJMaW1pdH1gO1xuICAgICAgICAgICAgICAgICAgICB2YXIgbGlzdGluZ0FwaVBhdGggPSBMSVNUSU5HX0FQSV9QQVRIICsgZmlsdGVyUXVlcnk7XG4gICAgICAgICAgICAgICAgICAgIGFwaUNhbGwucHVzaChcbiAgICAgICAgICAgICAgICAgICAgICAgICQuYWpheCh7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdHlwZTogJ0dFVCcsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdXJsOiBsaXN0aW5nQXBpUGF0aCxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBkYXRhVHlwZTogJ2pzb24nXG4gICAgICAgICAgICAgICAgICAgICAgICB9KVxuICAgICAgICAgICAgICAgICAgICApO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB2YXIgcHJvZHVjdHNhcnJ5ID0gW107XG4gICAgICAgICAgICAgICAgJC53aGVuLmFwcGx5KHVuZGVmaW5lZCwgYXBpQ2FsbCkudGhlbihmdW5jdGlvbiguLi5yZXN1bHRzKSB7XG4gICAgICAgICAgICAgICAgICAgIHJlc3VsdHMubWFwKGRhdGEgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgcHJvZHVjdHNhcnJ5ID0gWy4uLnByb2R1Y3RzYXJyeSwgLi4uZGF0YVswXS5wcm9kdWN0c107XG4gICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgICAgICByZXN1bHRzWzBdWzBdLnByb2R1Y3RzID0gcHJvZHVjdHNhcnJ5O1xuICAgICAgICAgICAgICAgICAgICBsaXN0aW5nQXBpUmVuZGVyaW5nKHJlc3VsdHNbMF1bMF0pO1xuICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgIGlQYWdlTm8gKz0gMTtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgaVBhZ2VObyArPSAxO1xuICAgICAgICAgICAgICAgICQuYWpheCh7XG4gICAgICAgICAgICAgICAgICAgIHR5cGU6ICdHRVQnLFxuICAgICAgICAgICAgICAgICAgICB1cmw6IGxpc3RpbmdBcGlQYXRoLFxuICAgICAgICAgICAgICAgICAgICBkYXRhVHlwZTogJ2pzb24nLFxuICAgICAgICAgICAgICAgICAgICBzdWNjZXNzOiBmdW5jdGlvbihkYXRhKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBsaXN0aW5nQXBpUmVuZGVyaW5nKGRhdGEpO1xuICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICBlcnJvcjogZnVuY3Rpb24oanFYSFIsIGV4Y2VwdGlvbikge1xuICAgICAgICAgICAgICAgICAgICAgICAgYkZldGNoaW5nUHJvZHVjdHMgPSBmYWxzZTtcbiAgICAgICAgICAgICAgICAgICAgICAgIGNvbnNvbGUubG9nKGpxWEhSKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIGNvbnNvbGUubG9nKGV4Y2VwdGlvbik7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICB3aW5kb3cubGlzdGluZ0FwaVJlbmRlcmluZyA9IGZ1bmN0aW9uKGRhdGEpIHtcbiAgICAgICAgICAgIGJGZXRjaGluZ1Byb2R1Y3RzID0gZmFsc2U7XG4gICAgICAgICAgICBpZiAoYkNsZWFyUHJldlByb2R1Y3RzKSB7XG4gICAgICAgICAgICAgICAgJCgnI3Byb2R1Y3RzQ29udGFpbmVyRGl2JykuZW1wdHkoKTtcbiAgICAgICAgICAgICAgICB0b3RhbFJlc3VsdHMgPSAwO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgLy8kKCcjbG9hZGVySW1nJykuaGlkZSgpO1xuICAgICAgICAgICAgaWYgKGRhdGEgPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGlmIChkYXRhLnByb2R1Y3RzICYmIGRhdGEucHJvZHVjdHMubGVuZ3RoKSB7XG4gICAgICAgICAgICAgICAgYk5vTW9yZVByb2R1Y3RzVG9TaG93ID0gdHJ1ZTtcblxuICAgICAgICAgICAgICAgIHRvdGFsUmVzdWx0cyA9IGRhdGEudG90YWw7XG4gICAgICAgICAgICAgICAgJCgnI3RvdGFsUmVzdWx0cycpLnRleHQodG90YWxSZXN1bHRzKTtcblxuICAgICAgICAgICAgICAgIHZhciBhbmNob3IgPSAkKCc8YS8+Jywge1xuICAgICAgICAgICAgICAgICAgICBocmVmOiAnI3BhZ2UnICsgaVBhZ2VObyxcbiAgICAgICAgICAgICAgICAgICAgaWQ6ICcjYW5jaG9yLXBhZ2UnICsgaVBhZ2VOb1xuICAgICAgICAgICAgICAgIH0pLmFwcGVuZFRvKCcjcHJvZHVjdHNDb250YWluZXJEaXYnKTtcbiAgICAgICAgICAgICAgICBmb3IgKHZhciBwcm9kdWN0IG9mIGRhdGEucHJvZHVjdHMpIHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKFxuICAgICAgICAgICAgICAgICAgICAgICAgcHJvZHVjdC5yZXZpZXdzICE9IG51bGwgJiZcbiAgICAgICAgICAgICAgICAgICAgICAgIHBhcnNlSW50KHByb2R1Y3QucmV2aWV3cykgIT0gMFxuICAgICAgICAgICAgICAgICAgICApIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHByb2R1Y3QucmV2aWV3RXhpc3QgPSB0cnVlO1xuICAgICAgICAgICAgICAgICAgICAgICAgcHJvZHVjdC5yYXRpbmdDbGFzcyA9IGByYXRpbmctJHtwYXJzZUZsb2F0KFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHByb2R1Y3QucmF0aW5nXG4gICAgICAgICAgICAgICAgICAgICAgICApXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgLnRvRml4ZWQoMSlcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAudG9TdHJpbmcoKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC5yZXBsYWNlKCcuJywgJ18nKX1gO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICQoJyNwcm9kdWN0c0NvbnRhaW5lckRpdicpLmFwcGVuZChsaXN0aW5nVGVtcGxhdGUocHJvZHVjdCkpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBzY3JvbGxUb0FuY2hvcigpO1xuICAgICAgICAgICAgICAgIG11bHRpQ2Fyb3VzZWxGdW5jcy5tYWtlTXVsdGlDYXJvdXNlbCgpO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAvLyBpZiAoIWJDbGVhclByZXZQcm9kdWN0cykge1xuICAgICAgICAgICAgICAgIGJOb01vcmVQcm9kdWN0c1RvU2hvdyA9IHRydWU7XG4gICAgICAgICAgICAgICAgaVBhZ2VObyAtPSAxO1xuICAgICAgICAgICAgICAgICQoJyNub1Byb2R1Y3RzVGV4dCcpLnNob3coKTtcbiAgICAgICAgICAgICAgICAkKCcjbG9hZGVySW1nJykuaGlkZSgpO1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgICAgICAvLyB9XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAoZGF0YS5maWx0ZXJEYXRhKSB7XG4gICAgICAgICAgICAgICAgb2JqR2xvYmFsRmlsdGVyRGF0YSA9IGRhdGEuZmlsdGVyRGF0YTtcbiAgICAgICAgICAgICAgICBjcmVhdGVVcGRhdGVGaWx0ZXJEYXRhKGRhdGEuZmlsdGVyRGF0YSk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAoZGF0YS5zb3J0VHlwZSkge1xuICAgICAgICAgICAgICAgICQoJyNzb3J0JykuZW1wdHkoKTtcbiAgICAgICAgICAgICAgICBkYXRhLnNvcnRUeXBlLmZvckVhY2goZWxlbWVudCA9PiB7XG4gICAgICAgICAgICAgICAgICAgIHZhciBzb3J0RWxtID0galF1ZXJ5KCc8b3B0aW9uIC8+Jywge1xuICAgICAgICAgICAgICAgICAgICAgICAgdmFsdWU6IGVsZW1lbnQudmFsdWUsXG4gICAgICAgICAgICAgICAgICAgICAgICBzZWxlY3RlZDogZWxlbWVudC5lbmFibGVkLFxuICAgICAgICAgICAgICAgICAgICAgICAgdGV4dDogZWxlbWVudC5uYW1lXG4gICAgICAgICAgICAgICAgICAgIH0pLmFwcGVuZFRvKCcjc29ydCcpO1xuICAgICAgICAgICAgICAgICAgICBpZiAoZWxlbWVudC5lbmFibGVkKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBzdHJTb3J0VHlwZSA9IGVsZW1lbnQudmFsdWU7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICBtYWtlU2VsZWN0Qm94KCk7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIC8vICAgICAkKFwiI2FuY2hvci1wYWdlXCIraVBhZ2VObylbMF0uY2xpY2soKVxuICAgICAgICB9O1xuICAgIH1cblxuICAgIHZhciBtYWluUHJvZHVjdERpdjtcbiAgICBmdW5jdGlvbiBjcmVhdGVQcm9kdWN0RGl2KHByb2R1Y3REZXRhaWxzKSB7XG4gICAgICAgIC8vTWFrZSBwcm9kdWN0IG1haW4gZGl2XG4gICAgICAgIG1haW5Qcm9kdWN0RGl2ID0galF1ZXJ5KCc8ZGl2Lz4nLCB7XG4gICAgICAgICAgICBpZDogcHJvZHVjdERldGFpbHMuaWQsXG4gICAgICAgICAgICBza3U6IHByb2R1Y3REZXRhaWxzLnNrdSxcbiAgICAgICAgICAgIHNpdGU6IHByb2R1Y3REZXRhaWxzLnNpdGUsXG4gICAgICAgICAgICBjbGFzczogJ2xzLXByb2R1Y3QtZGl2IGNvbC1tZC0zICcgKyBzdHJJdGVtc051bUNsYXNzXG4gICAgICAgIH0pLmFwcGVuZFRvO1xuICAgICAgICB2YXIgcHJvZHVjdExpbmsgPSBqUXVlcnkoJzxhLz4nLCB7XG4gICAgICAgICAgICBocmVmOiBQUk9EVUNUX1VSTCArIHByb2R1Y3REZXRhaWxzLnNrdSxcbiAgICAgICAgICAgIGNsYXNzOiAncHJvZHVjdC1kZXRhaWwtbW9kYWwnXG4gICAgICAgIH0pLmFwcGVuZFRvKG1haW5Qcm9kdWN0RGl2KTtcblxuICAgICAgICB2YXIgcHJvZHVjdCA9IGpRdWVyeSgnPGRpdi8+Jywge1xuICAgICAgICAgICAgY2xhc3M6ICdscy1wcm9kdWN0J1xuICAgICAgICB9KS5hcHBlbmRUbyhwcm9kdWN0TGluayk7XG4gICAgICAgIGlmIChwcm9kdWN0RGV0YWlscy5pc19wcmljZS5pbmNsdWRlcygnLScpKSB7XG4gICAgICAgICAgICBsZXQgc2FsZXByaWNlUmFuZ2UgPSBwcm9kdWN0RGV0YWlscy5pc19wcmljZS5zcGxpdCgnLScpO1xuICAgICAgICAgICAgdmFyIHNhbGVwcmljZSA9IGpRdWVyeSgnPHNwYW4gLz4nLCB7XG4gICAgICAgICAgICAgICAgdGV4dDogYCQke01hdGgucm91bmQoXG4gICAgICAgICAgICAgICAgICAgIHNhbGVwcmljZVJhbmdlWzBdXG4gICAgICAgICAgICAgICAgKS50b0xvY2FsZVN0cmluZygpfSAtICQke01hdGgucm91bmQoXG4gICAgICAgICAgICAgICAgICAgIHNhbGVwcmljZVJhbmdlWzFdXG4gICAgICAgICAgICAgICAgKS50b0xvY2FsZVN0cmluZygpfWAsXG4gICAgICAgICAgICAgICAgY2xhc3M6ICdwcm9kLXNhbGUtcHJpY2UgZC1tZC1ub25lJ1xuICAgICAgICAgICAgfSkuYXBwZW5kVG8obWFpblByb2R1Y3REaXYpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgdmFyIHNhbGVwcmljZSA9IGpRdWVyeSgnPHNwYW4gLz4nLCB7XG4gICAgICAgICAgICAgICAgdGV4dDogYCQke01hdGgucm91bmQoXG4gICAgICAgICAgICAgICAgICAgIHByb2R1Y3REZXRhaWxzLmlzX3ByaWNlXG4gICAgICAgICAgICAgICAgKS50b0xvY2FsZVN0cmluZygpfWAsXG4gICAgICAgICAgICAgICAgY2xhc3M6ICdwcm9kLXNhbGUtcHJpY2UgZC1tZC1ub25lJ1xuICAgICAgICAgICAgfSkuYXBwZW5kVG8obWFpblByb2R1Y3REaXYpO1xuICAgICAgICB9XG4gICAgICAgIGlmIChNYXRoLmNlaWwocHJvZHVjdERldGFpbHMucGVyY2VudF9kaXNjb3VudCkgPiAwKSB7XG4gICAgICAgICAgICB2YXIgZGlzY291bnR0YWcgPSBqUXVlcnkoJzxzcGFuIC8+Jywge1xuICAgICAgICAgICAgICAgIHRleHQ6IGAke01hdGguY2VpbChwcm9kdWN0RGV0YWlscy5wZXJjZW50X2Rpc2NvdW50KX0lYCxcbiAgICAgICAgICAgICAgICBjbGFzczogYHByb2QtZGlzY291bnQtdGFnIGQtbWQtbm9uZSAke1xuICAgICAgICAgICAgICAgICAgICBwcm9kdWN0RGV0YWlscy5wZXJjZW50X2Rpc2NvdW50ID49IDIwID8gJ18yMCcgOiAnJ1xuICAgICAgICAgICAgICAgIH1gXG4gICAgICAgICAgICB9KS5hcHBlbmRUbyhtYWluUHJvZHVjdERpdik7XG4gICAgICAgIH1cblxuICAgICAgICBqUXVlcnkoJzxpbWcgLz4nLCB7XG4gICAgICAgICAgICBjbGFzczogJ3Byb2QtaW1nIGltZy1mbHVpZCcsXG4gICAgICAgICAgICBzcmM6IHByb2R1Y3REZXRhaWxzLm1haW5faW1hZ2UsXG4gICAgICAgICAgICBhbHQ6IHByb2R1Y3REZXRhaWxzLm5hbWVcbiAgICAgICAgfSkuYXBwZW5kVG8ocHJvZHVjdCk7XG5cbiAgICAgICAgLy9Qcm9kdWN0IGluZm9ybWF0aW9uXG4gICAgICAgIHZhciBwcm9kSW5mbyA9IGpRdWVyeSgnPGRpdi8+Jywge1xuICAgICAgICAgICAgY2xhc3M6ICdwcm9kLWluZm8gZC1ub25lIGQtbWQtYmxvY2snXG4gICAgICAgIH0pLmFwcGVuZFRvKHByb2R1Y3QpO1xuICAgICAgICB2YXIgY2F0RGV0YWlscyA9IGpRdWVyeSgnPHNwYW4vPicsIHtcbiAgICAgICAgICAgIGNsYXNzOiAnLWNhdC1uYW1lJ1xuICAgICAgICB9KS5hcHBlbmRUbyhwcm9kSW5mbyk7XG4gICAgICAgICQoY2F0RGV0YWlscykudGV4dChwcm9kdWN0RGV0YWlscy5zaXRlKTtcbiAgICAgICAgdmFyIHByaWNlcyA9IGpRdWVyeSgnPHNwYW4vPicsIHtcbiAgICAgICAgICAgIGNsYXNzOiAnLXByaWNlcyBmbG9hdC1yaWdodCdcbiAgICAgICAgfSkuYXBwZW5kVG8ocHJvZEluZm8pO1xuICAgICAgICB2YXIgY3VyclByaWNlID0galF1ZXJ5KCc8c3Bhbi8+Jywge1xuICAgICAgICAgICAgY2xhc3M6ICctY3ByaWNlJ1xuICAgICAgICB9KS5hcHBlbmRUbyhwcmljZXMpO1xuICAgICAgICAkKGN1cnJQcmljZSkudGV4dCgnJCcgKyBwcm9kdWN0RGV0YWlscy5pc19wcmljZSk7XG4gICAgICAgIGlmIChwcm9kdWN0RGV0YWlscy5pc19wcmljZSA8IHByb2R1Y3REZXRhaWxzLndhc19wcmljZSkge1xuICAgICAgICAgICAgdmFyIG9sZFByaWNlID0galF1ZXJ5KCc8c3Bhbi8+Jywge1xuICAgICAgICAgICAgICAgIGNsYXNzOiAnLW9sZHByaWNlJ1xuICAgICAgICAgICAgfSkuYXBwZW5kVG8ocHJpY2VzKTtcbiAgICAgICAgICAgICQob2xkUHJpY2UpLnRleHQoJyQnICsgcHJvZHVjdERldGFpbHMud2FzX3ByaWNlKTtcbiAgICAgICAgfVxuICAgICAgICB2YXIgc3RyTWFya2VkID0gcHJvZHVjdERldGFpbHMud2lzaGxpc3RlZCA/ICdtYXJrZWQnIDogJyc7XG4gICAgICAgICQocHJvZHVjdCkuYXBwZW5kKFxuICAgICAgICAgICAgJzxkaXYgY2xhc3M9XCJ3aXNobGlzdC1pY29uICcgK1xuICAgICAgICAgICAgICAgIHN0ck1hcmtlZCArXG4gICAgICAgICAgICAgICAgJ1wiIHNrdT0nICtcbiAgICAgICAgICAgICAgICBwcm9kdWN0RGV0YWlscy5za3UgK1xuICAgICAgICAgICAgICAgICc+PGkgY2xhc3M9XCJmYXIgZmEtaGVhcnQgLWljb25cIj48L2k+PC9kaXY+J1xuICAgICAgICApO1xuXG4gICAgICAgIHZhciBwcm9kdWN0SW5mb05leHQgPSBqUXVlcnkoJzxkaXYvPicsIHtcbiAgICAgICAgICAgIGNsYXNzOiAnZC1ub25lIGQtbWQtYmxvY2snXG4gICAgICAgIH0pLmFwcGVuZFRvKG1haW5Qcm9kdWN0RGl2KTtcbiAgICAgICAgJChwcm9kdWN0SW5mb05leHQpLmFwcGVuZChcbiAgICAgICAgICAgICc8ZGl2IGNsYXNzPVwiLW5hbWVcIj4nICsgcHJvZHVjdERldGFpbHMubmFtZSArICc8L2Rpdj4nXG4gICAgICAgICk7XG5cbiAgICAgICAgdmFyIGNhcm91c2VsTWFpbkRpdiA9IGpRdWVyeSgnPGRpdi8+Jywge1xuICAgICAgICAgICAgY2xhc3M6ICdyZXNwb25zaXZlJ1xuICAgICAgICB9KS5hcHBlbmRUbyhwcm9kdWN0SW5mb05leHQpO1xuXG4gICAgICAgIHZhciB2YXJpYXRpb25JbWFnZXMgPSBwcm9kdWN0RGV0YWlscy52YXJpYXRpb25zLm1hcChcbiAgICAgICAgICAgIHZhcmlhdGlvbiA9PiB2YXJpYXRpb24uaW1hZ2VcbiAgICAgICAgKTtcbiAgICAgICAgdmFyIHZhcmlhdGlvblN3YXRjaEltYWdlcyA9IHByb2R1Y3REZXRhaWxzLnZhcmlhdGlvbnMubWFwKFxuICAgICAgICAgICAgKHZhcmlhdGlvbiwgaWR4KSA9PiB7XG4gICAgICAgICAgICAgICAgaWYgKHByb2R1Y3REZXRhaWxzLnNpdGUgIT09ICdXZXN0ZWxtJykge1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gdmFyaWF0aW9uLnN3YXRjaF9pbWFnZSB8fCB2YXJpYXRpb25JbWFnZXNbaWR4XTtcbiAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gdmFyaWF0aW9uLnN3YXRjaF9pbWFnZTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgICk7XG4gICAgICAgIHZhciB2YXJpYXRpb25MaW5rcyA9IHByb2R1Y3REZXRhaWxzLnZhcmlhdGlvbnMubWFwKFxuICAgICAgICAgICAgdmFyaWF0aW9uID0+IHZhcmlhdGlvbi5saW5rXG4gICAgICAgICk7XG5cbiAgICAgICAgaWYgKHByb2R1Y3REZXRhaWxzLm1haW5faW1hZ2UgIT0gbnVsbCkge1xuICAgICAgICAgICAgalF1ZXJ5KCc8aW1nIC8+Jywge1xuICAgICAgICAgICAgICAgIGNsYXNzOiAndmFyaWF0aW9uLWltZyBpbWctZmx1aWQnLFxuICAgICAgICAgICAgICAgIHNyYzogcHJvZHVjdERldGFpbHMubWFpbl9pbWFnZSxcbiAgICAgICAgICAgICAgICBhbHQ6ICd2YXJpYXRpb24taW1nJ1xuICAgICAgICAgICAgfSkuYXBwZW5kVG8ocHJvZHVjdCk7XG4gICAgICAgIH1cblxuICAgICAgICBpZiAodmFyaWF0aW9uU3dhdGNoSW1hZ2VzLmxlbmd0aCA+IDApIHtcbiAgICAgICAgICAgIHZhcmlhdGlvblN3YXRjaEltYWdlcy5mb3JFYWNoKChpbWcsIGlkeCkgPT4ge1xuICAgICAgICAgICAgICAgIHZhciByZXNwb25zaXZlSW1nRGl2ID0galF1ZXJ5KCc8ZGl2Lz4nLCB7XG4gICAgICAgICAgICAgICAgICAgIGNsYXNzOiAnbWluaS1jYXJvdXNlbC1pdGVtJ1xuICAgICAgICAgICAgICAgIH0pLmFwcGVuZFRvKGNhcm91c2VsTWFpbkRpdik7XG4gICAgICAgICAgICAgICAgdmFyIGFuY2hvciA9IGpRdWVyeSgnPGEvPicsIHtcbiAgICAgICAgICAgICAgICAgICAgY2xhc3M6ICdyZXNwb25zaXZlLWltZy1hJyxcbiAgICAgICAgICAgICAgICAgICAgaHJlZjogdmFyaWF0aW9uTGlua3NbaWR4XVxuICAgICAgICAgICAgICAgIH0pLmFwcGVuZFRvKHJlc3BvbnNpdmVJbWdEaXYpO1xuICAgICAgICAgICAgICAgIHZhciByZXNwb25zaXZlSW1nID0galF1ZXJ5KCc8aW1nLz4nLCB7XG4gICAgICAgICAgICAgICAgICAgIGNsYXNzOiAnY2Fyb3VzZWwtaW1nIGltZy1mbHVpZCcsXG4gICAgICAgICAgICAgICAgICAgIHNyYzogaW1nLFxuICAgICAgICAgICAgICAgICAgICAnZGF0YS1wcm9kaW1nJzogdmFyaWF0aW9uSW1hZ2VzW2lkeF1cbiAgICAgICAgICAgICAgICB9KS5hcHBlbmRUbyhhbmNob3IpO1xuICAgICAgICAgICAgfSk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICBjYXJvdXNlbE1haW5EaXYuYWRkQ2xhc3MoJ2Qtbm9uZScpO1xuICAgICAgICB9XG5cbiAgICAgICAgaWYgKFxuICAgICAgICAgICAgcHJvZHVjdERldGFpbHMucmV2aWV3cyAhPSBudWxsICYmXG4gICAgICAgICAgICBwYXJzZUludChwcm9kdWN0RGV0YWlscy5yZXZpZXdzKSAhPSAwXG4gICAgICAgICkge1xuICAgICAgICAgICAgdmFyIHJldmlld1ZhbHVlID0gcGFyc2VJbnQocHJvZHVjdERldGFpbHMucmV2aWV3cyk7XG4gICAgICAgICAgICB2YXIgcmF0aW5nVmFsdWUgPSBwYXJzZUZsb2F0KHByb2R1Y3REZXRhaWxzLnJhdGluZykudG9GaXhlZCgxKTtcbiAgICAgICAgICAgIHZhciByYXRpbmdDbGFzcyA9IHJhdGluZ1ZhbHVlLnRvU3RyaW5nKCkucmVwbGFjZSgnLicsICdfJyk7XG4gICAgICAgICAgICAkKHByb2R1Y3RJbmZvTmV4dCkuYXBwZW5kKFxuICAgICAgICAgICAgICAgICc8ZGl2IGNsYXNzPVwicmF0aW5nLWNvbnRhaW5lclwiPjxkaXYgY2xhc3M9XCJyYXRpbmcgIHJhdGluZy0nICtcbiAgICAgICAgICAgICAgICAgICAgcmF0aW5nQ2xhc3MgK1xuICAgICAgICAgICAgICAgICAgICAnXCI+PC9kaXY+PHNwYW4gY2xhc3M9XCJ0b3RhbC1yYXRpbmdzXCI+JyArXG4gICAgICAgICAgICAgICAgICAgIHJldmlld1ZhbHVlICtcbiAgICAgICAgICAgICAgICAgICAgJzwvc3Bhbj48L2Rpdj4nXG4gICAgICAgICAgICApO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgZnVuY3Rpb24gY3JlYXRlVXBkYXRlRmlsdGVyRGF0YShmaWx0ZXJEYXRhKSB7XG4gICAgICAgIGJOb01vcmVQcm9kdWN0c1RvU2hvdyA9IGZhbHNlO1xuICAgICAgICBpZiAoIWJGaWx0ZXJzQ3JlYXRlZCkge1xuICAgICAgICAgICAgYkZpbHRlcnNDcmVhdGVkID0gdHJ1ZTtcbiAgICAgICAgICAgICQoJyNmaWx0ZXJzJykuZW1wdHkoKTtcbiAgICAgICAgICAgIHZhciBtb2JpbGVGaWx0ZXJIZWFkZXIgPSBqUXVlcnkoJzxkaXYvPicsIHtcbiAgICAgICAgICAgICAgICBjbGFzczogJ21vYmlsZS1maWx0ZXItaGVhZGVyIGQtbWQtbm9uZSdcbiAgICAgICAgICAgIH0pLmFwcGVuZFRvKCcjZmlsdGVycycpO1xuICAgICAgICAgICAgalF1ZXJ5KCc8c3Bhbi8+Jywge1xuICAgICAgICAgICAgICAgIGNsYXNzOiAnZmxvYXQtbGVmdCBmaWx0ZXJzLWNsb3NlLWJ0bicsXG4gICAgICAgICAgICAgICAgaHRtbDogJzxpIGNsYXNzPVwiZmEgZmEtdGltZXNcIiBhcmlhLWhpZGRlbj1cInRydWVcIj48L2k+J1xuICAgICAgICAgICAgfSkuYXBwZW5kVG8obW9iaWxlRmlsdGVySGVhZGVyKTtcbiAgICAgICAgICAgIGpRdWVyeSgnPHNwYW4vPicsIHtcbiAgICAgICAgICAgICAgICBjbGFzczogJ2ZpbHRlci10aXRsZScsXG4gICAgICAgICAgICAgICAgdGV4dDogJ0ZpbHRlcnMnXG4gICAgICAgICAgICB9KS5hcHBlbmRUbyhtb2JpbGVGaWx0ZXJIZWFkZXIpO1xuICAgICAgICAgICAgalF1ZXJ5KCc8c3Bhbi8+Jywge1xuICAgICAgICAgICAgICAgIGNsYXNzOiAnZmxvYXQtcmlnaHQnLFxuICAgICAgICAgICAgICAgIGh0bWw6XG4gICAgICAgICAgICAgICAgICAgICc8YSBjbGFzcz1cImJ0biBjbGVhcmFsbC1maWx0ZXItYnRuXCIgaHJlZj1cIiNcIiBpZD1cImNsZWFyQWxsRmlsdGVyc0J0blwiPkNsZWFyIEFsbDwvYT4nXG4gICAgICAgICAgICB9KS5hcHBlbmRUbyhtb2JpbGVGaWx0ZXJIZWFkZXIpO1xuICAgICAgICAgICAgT2JqZWN0LmtleXMoZmlsdGVyRGF0YSkuZm9yRWFjaCgoa2V5LCBpbmRleCkgPT4ge1xuICAgICAgICAgICAgICAgIGNvbnN0IGRhdGEgPSBmaWx0ZXJEYXRhW2tleV07XG4gICAgICAgICAgICAgICAgaWYgKGRhdGEubGVuZ3RoID09IDApIHtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIHZhciBmaWx0ZXJEaXYgPSBqUXVlcnkoJzxkaXYvPicsIHtcbiAgICAgICAgICAgICAgICAgICAgY2xhc3M6ICdmaWx0ZXInLFxuICAgICAgICAgICAgICAgICAgICAnZGF0YS1maWx0ZXInOiBrZXlcbiAgICAgICAgICAgICAgICB9KS5hcHBlbmRUbygnI2ZpbHRlcnMnKTtcbiAgICAgICAgICAgICAgICAkKGZpbHRlckRpdikuYXBwZW5kKCc8aHIvPicpO1xuXG4gICAgICAgICAgICAgICAgJChmaWx0ZXJEaXYpLmFwcGVuZChcbiAgICAgICAgICAgICAgICAgICAgJzxzcGFuIGNsYXNzPVwiZmlsdGVyLWhlYWRlclwiPicgK1xuICAgICAgICAgICAgICAgICAgICAgICAga2V5LnJlcGxhY2UoJ18nLCAnICcpICtcbiAgICAgICAgICAgICAgICAgICAgICAgICc8L3NwYW4+J1xuICAgICAgICAgICAgICAgICk7XG4gICAgICAgICAgICAgICAgJChmaWx0ZXJEaXYpLmFwcGVuZChcbiAgICAgICAgICAgICAgICAgICAgJzxsYWJlbCBmb3I9XCInICtcbiAgICAgICAgICAgICAgICAgICAgICAgIGtleSArXG4gICAgICAgICAgICAgICAgICAgICAgICAnXCIgY2xhc3M9XCJjbGVhci1maWx0ZXIgZmxvYXQtcmlnaHRcIj5DbGVhcjwvbGFiZWw+J1xuICAgICAgICAgICAgICAgICk7XG5cbiAgICAgICAgICAgICAgICBpZiAoa2V5ICE9ICdwcmljZScpIHtcbiAgICAgICAgICAgICAgICAgICAgdmFyIGZpbHRlclVsID0galF1ZXJ5KCc8dWwvPicsIHt9KS5hcHBlbmRUbyhmaWx0ZXJEaXYpO1xuICAgICAgICAgICAgICAgICAgICBkYXRhLmZvckVhY2goZWxlbWVudCA9PiB7XG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgZmlsdGVyTGkgPSBqUXVlcnkoJzxsaS8+Jywge30pLmFwcGVuZFRvKGZpbHRlclVsKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciBmaWx0ZXJMYWJlbCA9IGpRdWVyeSgnPGxhYmVsLz4nLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgY2xhc3M6ICdmaWx0ZXItbGFiZWwnXG4gICAgICAgICAgICAgICAgICAgICAgICB9KS5hcHBlbmRUbyhmaWx0ZXJMaSk7XG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgZmlsdGVyQ2hlY2tib3ggPSBqUXVlcnkoJzxpbnB1dCAvPicsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB0eXBlOiAnY2hlY2tib3gnLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNoZWNrZWQ6IGVsZW1lbnQuY2hlY2tlZCxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YWx1ZTogZWxlbWVudC52YWx1ZSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBkaXNhYmxlZDogIWVsZW1lbnQuZW5hYmxlZCxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBiZWxvbmdzVG86IGtleVxuICAgICAgICAgICAgICAgICAgICAgICAgfSkuYXBwZW5kVG8oZmlsdGVyTGFiZWwpO1xuICAgICAgICAgICAgICAgICAgICAgICAgJChmaWx0ZXJMYWJlbCkuYXBwZW5kKFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICc8c3BhbiBjbGFzcz1cImNoZWNrbWFya1wiPjwvc3Bhbj4nXG4gICAgICAgICAgICAgICAgICAgICAgICApO1xuICAgICAgICAgICAgICAgICAgICAgICAgJChmaWx0ZXJMYWJlbCkuYXBwZW5kKFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICc8c3BhbiBjbGFzcz1cInRleHRcIj4nICsgZWxlbWVudC5uYW1lICsgJzwvc3Bhbj4nXG4gICAgICAgICAgICAgICAgICAgICAgICApO1xuICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAkKGZpbHRlckRpdikuYXR0cignaWQnLCAncHJpY2VGaWx0ZXInKTtcbiAgICAgICAgICAgICAgICAgICAgdmFyIHByaWNlSW5wdXQgPSBqUXVlcnkoJzxpbnB1dC8+Jywge1xuICAgICAgICAgICAgICAgICAgICAgICAgY2xhc3M6ICdwcmljZS1yYW5nZS1zbGlkZXInLFxuICAgICAgICAgICAgICAgICAgICAgICAgaWQ6ICdwcmljZVJhbmdlU2xpZGVyJyxcbiAgICAgICAgICAgICAgICAgICAgICAgIG5hbWU6ICdwcmljZV9yYW5nZScsXG4gICAgICAgICAgICAgICAgICAgICAgICB2YWx1ZTogJydcbiAgICAgICAgICAgICAgICAgICAgfSkuYXBwZW5kVG8oZmlsdGVyRGl2KTtcblxuICAgICAgICAgICAgICAgICAgICAvLyAkKFwiI3ByaWNlUmFuZ2VTbGlkZXJcIikuY2hhbmdlKGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgLy8gICAgICQoXCIjcHJpY2VJbmZvXCIpLmZpbmQoJy5sb3cnKS50ZXh0KCQodGhpcykuYXR0cignbWluJykpO1xuICAgICAgICAgICAgICAgICAgICAvLyAgICAgJChcIiNwcmljZUluZm9cIikuZmluZCgnLmhpZ2gnKS50ZXh0KCQodGhpcykudmFsKCkpO1xuICAgICAgICAgICAgICAgICAgICAvLyB9KTtcblxuICAgICAgICAgICAgICAgICAgICAkcHJpY2VSYW5nZVNsaWRlciA9ICQoJyNwcmljZVJhbmdlU2xpZGVyJyk7XG5cbiAgICAgICAgICAgICAgICAgICAgJHByaWNlUmFuZ2VTbGlkZXIuaW9uUmFuZ2VTbGlkZXIoe1xuICAgICAgICAgICAgICAgICAgICAgICAgc2tpbjogJ3NoYXJwJyxcbiAgICAgICAgICAgICAgICAgICAgICAgIHR5cGU6ICdkb3VibGUnLFxuICAgICAgICAgICAgICAgICAgICAgICAgbWluOiBkYXRhLm1pbiA/IGRhdGEubWluIDogMCxcbiAgICAgICAgICAgICAgICAgICAgICAgIG1heDogZGF0YS5tYXggPyBkYXRhLm1heCA6IDEwMDAwLFxuICAgICAgICAgICAgICAgICAgICAgICAgZnJvbTogZGF0YS5mcm9tID8gZGF0YS5mcm9tIDogZGF0YS5taW4sXG4gICAgICAgICAgICAgICAgICAgICAgICB0bzogZGF0YS50byA/IGRhdGEudG8gOiBkYXRhLm1heCxcbiAgICAgICAgICAgICAgICAgICAgICAgIHByZWZpeDogJyQnLFxuICAgICAgICAgICAgICAgICAgICAgICAgcHJldHRpZnlfc2VwYXJhdG9yOiAnLCcsXG4gICAgICAgICAgICAgICAgICAgICAgICBvblN0YXJ0OiBmdW5jdGlvbihkYXRhKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgLy8gZmlyZWQgdGhlbiByYW5nZSBzbGlkZXIgaXMgcmVhZHlcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBvbkNoYW5nZTogZnVuY3Rpb24oZGF0YSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vIGZpcmVkIG9uIGV2ZXJ5IHJhbmdlIHNsaWRlciB1cGRhdGVcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBvbkZpbmlzaDogZnVuY3Rpb24oZGF0YSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vIGZpcmVkIG9uIHBvaW50ZXIgcmVsZWFzZVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyICRpbnAgPSAkKCcjcHJpY2VSYW5nZVNsaWRlcicpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHByaWNlX2Zyb20gPSAkaW5wLmRhdGEoJ2Zyb20nKTsgLy8gcmVhZGluZyBpbnB1dCBkYXRhLWZyb20gYXR0cmlidXRlXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcHJpY2VfdG8gPSAkaW5wLmRhdGEoJ3RvJyk7IC8vIHJlYWRpbmcgaW5wdXQgZGF0YS10byBhdHRyaWJ1dGVcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpUGFnZU5vID0gMDtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB1cGRhdGVGaWx0ZXJzKCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgZmV0Y2hQcm9kdWN0cyh0cnVlKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBvblVwZGF0ZTogZnVuY3Rpb24oZGF0YSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vIGZpcmVkIG9uIGNoYW5naW5nIHNsaWRlciB3aXRoIFVwZGF0ZSBtZXRob2RcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgaWYgKGluZGV4ID09IE9iamVjdC5rZXlzKGZpbHRlckRhdGEpLmxlbmd0aCAtIDEpIHtcbiAgICAgICAgICAgICAgICAgICAgJChmaWx0ZXJEaXYpLmFwcGVuZCgnPGhyLz4nKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgLy8gJChmaWx0ZXJEaXYpLmFwcGVuZCgnPGhyLz4nKTtcbiAgICAgICAgICAgIGlmICghaXNNb2JpbGUoKSkge1xuICAgICAgICAgICAgICAgICQoJyNmaWx0ZXJzJykuYXBwZW5kKFxuICAgICAgICAgICAgICAgICAgICAnPGEgY2xhc3M9XCJidG4gY2xlYXJhbGwtZmlsdGVyLWJ0blwiIGhyZWY9XCIjXCIgaWQ9XCJjbGVhckFsbEZpbHRlcnNCdG5cIj5DbGVhciBBbGw8L2E+J1xuICAgICAgICAgICAgICAgICk7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIC8vICQoJyNmaWx0ZXJzJykuYXBwZW5kKCc8aHIvPicpXG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICBPYmplY3Qua2V5cyhmaWx0ZXJEYXRhKS5mb3JFYWNoKChrZXksIGluZGV4KSA9PiB7XG4gICAgICAgICAgICAgICAgY29uc3QgZGF0YSA9IGZpbHRlckRhdGFba2V5XTtcbiAgICAgICAgICAgICAgICBpZiAoa2V5ICE9ICdwcmljZScpIHtcbiAgICAgICAgICAgICAgICAgICAgZGF0YS5mb3JFYWNoKGVsZW1lbnQgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgJChcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAnaW5wdXRbdHlwZT1cImNoZWNrYm94XCJdW3ZhbHVlPScgK1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBlbGVtZW50LnZhbHVlICtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJ10nXG4gICAgICAgICAgICAgICAgICAgICAgICApLmF0dHIoJ2NoZWNrZWQnLCBlbGVtZW50LmNoZWNrZWQpO1xuICAgICAgICAgICAgICAgICAgICAgICAgJChcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAnaW5wdXRbdHlwZT1cImNoZWNrYm94XCJdW3ZhbHVlPScgK1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBlbGVtZW50LnZhbHVlICtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJ10nXG4gICAgICAgICAgICAgICAgICAgICAgICApLmF0dHIoJ2Rpc2FibGVkJywgIWVsZW1lbnQuZW5hYmxlZCk7XG4gICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgIHZhciBpbnN0YW5jZSA9ICQoJyNwcmljZVJhbmdlU2xpZGVyJykuZGF0YShcbiAgICAgICAgICAgICAgICAgICAgICAgICdpb25SYW5nZVNsaWRlcidcbiAgICAgICAgICAgICAgICAgICAgKTtcbiAgICAgICAgICAgICAgICAgICAgaW5zdGFuY2UudXBkYXRlKHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGZyb206IGRhdGEuZnJvbSA/IGRhdGEuZnJvbSA6IGRhdGEubWluLFxuICAgICAgICAgICAgICAgICAgICAgICAgdG86IGRhdGEudG8gPyBkYXRhLnRvIDogZGF0YS5tYXgsXG4gICAgICAgICAgICAgICAgICAgICAgICBtaW46IGRhdGEubWluLFxuICAgICAgICAgICAgICAgICAgICAgICAgbWF4OiBkYXRhLm1heFxuICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuICAgIH1cblxuICAgIGZldGNoUHJvZHVjdHMoZmFsc2UpO1xuXG4gICAgZnVuY3Rpb24gc2Nyb2xsVG9BbmNob3IoKSB7XG4gICAgICAgIHZhciBhVGFnID0gJChcImFbaHJlZj0nI3BhZ2VcIiArIGlQYWdlTm8gKyBcIiddXCIpO1xuICAgICAgICBpUGFnZU5vID09IDFcbiAgICAgICAgICAgID8gJCgnaHRtbCxib2R5Jykuc2Nyb2xsVG9wKDApXG4gICAgICAgICAgICA6ICQoJ2h0bWwsYm9keScpLnNjcm9sbFRvcChhVGFnLnBvc2l0aW9uKCkudG9wKTtcbiAgICB9XG5cbiAgICAkKCdib2R5Jykub24oJ2NsaWNrJywgJy5jbGVhci1maWx0ZXInLCBmdW5jdGlvbigpIHtcbiAgICAgICAgaVBhZ2VObyA9IDA7XG5cbiAgICAgICAgdmFyICRmaWx0ZXIgPSAkKHRoaXMpLmNsb3Nlc3QoJy5maWx0ZXInKTtcbiAgICAgICAgaWYgKCRmaWx0ZXIuYXR0cignaWQnKSA9PT0gJ3ByaWNlRmlsdGVyJykge1xuICAgICAgICAgICAgdmFyICRpbnAgPSAkKHRoaXMpO1xuICAgICAgICAgICAgcHJpY2VfZnJvbSA9ICRpbnAuZGF0YSgnZnJvbScpO1xuICAgICAgICAgICAgcHJpY2VfdG8gPSAkaW5wLmRhdGEoJ3RvJyk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAkZmlsdGVyLmZpbmQoJ2lucHV0W3R5cGU9XCJjaGVja2JveFwiXScpLmVhY2goZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICAgICAgaWYgKHRoaXMuY2hlY2tlZCkge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLmNoZWNrZWQgPSBmYWxzZTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuXG4gICAgICAgIHVwZGF0ZUZpbHRlcnMoKTtcbiAgICAgICAgZmV0Y2hQcm9kdWN0cyh0cnVlKTtcbiAgICB9KTtcblxuICAgICQoJ2JvZHknKS5vbignY2xpY2snLCAnI2NsZWFyQWxsRmlsdGVyc0J0bicsIGZ1bmN0aW9uKCkge1xuICAgICAgICBpUGFnZU5vID0gMDtcblxuICAgICAgICBzdHJGaWx0ZXJzID0gJyc7XG4gICAgICAgICQoJy5maWx0ZXInKS5lYWNoKGZ1bmN0aW9uKCkge1xuICAgICAgICAgICAgaWYgKCQodGhpcykuYXR0cignaWQnKSA9PT0gJ3ByaWNlRmlsdGVyJykge1xuICAgICAgICAgICAgICAgIHZhciAkaW5wID0gJCh0aGlzKTtcbiAgICAgICAgICAgICAgICBwcmljZV9mcm9tID0gJGlucC5kYXRhKCdmcm9tJyk7XG4gICAgICAgICAgICAgICAgcHJpY2VfdG8gPSAkaW5wLmRhdGEoJ3RvJyk7XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICQodGhpcylcbiAgICAgICAgICAgICAgICAgICAgLmZpbmQoJ2lucHV0W3R5cGU9XCJjaGVja2JveFwiXScpXG4gICAgICAgICAgICAgICAgICAgIC5lYWNoKGZ1bmN0aW9uKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHRoaXMuY2hlY2tlZCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHRoaXMuY2hlY2tlZCA9IGZhbHNlO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSk7XG4gICAgICAgIGZldGNoUHJvZHVjdHModHJ1ZSk7XG4gICAgfSk7XG5cbiAgICAvKioqKioqKioqKioqKioqSW1wbGVtZW50YXRpb24gb2YgZmlsdGVyIGNoYW5nZXMgKioqKioqKioqKioqKiovXG4gICAgJCgnYm9keScpLm9uKCdjaGFuZ2UnLCAnLmZpbHRlciBpbnB1dFt0eXBlPVwiY2hlY2tib3hcIl0nLCBmdW5jdGlvbigpIHtcbiAgICAgICAgaVBhZ2VObyA9IDA7XG4gICAgICAgIHVwZGF0ZUZpbHRlcnMoKTtcbiAgICAgICAgZmV0Y2hQcm9kdWN0cyh0cnVlKTtcbiAgICB9KTtcblxuICAgICQoZG9jdW1lbnQpLm9uKCdzZWxlY3QtdmFsdWUtY2hhbmdlZCcsIGZ1bmN0aW9uKCkge1xuICAgICAgICBzdHJTb3J0VHlwZSA9ICQoJyNzZWxlY3Rib3gtc29ydCcpLmF0dHIoJ2FjdGl2ZScpO1xuICAgICAgICBpUGFnZU5vID0gMDtcbiAgICAgICAgdXBkYXRlRmlsdGVycygpO1xuICAgICAgICBmZXRjaFByb2R1Y3RzKHRydWUpO1xuICAgIH0pO1xuICAgICQoJ2lucHV0W25hbWU9XCJzb3J0LXByaWNlLWZpbHRlclwiXScpLmNsaWNrKGZ1bmN0aW9uKCkge1xuICAgICAgICBzdHJTb3J0VHlwZSA9ICQoJ2lucHV0W25hbWU9XCJzb3J0LXByaWNlLWZpbHRlclwiXTpjaGVja2VkJykudmFsKCk7XG4gICAgICAgIGlQYWdlTm8gPSAwO1xuICAgICAgICB1cGRhdGVGaWx0ZXJzKCk7XG4gICAgICAgIGZldGNoUHJvZHVjdHModHJ1ZSk7XG4gICAgICAgICQoJyNzb3J0LW1vYmlsZScpLnRvZ2dsZUNsYXNzKCdzaG93Jyk7XG4gICAgfSk7XG5cbiAgICBmdW5jdGlvbiB1cGRhdGVGaWx0ZXJzKCkge1xuICAgICAgICBzdHJGaWx0ZXJzID0gJyc7XG4gICAgICAgICQoJy5maWx0ZXInKS5lYWNoKGZ1bmN0aW9uKCkge1xuICAgICAgICAgICAgaWYgKCQodGhpcykuYXR0cignaWQnKSA9PT0gJ3ByaWNlRmlsdGVyJykge1xuICAgICAgICAgICAgICAgIGlmIChwcmljZV9mcm9tKSB7XG4gICAgICAgICAgICAgICAgICAgIHN0ckZpbHRlcnMgKz0gJ3ByaWNlX2Zyb206JyArIHByaWNlX2Zyb20gKyAnOyc7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGlmIChwcmljZV90bykge1xuICAgICAgICAgICAgICAgICAgICBzdHJGaWx0ZXJzICs9ICdwcmljZV90bzonICsgcHJpY2VfdG8gKyAnOyc7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICB2YXIgY3VyckZpbHRlciA9ICQodGhpcykuYXR0cignZGF0YS1maWx0ZXInKTtcbiAgICAgICAgICAgICAgICBzdHJGaWx0ZXJzICs9IGN1cnJGaWx0ZXIgKyAnOic7XG4gICAgICAgICAgICAgICAgdmFyIGJGaXJzdENoZWNrZWQgPSBmYWxzZTtcbiAgICAgICAgICAgICAgICAkKHRoaXMpXG4gICAgICAgICAgICAgICAgICAgIC5maW5kKCdpbnB1dFt0eXBlPVwiY2hlY2tib3hcIl0nKVxuICAgICAgICAgICAgICAgICAgICAuZWFjaChmdW5jdGlvbihpZHgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGlmICh0aGlzLmNoZWNrZWQpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YXIgZGVsaW07XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKCFiRmlyc3RDaGVja2VkKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGRlbGltID0gJyc7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGJGaXJzdENoZWNrZWQgPSB0cnVlO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGRlbGltID0gJywnO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBzdHJGaWx0ZXJzICs9IGRlbGltICsgJCh0aGlzKS5hdHRyKCd2YWx1ZScpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICBzdHJGaWx0ZXJzICs9ICc7JztcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSk7XG5cbiAgICAgICAgLy8gIHdpbmRvdy5sb2NhdGlvbi5zZWFyY2ggPSBzdHJGaWx0ZXJzO1xuICAgIH1cblxuICAgICQoJ2JvZHknKS5vbignbW91c2VvdmVyJywgJy5zbGljay1zbGlkZScsIGZ1bmN0aW9uKCkge1xuICAgICAgICAkKHRoaXMpXG4gICAgICAgICAgICAuY2xvc2VzdCgnLmxzLXByb2R1Y3QtZGl2JylcbiAgICAgICAgICAgIC5maW5kKCcudmFyaWF0aW9uLWltZycpXG4gICAgICAgICAgICAuYXR0cihcbiAgICAgICAgICAgICAgICAnc3JjJyxcbiAgICAgICAgICAgICAgICAkKHRoaXMpXG4gICAgICAgICAgICAgICAgICAgIC5maW5kKCcuY2Fyb3VzZWwtaW1nJylcbiAgICAgICAgICAgICAgICAgICAgLmF0dHIoJ2RhdGEtcHJvZGltZycpXG4gICAgICAgICAgICApO1xuICAgICAgICAkKHRoaXMpXG4gICAgICAgICAgICAuY2xvc2VzdCgnLmxzLXByb2R1Y3QtZGl2JylcbiAgICAgICAgICAgIC5maW5kKCcucHJvZC1pbWcnKVxuICAgICAgICAgICAgLmNzcygndmlzaWJpbGl0eScsICdoaWRkZW4nKTtcbiAgICAgICAgJCh0aGlzKVxuICAgICAgICAgICAgLmNsb3Nlc3QoJy5scy1wcm9kdWN0LWRpdicpXG4gICAgICAgICAgICAuZmluZCgnLnZhcmlhdGlvbi1pbWcnKVxuICAgICAgICAgICAgLnNob3coKTtcbiAgICB9KTtcblxuICAgICQoJ2JvZHknKS5vbignbW91c2VsZWF2ZScsICcuc2xpY2stc2xpZGUnLCBmdW5jdGlvbigpIHtcbiAgICAgICAgJCh0aGlzKVxuICAgICAgICAgICAgLmNsb3Nlc3QoJy5scy1wcm9kdWN0LWRpdicpXG4gICAgICAgICAgICAuZmluZCgnLnZhcmlhdGlvbi1pbWcnKVxuICAgICAgICAgICAgLmhpZGUoKTtcbiAgICAgICAgJCh0aGlzKVxuICAgICAgICAgICAgLmNsb3Nlc3QoJy5scy1wcm9kdWN0LWRpdicpXG4gICAgICAgICAgICAuZmluZCgnLnByb2QtaW1nJylcbiAgICAgICAgICAgIC5jc3MoJ3Zpc2liaWxpdHknLCAndW5zZXQnKTtcbiAgICB9KTtcblxuICAgICQoJ2JvZHknKS5vbignY2xpY2snLCAnLmRyb3Bkb3duLXN1Ym1lbnUgYScsIGZ1bmN0aW9uKGUpIHtcbiAgICAgICAgaWYgKGlzTW9iaWxlKCkpIHtcbiAgICAgICAgICAgIC8vIGVhcmx5IHJldHVybiBpZiB0aGUgcGFyZW50IGhhcyBubyBob3Zlci1jbGFzc1xuICAgICAgICAgICAgaWYgKCEkKHRoaXMpLmhhc0NsYXNzKCdob3ZlcicpKSByZXR1cm47XG5cbiAgICAgICAgICAgIC8vIHByZXZlbnQgY2xpY2sgd2hlbiBkZWxheSBpcyB0b28gc21hbGxcbiAgICAgICAgICAgIHZhciBkZWxheSA9IERhdGUubm93KCkgLSAkKHRoaXMpLmRhdGEoJ2hvdmVyZWQnKTtcbiAgICAgICAgICAgIGlmIChkZWxheSA8IDEwMCkgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICB9XG4gICAgfSk7XG5cbiAgICAkKCdib2R5Jykub24oJ21vdXNlb3ZlcicsICcuZHJvcGRvd24tc3VibWVudSBhJywgZnVuY3Rpb24oZSkge1xuICAgICAgICBpZiAoaXNNb2JpbGUoKSkge1xuICAgICAgICAgICAgdmFyIHRpbWUgPSBEYXRlLm5vdygpO1xuICAgICAgICAgICAgJCh0aGlzKS5kYXRhKCdob3ZlcmVkJywgdGltZSk7XG4gICAgICAgIH1cbiAgICB9KTtcblxuICAgICQoJ2JvZHknKS5vbignY2xpY2snLCAnLndpc2hsaXN0LWljb246bm90KC5uYXYtbGluayknLCBmdW5jdGlvbihlKSB7XG4gICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgZS5zdG9wUHJvcGFnYXRpb24oKTtcbiAgICAgICAgaWYgKCQoJyNpc0xvZ2dlZEluJykudmFsKCkgPT0gMCkge1xuICAgICAgICAgICAgJCgnI21vZGFsTG9naW5Gb3JtJykubW9kYWwoKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIHZhciBpU2t1ID0gJCh0aGlzKS5hdHRyKCdza3UnKTtcbiAgICAgICAgICAgIGNhbGxXaXNobGlzdEFQSSgkKHRoaXMpKTtcbiAgICAgICAgfVxuICAgIH0pO1xuXG4gICAgZnVuY3Rpb24gY2FsbFdpc2hsaXN0QVBJKCRlbG0pIHtcbiAgICAgICAgdmFyIHN0ckFwaVRvQ2FsbCA9ICcnO1xuICAgICAgICBpZiAoISRlbG0uaGFzQ2xhc3MoJ21hcmtlZCcpKSB7XG4gICAgICAgICAgICBzdHJBcGlUb0NhbGwgPSBGQVZfTUFSS19BUEkgKyAkZWxtLmF0dHIoJ3NrdScpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgc3RyQXBpVG9DYWxsID0gRkFWX1VOTUFSS19BUEkgKyAkZWxtLmF0dHIoJ3NrdScpO1xuICAgICAgICB9XG5cbiAgICAgICAgJC5hamF4KHtcbiAgICAgICAgICAgIHR5cGU6ICdHRVQnLFxuICAgICAgICAgICAgdXJsOiBzdHJBcGlUb0NhbGwsXG4gICAgICAgICAgICBkYXRhVHlwZTogJ2pzb24nLFxuICAgICAgICAgICAgc3VjY2VzczogZnVuY3Rpb24oZGF0YSkge1xuICAgICAgICAgICAgICAgIGlmICghJGVsbS5oYXNDbGFzcygnbWFya2VkJykpIHtcbiAgICAgICAgICAgICAgICAgICAgJGVsbS5hZGRDbGFzcygnbWFya2VkJyk7XG4gICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgJGVsbS5yZW1vdmVDbGFzcygnbWFya2VkJyk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIGVycm9yOiBmdW5jdGlvbihqcVhIUiwgZXhjZXB0aW9uKSB7XG4gICAgICAgICAgICAgICAgY29uc29sZS5sb2coanFYSFIpO1xuICAgICAgICAgICAgICAgIGNvbnNvbGUubG9nKGV4Y2VwdGlvbik7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICAgIH1cbn0pO1xuIiwicmVxdWlyZSgnYm9vdHN0cmFwJyk7XG5yZXF1aXJlKCdzbGljay1saWdodGJveCcpO1xucmVxdWlyZSgnc2xpY2stY2Fyb3VzZWwnKTtcbnJlcXVpcmUoJy4vY29tcG9uZW50cy9tdWx0aS1jYXJvdXNlbCcpO1xucmVxdWlyZSgnLi9jb21wb25lbnRzL2N1c3RvbS1zZWxlY3Rib3gnKTtcbnZhciBtZCA9IHJlcXVpcmUoJ21hcmtkb3duLWl0Jykoe1xuICAgIGh0bWw6IHRydWUsXG4gICAgYnJlYWtzOiB0cnVlXG59KTtcbmZ1bmN0aW9uIHNsaWNrTGlnaHRib3hjb2RlKCkge1xuICAgICQoJy50b3BjYXRlTGlnaHRCb3gnKS5zbGljayh7XG4gICAgICAgIGluZmluaXRlOiB0cnVlLFxuICAgICAgICBzbGlkZXNUb1Nob3c6IDMsXG4gICAgICAgIHNsaWRlc1RvU2Nyb2xsOiAxLFxuICAgICAgICBtb2JpbGVGaXJzdDogdHJ1ZVxuICAgIH0pO1xuICAgICQoJy50b3BjYXRlTGlnaHRCb3gnKS5zbGlja0xpZ2h0Ym94KHtcbiAgICAgICAgaXRlbVNlbGVjdG9yOiAnYScsXG4gICAgICAgIG5hdmlnYXRlQnlLZXlib2FyZDogdHJ1ZSxcbiAgICAgICAgY2FwdGlvblBvc2l0aW9uOiAnZHluYW1pYycsXG4gICAgICAgIGxheW91dHM6IHtcbiAgICAgICAgICAgIGNsb3NlQnV0dG9uOlxuICAgICAgICAgICAgICAgICc8YnV0dG9uIHR5cGU9XCJidXR0b25cIiBjbGFzcz1cInNsaWNrLWxpZ2h0Ym94LWNsb3NlXCI+PC9idXR0b24+J1xuICAgICAgICB9XG4gICAgfSk7XG59XG4kKGRvY3VtZW50KS5yZWFkeShmdW5jdGlvbigpIHtcbiAgICAkKCcjZGVwYXJ0bWVudHNOYXYnKS5vbignY2xpY2snLCAnLmRyb3Bkb3duJywgZnVuY3Rpb24oZSkge1xuICAgICAgICBjb25zb2xlLmxvZygndGVzdCcpO1xuICAgICAgICAvLyBlLnByZXZlbnREZWZhdWx0KClcbiAgICAgICAgJCh0aGlzKVxuICAgICAgICAgICAgLnNpYmxpbmdzKClcbiAgICAgICAgICAgIC5yZW1vdmVDbGFzcygnYWN0aXZlJyk7XG4gICAgICAgICQodGhpcykuYWRkQ2xhc3MoJ2FjdGl2ZScpO1xuICAgIH0pO1xuICAgICQoJyNzZWFyY2hiYXJIZWFkZXInKS5zdWJtaXQoZnVuY3Rpb24oZSkge1xuICAgICAgICBjYWxsU2VhcmNoKGUsIHRoaXMpO1xuICAgIH0pO1xuICAgICQoJy5uYXZiYXItdG9nZ2xlcicpLmNsaWNrKGZ1bmN0aW9uKCkge1xuICAgICAgICAkKCcjU2lkZW5hdmJhcicpLmNzcygnd2lkdGgnLCAnMzAwcHgnKTtcbiAgICB9KTtcbiAgICAkKCcuc2ItYm9keScpLnN1Ym1pdChmdW5jdGlvbihlKSB7XG4gICAgICAgIGNhbGxTZWFyY2goZSwgdGhpcyk7XG4gICAgfSk7XG4gICAgJCgnI1NpZGVuYXZiYXJjbG9zZScpLmNsaWNrKGZ1bmN0aW9uKCkge1xuICAgICAgICAkKCcjU2lkZW5hdmJhcicpLmNzcygnd2lkdGgnLCAnMHB4Jyk7XG4gICAgfSk7XG4gICAgJCgnLmFycm93Jykub24oJ2NsaWNrJywgZnVuY3Rpb24oZXZlbnQpIHtcbiAgICAgICAgJCgnLmFycm93LWltZycpLnRvZ2dsZUNsYXNzKCdyb3RhdGUnKTtcbiAgICAgICAgJCgnLmFycm93LWltZycpLnRvZ2dsZUNsYXNzKCdyb3RhdGUtcmVzZXQnKTtcbiAgICB9KTtcblxuICAgICQoZG9jdW1lbnQpLm9uKCdjbGljaycsICcuY29sbGFwc2libGUnLCBmdW5jdGlvbigpIHtcbiAgICAgICAgJCgnLmNvbGxhcHNpYmxlJykucmVtb3ZlQ2xhc3MoJ2FjdGl2ZScpO1xuICAgICAgICB0aGlzLmNsYXNzTGlzdC50b2dnbGUoJ2FjdGl2ZScpO1xuICAgICAgICAkKCcuY29sbGFwc2UnKS5oaWRlKCk7XG4gICAgICAgICQodGhpcy5nZXRBdHRyaWJ1dGUoJ2RhdGEtdGFyZ2V0JykpLnNob3coKTtcbiAgICB9KTtcblxuICAgIGZ1bmN0aW9uIGNhbGxTZWFyY2goZSwgZWxtKSB7XG4gICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgd2luZG93LmxvY2F0aW9uLmhyZWYgPVxuICAgICAgICAgICAgJy9zZWFyY2g/cXVlcnk9JyArXG4gICAgICAgICAgICAkKGVsbSlcbiAgICAgICAgICAgICAgICAuZmluZCgnaW5wdXQnKVxuICAgICAgICAgICAgICAgIC52YWwoKTsgLy9yZWxhdGl2ZSB0byBkb21haW5cbiAgICB9XG5cbiAgICB2YXIgJHNlYXJjaEljb24gPSAkKCcjc2VhcmNoSWNvbk1vYmlsZScpO1xuXG4gICAgY29uc3QgREVQVF9BUEkgPSAnL2FwaS9hbGwtZGVwYXJ0bWVudHMnO1xuXG4gICAgJHNlYXJjaEljb24ub24oJ2NsaWNrJywgZnVuY3Rpb24oZSkge1xuICAgICAgICBpZiAoJCh0aGlzKS5hdHRyKCdpZCcpID09ICdzZWFyY2hJY29uTW9iaWxlJykge1xuICAgICAgICAgICAgaWYgKCQoJyNzZWFyY2hiYXJIZWFkZXInKS5oYXNDbGFzcygnb3BlbicpKSB7XG4gICAgICAgICAgICAgICAgJCgnI3NlYXJjaGJhckhlYWRlcicpLnJlbW92ZUNsYXNzKCdvcGVuJyk7XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICQoJyNzZWFyY2hiYXJIZWFkZXInKS5hZGRDbGFzcygnb3BlbicpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfSk7XG5cbiAgICAkKCcudXNlci1sb2dpbi1tb2RhbCcpLmNsaWNrKGZ1bmN0aW9uKCkge1xuICAgICAgICAkKCcjbW9kYWxTaWdudXBGb3JtJykubW9kYWwoJ3RvZ2dsZScpO1xuICAgIH0pO1xuICAgICQoJyNyZWdpc3Rlci1tb2RhbCcpLmNsaWNrKGZ1bmN0aW9uKCkge1xuICAgICAgICAkKCcjbW9kYWxTaWdudXBGb3JtJykubW9kYWwoJ3RvZ2dsZScpO1xuICAgICAgICAkKCcjbW9kYWxMb2dpbkZvcm0nKS5tb2RhbCgndG9nZ2xlJyk7XG4gICAgfSk7XG4gICAgJCgnLnVzZXItbG9naW4tbW9kYWwxJykuY2xpY2soZnVuY3Rpb24oKSB7XG4gICAgICAgICQoJyNtb2RhbFNpZ251cEZvcm0nKS5tb2RhbCgndG9nZ2xlJyk7XG4gICAgICAgICQoJyNtb2RhbExvZ2luRm9ybScpLm1vZGFsKCd0b2dnbGUnKTtcbiAgICB9KTtcblxuICAgICQoJy53aXNobGlzdC1sb2dpbi1tb2RhbCcpLmNsaWNrKGZ1bmN0aW9uKCkge1xuICAgICAgICAkKCcjbW9kYWxMb2dpbkZvcm0nKS5tb2RhbCgpO1xuICAgIH0pO1xuXG4gICAgJCgnYm9keScpLm9uKCdtb3VzZW92ZXInLCAnLmRyb3Bkb3duLXN1Ym1lbnUnLCBmdW5jdGlvbihlKSB7XG4gICAgICAgIHZhciBzZWxmID0gdGhpcztcbiAgICAgICAgJCgnLmRyb3Bkb3duLXN1Ym1lbnUnKS5lYWNoKGZ1bmN0aW9uKCkge1xuICAgICAgICAgICAgaWYgKCQodGhpcykuZmluZCgnLmRyb3Bkb3duLW1lbnUnKVswXSAhPSAkKHNlbGYpLm5leHQoJ3VsJylbMF0pIHtcbiAgICAgICAgICAgICAgICAkKHRoaXMpXG4gICAgICAgICAgICAgICAgICAgIC5maW5kKCcuZHJvcGRvd24tbWVudScpXG4gICAgICAgICAgICAgICAgICAgIC5oaWRlKCk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICAgICAgICAkKHRoaXMpXG4gICAgICAgICAgICAuZmluZCgndWwnKVxuICAgICAgICAgICAgLnRvZ2dsZSgpO1xuICAgICAgICBpZiAoIWlzTW9iaWxlKCkpIHtcbiAgICAgICAgICAgICQodGhpcylcbiAgICAgICAgICAgICAgICAuZmluZCgnLmRyb3Bkb3duLW1lbnUnKVxuICAgICAgICAgICAgICAgIC5jc3MoJ3RvcCcsICQodGhpcykucG9zaXRpb24oKS50b3ApO1xuICAgICAgICB9XG4gICAgfSk7XG4gICAgJCgnI21hZGluYWgtY2Fyb3VzZWwnKS5jYXJvdXNlbCh7IGludGVydmFsOiBmYWxzZSwgYXV0b3BsYXk6IGZhbHNlIH0pO1xuICAgICQoJyNjYXJvdXNlbFRyZW5kaW5nJykuY2Fyb3VzZWwoeyBpbnRlcnZhbDogZmFsc2UsIGF1dG9wbGF5OiBmYWxzZSB9KTtcblxuICAgICQuYWpheCh7XG4gICAgICAgIHR5cGU6ICdHRVQnLFxuICAgICAgICB1cmw6IERFUFRfQVBJLFxuICAgICAgICBkYXRhVHlwZTogJ2pzb24nLFxuICAgICAgICBzdWNjZXNzOiBmdW5jdGlvbihkYXRhKSB7XG4gICAgICAgICAgICBjb25zdCB7XG4gICAgICAgICAgICAgICAgYWxsX2RlcGFydG1lbnRzLFxuICAgICAgICAgICAgICAgIHRyZW5kaW5nX2NhdGVnb3JpZXMsXG4gICAgICAgICAgICAgICAgdHJlbmRpbmdfcHJvZHVjdHNcbiAgICAgICAgICAgIH0gPSBkYXRhO1xuICAgICAgICAgICAgdmFyICRjYXJvdXNlbElubmVyID0gJCgnI2Nhcm91c2VsLWlubmVyJyk7XG4gICAgICAgICAgICB2YXIgJGNhcm91c2VsSW5uZXJ0cmVuZCA9ICQoJyNjYXJvdXNlbC1pbm5lci10cmVuZGluZycpO1xuICAgICAgICAgICAgdmFyIGRlcHRUb0FwcGVuZCA9ICcnO1xuICAgICAgICAgICAgaWYgKGlzTW9iaWxlKCkpIHtcbiAgICAgICAgICAgICAgICB0cmVuZGluZ19jYXRlZ29yaWVzLm1hcCgoaXRlbSwgaW5kZXgpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgdmFyICRpdGVtID0galF1ZXJ5KCc8ZGl2Lz4nLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICBjbGFzczpcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpbmRleCA9PSAwXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgID8gJ2Nhcm91c2VsLWl0ZW0gY29sLXNtLTEyICBhY3RpdmUnXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDogJ2Nhcm91c2VsLWl0ZW0gY29sLXNtLTEyJ1xuICAgICAgICAgICAgICAgICAgICB9KS5hcHBlbmRUbygkY2Fyb3VzZWxJbm5lcik7XG5cbiAgICAgICAgICAgICAgICAgICAgdmFyIGFuY2hvciA9IGpRdWVyeSgnPGEvPicsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGhyZWY6IGl0ZW0ubGlua1xuICAgICAgICAgICAgICAgICAgICB9KS5hcHBlbmRUbygkaXRlbSk7XG4gICAgICAgICAgICAgICAgICAgIHZhciBpbWcgPSBqUXVlcnkoJzxpbWcvPicsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHNyYzogYCR7aXRlbS5pbWFnZX1gLFxuICAgICAgICAgICAgICAgICAgICAgICAgaGVpZ2h0OiAnMTUwcHgnXG4gICAgICAgICAgICAgICAgICAgIH0pLmFwcGVuZFRvKGFuY2hvcik7XG4gICAgICAgICAgICAgICAgICAgIHZhciBkaXYgPSBqUXVlcnkoJzxkaXYvPicsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGNsYXNzOiAnY29sLXNtLTEyJ1xuICAgICAgICAgICAgICAgICAgICB9KS5hcHBlbmRUbyhhbmNob3IpO1xuICAgICAgICAgICAgICAgICAgICB2YXIgc3BhbiA9IGpRdWVyeSgnPHNwYW4vPicsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGh0bWw6IGAke2l0ZW0uY2F0ZWdvcnl9YCxcbiAgICAgICAgICAgICAgICAgICAgICAgIGNsYXNzOiAndG9wLXRyZW5kaW5nLXRleHQgdGV4dC1jZW50ZXInXG4gICAgICAgICAgICAgICAgICAgIH0pLmFwcGVuZFRvKGRpdik7XG4gICAgICAgICAgICAgICAgICAgIHZhciBsaSA9IGpRdWVyeSgnPGxpLz4nLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICAnZGF0YS10YXJnZXQnOiAnI21hZGluYWgtY2Fyb3VzZWwnLFxuICAgICAgICAgICAgICAgICAgICAgICAgJ2RhdGEtc2xpZGUtdG8nOiBpbmRleCxcbiAgICAgICAgICAgICAgICAgICAgICAgIGNsYXNzOiBpbmRleCA9PSAwID8gJ2FjdGl2ZScgOiAnJ1xuICAgICAgICAgICAgICAgICAgICB9KS5hcHBlbmRUbygnI21hZGluYWhjYXJvdXNlbGluZGljYXRvcicpO1xuICAgICAgICAgICAgICAgICAgICAvLyBzbGlja0xpZ2h0Ym94Y29kZSgpXG4gICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgc2xpY2tMaWdodGJveGNvZGUoKTtcblxuICAgICAgICAgICAgICAgIHRyZW5kaW5nX3Byb2R1Y3RzLm1hcCgoaXRlbSwgaW5kZXgpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgdmFyICRpdGVtID0galF1ZXJ5KCc8ZGl2Lz4nLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICBjbGFzczpcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpbmRleCA9PSAwXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgID8gJ2Nhcm91c2VsLWl0ZW0gY29sLXNtLTEyICBhY3RpdmUnXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDogJ2Nhcm91c2VsLWl0ZW0gY29sLXNtLTEyJ1xuICAgICAgICAgICAgICAgICAgICB9KS5hcHBlbmRUbygkY2Fyb3VzZWxJbm5lcnRyZW5kKTtcbiAgICAgICAgICAgICAgICAgICAgdmFyIGxpZ2h0Ym94ID0galF1ZXJ5KCc8YS8+Jywge1xuICAgICAgICAgICAgICAgICAgICAgICAgY2xhc3M6ICdsaWdodC1ib3gnLFxuICAgICAgICAgICAgICAgICAgICAgICAgaHJlZjogaXRlbS5pbWFnZSxcbiAgICAgICAgICAgICAgICAgICAgICAgICdkYXRhLWNhcHRpb24nOiAnJ1xuICAgICAgICAgICAgICAgICAgICB9KS5hcHBlbmRUbygkaXRlbSk7XG4gICAgICAgICAgICAgICAgICAgIHZhciBpbWcgPSBqUXVlcnkoJzxpbWcvPicsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHNyYzogYCR7aXRlbS5tYWluX2ltYWdlfWBcbiAgICAgICAgICAgICAgICAgICAgfSkuYXBwZW5kVG8obGlnaHRib3gpO1xuICAgICAgICAgICAgICAgICAgICB2YXIgZGl2ID0galF1ZXJ5KCc8ZGl2Lz4nLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICBodG1sOiBgJHtpdGVtLnNpdGV9YCxcbiAgICAgICAgICAgICAgICAgICAgICAgIGNsYXNzOiAndG9wLXRyZW5kaW5nLXNpdGUgdGV4dC1jZW50ZXInXG4gICAgICAgICAgICAgICAgICAgIH0pLmFwcGVuZFRvKCRpdGVtKTtcbiAgICAgICAgICAgICAgICAgICAgdmFyIG5hbWVMaW5rID0galF1ZXJ5KCc8YS8+Jywge1xuICAgICAgICAgICAgICAgICAgICAgICAgdGFyZ2V0OiAnX2JsYW5rJyxcbiAgICAgICAgICAgICAgICAgICAgICAgIGhyZWY6IGl0ZW0ucHJvZHVjdF91cmxcbiAgICAgICAgICAgICAgICAgICAgfSkuYXBwZW5kVG8oJGl0ZW0pO1xuICAgICAgICAgICAgICAgICAgICB2YXIgZGl2ID0galF1ZXJ5KCc8aDMvPicsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGh0bWw6IGAke2l0ZW0ubmFtZX1gLFxuICAgICAgICAgICAgICAgICAgICAgICAgY2xhc3M6ICd0b3AtdHJlbmRpbmctdGV4dCB0ZXh0LWNlbnRlcidcbiAgICAgICAgICAgICAgICAgICAgfSkuYXBwZW5kVG8obmFtZUxpbmspO1xuICAgICAgICAgICAgICAgICAgICB2YXIgcHJpY2VMaW5rID0galF1ZXJ5KCc8YS8+Jywge1xuICAgICAgICAgICAgICAgICAgICAgICAgdGFyZ2V0OiAnX2JsYW5rJyxcbiAgICAgICAgICAgICAgICAgICAgICAgIGhyZWY6IGl0ZW0ucHJvZHVjdF91cmxcbiAgICAgICAgICAgICAgICAgICAgfSkuYXBwZW5kVG8oJGl0ZW0pO1xuICAgICAgICAgICAgICAgICAgICB2YXIgcHJpY2VkaXYgPSBqUXVlcnkoJzxkaXYvPicsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGNsYXNzOiAncHJvZC1wcmljZS1kaXYnXG4gICAgICAgICAgICAgICAgICAgIH0pLmFwcGVuZFRvKHByaWNlTGluayk7XG4gICAgICAgICAgICAgICAgICAgIHZhciBsaSA9IGpRdWVyeSgnPGxpLz4nLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICAnZGF0YS10YXJnZXQnOiAnI2Nhcm91c2VsVHJlbmRpbmcnLFxuICAgICAgICAgICAgICAgICAgICAgICAgJ2RhdGEtc2xpZGUtdG8nOiBpbmRleCxcbiAgICAgICAgICAgICAgICAgICAgICAgIGNsYXNzOiBpbmRleCA9PSAwID8gJ2FjdGl2ZScgOiAnJ1xuICAgICAgICAgICAgICAgICAgICB9KS5hcHBlbmRUbygnI3RvcHRyZW5kaW5naW5kaWNhdG9yJyk7XG5cbiAgICAgICAgICAgICAgICAgICAgaWYgKGl0ZW0uaXNfcHJpY2UuaW5jbHVkZXMoJy0nKSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgbGV0IHNhbGVwcmljZVJhbmdlID0gaXRlbS5pc19wcmljZS5zcGxpdCgnLScpO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgc2FsZXByaWNlID0galF1ZXJ5KCc8c3BhbiAvPicsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB0ZXh0OiBgJCR7TWF0aC5yb3VuZChcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgc2FsZXByaWNlUmFuZ2VbMF1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICApLnRvTG9jYWxlU3RyaW5nKCl9IC0gJCR7TWF0aC5yb3VuZChcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgc2FsZXByaWNlUmFuZ2VbMV1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICApLnRvTG9jYWxlU3RyaW5nKCl9YCxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBjbGFzczogJ3Byb2Qtc2FsZS1wcmljZSBkLW1kLW5vbmUnXG4gICAgICAgICAgICAgICAgICAgICAgICB9KS5hcHBlbmRUbyhwcmljZWRpdik7XG4gICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgc2FsZXByaWNlID0galF1ZXJ5KCc8c3BhbiAvPicsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB0ZXh0OiBgJCR7TWF0aC5yb3VuZChcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaXRlbS5pc19wcmljZVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICkudG9Mb2NhbGVTdHJpbmcoKX1gLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNsYXNzOiAncHJvZC1zYWxlLXByaWNlIGQtbWQtbm9uZSdcbiAgICAgICAgICAgICAgICAgICAgICAgIH0pLmFwcGVuZFRvKHByaWNlZGl2KTtcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICBpZiAoaXRlbS53YXNfcHJpY2UgIT09IGl0ZW0uaXNfcHJpY2UpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGlmIChpdGVtLndhc19wcmljZS5pbmNsdWRlcygnLScpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgbGV0IHNhbGVwcmljZVJhbmdlID0gaXRlbS53YXNfcHJpY2Uuc3BsaXQoJy0nKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YXIgc2FsZXByaWNlID0galF1ZXJ5KCc8c3BhbiAvPicsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdGV4dDogYCQke01hdGgucm91bmQoXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBzYWxlcHJpY2VSYW5nZVswXVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICApLnRvTG9jYWxlU3RyaW5nKCl9IC0gJCR7TWF0aC5yb3VuZChcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNhbGVwcmljZVJhbmdlWzFdXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICkudG9Mb2NhbGVTdHJpbmcoKX1gLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBjbGFzczogJ3Byb2Qtd2FzLXByaWNlIGQtbWQtbm9uZSdcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KS5hcHBlbmRUbyhwcmljZWRpdik7XG4gICAgICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBzYWxlcHJpY2UgPSBqUXVlcnkoJzxzcGFuIC8+Jywge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB0ZXh0OiBgJCR7TWF0aC5yb3VuZChcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGl0ZW0ud2FzX3ByaWNlXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICkudG9Mb2NhbGVTdHJpbmcoKX1gLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBjbGFzczogJ3Byb2Qtd2FzLXByaWNlIGQtbWQtbm9uZSdcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KS5hcHBlbmRUbyhwcmljZWRpdik7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICAgICB2YXIgY29sbGFwc2VCdG5EaXYgPSBqUXVlcnkoJzxkaXYvPicsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGNsYXNzOiAnY29sbGFwc2UtYnRuJ1xuICAgICAgICAgICAgICAgICAgICB9KS5hcHBlbmRUbygkaXRlbSk7XG4gICAgICAgICAgICAgICAgICAgIHZhciBjb2xsYXBzZUJ0biA9IGpRdWVyeSgnPGEvPicsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGNsYXNzOiAnbG9hZC1tb3JlLWJ1dHRvbiBjb2xsYXBzZWQnLFxuICAgICAgICAgICAgICAgICAgICAgICAgJ2RhdGEtdG9nZ2xlJzogJ2NvbGxhcHNlJyxcbiAgICAgICAgICAgICAgICAgICAgICAgIGhyZWY6ICcjY29sbGFwc2VFeGFtcGxlJyArIGluZGV4ICsgJycsXG4gICAgICAgICAgICAgICAgICAgICAgICByb2xlOiAnYnV0dG9uJyxcbiAgICAgICAgICAgICAgICAgICAgICAgICdhcmlhLWV4cGFuZGVkJzogJ2ZhbHNlJyxcbiAgICAgICAgICAgICAgICAgICAgICAgICdhcmlhLWNvbnRyb2xzJzogJ2NvbGxhcHNlRXhhbXBsZSdcbiAgICAgICAgICAgICAgICAgICAgfSkuYXBwZW5kVG8oY29sbGFwc2VCdG5EaXYpO1xuICAgICAgICAgICAgICAgICAgICB2YXIgbG9hZE1vcmUgPSBqUXVlcnkoJzxzcGFuLz4nLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICBjbGFzczogJ21vcmUnLFxuICAgICAgICAgICAgICAgICAgICAgICAgaHRtbDpcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAnPGkgY2xhc3M9XCJmYSBmYS1hbmdsZS1kb3duXCIgYXJpYS1oaWRkZW49XCJ0cnVlXCI+PC9pPiBNb3JlICdcbiAgICAgICAgICAgICAgICAgICAgfSkuYXBwZW5kVG8oY29sbGFwc2VCdG4pO1xuICAgICAgICAgICAgICAgICAgICB2YXIgbG9hZExlc3MgPSBqUXVlcnkoJzxzcGFuLz4nLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICBjbGFzczogJ2xlc3MnLFxuICAgICAgICAgICAgICAgICAgICAgICAgaHRtbDpcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAnPGkgY2xhc3M9XCJmYSBmYS1hbmdsZS11cFwiIGFyaWEtaGlkZGVuPVwidHJ1ZVwiPjwvaT4gTGVzcyAnXG4gICAgICAgICAgICAgICAgICAgIH0pLmFwcGVuZFRvKGNvbGxhcHNlQnRuKTtcbiAgICAgICAgICAgICAgICAgICAgdmFyIGNvbGxhcHNlTWFpbkRpdiA9IGpRdWVyeSgnPGRpdi8+Jywge1xuICAgICAgICAgICAgICAgICAgICAgICAgY2xhc3M6ICdjb2xsYXBzZScsXG4gICAgICAgICAgICAgICAgICAgICAgICBpZDogJ2NvbGxhcHNlRXhhbXBsZScgKyBpbmRleCArICcnXG4gICAgICAgICAgICAgICAgICAgIH0pLmFwcGVuZFRvKCRpdGVtKTtcbiAgICAgICAgICAgICAgICAgICAgdmFyIGNvbGxhcHNlSW5uZXJEaXYgPSBqUXVlcnkoJzxkaXYvPicsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGNsYXNzOiAnY2xhc3MtYm9keSB0b3AtdHJlbmRpbmctdGV4dCB0ZXh0LWNlbnRlcicsXG4gICAgICAgICAgICAgICAgICAgICAgICBodG1sOiBtZC5yZW5kZXIoaXRlbS5kZXNjcmlwdGlvbi5qb2luKCdcXG4nKSlcbiAgICAgICAgICAgICAgICAgICAgfSkuYXBwZW5kVG8oY29sbGFwc2VNYWluRGl2KTtcbiAgICAgICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgICAgIC8vICQoJyNjYXJvdXNlbFRyZW5kaW5nJykuc2xpY2soe1xuICAgICAgICAgICAgICAgIC8vICAgICBpbmZpbml0ZTogdHJ1ZSxcbiAgICAgICAgICAgICAgICAvLyAgICAgc2xpZGVzVG9TaG93OiAzLFxuICAgICAgICAgICAgICAgIC8vICAgICBzbGlkZXNUb1Njcm9sbDogMSxcbiAgICAgICAgICAgICAgICAvLyAgICAgbW9iaWxlRmlyc3Q6IHRydWVcbiAgICAgICAgICAgICAgICAvLyB9KVxuICAgICAgICAgICAgICAgIC8vICQoJyNjYXJvdXNlbFRyZW5kaW5nJykuc2xpY2soe1xuICAgICAgICAgICAgICAgIC8vICAgICBpdGVtU2VsZWN0b3I6ICdhJyxcbiAgICAgICAgICAgICAgICAvLyAgICAgbmF2aWdhdGVCeUtleWJvYXJkOiB0cnVlLFxuICAgICAgICAgICAgICAgIC8vICAgICBjYXB0aW9uUG9zaXRpb246ICdkeW5hbWljJyxcbiAgICAgICAgICAgICAgICAvLyAgICAgbGF5b3V0czoge1xuICAgICAgICAgICAgICAgIC8vICAgICAgICAgY2xvc2VCdXR0b246XG4gICAgICAgICAgICAgICAgLy8gICAgICAgICAgICAgJzxidXR0b24gdHlwZT1cImJ1dHRvblwiIGNsYXNzPVwic2xpY2stbGlnaHRib3gtY2xvc2VcIj48L2J1dHRvbj4nXG4gICAgICAgICAgICAgICAgLy8gICAgIH1cbiAgICAgICAgICAgICAgICAvLyB9KVxuXG4gICAgICAgICAgICAgICAgJCgnI2NvbGxhcHNpYmxlLWRlcHQnKS5lbXB0eSgpO1xuICAgICAgICAgICAgICAgIHZhciBkZXB0VG9BcHBlbmQgPSAnJztcbiAgICAgICAgICAgICAgICBmb3IgKHZhciBpID0gMDsgaSA8IGFsbF9kZXBhcnRtZW50cy5sZW5ndGg7IGkrKykge1xuICAgICAgICAgICAgICAgICAgICBpZiAoYWxsX2RlcGFydG1lbnRzW2ldLmNhdGVnb3JpZXMubGVuZ3RoID09IDApIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGRlcHRUb0FwcGVuZCArPVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICc8bGkgY2xhc3M9XCJkZXBhcnRtZW50XCI+PGEgY2xhc3M9XCJsaW5rIGNvbGxhcHNpYmxlXCIgaHJlZj1cIicgK1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGFsbF9kZXBhcnRtZW50c1tpXS5saW5rICtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAnXCI+JyArXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgYWxsX2RlcGFydG1lbnRzW2ldLmRlcGFydG1lbnQgK1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICc8L2E+PC9saT4nO1xuICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgZGVwdFRvQXBwZW5kICs9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJzxsaSBjbGFzcz1cImRlcGFydG1lbnRcIj48YSAgY2xhc3M9XCJjb2xsYXBzaWJsZVwiIGRhdGEtdG9nZ2xlPVwiY29sbGFwc2VcIiBkYXRhLXRhcmdldD1cIiMnICtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBhbGxfZGVwYXJ0bWVudHNbaV0uZGVwYXJ0bWVudCArXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJ1wiPjxzcGFuIGNsYXNzPVwibGlua1wiPicgK1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGFsbF9kZXBhcnRtZW50c1tpXS5kZXBhcnRtZW50ICtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAnPC9zcGFuPjxzcGFuICBjbGFzcz1cInNpZGUtbmF2LWljb25cIiBpZD1cIm5hdmJhckRyb3Bkb3duJyArXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaSArXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJ1wiPjxpIGNsYXNzPVwiZmFzIGZhLWFuZ2xlLXJpZ2h0IGFycm93XCI+PC9pPjwvc3Bhbj48L2E+JztcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciBjYXRnVG9BcHBlbmQgPVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICc8dWwgY2xhc3M9XCJjb2xsYXBzZSBjYXRlZ29yeS1saXN0XCIgYXJpYS1sYWJlbGxlZGJ5PVwibmF2YmFyRHJvcGRvd25cIiBpZD1cIicgK1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGFsbF9kZXBhcnRtZW50c1tpXS5kZXBhcnRtZW50ICtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAnXCI+JztcbiAgICAgICAgICAgICAgICAgICAgICAgIGZvciAoXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIGogPSAwO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGogPCBhbGxfZGVwYXJ0bWVudHNbaV0uY2F0ZWdvcmllcy5sZW5ndGg7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaisrXG4gICAgICAgICAgICAgICAgICAgICAgICApIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBjYXRnVG9BcHBlbmQgKz1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJzxsaT48YSBjbGFzcz1cImxpbmtcIiBocmVmPVwiJyArXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGFsbF9kZXBhcnRtZW50c1tpXS5jYXRlZ29yaWVzW2pdLmxpbmsgK1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAnXCI+JyArXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGFsbF9kZXBhcnRtZW50c1tpXS5jYXRlZ29yaWVzW2pdLmNhdGVnb3J5ICtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJzwvYT48L2xpPic7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICBjYXRnVG9BcHBlbmQgKz0gJzwvdWw+JztcbiAgICAgICAgICAgICAgICAgICAgICAgIGRlcHRUb0FwcGVuZCArPSBjYXRnVG9BcHBlbmQ7XG4gICAgICAgICAgICAgICAgICAgICAgICBkZXB0VG9BcHBlbmQgKz0gJzwvbGk+JztcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAkKCcjY29sbGFwc2libGUtZGVwdCcpLmh0bWwoZGVwdFRvQXBwZW5kKTtcbiAgICAgICAgICAgICAgICB2YXIgc2luZ2xlRGVwdE1vYmlsZSA9ICcnO1xuICAgICAgICAgICAgICAgIGZvciAodmFyIGkgPSAwOyBpIDwgYWxsX2RlcGFydG1lbnRzLmxlbmd0aDsgaSsrKSB7XG4gICAgICAgICAgICAgICAgICAgIGlmIChhbGxfZGVwYXJ0bWVudHMubGVuZ3RoICE9IDApIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHNpbmdsZURlcHRNb2JpbGUgPVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICc8ZGl2IGNsYXNzPVwiY29sLXNtLTQgIC1kZXB0IFwiPjxhICBocmVmPVwiJyArXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgYWxsX2RlcGFydG1lbnRzW2ldLmxpbmsgK1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICdcIj4nICtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBhbGxfZGVwYXJ0bWVudHNbaV0uZGVwYXJ0bWVudCArXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJzwvYT48L2Rpdj4nO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICQoJyNtb2JpbGVEZXBhcnRtZW50cycpLmFwcGVuZChzaW5nbGVEZXB0TW9iaWxlKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIGZvciAodmFyIGkgPSAwOyBpIDwgYWxsX2RlcGFydG1lbnRzLmxlbmd0aDsgaSsrKSB7XG4gICAgICAgICAgICAgICAgaWYgKGFsbF9kZXBhcnRtZW50c1tpXS5jYXRlZ29yaWVzLmxlbmd0aCA9PSAwKSB7XG4gICAgICAgICAgICAgICAgICAgIGRlcHRUb0FwcGVuZCArPVxuICAgICAgICAgICAgICAgICAgICAgICAgJzxsaT48YSBocmVmPVwiJyArXG4gICAgICAgICAgICAgICAgICAgICAgICBhbGxfZGVwYXJ0bWVudHNbaV0ubGluayArXG4gICAgICAgICAgICAgICAgICAgICAgICAnXCI+JyArXG4gICAgICAgICAgICAgICAgICAgICAgICBhbGxfZGVwYXJ0bWVudHNbaV0uZGVwYXJ0bWVudCArXG4gICAgICAgICAgICAgICAgICAgICAgICAnPC9hPjwvbGk+JztcbiAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICBsZXQgY2xhc3NBY3RpdmUgPVxuICAgICAgICAgICAgICAgICAgICAgICAgYWxsX2RlcGFydG1lbnRzW2ldLmxpbmsgPT09IGxvY2F0aW9uLnBhdGhuYW1lXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgPyAnYWN0aXZlJ1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIDogJyc7XG4gICAgICAgICAgICAgICAgICAgIGRlcHRUb0FwcGVuZCArPVxuICAgICAgICAgICAgICAgICAgICAgICAgJzxsaSBjbGFzcz1cImRyb3Bkb3duICcgK1xuICAgICAgICAgICAgICAgICAgICAgICAgY2xhc3NBY3RpdmUgK1xuICAgICAgICAgICAgICAgICAgICAgICAgJ1wiPjxhICBocmVmPVwiJyArXG4gICAgICAgICAgICAgICAgICAgICAgICBhbGxfZGVwYXJ0bWVudHNbaV0ubGluayArXG4gICAgICAgICAgICAgICAgICAgICAgICAnXCIgaWQ9XCJuYXZiYXJEcm9wZG93bicgK1xuICAgICAgICAgICAgICAgICAgICAgICAgaSArXG4gICAgICAgICAgICAgICAgICAgICAgICAnXCIgcm9sZT1cImJ1dHRvblwiICBhcmlhLWhhc3BvcHVwPVwidHJ1ZVwiIGFyaWEtZXhwYW5kZWQ9XCJmYWxzZVwiPicgK1xuICAgICAgICAgICAgICAgICAgICAgICAgYWxsX2RlcGFydG1lbnRzW2ldLmRlcGFydG1lbnQgK1xuICAgICAgICAgICAgICAgICAgICAgICAgJzwvYT4nO1xuICAgICAgICAgICAgICAgICAgICB2YXIgY2F0Z1RvQXBwZW5kID1cbiAgICAgICAgICAgICAgICAgICAgICAgICc8dWwgY2xhc3M9XCJkcm9wZG93bi1tZW51XCIgYXJpYS1sYWJlbGxlZGJ5PVwibmF2YmFyRHJvcGRvd25cIj4nO1xuICAgICAgICAgICAgICAgICAgICBmb3IgKFxuICAgICAgICAgICAgICAgICAgICAgICAgdmFyIGogPSAwO1xuICAgICAgICAgICAgICAgICAgICAgICAgaiA8IGFsbF9kZXBhcnRtZW50c1tpXS5jYXRlZ29yaWVzLmxlbmd0aDtcbiAgICAgICAgICAgICAgICAgICAgICAgIGorK1xuICAgICAgICAgICAgICAgICAgICApIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vIGlmIChhbGxfZGVwYXJ0bWVudHNbaV0uY2F0ZWdvcmllc1tqXS5zdWJfY2F0ZWdvcmllcy5sZW5ndGggPT0gMCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgY2F0Z1RvQXBwZW5kICs9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJzxsaT48YSBocmVmPVwiJyArXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgYWxsX2RlcGFydG1lbnRzW2ldLmNhdGVnb3JpZXNbal0ubGluayArXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJ1wiPicgK1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGFsbF9kZXBhcnRtZW50c1tpXS5jYXRlZ29yaWVzW2pdLmNhdGVnb3J5ICtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAnPC9hPjwvbGk+JztcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vIH1cbiAgICAgICAgICAgICAgICAgICAgICAgIC8vIGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgLy8gICBjYXRnVG9BcHBlbmQgKz0gJzxsaSBjbGFzcz1cImRyb3Bkb3duLXN1Ym1lbnVcIj4nO1xuICAgICAgICAgICAgICAgICAgICAgICAgLy8gICBjYXRnVG9BcHBlbmQgKz0gJzxhIGhyZWY9XCInK2FsbF9kZXBhcnRtZW50c1tpXS5jYXRlZ29yaWVzW2pdLmxpbmsrJ1wiPicgKyBhbGxfZGVwYXJ0bWVudHNbaV0uY2F0ZWdvcmllc1tqXS5jYXRlZ29yeSArICc8c3BhbiBjbGFzcz1cIm14LTJcIj48aSBjbGFzcz1cImZhcyBmYS1hbmdsZS1yaWdodFwiPjwvaT48L3NwYW4+JztcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vICAgdmFyIHN1YmNhdFRvQXBwZW5kID0gJzx1bCBjbGFzcz1cImRyb3Bkb3duLW1lbnVcIj4nO1xuICAgICAgICAgICAgICAgICAgICAgICAgLy8gICBmb3IgKGsgPSAwOyBrIDwgYWxsX2RlcGFydG1lbnRzW2ldLmNhdGVnb3JpZXNbal0uc3ViX2NhdGVnb3JpZXMubGVuZ3RoOyBrKyspIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vICAgICBzdWJjYXRUb0FwcGVuZCArPSAnPGxpPjxhIGhyZWY9XCInICsgYWxsX2RlcGFydG1lbnRzW2ldLmNhdGVnb3JpZXNbal0uc3ViX2NhdGVnb3JpZXNba10ubGluayArICdcIj4nICsgYWxsX2RlcGFydG1lbnRzW2ldLmNhdGVnb3JpZXNbal0uc3ViX2NhdGVnb3JpZXNba10uc3ViX2NhdGVnb3J5ICsgJzwvYT48L2xpPidcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAvLyAgIHN1YmNhdFRvQXBwZW5kICs9ICc8L3VsPic7XG4gICAgICAgICAgICAgICAgICAgICAgICAvLyAgIGNhdGdUb0FwcGVuZCArPSBzdWJjYXRUb0FwcGVuZDtcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vICAgY2F0Z1RvQXBwZW5kICs9ICc8L2xpPic7XG4gICAgICAgICAgICAgICAgICAgICAgICAvLyB9XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgY2F0Z1RvQXBwZW5kICs9ICc8L3VsPic7XG4gICAgICAgICAgICAgICAgICAgIGRlcHRUb0FwcGVuZCArPSBjYXRnVG9BcHBlbmQ7XG4gICAgICAgICAgICAgICAgICAgIGRlcHRUb0FwcGVuZCArPSAnPC9saT4nO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgICQoJyNkZXBhcnRtZW50c05hdicpLmFwcGVuZChkZXB0VG9BcHBlbmQpO1xuICAgICAgICB9LFxuICAgICAgICBlcnJvcjogZnVuY3Rpb24oanFYSFIsIGV4Y2VwdGlvbikge1xuICAgICAgICAgICAgY29uc29sZS5sb2coanFYSFIpO1xuICAgICAgICAgICAgY29uc29sZS5sb2coZXhjZXB0aW9uKTtcbiAgICAgICAgfVxuICAgIH0pO1xufSk7XG5cbmV4cG9ydCBkZWZhdWx0IGZ1bmN0aW9uIGlzTW9iaWxlKCkge1xuICAgIHZhciBpc01vYmlsZSA9IHdpbmRvdy5tYXRjaE1lZGlhKCdvbmx5IHNjcmVlbiBhbmQgKG1heC13aWR0aDogNzY4cHgpJyk7XG4gICAgcmV0dXJuIGlzTW9iaWxlLm1hdGNoZXMgPyB0cnVlIDogZmFsc2U7XG59XG4iLCIvKlxuUmVmZXJlbmNlOiBodHRwOi8vanNmaWRkbGUubmV0L0JCM0pLLzQ3L1xuKi9cblxuZXhwb3J0IGRlZmF1bHQgZnVuY3Rpb24gbWFrZVNlbGVjdEJveCgpIHtcbiAgICAkKCdzZWxlY3QnKS5lYWNoKGZ1bmN0aW9uKCkge1xuICAgICAgICB2YXIgJHRoaXMgPSAkKHRoaXMpLFxuICAgICAgICAgICAgbnVtYmVyT2ZPcHRpb25zID0gJCh0aGlzKS5jaGlsZHJlbignb3B0aW9uJykubGVuZ3RoXG5cbiAgICAgICAgLy9SZW1vdmUgcHJldmlvdXNseSBtYWRlIHNlbGVjdGJveFxuICAgICAgICAkKCcjc2VsZWN0Ym94LScgKyAkdGhpcy5hdHRyKCdpZCcpKS5yZW1vdmUoKVxuXG4gICAgICAgICR0aGlzLmFkZENsYXNzKCdzZWxlY3QtaGlkZGVuJylcbiAgICAgICAgJHRoaXMud3JhcCgnPGRpdiBjbGFzcz1cInNlbGVjdFwiPjwvZGl2PicpXG4gICAgICAgICR0aGlzLmFmdGVyKFxuICAgICAgICAgICAgJzxkaXYgY2xhc3M9XCJzZWxlY3Qtc3R5bGVkXCIgaWQ9XCJzZWxlY3Rib3gtJyArXG4gICAgICAgICAgICAgICAgJHRoaXMuYXR0cignaWQnKSArXG4gICAgICAgICAgICAgICAgJ1wiPjwvZGl2PidcbiAgICAgICAgKVxuXG4gICAgICAgIHZhciAkc3R5bGVkU2VsZWN0ID0gJHRoaXMubmV4dCgnZGl2LnNlbGVjdC1zdHlsZWQnKVxuICAgICAgICB2YXIgc3RyU2VsZWN0ZWRUZXh0ID0gJCh0aGlzKS5jaGlsZHJlbignb3B0aW9uOnNlbGVjdGVkJylcbiAgICAgICAgICAgID8gJCh0aGlzKVxuICAgICAgICAgICAgICAgICAgLmNoaWxkcmVuKCdvcHRpb246c2VsZWN0ZWQnKVxuICAgICAgICAgICAgICAgICAgLnRleHQoKVxuICAgICAgICAgICAgOiAkdGhpc1xuICAgICAgICAgICAgICAgICAgLmNoaWxkcmVuKCdvcHRpb246c2VsZWN0ZWQnKVxuICAgICAgICAgICAgICAgICAgLmVxKDApXG4gICAgICAgICAgICAgICAgICAudGV4dCgpXG4gICAgICAgIHZhciBzdHJTZWxlY3RlZFZhbHVlID0gJCh0aGlzKS5jaGlsZHJlbignb3B0aW9uOnNlbGVjdGVkJylcbiAgICAgICAgICAgID8gJCh0aGlzKVxuICAgICAgICAgICAgICAgICAgLmNoaWxkcmVuKCdvcHRpb246c2VsZWN0ZWQnKVxuICAgICAgICAgICAgICAgICAgLmF0dHIoJ3ZhbHVlJylcbiAgICAgICAgICAgIDogJHRoaXNcbiAgICAgICAgICAgICAgICAgIC5jaGlsZHJlbignb3B0aW9uOnNlbGVjdGVkJylcbiAgICAgICAgICAgICAgICAgIC5lcSgwKVxuICAgICAgICAgICAgICAgICAgLmF0dHIoJ3ZhbHVlJylcbiAgICAgICAgJHN0eWxlZFNlbGVjdC50ZXh0KHN0clNlbGVjdGVkVGV4dClcbiAgICAgICAgJHN0eWxlZFNlbGVjdC5hdHRyKCdhY3RpdmUnLCBzdHJTZWxlY3RlZFZhbHVlKVxuXG4gICAgICAgIHZhciAkbGlzdCA9ICQoJzx1bCAvPicsIHtcbiAgICAgICAgICAgIGNsYXNzOiAnc2VsZWN0LW9wdGlvbnMnXG4gICAgICAgIH0pLmluc2VydEFmdGVyKCRzdHlsZWRTZWxlY3QpXG5cbiAgICAgICAgZm9yICh2YXIgaSA9IDA7IGkgPCBudW1iZXJPZk9wdGlvbnM7IGkrKykge1xuICAgICAgICAgICAgJCgnPGxpIC8+Jywge1xuICAgICAgICAgICAgICAgIHRleHQ6ICR0aGlzXG4gICAgICAgICAgICAgICAgICAgIC5jaGlsZHJlbignb3B0aW9uJylcbiAgICAgICAgICAgICAgICAgICAgLmVxKGkpXG4gICAgICAgICAgICAgICAgICAgIC50ZXh0KCksXG4gICAgICAgICAgICAgICAgcmVsOiAkdGhpc1xuICAgICAgICAgICAgICAgICAgICAuY2hpbGRyZW4oJ29wdGlvbicpXG4gICAgICAgICAgICAgICAgICAgIC5lcShpKVxuICAgICAgICAgICAgICAgICAgICAudmFsKClcbiAgICAgICAgICAgIH0pLmFwcGVuZFRvKCRsaXN0KVxuICAgICAgICB9XG5cbiAgICAgICAgdmFyICRsaXN0SXRlbXMgPSAkbGlzdC5jaGlsZHJlbignbGknKVxuXG4gICAgICAgICRzdHlsZWRTZWxlY3QuY2xpY2soZnVuY3Rpb24oZSkge1xuICAgICAgICAgICAgZS5zdG9wUHJvcGFnYXRpb24oKVxuICAgICAgICAgICAgJCgnZGl2LnNlbGVjdC1zdHlsZWQuYWN0aXZlJylcbiAgICAgICAgICAgICAgICAubm90KHRoaXMpXG4gICAgICAgICAgICAgICAgLmVhY2goZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICAgICAgICAgICQodGhpcylcbiAgICAgICAgICAgICAgICAgICAgICAgIC5yZW1vdmVDbGFzcygnYWN0aXZlJylcbiAgICAgICAgICAgICAgICAgICAgICAgIC5uZXh0KCd1bC5zZWxlY3Qtb3B0aW9ucycpXG4gICAgICAgICAgICAgICAgICAgICAgICAuaGlkZSgpXG4gICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgICQodGhpcylcbiAgICAgICAgICAgICAgICAudG9nZ2xlQ2xhc3MoJ2FjdGl2ZScpXG4gICAgICAgICAgICAgICAgLm5leHQoJ3VsLnNlbGVjdC1vcHRpb25zJylcbiAgICAgICAgICAgICAgICAudG9nZ2xlKClcbiAgICAgICAgfSlcblxuICAgICAgICAkbGlzdEl0ZW1zLmNsaWNrKGZ1bmN0aW9uKGUpIHtcbiAgICAgICAgICAgIGUuc3RvcFByb3BhZ2F0aW9uKClcbiAgICAgICAgICAgICRzdHlsZWRTZWxlY3QudGV4dCgkKHRoaXMpLnRleHQoKSkucmVtb3ZlQ2xhc3MoJ2FjdGl2ZScpXG4gICAgICAgICAgICB2YXIgc3RyU2VsZWN0ZWRWYWx1ZSA9ICQodGhpcykuYXR0cigncmVsJylcbiAgICAgICAgICAgICRzdHlsZWRTZWxlY3QuYXR0cignYWN0aXZlJywgc3RyU2VsZWN0ZWRWYWx1ZSlcbiAgICAgICAgICAgICQoZG9jdW1lbnQpLnRyaWdnZXIoJ3NlbGVjdC12YWx1ZS1jaGFuZ2VkJywgJHN0eWxlZFNlbGVjdClcblxuICAgICAgICAgICAgJHRoaXMudmFsKCQodGhpcykuYXR0cigncmVsJykpXG4gICAgICAgICAgICAkbGlzdC5oaWRlKClcbiAgICAgICAgICAgIC8vY29uc29sZS5sb2coJHRoaXMudmFsKCkpO1xuICAgICAgICB9KVxuXG4gICAgICAgICQoZG9jdW1lbnQpLmNsaWNrKGZ1bmN0aW9uKCkge1xuICAgICAgICAgICAgJHN0eWxlZFNlbGVjdC5yZW1vdmVDbGFzcygnYWN0aXZlJylcbiAgICAgICAgICAgICRsaXN0LmhpZGUoKVxuICAgICAgICB9KVxuICAgIH0pXG59XG4iLCJleHBvcnQgZnVuY3Rpb24gbWFrZU11bHRpQ2Fyb3VzZWwoc2xpZGVzU2hvdyA9IDQsIHNsaWRlc1Njcm9sbCA9IDQpIHtcbiAgICAkKCcucmVzcG9uc2l2ZTpub3QoLnNsaWNrLXNsaWRlciknKS5zbGljayh7XG4gICAgICAgIGluZmluaXRlOiBmYWxzZSxcbiAgICAgICAgc3BlZWQ6IDMwMCxcbiAgICAgICAgc2xpZGVzVG9TaG93OiBzbGlkZXNTaG93LFxuICAgICAgICBzbGlkZXNUb1Njcm9sbDogc2xpZGVzU2Nyb2xsLFxuICAgICAgICBhcnJvd3M6IHRydWUsXG4gICAgICAgIC8vIGNlbnRlck1vZGU6IHRydWUsXG4gICAgICAgIHJlc3BvbnNpdmU6IFtcbiAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICBicmVha3BvaW50OiAxMDI0LFxuICAgICAgICAgICAgICAgIHNldHRpbmdzOiB7XG4gICAgICAgICAgICAgICAgICAgIHNsaWRlc1RvU2hvdzogNCxcbiAgICAgICAgICAgICAgICAgICAgc2xpZGVzVG9TY3JvbGw6IDQsXG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICBicmVha3BvaW50OiA2MDAsXG4gICAgICAgICAgICAgICAgc2V0dGluZ3M6IHtcbiAgICAgICAgICAgICAgICAgICAgc2xpZGVzVG9TaG93OiAzLFxuICAgICAgICAgICAgICAgICAgICBzbGlkZXNUb1Njcm9sbDogMyxcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9LFxuICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgIGJyZWFrcG9pbnQ6IDQ4MCxcbiAgICAgICAgICAgICAgICBzZXR0aW5nczoge1xuICAgICAgICAgICAgICAgICAgICBzbGlkZXNUb1Nob3c6IDMsXG4gICAgICAgICAgICAgICAgICAgIHNsaWRlc1RvU2Nyb2xsOiAzLFxuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIC8vIFlvdSBjYW4gdW5zbGljayBhdCBhIGdpdmVuIGJyZWFrcG9pbnQgbm93IGJ5IGFkZGluZzpcbiAgICAgICAgICAgIC8vIHNldHRpbmdzOiBcInVuc2xpY2tcIlxuICAgICAgICAgICAgLy8gaW5zdGVhZCBvZiBhIHNldHRpbmdzIG9iamVjdFxuICAgICAgICBdXG4gICAgfSk7XG59XG4iLCJyZXF1aXJlKCdpb24tcmFuZ2VzbGlkZXInKTtcbndpbmRvdy5HTE9CQUxfTElTVElOR19BUElfUEFUSCA9ICcnO1xuXG5yZXF1aXJlKCcuLi9hcGlzL2xpc3RpbmctYXBpJyk7XG5cbiQoZG9jdW1lbnQpLnJlYWR5KGZ1bmN0aW9uKCkge1xuICAgIGxldCBpSXRlbXNUb1Nob3cgPSAyO1xuICAgIHN0ckl0ZW1zTnVtQ2xhc3MgPSAnaXRlbS0yJztcblxuICAgICQoJyNwcmljZVJhbmdlU2xpZGVyJykuY2hhbmdlKGZ1bmN0aW9uKCkge1xuICAgICAgICAkKCcjcHJpY2VJbmZvJylcbiAgICAgICAgICAgIC5maW5kKCcubG93JylcbiAgICAgICAgICAgIC50ZXh0KCQodGhpcykuYXR0cignbWluJykpO1xuICAgICAgICAkKCcjcHJpY2VJbmZvJylcbiAgICAgICAgICAgIC5maW5kKCcuaGlnaCcpXG4gICAgICAgICAgICAudGV4dCgkKHRoaXMpLnZhbCgpKTtcbiAgICB9KTtcblxuICAgICRwcmljZVJhbmdlU2xpZGVyID0gJCgnI3ByaWNlUmFuZ2VTbGlkZXInKTtcblxuICAgICRwcmljZVJhbmdlU2xpZGVyLmlvblJhbmdlU2xpZGVyKHtcbiAgICAgICAgc2tpbjogJ3NoYXJwJyxcbiAgICAgICAgdHlwZTogJ2RvdWJsZScsXG4gICAgICAgIG1pbjogMTAwLFxuICAgICAgICBtYXg6IDUwMDAsXG4gICAgICAgIGZyb206IDUwMCxcbiAgICAgICAgdG86IDI1MDAsXG4gICAgICAgIHByZWZpeDogJyQnLFxuICAgICAgICBwcmV0dGlmeV9zZXBhcmF0b3I6ICcsJ1xuICAgIH0pO1xuXG4gICAgLy8gdmFyIHByaWNlU2xpZGVyID0gcHJpY2VTbGlkZXJDb250YWluZXIuJHByaWNlUmFuZ2VTbGlkZXI7XG5cbiAgICAvLyAkKCdib2R5Jykub24oJ2NoYW5nZScsICcucHJpY2UtcmFuZ2Utc2xpZGVyJywgZnVuY3Rpb24gKCkge1xuICAgIC8vICAgICB2YXIgJGlucCA9ICQodGhpcyk7XG4gICAgLy8gICAgIHZhciBmcm9tID0gJGlucC5wcm9wKFwidmFsdWVcIik7IC8vIHJlYWRpbmcgaW5wdXQgdmFsdWVcbiAgICAvLyAgICAgdmFyIGZyb20yID0gJGlucC5kYXRhKFwiZnJvbVwiKTsgLy8gcmVhZGluZyBpbnB1dCBkYXRhLWZyb20gYXR0cmlidXRlXG5cbiAgICAvLyAgICAgY29uc29sZS5sb2coZnJvbSwgZnJvbTIpOyAvLyBGUk9NIHZhbHVlXG4gICAgLy8gfSk7XG5cbiAgICAvL1RvcCBidXR0b25cbiAgICAkKCcudG9wLWJ1dHRvbicpLmNsaWNrKGZ1bmN0aW9uKCkge1xuICAgICAgICB3aW5kb3cuc2Nyb2xsVG8oMCwgMCk7XG4gICAgICAgICQoJ2h0bWwsIGJvZHknKS5hbmltYXRlKHsgc2Nyb2xsVG9wOiAwIH0sIDgwMCk7XG4gICAgfSk7XG5cbiAgICAkKCcjZmlsdGVyVG9nZ2xlQnRuJykuY2xpY2soZnVuY3Rpb24oKSB7XG4gICAgICAgICQoJyNmaWx0ZXJzJykudG9nZ2xlQ2xhc3MoJ3Nob3cnKTtcbiAgICAgICAgJCgnI3NvcnQtbW9iaWxlJykuaGFzQ2xhc3MoJ3Nob3cnKVxuICAgICAgICAgICAgPyAkKCcjc29ydC1tb2JpbGUnKS5yZW1vdmVDbGFzcygnc2hvdycpXG4gICAgICAgICAgICA6ICcnO1xuICAgIH0pO1xuICAgICQoJyNzZWxlY3Rib3gtc29ydG1vYmlsZScpLmNsaWNrKGZ1bmN0aW9uKCkge1xuICAgICAgICAkKCcjc29ydC1tb2JpbGUnKS50b2dnbGVDbGFzcygnc2hvdycpO1xuICAgICAgICAkKCcjZmlsdGVycycpLnJlbW92ZUNsYXNzKCdzaG93Jyk7XG4gICAgfSk7XG5cbiAgICAkKCcjdmlld0l0ZW1zQnRuJykuY2xpY2soZnVuY3Rpb24oKSB7XG4gICAgICAgIGlJdGVtc1RvU2hvdyA9IGlJdGVtc1RvU2hvdyA9PSAxID8gMyA6IGlJdGVtc1RvU2hvdyAtIDE7XG4gICAgICAgIC8vIGlmIChpSXRlbXNUb1Nob3cgIT09IDEpIHtcbiAgICAgICAgLy8gICAgICQoJyN2aWV3SXRlbXNCdG4nKVxuICAgICAgICAvLyAgICAgICAgIC5jaGlsZHJlbignaScpXG4gICAgICAgIC8vICAgICAgICAgLnJlbW92ZUNsYXNzKClcbiAgICAgICAgLy8gICAgICQoJyN2aWV3SXRlbXNCdG4nKVxuICAgICAgICAvLyAgICAgICAgIC5jaGlsZHJlbignaScpXG4gICAgICAgIC8vICAgICAgICAgLmFkZENsYXNzKCdmYXMgZmEtdGgtbGlzdCcpXG4gICAgICAgIC8vIH0gZWxzZSB7XG4gICAgICAgIC8vICAgICAkKCcjdmlld0l0ZW1zQnRuJylcbiAgICAgICAgLy8gICAgICAgICAuY2hpbGRyZW4oJ2knKVxuICAgICAgICAvLyAgICAgICAgIC5yZW1vdmVDbGFzcygpXG4gICAgICAgIC8vICAgICAkKCcjdmlld0l0ZW1zQnRuJylcbiAgICAgICAgLy8gICAgICAgICAuY2hpbGRyZW4oJ2knKVxuICAgICAgICAvLyAgICAgICAgIC5hZGRDbGFzcygnZmFiIGZhLWJ1cm9tb2JlbGV4cGVydGUnKVxuICAgICAgICAvLyB9XG4gICAgICAgIGlmIChpSXRlbXNUb1Nob3cgPT0gMykge1xuICAgICAgICAgICAgJCgnLnByb2Qtc2FsZS1wcmljZScpLmFkZENsYXNzKCdkLW5vbmUnKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICQoJy5wcm9kLXNhbGUtcHJpY2UnKS5yZW1vdmVDbGFzcygnZC1ub25lJyk7XG4gICAgICAgIH1cbiAgICAgICAgJCgnI3Byb2R1Y3RzQ29udGFpbmVyRGl2JylcbiAgICAgICAgICAgIC5maW5kKCcubHMtcHJvZHVjdC1kaXYnKVxuICAgICAgICAgICAgLmVhY2goZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICAgICAgJCh0aGlzKS5yZW1vdmVDbGFzcyhmdW5jdGlvbihpbmRleCwgY2xhc3NOYW1lKSB7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiAoY2xhc3NOYW1lLm1hdGNoKC8oXnxcXHMpaXRlbS1cXFMrL2cpIHx8IFtdKS5qb2luKCcgJyk7XG4gICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgc3RySXRlbXNOdW1DbGFzcyA9ICdpdGVtLScgKyBpSXRlbXNUb1Nob3c7XG4gICAgICAgICAgICAgICAgJCh0aGlzKS5hZGRDbGFzcyhzdHJJdGVtc051bUNsYXNzKTtcbiAgICAgICAgICAgIH0pO1xuICAgIH0pO1xuICAgIC8vY2xvc2UtYnRuLWZpbHRlclxuICAgICQoZG9jdW1lbnQpLm9uKCdjbGljaycsICcuZmlsdGVycy1jbG9zZS1idG4nLCBmdW5jdGlvbihlKSB7XG4gICAgICAgICQoJyNmaWx0ZXJzJykuaGFzQ2xhc3MoJ3Nob3cnKVxuICAgICAgICAgICAgPyAkKCcjZmlsdGVycycpLnJlbW92ZUNsYXNzKCdzaG93JylcbiAgICAgICAgICAgIDogJCgnI3NvcnQtbW9iaWxlJykuaGFzQ2xhc3MoJ3Nob3cnKVxuICAgICAgICAgICAgPyAkKCcjc29ydC1tb2JpbGUnKS5yZW1vdmVDbGFzcygnc2hvdycpXG4gICAgICAgICAgICA6ICcnO1xuICAgIH0pO1xuXG4gICAgJCh3aW5kb3cpLnNjcm9sbChmdW5jdGlvbihldmVudCkge1xuICAgICAgICBpZiAoJCh3aW5kb3cpLnNjcm9sbFRvcCgpID4gNTApIHtcbiAgICAgICAgICAgICQoJy5maWx0ZXItdG9nZ2xlLW1vYmlsZScpLmFkZENsYXNzKCdmaXgtc2VhcmNoJyk7XG4gICAgICAgICAgICAkKCcuZmlsdGVycycpLmFkZENsYXNzKCdmaXgtc2VhcmNoLWZpbHRlcicpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgJCgnLmZpbHRlci10b2dnbGUtbW9iaWxlJykucmVtb3ZlQ2xhc3MoJ2ZpeC1zZWFyY2gnKTtcbiAgICAgICAgICAgICQoJy5maWx0ZXJzJykucmVtb3ZlQ2xhc3MoJ2ZpeC1zZWFyY2gtZmlsdGVyJyk7XG4gICAgICAgIH1cbiAgICB9KTtcblxuICAgICQoJy5kcm9wZG93bi1tZW51IGEuZHJvcGRvd24tdG9nZ2xlJykub24oJ2NsaWNrJywgZnVuY3Rpb24oZSkge1xuICAgICAgICBpZiAoXG4gICAgICAgICAgICAhJCh0aGlzKVxuICAgICAgICAgICAgICAgIC5uZXh0KClcbiAgICAgICAgICAgICAgICAuaGFzQ2xhc3MoJ3Nob3cnKVxuICAgICAgICApIHtcbiAgICAgICAgICAgICQodGhpcylcbiAgICAgICAgICAgICAgICAucGFyZW50cygnLmRyb3Bkb3duLW1lbnUnKVxuICAgICAgICAgICAgICAgIC5maXJzdCgpXG4gICAgICAgICAgICAgICAgLmZpbmQoJy5zaG93JylcbiAgICAgICAgICAgICAgICAucmVtb3ZlQ2xhc3MoJ3Nob3cnKTtcbiAgICAgICAgfVxuICAgICAgICB2YXIgJHN1Yk1lbnUgPSAkKHRoaXMpLm5leHQoJy5kcm9wZG93bi1tZW51Jyk7XG4gICAgICAgICRzdWJNZW51LnRvZ2dsZUNsYXNzKCdzaG93Jyk7XG4gICAgICAgICQoJ3VsIGFbaHJlZl49XCIvJyArIGxvY2F0aW9uLnBhdGhuYW1lLnNwbGl0KCcvJylbMV0gKyAnXCJdJykuYWRkQ2xhc3MoXG4gICAgICAgICAgICAnYWN0aXZlJ1xuICAgICAgICApO1xuICAgICAgICAkKHRoaXMpXG4gICAgICAgICAgICAucGFyZW50cygnbGkubmF2LWl0ZW0uZHJvcGRvd24uc2hvdycpXG4gICAgICAgICAgICAub24oJ2hpZGRlbi5icy5kcm9wZG93bicsIGZ1bmN0aW9uKGUpIHtcbiAgICAgICAgICAgICAgICAkKCcuZHJvcGRvd24tc3VibWVudSAuc2hvdycpLnJlbW92ZUNsYXNzKCdzaG93Jyk7XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgIH0pO1xufSk7XG4iXSwic291cmNlUm9vdCI6IiJ9