<?php

return [
    'variations' => [
        'westelm' => [
            'table' => 'westelm_products_skus',
            'name' => 'name',
            'image' => 'image_path',
            'sku' => 'sku',
            'parent_sku' => 'product_id'
        ],
        'cab' => [
            'table' => 'crateandbarrel_products_variations',
            'name' => 'sku',
            'image' => 'image_path',
            'sku' => 'sku',
            'parent_sku' => 'product_id'
        ],
        'cb2' => [
            'table' => 'cb2_products_variations',
            'name' => 'variation_name',
            'image' => 'variation_image',
            'sku' => 'variation_sku',
            'parent_sku' => 'product_sku'
        ],
        'pier1' => [
            'table' => 'pier1_products',
            'name' => 'product_name',
            'image' => 'main_product_images',
            'sku' => 'product_sku',
            'parent_sku' => 'product_sku'
        ],
        'nw' => [
            'table' => 'nw_variations',
            'name' => 'name',
            'image' => 'image_path',
            'sku' => 'sku',
            'parent_sku' => 'product_id'
        ]
    ],

    'LS_ID_mapping' => 'mapping_core',
    'master_table' => 'master_data',
    'master_brands' => 'master_brands',
    'collections' => 'master_collections',
    'inventory' => 'lz_inventory',
    'blowout_deals' => 'site_deals_flash',
    'user_views' => 'user_views',
    'collection_detail_count' => 3,
    'dimension_columns' => ['dim_height', 'dim_width', 'dim_length', 'dim_depth', 'dim_diameter', 'dim_square'],
    'dimension_labels' => [
        'dim_height' => 'Height',
        'dim_width' => 'Width',
        'dim_length' => 'Length',
        'dim_depth' => 'Depth',
        'dim_diameter' => 'Diameter',
        'dim_square' => 'Square'
    ],
    'shipping_codes' => 'lz_ship_code',
    'trending_products' => 'master_trending',
    'order_delivery' => 'lz_order_delivery',
    'order_dump' => 'lz_order_dump'
];
