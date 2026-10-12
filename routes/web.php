<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/products', function () {
    return view('products');
});

Route::get('/products/{id}', function () {
    return view('product.show');
});

Route::middleware('auth')->group(function () {
  Route::get('/products/create', function () {
      return view('product.create');
  });

  Route::get('/products/{id}/edit', function () {
      return view('product.edit');
  });

  Route::post('/products', function () {
      return 'test';
  });

  Route::patch('/products', function () {
      return 'fixed!';
  });

  Route::delete('/products', function () {
      return 'deleted!';
  });
});
