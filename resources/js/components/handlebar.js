const Handlebars = require('handlebars');

Handlebars.registerHelper('ifEq', function(v1, v2, options) {
    if (v1 === v2) {
        return options.fn(this);
    }
    return options.inverse(this);
});
Handlebars.registerHelper('ifNeq', function(v1, v2, options) {
    if (v1 !== v2) {
        return options.fn(this);
    }
    return options.inverse(this);
});
Handlebars.registerHelper('formatPrice', function(price) {
    if (price && price.includes('-')) {
        let salepriceRange = price.split('-');
        return `$${Math.round(
            salepriceRange[0]
        ).toLocaleString()} - $${Math.round(
            salepriceRange[1]
        ).toLocaleString()}`;
    }
    return `$${Math.round(price).toLocaleString()}`;
});
Handlebars.registerHelper('printDiscount', function(discount) {
    if (Math.ceil(discount) > 0) {
        return new Handlebars.SafeString(
            `<span class="prod-discount-tag d-md-none ${
                discount >= 20 ? '_20' : ''
            }">${Math.ceil(discount)}%</span>`
        );
    }
    return null;
});
Handlebars.registerHelper('each_upto', function(ary, max, options) {
    if (!ary || ary.length == 0) return options.inverse(this);

    var result = [];
    for (var i = 0; i < max && i < ary.length; ++i)
        result.push(options.fn(ary[i]));
    return result.join('');
});

export default Handlebars;
