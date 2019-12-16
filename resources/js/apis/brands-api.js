import Handlebars from '../components/handlebar';
import isMobile from '../app.js';

$(document).ready(function() {
    const BRAND_SLUG = window.location.pathname.split('/').pop();
    var source = document.getElementById('listing-template').innerHTML;
    var sourceMobile = document.getElementById('listing-template-mobile')
        .innerHTML;
    var listingTemplate = Handlebars.compile(source);
    var listingTemplateMobile = Handlebars.compile(sourceMobile);
    const brandHeaderTemplate = Handlebars.compile($('#brandHeader').html());
    const BRAND_API = `/api${window.location.pathname}`;

    $.ajax({
        type: 'GET',
        url: BRAND_API,
        dataType: 'json',
        success: function(data) {
            const brandData = data[0];
            brandData.isFeaturesVisible = brandData.value === 'floyd';
            $('.js-brand-header').html(brandHeaderTemplate(brandData));
        },
        error: function(jqXHR, exception) {
            console.log(jqXHR);
            console.log(exception);
        }
    });

    $.ajax({
        type: 'GET',
        url: `/api/products/all?filters=brand:${BRAND_SLUG}`,
        dataType: 'json',
        success: function(data) {
            for (var product of data.products) {
                if (isMobile()) {
                    product.percent_discount = Math.round(
                        product.percent_discount
                    );
                    product.discountClass =
                        product.percent_discount == 0
                            ? 'd-none'
                            : product.percent_discount > 20
                            ? '_20'
                            : '';
                    Math.round(product.percent_discount);
                    $('#product-div-main').append(
                        listingTemplateMobile(product)
                    );
                } else {
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
                            .replace('.', '_')}`;
                    }
                    product.variations = product.variations.map(variation => {
                        variation.swatch_image =
                            variation.swatch_image || variation.swatch || '';
                        return variation;
                    });
                    product.showMoreVariations = product.variations.length > 6;
                    $('#product-div-main').append(listingTemplate(product));
                }
            }
        },
        error: function(jqXHR, exception) {
            console.log(jqXHR);
            console.log(exception);
        }
    });
});
