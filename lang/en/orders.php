<?php

return [
    'connector_not_configured' => 'The store does not have the connector configured for order verification.',
    'credentials_missing' => 'WooCommerce credentials are not configured.',
    'invalid_url' => 'Invalid URL.',
    'rate_limited' => 'Too many order checks. Please try again in a minute.',
    'need_identifier' => 'You need the order number, email, or phone to check the order.',
    'lookup_failed' => 'Could not check the order. Please try again or contact the store.',
    'order_not_found' => 'Order #:number not found.',
    'orders_not_found_email' => 'No orders found for email :email.',
    'orders_not_found_phone' => 'No orders found for this phone number.',
    'verify_failed' => 'Could not verify orders.',
    'verification_required' => 'For security, please confirm your identity: provide the last 4 digits of the phone number or the email associated with the order.',
    'verification_failed' => 'The provided data does not match. Please check and try again.',
    'too_many_results' => 'Found :count orders. Showing the first :shown.',

    // Statuses
    'status_pending' => 'Pending payment',
    'status_processing' => 'Processing',
    'status_on-hold' => 'On hold',
    'status_completed' => 'Completed',
    'status_cancelled' => 'Cancelled',
    'status_refunded' => 'Refunded',
    'status_failed' => 'Failed',
    'status_trash' => 'Deleted',
];
