<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/package/status', static fn () => ['status' => 'ok']);
