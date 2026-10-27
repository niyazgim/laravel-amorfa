<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\RegLogController;
use App\Http\Middleware\IsAdmin;
use Illuminate\Support\Facades\Route;

// Уточняем роут, т.е.
// метод (GET, POST, PUT, PATCH, DELETE, OPTIONS) + путь через слэш '/' (URI)
// и указываем обработчик (функция либо метод у контроллера)
// Контроллер (controller) - это сбор функций-обработчиков под одной крышей
Route::get('/', function () {
  return view('pages.welcome');
});

// Пример синтаксиса:
// Route::Метод_REST(нужный_нам_URI, [Указатель_на_класс_контроллера, Имя_метода])
Route::get('/products', [ProductController::class, 'indexPage']);

Route::get('/products/{id}', [ProductController::class, 'showPage']);

Route::middleware('guest')->group(function () {
  Route::get('/login', [RegLogController::class, 'loginPage'])->name('login');
  Route::post('/login', [RegLogController::class, 'login']);

  Route::get('/register', [RegLogController::class, 'registerPage']);
  Route::post('/register', [RegLogController::class, 'register']);
});

Route::middleware('auth')->group(function () {
  Route::post('/logout', [RegLogController::class, 'logout']);
});

Route::middleware(IsAdmin::class)->group(function () {
  Route::get('/products/create', [ProductController::class, 'createPage']);

  Route::get('/products/{id}/edit', [ProductController::class, 'editPage']);

  Route::post('/products', [ProductController::class, 'create']);

  Route::patch('/products/{id}',  [ProductController::class, 'edit']);

  Route::delete('/products/{id}',  [ProductController::class, 'delete']);
});
