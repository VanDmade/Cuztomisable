<?php

return [
    // References whether the filtered total will need to be displayed along with the base query's total
    'filtered' => true,
    // Whether to return the next/previous page parameter in the table response
    'page_details' => true,
    'default' => [
        // Default page size, if for some reason the page size is not sent in
        'size' => 10,
        'order_by' => 'id',
        'order_direction' => 'desc',
    ],
];