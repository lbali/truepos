<?php

declare(strict_types=1);

namespace TruePos\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use TruePos\Contracts\ThreeDSecureInterface;
use TruePos\Events\PaymentCompleted;
use TruePos\Events\PaymentFailed;
use TruePos\Events\ThreeDSecureCallbackReceived;
use TruePos\Exceptions\ThreeDSecureException;
use TruePos\TruePosManager;

final class ThreeDSecureController extends Controller
{
    public function callback(Request $request, TruePosManager $manager): mixed
    {
        $callbackData = $request->all();

        event(new ThreeDSecureCallbackReceived($callbackData));

        $gatewayName = $this->resolveGateway($callbackData);
        $gateway = $manager->gateway($gatewayName);

        if (! $gateway instanceof ThreeDSecureInterface) {
            throw ThreeDSecureException::authenticationFailed();
        }

        $response = $gateway->completeThreeD($callbackData);

        if ($response->isSuccessful()) {
            event(new PaymentCompleted($response));
        } else {
            event(new PaymentFailed($response));
        }

        $redirectUrl = $response->isSuccessful()
            ? config('truepos.threed_success_url', '/')
            : config('truepos.threed_failure_url', '/');

        return redirect($redirectUrl)->with('truepos_response', $response);
    }

    private function resolveGateway(array $data): string
    {
        $orderId = $data['oid']
            ?? $data['OrderId']
            ?? $data['orderID']
            ?? $data['MerchantOrderId']
            ?? '';

        $gateway = Cache::get("truepos_3d_{$orderId}");

        if ($gateway === null) {
            throw ThreeDSecureException::gatewayNotResolved();
        }

        return $gateway;
    }
}
