<?php
// Check all users
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use App\Models\User;

echo "=== USERS IN DATABASE ===\n";
$users = User::all();
foreach ($users as $user) {
    echo "ID: {$user->id}, Name: {$user->name}, Email: {$user->email}, Role: {$user->role}\n";
}
