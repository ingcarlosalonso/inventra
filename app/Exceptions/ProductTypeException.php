<?php

namespace App\Exceptions;

use Exception;

class ProductTypeException extends Exception
{
    public static function hasChildren(): self
    {
        return new self(__('product_types.has_children_error'), 422);
    }

    public static function selfParent(): self
    {
        return new self(__('product_types.self_parent_error'), 422);
    }
}
