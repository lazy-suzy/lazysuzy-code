window.GLOBAL_LISTING_API_PATH = '';

require('../apis/wishlist-listing-api');

$(document).ready(function() {
    let iItemsToShow = 2;
    strItemsNumClass = 'item-2';

    //Top button
    $('.top-button').click(function() {
        $('html, body').animate({ scrollTop: 0 }, 800);
    });

    $('#filterToggleBtn').click(function() {
        $('#filters').toggleClass('show');
        $('#sort-mobile').hasClass('show')
            ? $('#sort-mobile').removeClass('show')
            : '';
    });
    $('#selectbox-sortmobile').click(function() {
        $('#sort-mobile').toggleClass('show');
        $('#filters').removeClass('show');
    });

    $('#viewItemsBtn').click(function() {
        iItemsToShow = iItemsToShow == 1 ? 3 : iItemsToShow - 1;
        // if (iItemsToShow !== 1) {
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
        $('#productsContainerDiv')
            .find('.ls-product-div')
            .each(function() {
                $(this).removeClass(function(index, className) {
                    return (className.match(/(^|\s)item-\S+/g) || []).join(' ');
                });
                strItemsNumClass = 'item-' + iItemsToShow;
                $(this).addClass(strItemsNumClass);
            });
    });
    //close-btn-filter
    $(document).on('click', '.filters-close-btn', function(e) {
        $('#filters').hasClass('show')
            ? $('#filters').removeClass('show')
            : $('#sort-mobile').hasClass('show')
            ? $('#sort-mobile').removeClass('show')
            : '';
    });

    $(window).scroll(function(event) {
        if ($(window).scrollTop() > 50) {
            $('.filter-toggle-mobile').addClass('fix-search');
            $('.filters').addClass('fix-search-filter');
        } else {
            $('.filter-toggle-mobile').removeClass('fix-search');
            $('.filters').removeClass('fix-search-filter');
        }
    });

    $('.dropdown-menu a.dropdown-toggle').on('click', function(e) {
        if (
            !$(this)
                .next()
                .hasClass('show')
        ) {
            $(this)
                .parents('.dropdown-menu')
                .first()
                .find('.show')
                .removeClass('show');
        }
        var $subMenu = $(this).next('.dropdown-menu');
        $subMenu.toggleClass('show');
        $('ul a[href^="/' + location.pathname.split('/')[1] + '"]').addClass(
            'active'
        );
        $(this)
            .parents('li.nav-item.dropdown.show')
            .on('hidden.bs.dropdown', function(e) {
                $('.dropdown-submenu .show').removeClass('show');
            });
        return false;
    });
});
