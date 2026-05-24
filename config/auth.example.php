<?php
/**
 * Template for config/auth.php
 *
 * config/auth.php is git-ignored and holds the admin password hash.
 * It is normally created automatically the first time you open /admin/
 * (first-run setup screen). You only need this file if you want to set
 * the password by hand.
 *
 * To create a hash manually run on the server:
 *   php -r "echo password_hash('YOUR-PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
 * then copy this file to config/auth.php and paste the hash below.
 */

return [
    // bcrypt/argon hash produced by password_hash()
    'password_hash' => 'PASTE_PASSWORD_HASH_HERE',
];
