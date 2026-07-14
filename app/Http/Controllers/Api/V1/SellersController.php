<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SellerProfileResource;
use App\Models\User;

final class SellersController extends Controller
{
    public function show(User $seller): SellerProfileResource
    {
        $seller->loadCount('activeAds');

        return new SellerProfileResource($seller);
    }
}
