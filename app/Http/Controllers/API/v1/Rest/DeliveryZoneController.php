<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Helpers\Utility;
use App\Http\Requests\DeliveryZone\CheckDistanceRequest;
use App\Http\Requests\DeliveryZone\DistanceRequest;
use App\Http\Resources\ShopResource;
use App\Models\DeliveryZone;
use App\Models\Language;
use App\Models\Shop;
use App\Traits\SetCurrency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class DeliveryZoneController extends RestBaseController
{
    use SetCurrency;

    /**
     * @param int $shopId
     * @return array
     */
    public function getByShopId(int $shopId): array
    {
        try {
            $deliveryZone = DeliveryZone::where('shop_id', $shopId)->firstOrFail();

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => $deliveryZone,
            ];
        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status' => false,
                'code' => ResponseError::ERROR_404,
            ];
        }
    }

    public function deliveryCalculatePrice(int $deliveryId, Request $request): float|JsonResponse
    {
        /** @var DeliveryZone $deliveryZone */
        $deliveryZone = DeliveryZone::find($deliveryId);

        if (!$deliveryZone) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $km = $request->input('km');

        if ($km <= 0) {
            $km = 1;
        }

        return round(
            ($deliveryZone->shop->price + ($deliveryZone->shop->price_per_km * $km)) * $this->currency(), 2
        );
    }

    /**
     * @param DistanceRequest $request
     * @return array
     */
    public function distance(DistanceRequest $request): array
    {
        return [
            'status' => true,
            'code' => ResponseError::NO_ERROR,
            'data' => (new Utility)->getDistance($request->input('origin'), $request->input('destination')),
        ];
    }

    /**
     * @param CheckDistanceRequest $request
     * @return JsonResponse
     */
    public function checkDistance(CheckDistanceRequest $request): JsonResponse
    {

        $shops = Shop::with('deliveryZone:id,shop_id,address')
            ->where([
                ['open', 1],
                ['status', 'approved'],
            ])
            ->whereHas('deliveryZone')
            ->select(['id', 'open', 'status'])
            ->get();

        foreach ($shops as $shop) {

            /** @var Shop $shop */
            $address = optional($shop->deliveryZone)->address;

            if (!is_array($address) || count($address) === 0) {
                continue;
            }

            $check = Utility::pointInPolygon($request->input('address'), $shop->deliveryZone->address);

            if ($check) {
                return $this->successResponse('success', 'success');
            }

        }

        return $this->onErrorResponse(['code' => ResponseError::ERROR_400]);
    }

    /**
     * @param CheckDistanceRequest $request
     * @return JsonResponse
     */
    public function checkSmallest(CheckDistanceRequest $request): JsonResponse
    {
        $kms = [];

        $shops = Shop::with([
            'deliveryZone:id,shop_id,address',
        ])
            ->where([
                ['open', 1],
                ['status', 'approved'],
            ])
            ->whereNotNull('location')
            ->whereHas('deliveryZone')
            ->select(['id', 'open', 'status', 'location'])
            ->get();

        foreach ($shops as $shop) {

            /** @var Shop $shop */
            $address = $shop->deliveryZone?->address;

            if (!is_array($address) || count($address) === 0) {
                continue;
            }

            $check = Utility::pointInPolygon($request->input('address'), $shop->deliveryZone->address);

            if ($check) {
                $kms[$shop->id] = ['value' => (new Utility)->getDistance($request->input('address'), $shop->location)];
            }

        }

        $kms    = collect($kms);
        $min    = $kms->min('value');
        $id     = $kms->where('value', $min)->keys()->first();
        $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');
        $shop   = Shop::with([
            'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
            'workingDays',
            'closedDates',
        ])->find($id);

        if (empty($shop)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_400, 'message' => 'Not Delivered']);
        }

        return $this->successResponse(__('web.success'), ShopResource::make($shop));
    }

    /**
     * @param int $id
     * @param CheckDistanceRequest $request
     * @return JsonResponse
     */
    public function checkDistanceByShop(int $id, CheckDistanceRequest $request): JsonResponse
    {
        /** @var Shop $shop */
        $shop = Shop::with('deliveryZone:id,shop_id,address')->whereHas('deliveryZone')
            ->where([
                ['open', 1],
                ['status', 'approved'],
            ])
            ->select(['id', 'open', 'status'])
            ->find($id);

        if (empty($shop?->deliveryZone)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404, 'message' => 'empty shop or delivery zone']);
        }

        $check = Utility::pointInPolygon($request->input('address'), $shop->deliveryZone->address);

        if ($check) {
            return $this->successResponse('success');
        }

        return $this->onErrorResponse(['code' => ResponseError::ERROR_400, 'message' => 'not in polygon']);
    }

}
