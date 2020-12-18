<?php

return [
    'to_format_brands' => ["West Elm", "Roar + Rabbit", "Amigo Modern"],
    'dims_form_feature_brands' => ["World Market"],
    'sets_enabled_brands' => ['pier1', 'nw'],
    'sets_enabled_tables' => [
        'nw' => 'nw_products_API',
        'pier1' => 'pier1_products'
    ],
    'dimension_range_difference' => 10,
    'discount_percent' => 'pct',
    'discount_flat' => 'amt',
    'discount_on_products' => 'PRODUCTS',
    'discount_on_total' => 'TOTAL',
    'S_COUNTRIES' => ['usa'],
    'FILTER_ESCAPE_CATGEORY' => 'FILTER_ESCAPE_CATGEORY',
    'FILTER_ESCAPE_MATERIAL' => 'FILTER_ESCAPE_MATERIAL',
    'FILTER_ESCAPE_DESIGNER' => 'FILTER_ESCAPE_DESIGNER',
    'FILTER_ESCAPE_PRICE' => 'FILTER_ESCAPE_PRICE',
    'FILTER_ESCAPE_BRAND' => 'FILTER_ESCAPE_BRAND',
    'STRING' => 'DEMO__STRING',
    'DEAL_EXPIRED' => 'DEAL_EXPIRED',
    'DEAL_ONGOING' => 'DEAL_ONGOING',
    'DEAL_COMMING_NEXT' => 'DEAL_COMMING_NEXT',
    'DEAL_INQUEUE' => 'DEAL_INQUEUE',
    'westelm_variations_cols' => [
        "sku",
        "product_id",
        "swatch_image_path",
        "image_path",
        "name",
        "swatch_image",
        "attribute_1",
        "attribute_2",
        "attribute_3",
        "attribute_4",
        "attribute_5",
        "attribute_6",
        "price",
        "was_price"
    ],
    'cab_variations_cols' => [
        "sku",
        "product_id",
        "swatch_image_path",
        "has_parent_sku",
        "image_path",
        "attribute_1",
        "attribute_2",
        "attribute_3",
        "price",
        "was_price"
    ]
];
