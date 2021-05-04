<?php

Breadcrumbs::for('/', function ($trail) {
    $trail->push('Home', '/');
});

Breadcrumbs::for('products', function ($trail) {
    $trail->push('Products', 'products');
});