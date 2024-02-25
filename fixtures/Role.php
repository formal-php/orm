<?php
declare(strict_types = 1);

namespace Fixtures\Formal\ORM;

enum Role
{
    case admin;
    case user;
    case guest;
}
