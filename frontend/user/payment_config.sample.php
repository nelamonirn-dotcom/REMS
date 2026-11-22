<?php
// Copy this file to payment_config.php (same folder) and fill in your values.
// payment_config.php is git-ignored, so private credentials are never committed.
//
// 1. Register a free sandbox account at https://developer.sslcommerz.com/registration/
//    You get a Store ID and Store Password by email.
//    (Default developer test credentials: store_id = 'testbox', store_password = 'qwerty')
// 2. base_url = the address you open the project at, with no trailing slash and no /frontend part.
//    XAMPP example:  http://localhost/REMS
//    (a live server must use https).
return [
  'store_id'       => 'testbox',
  'store_password' => 'qwerty',
  'is_sandbox'     => true,
  'base_url'       => 'http://localhost/REMS',
  'ssl_verify'     => false,
];
