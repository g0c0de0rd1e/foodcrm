<?php

namespace App\Services\CartService;


use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\Stock;
use App\Models\UserCart;
use App\Services\CoreService;

class CheckQuantityService extends CoreService
{
    protected function getModelClass(): string
    {
        return Cart::class;
    }

    public function checkQuantity($cartId)
    {
        $userCartIds = UserCart::where('cart_id', $cartId)->pluck('id')->toArray();

        $cartDetails = CartDetail::whereIn('user_cart_id', $userCartIds)->get();

        $stocks = Stock::whereIn('id', $cartDetails->pluck('stock_id')->toArray())->get();

        $cartDetails->map(function ($item) use ($stocks) {
            /**
             * @var Stock $stock
             * @var CartDetail $item
             */
            foreach ($stocks as $stock) {
                if (($item->stock_id == $stock->id) && ($item->quantity > $stock->quantity)) {
                    $item->update(['quantity' => $stock->quantity]);
                }
            }
        });
    }


}
