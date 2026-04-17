<?php
/**
 * App configuration.
 * Update the Razorpay keys to enable real online payments.
 * Do NOT commit real keys for public repos.
 */
return [
    // Razorpay credentials (put your values here)
    'RAZORPAY_KEY_ID' => '',
    'RAZORPAY_KEY_SECRET' => '',

    // When true, the "online payment" will be simulated (for development).
    // Set to false for real payments.
    'DEMO_MODE' => false,

    // Optional: display-only (Razorpay Checkout uses your merchant UPI configured in Razorpay dashboard).
    'UPI_VPA' => '7677601051@ybl',

    'RAZORPAY_CURRENCY' => 'INR',
];

