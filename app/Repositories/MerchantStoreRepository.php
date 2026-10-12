<?php

namespace App\Repositories;

use App\Models\MerchantStore;
use App\Models\User;
use App\Models\WholesaleAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MerchantStoreRepository
{
    public function approvedAccount(User $user): WholesaleAccount
    {
        return WholesaleAccount::where('user_id', $user->id)->where('status', 'approved')->firstOrFail();
    }

    public function ownedStore(User $user): MerchantStore
    {
        $account = $this->approvedAccount($user);

        return MerchantStore::where('wholesale_account_id', $account->id)->firstOrFail();
    }

    public function assertPublic(MerchantStore $store): void
    {
        abort_unless($store->active && $store->account()->where('status', 'approved')->exists(), 404);
    }

    public function directory(array $filters): LengthAwarePaginator
    {
        return MerchantStore::query()->where('active', true)
            ->whereHas('account', fn ($query) => $query->where('status', 'approved'))
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('name', 'like', '%'.$term.'%'))
            ->withCount(['products' => fn ($query) => $query->where('active', true)])
            ->latest()->paginate(12)->withQueryString();
    }

    public function products(MerchantStore $store, array $filters): LengthAwarePaginator
    {
        return $store->products()->with('variants')->where('active', true)
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('name', 'like', '%'.$term.'%'))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->latest()->paginate(12)->withQueryString();
    }
}
