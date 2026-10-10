<?php

namespace App\Payments;

use App\Models\PaymentAttempt;

interface Gateway
{
    public function available(): bool;

    public function currency(): string;

    public function initiate(PaymentAttempt $attempt): array;

    public function lookup(PaymentAttempt $attempt): array;
}
