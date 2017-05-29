<?php

namespace myafk\cart\storage;

use myafk\cart\Cart;

/**
 * Interface StorageInterface
 *
 * @package myafk\cart\storage
 */
interface StorageInterface
{
    /**
     * @param Cart $cart
     *
     * @return array|mixed
     */
    public function load(Cart $cart);

    /**
     * @param Cart $cart
     */
    public function save(Cart $cart);
}
