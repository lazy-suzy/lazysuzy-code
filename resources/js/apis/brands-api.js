import Handlebars from '../components/handlebar';
import isMobile from '../app.js';
import ListingFactory from '../components/listingFactory';

$(document).ready(function() {
    var source = document.getElementById('listing-template').innerHTML;
    var sourceMobile = document.getElementById('listing-template-mobile')
        .innerHTML;
    var desktopListingTemplate = Handlebars.compile(source);
    var mobileListingTemplate = Handlebars.compile(sourceMobile);
    const brandHeaderTemplate = Handlebars.compile($('#brandHeader').html());
    const filterSource = document.getElementById('desktop-filter-template')
        .innerHTML;
    const desktopFilterTemplate = Handlebars.compile(filterSource);
    const mobileFilterTemplate = Handlebars.compile(
        $('#mobile-filter-template').html()
    );

    const BRAND_API = `/api${window.location.pathname}`;

    const listingFactory = isMobile()
        ? new ListingFactory(
              `/api/products/all`,
              { filterToIgnore: 'brand' },
              { $filterContainer: $('#mobile-filters') },
              mobileListingTemplate,
              mobileFilterTemplate
          )
        : new ListingFactory(
              `/api/products/all`,
              { filterToIgnore: 'brand' },
              {},
              desktopListingTemplate,
              desktopFilterTemplate
          );

    $.ajax({
        type: 'GET',
        url: BRAND_API,
        dataType: 'json',
        success: function(data) {
            const brandData = data[0];
            brandData.isFeaturesVisible = brandData.value === 'floyd';
            $('.js-brand-header').html(brandHeaderTemplate(brandData));
            $('.loaderImg').remove();
        },
        error: function(jqXHR, exception) {
            console.log(jqXHR);
            console.log(exception);
        }
    });

    listingFactory.resetListing();

    $(window).scroll(function() {
        if (!listingFactory.bNoMoreProductsToShow) {
            if ($('#loaderImg') && isScrolledIntoView($('#loaderImg')[0])) {
                listingFactory.fetchProducts(false);
            } else if ($('#loaderImg') === null) {
                listingFactory.fetchProducts(false);
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

    $('body').on('click', '#clearAllFiltersBtn', function() {
        $('.filter').each(function() {
            if ($(this).attr('id') === 'priceFilter') {
                listingFactory.updateFromToPrice(
                    $(this).data('from'),
                    $(this).data('to')
                );
            } else {
                $(this)
                    .find('input[type="checkbox"]')
                    .each(function() {
                        if (this.checked && this.dataset.type !== 'hidden') {
                            this.checked = false;
                        }
                    });
            }
        });
        listingFactory.resetListing();
    });

    $('body').on('click', '.clear-filter', function() {
        var $filter = $(this).closest('.filter');
        if ($filter.attr('id') === 'priceFilter') {
            var $inp = $(this);
            price_from = $inp.data('from');
            price_to = $inp.data('to');
        } else {
            $filter.find('input[type="checkbox"]').each(function() {
                if (this.checked) {
                    this.checked = false;
                }
            });
        }
        listingFactory.resetListing();
    });

    /***************Implementation of filter changes **************/
    $('body').on('change', '.filter input[type="checkbox"]', function() {
        listingFactory.resetListing();
    });

    $(document).on('select-value-changed', function() {
        listingFactory.setSortType($('#selectbox-sort').attr('active'));
        listingFactory.resetListing();
    });
    $('input[name="sort-price-filter"]').click(function() {
        listingFactory.setSortType(
            $('input[name="sort-price-filter"]:checked').val()
        );
        listingFactory.resetListing();
        $('#sort-mobile').toggleClass('show');
    });

    $('body').on('mouseover', '.mini-carousel-item', function() {
        $(this)
            .closest('.ls-product-div')
            .find('.variation-img')
            .attr(
                'src',
                $(this)
                    .find('.carousel-img')
                    .attr('data-prodImg')
            );
        $(this)
            .closest('.ls-product-div')
            .find('.prod-img')
            .css('visibility', 'hidden');
        $(this)
            .closest('.ls-product-div')
            .find('.variation-img')
            .show();
    });

    $('body').on('mouseleave', '.slick-slide', function() {
        $(this)
            .closest('.ls-product-div')
            .find('.variation-img')
            .hide();
        $(this)
            .closest('.ls-product-div')
            .find('.prod-img')
            .css('visibility', 'unset');
    });

    $('body').on('click', '.wishlist-icon:not(.nav-link)', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if ($('#isLoggedIn').val() == 0) {
            $('#modalSignupForm').modal();
        } else {
            listingFactory.callWishlistAPI($(this));
        }
    });
});
