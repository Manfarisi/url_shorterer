<?php

namespace App\Services;

class Base62Encoder
{
     private const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const BASE = 62;

    public function encode(int $number): string{
        if($number === 0){
            return self::ALPHABET[0];
        }
        $result = '';

        while ($number > 0){
            $remainder = $number % self::BASE;
            $result = self::ALPHABET[$remainder]. $result;
            $number = intdiv($number, self::BASE);
        }
        return $result;
    }
    
    public function decode(string $code): int
    {

        $result = 0;
        foreach (str_split($code) as $char){
            $position = strpos(self::ALPHABET, $char);
            $result = $result * self::BASE + $position;
        }
        return $result;
    }
}
