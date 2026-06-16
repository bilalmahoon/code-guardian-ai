<?php

// Forward-compat alias: the actual User model lives in the Domain layer.
// This file exists so Laravel's default config/auth.php can resolve it.

namespace App\Models;

class User extends \App\Domain\Organization\Models\User
{
    // All logic is in the parent domain model
}
