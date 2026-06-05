<?php

function getDeliveryOptions($conn, $product_id)
{

    //query database to get product details, including whether it's perishable or not
    $stmt = $conn->prepare(
        "SELECT is_perishable FROM products WHERE id = ?"
    );
    $stmt->bind_param('i', $product_id);        //returns product row as an array
    $stmt->execute();

    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        return [];
    }

    //if product is perishable, only return same-day delivery option, otherwise return both options
    if ($product['is_perishable'] == 1) {

        return [
            [
                'type'           => 'same_day',
                'label'          => 'Same-day delivery',
                'description'    => 'Local gig driver. Delivered today.',
                'estimated_time' => 'Today within 4 hours',
                'price'          => 35.00
            ]
        ];
    } else {

        return [
            [
                'type'           => 'same_day',
                'label'          => 'Same-day delivery',
                'description'    => 'Local gig driver. Delivered today.',
                'estimated_time' => 'Today within 4 hours',
                'price'          => 35.00
            ],
            [
                'type'           => 'national_courier',
                'label'          => 'National courier',
                'description'    => 'The Courier Guy or Aramex.',
                'estimated_time' => '3 - 5 business days',
                'price'          => 85.00
            ]
        ];
    }
}

//creates a delivery record
function createDelivery($conn, $order_id, $delivery_type, $courier_name = null)
{       //same day deliveries may not have courier name, so it's optional

    $estimated_times = [
        'same_day'         => 'Today within 4 hours',
        'national_courier' => '3 - 5 business days'
    ];

    $estimated = $estimated_times[$delivery_type] ?? 'To be confirmed';

    $stmt = $conn->prepare(
        "INSERT INTO deliveries 
            (order_id, delivery_type, courier_name, status, estimated_time)
         VALUES (?, ?, ?, 'pending', ?)"
    );
    $stmt->bind_param('isss', $order_id, $delivery_type, $courier_name, $estimated);
    return $stmt->execute();
}
