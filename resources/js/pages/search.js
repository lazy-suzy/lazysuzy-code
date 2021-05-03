require('ion-rangeslider')
require('../apis/search-api')

$(document).ready(function() {
    let iItemsToShow = 3

    $('#priceRangeSlider').change(function() {
        $('#priceInfo')
            .find('.low')
            .text($(this).attr('min'))
        $('#priceInfo')
            .find('.high')
            .text($(this).val())
    })

    $priceRangeSlider = $('#priceRangeSlider')

    $priceRangeSlider.ionRangeSlider({
        skin: 'sharp',
        type: 'double',
        min: 100,
        max: 5000,
        from: 500,
        to: 2500,
        prefix: '$',
        prettify_separator: ','
    })

    $('#filterToggleBtn').click(function() {
        $('#filters').toggleClass('show')
    })

    $('#viewItemsBtn').click(function() {
        iItemsToShow = iItemsToShow == 1 ? 3 : iItemsToShow - 1
        $('#productsContainerDiv')
            .find('.ls-product-div')
            .each(function() {
                $(this).removeClass(function(index, className) {
                    return (className.match(/(^|\s)item-\S+/g) || []).join(' ')
                })
                $(this).addClass('item-' + iItemsToShow)
            })
    })

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
                .removeClass('show')
        }
        var $subMenu = $(this).next('.dropdown-menu')
        $subMenu.toggleClass('show')

        $(this)
            .parents('li.nav-item.dropdown.show')
            .on('hidden.bs.dropdown', function(e) {
                $('.dropdown-submenu .show').removeClass('show')
            })

        return false
    })
})
