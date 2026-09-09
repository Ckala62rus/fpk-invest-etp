<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Базовый контроллер REST API с единым JSON-конвертом ответов.
 */
class ApiController extends Controller
{
    use AuthorizesRequests;
    use RespondsWithJson;
}
