<?php

use App\Support\CurrentUser;

if (! function_exists('current_user')) {
    function current_user(): ?CurrentUser
    {
        return CurrentUser::resolve();
    }
}

if (! function_exists('current_user_check')) {
    function current_user_check(): bool
    {
        return CurrentUser::resolve() !== null;
    }
}
