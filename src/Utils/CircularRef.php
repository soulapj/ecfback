<?php

namespace App\Utils;

class CircularRef
{
    public static function handleCircularReference($object)
    {
        return $object->getId(); 
    }
}