require('../apis/brands-api');

$(document).ready(function() {
    let iItemsToShow = 2;
    strItemsNumClass = 'item-2';

    $('#priceRangeSlider').change(function() {
        $('#priceInfo')
            .find('.low')
            .text($(this).attr('min'));
        $('#priceInfo')
            .find('.high')
            .text($(this).val());
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
    });
    //Top button
    $('.top-button').click(function() {
        $('html, body').animate({ scrollTop: 0 }, 800);
    });

    $('#filterToggleBtn').click(function() {
        $('.js-filters').toggleClass('show');
        $('#sort-mobile').hasClass('show')
            ? $('#sort-mobile').removeClass('show')
            : '';
    });
    $('#selectbox-sortmobile').click(function() {
        $('#sort-mobile').toggleClass('show');
        $('.js-filters').removeClass('show');
    });

    $('#viewItemsBtn').click(function() {
        iItemsToShow = iItemsToShow == 1 ? 3 : iItemsToShow - 1;

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
        $('.js-filters').hasClass('show')
            ? $('.js-filters').removeClass('show')
            : $('#sort-mobile').hasClass('show')
            ? $('#sort-mobile').removeClass('show')
            : '';
    });

    $(window).scroll(function(event) {
        if ($(window).scrollTop() > 900) {
            $('.filter-toggle-mobile').addClass('fix-search');
            $('.filters').addClass('fix-search-filter');
        } else {
            $('.filter-toggle-mobile').removeClass('fix-search');
            $('.filters').removeClass('fix-search-filter');
        }
    });
});
