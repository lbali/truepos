<?php

declare(strict_types=1);

namespace TruePos\Factory;

use Psr\Http\Client\ClientInterface;
use TruePos\Contracts\GatewayInterface;
use TruePos\Enums\Gateway;
use TruePos\Exceptions\InvalidConfigurationException;
use TruePos\Gateways\Garanti\GarantiGateway;
use TruePos\Gateways\Garanti\GarantiHashGenerator;
use TruePos\Gateways\Garanti\GarantiResponseParser;
use TruePos\Gateways\Garanti\GarantiSerializer;
use TruePos\Gateways\NestPay\NestPayGateway;
use TruePos\Gateways\NestPay\NestPayHashGenerator;
use TruePos\Gateways\NestPay\NestPayResponseParser;
use TruePos\Gateways\NestPay\NestPaySerializer;
use TruePos\Gateways\PosNet\PosNetGateway;
use TruePos\Gateways\PosNet\PosNetHashGenerator;
use TruePos\Gateways\PosNet\PosNetResponseParser;
use TruePos\Gateways\PosNet\PosNetSerializer;
use TruePos\Gateways\PayFor\PayForGateway;
use TruePos\Gateways\PayFor\PayForHashGenerator;
use TruePos\Gateways\PayFor\PayForResponseParser;
use TruePos\Gateways\PayFor\PayForSerializer;
use TruePos\Gateways\Vakifbank\VakifbankGateway;
use TruePos\Gateways\Vakifbank\VakifbankHashGenerator;
use TruePos\Gateways\Vakifbank\VakifbankResponseParser;
use TruePos\Gateways\Vakifbank\VakifbankSerializer;
use TruePos\Gateways\KuveytTurk\KuveytTurkGateway;
use TruePos\Gateways\KuveytTurk\KuveytTurkHashGenerator;
use TruePos\Gateways\KuveytTurk\KuveytTurkResponseParser;
use TruePos\Gateways\KuveytTurk\KuveytTurkSerializer;
use TruePos\Gateways\PayTR\PayTRGateway;
use TruePos\Gateways\PayTR\PayTRHashGenerator;
use TruePos\Gateways\PayTR\PayTRResponseParser;
use TruePos\Gateways\PayTR\PayTRSerializer;
use TruePos\Gateways\Iyzico\IyzicoGateway;
use TruePos\Gateways\Iyzico\IyzicoHashGenerator;
use TruePos\Gateways\Iyzico\IyzicoResponseParser;
use TruePos\Gateways\Iyzico\IyzicoSerializer;
use TruePos\Gateways\Moka\MokaGateway;
use TruePos\Gateways\Moka\MokaHashGenerator;
use TruePos\Gateways\Moka\MokaResponseParser;
use TruePos\Gateways\Moka\MokaSerializer;
use TruePos\Gateways\Sipay\SipayGateway;
use TruePos\Gateways\Sipay\SipayHashGenerator;
use TruePos\Gateways\Sipay\SipayResponseParser;
use TruePos\Gateways\Sipay\SipaySerializer;
use TruePos\Gateways\Param\ParamGateway;
use TruePos\Gateways\Param\ParamHashGenerator;
use TruePos\Gateways\Param\ParamResponseParser;
use TruePos\Gateways\Param\ParamSerializer;
use TruePos\Gateways\Tosla\ToslaGateway;
use TruePos\Gateways\Tosla\ToslaHashGenerator;
use TruePos\Gateways\Tosla\ToslaResponseParser;
use TruePos\Gateways\Tosla\ToslaSerializer;
use TruePos\Gateways\Craftgate\CraftgateGateway;
use TruePos\Gateways\Craftgate\CraftgateHashGenerator;
use TruePos\Gateways\Craftgate\CraftgateResponseParser;
use TruePos\Gateways\Craftgate\CraftgateSerializer;
use TruePos\Gateways\EsnekPos\EsnekPosGateway;
use TruePos\Gateways\EsnekPos\EsnekPosHashGenerator;
use TruePos\Gateways\EsnekPos\EsnekPosResponseParser;
use TruePos\Gateways\EsnekPos\EsnekPosSerializer;
use TruePos\Gateways\Paratika\ParatikaGateway;
use TruePos\Gateways\Paratika\ParatikaHashGenerator;
use TruePos\Gateways\Paratika\ParatikaResponseParser;
use TruePos\Gateways\Paratika\ParatikaSerializer;
use TruePos\Gateways\Lidio\LidioGateway;
use TruePos\Gateways\Lidio\LidioHashGenerator;
use TruePos\Gateways\Lidio\LidioResponseParser;
use TruePos\Gateways\Lidio\LidioSerializer;

final class GatewayFactory
{
    public function create(Gateway $gateway, array $config, ClientInterface $httpClient): GatewayInterface
    {
        return match ($gateway) {
            Gateway::NestPay => new NestPayGateway(
                config: $config,
                serializer: new NestPaySerializer(),
                hashGenerator: new NestPayHashGenerator(),
                responseParser: new NestPayResponseParser(),
                httpClient: $httpClient,
            ),
            Gateway::Garanti => new GarantiGateway(
                config: $config,
                serializer: new GarantiSerializer(),
                hashGenerator: new GarantiHashGenerator(),
                responseParser: new GarantiResponseParser(),
                httpClient: $httpClient,
            ),
            Gateway::PosNet => new PosNetGateway(
                config: $config,
                serializer: new PosNetSerializer(),
                hashGenerator: new PosNetHashGenerator(),
                responseParser: new PosNetResponseParser(),
                httpClient: $httpClient,
            ),
            Gateway::PayFor => new PayForGateway(
                config: $config,
                serializer: new PayForSerializer(),
                hashGenerator: new PayForHashGenerator(),
                responseParser: new PayForResponseParser(),
                httpClient: $httpClient,
            ),
            Gateway::Vakifbank => new VakifbankGateway(
                config: $config,
                serializer: new VakifbankSerializer(),
                hashGenerator: new VakifbankHashGenerator(),
                responseParser: new VakifbankResponseParser(),
                httpClient: $httpClient,
            ),
            Gateway::KuveytTurk => new KuveytTurkGateway(
                config: $config,
                serializer: new KuveytTurkSerializer(),
                hashGenerator: new KuveytTurkHashGenerator(),
                responseParser: new KuveytTurkResponseParser(),
                httpClient: $httpClient,
            ),
            Gateway::PayTR => new PayTRGateway(
                config: $config,
                serializer: new PayTRSerializer(),
                hashGenerator: new PayTRHashGenerator(),
                responseParser: new PayTRResponseParser(),
                httpClient: $httpClient,
            ),
            Gateway::Iyzico => new IyzicoGateway(
                config: $config,
                serializer: new IyzicoSerializer(),
                hashGenerator: new IyzicoHashGenerator(),
                responseParser: new IyzicoResponseParser(),
                httpClient: $httpClient,
            ),
            Gateway::Moka => new MokaGateway(
                config: $config,
                serializer: new MokaSerializer(),
                hashGenerator: new MokaHashGenerator(),
                responseParser: new MokaResponseParser(),
                httpClient: $httpClient,
            ),
            Gateway::Sipay => new SipayGateway(
                config: $config,
                serializer: new SipaySerializer(),
                hashGenerator: new SipayHashGenerator(),
                responseParser: new SipayResponseParser(),
                httpClient: $httpClient,
            ),
            Gateway::Param => new ParamGateway(
                config: $config,
                serializer: new ParamSerializer(),
                hashGenerator: new ParamHashGenerator(),
                responseParser: new ParamResponseParser(),
                httpClient: $httpClient,
            ),
            Gateway::Tosla => new ToslaGateway(
                config: $config,
                serializer: new ToslaSerializer(),
                hashGenerator: new ToslaHashGenerator(),
                responseParser: new ToslaResponseParser(),
                httpClient: $httpClient,
            ),
            Gateway::Craftgate => new CraftgateGateway(
                config: $config,
                serializer: new CraftgateSerializer(),
                hashGenerator: new CraftgateHashGenerator(),
                responseParser: new CraftgateResponseParser(),
                httpClient: $httpClient,
            ),
            Gateway::EsnekPos => new EsnekPosGateway(
                config: $config,
                serializer: new EsnekPosSerializer(),
                hashGenerator: new EsnekPosHashGenerator(),
                responseParser: new EsnekPosResponseParser(),
                httpClient: $httpClient,
            ),
            Gateway::Paratika => new ParatikaGateway(
                config: $config,
                serializer: new ParatikaSerializer(),
                hashGenerator: new ParatikaHashGenerator(),
                responseParser: new ParatikaResponseParser(),
                httpClient: $httpClient,
            ),
            Gateway::Lidio => new LidioGateway(
                config: $config,
                serializer: new LidioSerializer(),
                hashGenerator: new LidioHashGenerator(),
                responseParser: new LidioResponseParser(),
                httpClient: $httpClient,
            ),
        };
    }
}
