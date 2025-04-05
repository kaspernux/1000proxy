<?php

namespace App\Helpers;

use App\Models\ServerPlan;
use Illuminate\Support\Facades\Cookie;

class CartManagement {

    // Add item to cart
    public static function addItemToCart($server_plan_id) {
        $order_items = self::getCartItemsFromCookie();

        $existing_item = null;

        foreach ($order_items as $key => $item) {
            if ($item['server_plan_id'] == $server_plan_id) {
                $existing_item = $key;
                break;
            }
        }

        if ($existing_item !== null) {
            $order_items[$existing_item]['quantity']++;
            $order_items[$existing_item]['total_amount'] = $order_items[$existing_item]['quantity'] * $order_items[$existing_item]['price'];
        } else {
            $server_plan = ServerPlan::find($server_plan_id, ['id', 'name', 'price', 'product_image']);
            if ($server_plan) {
                $order_items[] = [
                    'server_plan_id' => $server_plan_id,
                    'name' => $server_plan->name,
                    'product_image' => $server_plan->product_image,
                    'quantity' => 1,
                    'price' => $server_plan->price,
                    'total_amount' => $server_plan->price,
                ];
            }
        }

        self::addCartItemsToCookie($order_items);
        return count($order_items);
    }

    // Add item to cart with quantity
    public static function addItemToCartWithQty($server_plan_id, $qty = 1) {
        $order_items = self::getCartItemsFromCookie();

        $existing_item = null;

        foreach ($order_items as $key => $item) {
            if ($item['server_plan_id'] == $server_plan_id) {
                $existing_item = $key;
                break;
            }
        }

        if ($existing_item !== null) {
            $order_items[$existing_item]['quantity'] = $qty;
            $order_items[$existing_item]['total_amount'] = $order_items[$existing_item]['quantity'] * $order_items[$existing_item]['price'];
        } else {
            $server_plan = ServerPlan::find($server_plan_id, ['id', 'name', 'price', 'product_image']);
            if ($server_plan) {
                $order_items[] = [
                    'server_plan_id' => $server_plan_id,
                    'name' => $server_plan->name,
                    'product_image' => $server_plan->product_image,
                    'quantity' => $qty,
                    'price' => $server_plan->price,
                    'total_amount' => $server_plan->price * $qty,
                ];
            }
        }

        self::addCartItemsToCookie($order_items);
        return count($order_items);
    }

    // Remove item from cart
    public static function removeCartItem($server_plan_id) {
        $order_items = self::getCartItemsFromCookie();

        foreach ($order_items as $key => $item) {
            if ($item['server_plan_id'] == $server_plan_id) {
                unset($order_items[$key]);
                break;
            }
        }

        self::addCartItemsToCookie(array_values($order_items));
        return $order_items;
    }

    // Add cart items to cookie
    public static function addCartItemsToCookie($order_items) {
        Cookie::queue('order_items', json_encode($order_items), 60 * 24 * 30);
    }

    // Clear cart items from cookie
    public static function clearCartItems() {
        Cookie::queue(Cookie::forget('order_items'));
    }

    // Get all items from cookie
    public static function getCartItemsFromCookie() {
        $order_items = json_decode(Cookie::get('order_items'), true);
        return $order_items ?: [];
    }

    // Increment item quantity
    public static function incrementQuantityToCartItem($server_plan_id) {
        $order_items = self::getCartItemsFromCookie();

        foreach ($order_items as $key => $item) {
            if ($item['server_plan_id'] == $server_plan_id) {
                $order_items[$key]['quantity']++;
                $order_items[$key]['total_amount'] = $order_items[$key]['quantity'] * $order_items[$key]['price'];
                break;
            }
        }

        self::addCartItemsToCookie($order_items);
        return $order_items;
    }

    // Decrement item quantity
    public static function decrementQuantityToCartItem($server_plan_id) {
        $order_items = self::getCartItemsFromCookie();

        foreach ($order_items as $key => $item) {
            if ($item['server_plan_id'] == $server_plan_id) {
                if ($order_items[$key]['quantity'] > 1) {
                    $order_items[$key]['quantity']--;
                    $order_items[$key]['total_amount'] = $order_items[$key]['quantity'] * $order_items[$key]['price'];
                } else {
                    unset($order_items[$key]);
                }
                break;
            }
        }

        self::addCartItemsToCookie(array_values($order_items));
        return $order_items;
    }

    // Calculate grand total
    public static function calculateGrandTotal($items) {
        return array_sum(array_column($items, 'total_amount'));
    }
}