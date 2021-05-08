import Handlebars from '../components/handlebar';
import ListingFactory from '../components/listingFactory';
// import strItemsNumClass from '../pages/listing';
// import * as priceSliderContainer from '../pages/listing';

$(document).ready(function() {
    const LISTING_API_PATH =
        window.GLOBAL_LISTING_API_PATH || '/api' + location.pathname;

    var source = document.getElementById('listing-template').innerHTML;
    var desktopListingTemplate = Handlebars.compile(source);

    const listingFactory = new ListingFactory(
        LISTING_API_PATH,
        {},
        {},
        desktopListingTemplate,
        ''
    );

    $(window).scroll(function() {
        if (
            !listingFactory.bFetchingProducts &&
            !listingFactory.bNoMoreProductsToShow
        ) {
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

    listingFactory.fetchProducts(false);

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

    $('body').on('mouseleave', '.mini-carousel-item', function() {
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
