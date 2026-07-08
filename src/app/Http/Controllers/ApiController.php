<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;

/**
 * Базовый контроллер REST API с единым JSON-конвертом ответов.
 */
class ApiController extends Controller
{
    use RespondsWithJson;
}
