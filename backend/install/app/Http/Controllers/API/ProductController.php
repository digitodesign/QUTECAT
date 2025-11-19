<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Traits\ContextAware;
use App\Http\Requests\AddFavoriteRequest;
use App\Http\Requests\ReviewRequest;
use App\Http\Resources\BrandResource;
use App\Http\Resources\ColorResource;
use App\Http\Resources\ProductDetailsResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\SizeResource;
use App\Models\FlashSale;
use App\Repositories\ProductRepository;
use App\Repositories\ReviewRepository;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ContextAware; // SaaS: Context-aware filtering
    /**
     * Retrieve a paginated list of products based on the provided request parameters.
     * @return response
     */
    public function show(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = ProductRepository::find($request->product_id);
        ProductRepository::recentView($product);

        $relatedProducts = ProductRepository::query()->whereHas('categories', function ($query) use ($product) {
            $query->whereIn('categories.id', $product->categories->pluck('id'));
        })->where('id', '!=', $product->id)
            ->isActive()
            ->inRandomOrder()
            ->limit(6)->get();

        $shop = $product->shop;

        $popularProducts = $shop->products()->isActive()->where('id', '!=', $product->id)->withCount('orders')->withAvg('reviews as average_rating', 'rating')->orderByDesc('average_rating')->orderByDesc('orders_count')->take(6)->get();

        return $this->json('product details', [
            'product' => ProductDetailsResource::make($product),
            'related_products' => ProductResource::collection($relatedProducts),
            'popular_products' => ProductResource::collection($popularProducts),
        ]);
    }

    /**
     * Add or remove favorite product for the user.
     *
     * @param  AddFavoriteRequest  $request  The request for adding a favorite.
     * @return json Response with favorite updated successfully
     */
    public function addFavorite(AddFavoriteRequest $request)
    {
        $product = ProductRepository::find($request->product_id);

        auth()->user()?->customer->favorites()->toggle($product->id);

        return $this->json('favorite updated successfully', [
            'product' => ProductResource::make($product),
        ]);
    }

    /**
     * get list of favorite products.
     *
     * @return json Response
     */
    public function favoriteProducts()
    {
        $products = auth()->user()->customer->favorites;

        return $this->json('favorite products', [
            'products' => ProductResource::collection($products),
        ]);
    }

    /**
     * Store a new review.
     *
     * @param  ReviewRequest  $request  The review request
     * @return json Response
     */
    public function storeReview(ReviewRequest $request)
    {
        $product = ProductRepository::find($request->product_id);

        $hasReview = $product->reviews()->where('customer_id', auth()->user()->customer->id)->where('order_id', $request->order_id)->first();

        if ($hasReview) {
            return $this->json('review already exists', [
                'review' => ReviewResource::make($hasReview),
            ]);
        }

        $review = ReviewRepository::storeByRequest($request, $product);

        return $this->json('review added successfully', [
            'review' => ReviewResource::make($review),
        ]);
    }
}
