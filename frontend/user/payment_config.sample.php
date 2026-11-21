<?php
// Copy this file to payment_config.php (same folder) and fill in your values.
// payment_config.php is git-ignored, so the password is never committed.
//
// 1. Register a free sandbox account at https://developer.sslcommerz.com/registration/
//    You get a Store ID and Store Password by email.
// 2. base_url = the address you open the project at, with no trailing slash and no /frontend part.
//    Laragon examples:  http://localhost/RESM   or   http://resm.test
//    (a live server must use https).
return [
  'store_id'       => 'your_sandbox_store_id',
  'store_password' => 'your_sandbox_store_password',
  'base_url'       => 'http://localhost/RESM',
];
