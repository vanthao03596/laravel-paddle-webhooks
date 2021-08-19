<?php

namespace Vanthao03596\PaddleWebhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

class PaddleSignatureValidator implements SignatureValidator
{
    const SIGNATURE_KEY = 'p_signature';

    public function isValid(Request $request, WebhookConfig $config): bool
    {
        if (! config('paddle-webhooks.verify_signature')) {
            return true;
        }

        $fields = $this->extractFields($request);
        $signature = $request->get(self::SIGNATURE_KEY);

        if ($this->isInvalidSignature($fields, $signature, $config->signingSecret)) {
            return false;
        }

        return true;
    }

    protected function extractFields(Request $request)
    {
        $fields = $request->except(self::SIGNATURE_KEY);

        ksort($fields);

        foreach ($fields as $k => $v) {
            if (! in_array(gettype($v), ['object', 'array'])) {
                $fields[$k] = "$v";
            }
        }

        return $fields;
    }

    protected function isInvalidSignature(array $fields, $signature, $signingSecret)
    {
        return openssl_verify(
            serialize($fields),
            base64_decode($signature),
            openssl_get_publickey($signingSecret),
            OPENSSL_ALGO_SHA1
        ) !== 1;
    }
}
