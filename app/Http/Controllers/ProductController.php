<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function indexPage()
  {
    return view('pages.products.index');
  }

  /**
   * Show the form for creating a new resource.
   */
  public function createPage()
  {
    return view('pages.products.create');
  }

  /**
   * Store a newly created resource in storage.
   */
  public function create(Request $request)
  {
    $request->validate([
      'name' => 'required|min:2',
      'description' => 'required',
      'price' => 'required|numeric',
      'cover' => 'required|file|max:5000|mimes:png,jpg,webp',
      'product_category_id' => 'reqired|exists:product_categories,id',
    ]);

    if ($request->file('cover')) {
      $filename = uniqid() . '.' . $request->file('cover')->extension();
      $dir = 'products/images/';
      Storage::disk('public')->putFileAs($dir, $request->file('cover'), $filename);

      $product = Product::create([
        'name' => $request->name,
        'description' => $request->description,
        'price' => $request->price,
        'cover' => $dir . $filename,
        'product_category_id' => $request->product_category_id,
      ]);

      return redirect('/products/' . $product->id);
    }
    return abort('500');
  }

  /**
   * Display the specified resource.
   */
  public function showPage(Product $product)
  {
    return view('pages.products.show');
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function editPage(Product $product)
  {
    return view('pages.products.edit');
  }

  /**
   * Update the specified resource in storage.
   */
  public function edit(Request $request, string $id)
  {
    $request->validate([
      'name' => 'required|min:2',
      'description' => 'required',
      'price' => 'required|numeric',
      'cover' => 'required|file|max:5000|mimes:png,jpg,webp',
      'product_category_id' => 'reqired|exists:product_categories,id',
    ]);

    if ($request->file('cover')) {
      $product = Product::findOrFail($id);

      $filename = uniqid() . '.' . $request->file('cover')->extension();
      $dir = 'products/images/';
      Storage::disk('public')->putFileAs($dir, $request->file('cover'), $filename);

      $product->update([
        'name' => $request->name,
        'description' => $request->description,
        'price' => $request->price,
        'cover' => $dir . $filename,
        'product_category_id' => $request->product_category_id,
      ]);

      return redirect('/products/' . $id);
    }
    return abort('500');
  }

  /**
   * Remove the specified resource from storage.
   */
  public function delete(string $id)
  {
    $product = Product::findOrFail($id);

    $product->delete();

    return redirect('/products');
  }
}
