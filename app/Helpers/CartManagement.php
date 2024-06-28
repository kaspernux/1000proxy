<?php

namespace App\Helpers;

use App\Models\ServerPlan;
use Illuminate\Support\Facades\Cookie;

class CartManagement {

    // Add item to cart
    static public function addItemToCart($server_plan_id) {
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
            $server_plan = ServerPlan::where('id', $server_plan_id)->first(['id', 'name', 'price', 'product_image']);
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

    // Add item to cart with Qty
    static public function addItemToCartWithQty($server_plan_id, $qty = 1) {
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
            $server_plan = ServerPlan::where('id', $server_plan_id)->first(['id', 'name', 'price', 'product_image']);
            if ($server_plan) {
                $order_items[] = [
                    'server_plan_id' => $server_plan_id,
                    'name' => $server_plan->name,
                    'product_image' => $server_plan->product_image,
                    'quantity' => $qty,
                    'price' => $server_plan->price,
                    'total_amount' => $server_plan->price,
                ];
            }
        }

        self::addCartItemsToCookie($order_items);
        return count($order_items);
    }


    // Remove item from cart
    static public function removeCartItem($server_plan_id) {
        $order_items = self::getCartItemsFromCookie();

        foreach ($order_items as $key => $item) {
            if ($item['server_plan_id'] == $server_plan_id) {
                unset($order_items[$key]);
            }
        }

        self::addCartItemsToCookie($order_items);
        return $order_items;
    }

    // Add cart items to cookie
    static public function addCartItemsToCookie($order_items) {
        Cookie::queue('order_items', json_encode($order_items), 60 * 24 * 30);
    }

    // Clear cart items from cookie
    static public function clearCartItems() {
        Cookie::queue(Cookie::forget('order_items'));
    }

    // Get all items from cookie
    static public function getCartItemsFromCookie() {
        $order_items = json_decode(Cookie::get('order_items'), true);
        if(!$order_items){
            $order_items = [];
        }

        return $order_items;
    }

    // Increment item quantity
    static public function incrementQuantityToCartItem($server_plan_id){
        $order_items = self::getCartItemsFromCookie();

        foreach ($order_items as $key => $item) {
            if ($item['server_plan_id'] == $server_plan_id) {
                $order_items[$key]['quantity']++;
                $order_items[$key]['total_amount'] = $order_items[$key]['quantity'] * $order_items[$key]['price'];
                break; // Exit the loop once the item is found and updated
            }
        }

        self::addCartItemsToCookie($order_items);
        return $order_items;
    }


    // Decrement item quantity
    static public function decrementQuantityToCartItem($server_plan_id){
        $order_items = self::getCartItemsFromCookie();

        foreach ($order_items as $key => $item) {
            if ($item['server_plan_id'] == $server_plan_id) {
                if ($order_items[$key]['quantity'] > 1) {
                    $order_items[$key]['quantity']--;
                    $order_items[$key]['total_amount'] = $order_items[$key]['quantity'] * $order_items[$key]['price'];
                } else {
                    unset($order_items[$key]);
                }
                break; // Exit the loop once the item is found and updated
            }
        }

        self::addCartItemsToCookie($order_items);
        return $order_items;
    }


    // Calculate grand total

    static public function calculateGrandTotal($items){
        return array_sum(array_column($items, 'total_amount'));
    }
}