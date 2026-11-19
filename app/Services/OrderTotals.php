<?php

namespace App\Services;

class OrderTotals
{
    public static function cents(string|float|int $value): int
    {
        return (int) round((float) $value * 100);
    }

    public static function calculate(iterable $items, string|float|int $taxRate): array
    {
        $subtotal = 0;

        foreach ($items as $item) {
            $quantity = is_array($item) ? $item['quantity'] : $item->quantity;
            $price = is_array($item) ? $item['unit_price'] : $item->unit_price;
            $subtotal += (int) $quantity * self::cents($price);
        }

        $basisPoints = (int) round((float) $taxRate * 100);
        $tax = intdiv($subtotal * $basisPoints + 5000, 10000);

        if ($subtotal + $tax > 99999999999999) {
            throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'The order total exceeds the supported amount.']);
        }

        return [
            'subtotal' => number_format($subtotal / 100, 2, '.', ''),
            'tax_amount' => number_format($tax / 100, 2, '.', ''),
            'total' => number_format(($subtotal + $tax) / 100, 2, '.', ''),
        ];
    }
}
