<?php

namespace App\Support;

use App\Models\Barcode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DynamicBarcodeTokenService
{
    private const PREFIX = 'dbc1';

    public function generateTokenPayload(Barcode $barcode, ?Carbon $issuedAt = null): array
    {
        $issuedAt = ($issuedAt ?? now())->copy();
        $configuredTtl = $this->resolveTtl($barcode);
        $ttl = $this->resolveIssuedTtl($configuredTtl);
        $expiresAt = $issuedAt->copy()->addSeconds($ttl);

        $payload = [
            'v' => 2,
            'b' => $barcode->id,
            'i' => $issuedAt->timestamp,
            'e' => $expiresAt->timestamp,
            'n' => $this->randomNonce(),
        ];

        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $encodedPayload, $this->signingKey($barcode), true));
        $token = self::PREFIX.'.'.$encodedPayload.'.'.$signature;
        $graceSeconds = $this->resolveGraceSeconds($ttl);

        $this->rememberCurrentToken($barcode, $token, $expiresAt, $graceSeconds);

        return [
            'token' => $token,
            'issued_at' => $issuedAt->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
            'ttl_seconds' => $ttl,
            'configured_ttl_seconds' => $configuredTtl,
            'grace_seconds' => $graceSeconds,
            'refresh_in_seconds' => max(5, min(20, $ttl - 5)),
        ];
    }

    public function resolveScannedBarcode(string $scannedValue): ?Barcode
    {
        return $this->resolveScannedBarcodeWithSource($scannedValue)['barcode'];
    }

    public function resolveScannedBarcodeWithSource(string $scannedValue): array
    {
        if ($this->looksDynamicToken($scannedValue)) {
            return $this->resolveDynamicBarcode($scannedValue);
        }

        return [
            'barcode' => Barcode::query()
                ->where('dynamic_enabled', false)
                ->firstWhere('value', $scannedValue),
            'source' => 'static',
        ];
    }

    public function consumeScannedToken(Barcode $barcode, string $scannedValue): void
    {
        if (! $this->looksDynamicToken($scannedValue)) {
            return;
        }

        $currentFingerprint = Cache::get($this->currentTokenCacheKey($barcode));

        if (
            is_string($currentFingerprint) &&
            hash_equals($currentFingerprint, $this->tokenFingerprint($barcode, $scannedValue))
        ) {
            Cache::forget($this->currentTokenCacheKey($barcode));
        }
    }

    protected function resolveDynamicBarcode(string $token): array
    {
        $parts = explode('.', $token, 3);

        if (count($parts) !== 3 || $parts[0] !== self::PREFIX) {
            return ['barcode' => null, 'source' => 'dynamic'];
        }

        $payloadJson = $this->base64UrlDecode($parts[1]);

        if ($payloadJson === false) {
            return ['barcode' => null, 'source' => 'dynamic'];
        }

        $payload = json_decode($payloadJson, true);

        if (! is_array($payload)) {
            return ['barcode' => null, 'source' => 'dynamic'];
        }

        $barcodeId = $payload['b'] ?? $payload['bid'] ?? null;
        $issuedAt = (int) ($payload['i'] ?? $payload['iat'] ?? 0);
        $expiresAt = (int) ($payload['e'] ?? $payload['exp'] ?? 0);
        $nonce = (string) ($payload['n'] ?? $payload['nonce'] ?? '');

        if (! $barcodeId || ! $issuedAt || ! $expiresAt || $nonce === '' || ! array_key_exists('v', $payload)) {
            return ['barcode' => null, 'source' => 'dynamic'];
        }

        /** @var Barcode|null $barcode */
        $barcode = Barcode::query()->find($barcodeId);

        if (! $barcode || ! $barcode->dynamic_enabled) {
            return ['barcode' => null, 'source' => 'dynamic'];
        }

        $expectedSignatureBinary = hash_hmac('sha256', $parts[1], $this->signingKey($barcode), true);
        $expectedSignatureBase64 = $this->base64UrlEncode($expectedSignatureBinary);
        $expectedSignatureHex = bin2hex($expectedSignatureBinary);

        if (
            ! hash_equals($expectedSignatureBase64, (string) $parts[2]) &&
            ! hash_equals($expectedSignatureHex, (string) $parts[2])
        ) {
            return ['barcode' => null, 'source' => 'dynamic'];
        }

        if (
            array_key_exists('code_hash', $payload) &&
            ! hash_equals($this->barcodeValueHash($barcode), (string) $payload['code_hash'])
        ) {
            return ['barcode' => null, 'source' => 'dynamic'];
        }

        $now = now()->timestamp;
        $configuredTtl = $this->resolveTtl($barcode);
        [$minTtl, $maxTtl] = $this->resolveTtlRange($configuredTtl);
        $actualTtl = $expiresAt - $issuedAt;
        $grace = $this->resolveGraceSeconds($actualTtl);

        if ($issuedAt > $now + 30) {
            return ['barcode' => null, 'source' => 'dynamic'];
        }

        if ($expiresAt <= $issuedAt || $actualTtl < $minTtl || $actualTtl > $maxTtl) {
            return ['barcode' => null, 'source' => 'dynamic'];
        }

        if ($expiresAt <= $now - $grace) {
            return ['barcode' => null, 'source' => 'dynamic'];
        }

        if (! $this->isCurrentToken($barcode, $token)) {
            return ['barcode' => null, 'source' => 'dynamic'];
        }

        return [
            'barcode' => $barcode,
            'source' => 'dynamic',
        ];
    }

    protected function looksDynamicToken(string $value): bool
    {
        return str_starts_with($value, self::PREFIX.'.');
    }

    protected function resolveTtl(Barcode $barcode): int
    {
        return max(30, min(300, (int) ($barcode->dynamic_ttl_seconds ?: 60)));
    }

    protected function resolveIssuedTtl(int $configuredTtl): int
    {
        [$minTtl, $maxTtl] = $this->resolveTtlRange($configuredTtl);

        if ($minTtl === $maxTtl) {
            return $configuredTtl;
        }

        return random_int($minTtl, $maxTtl);
    }

    protected function resolveTtlRange(int $configuredTtl): array
    {
        if ($configuredTtl <= 45) {
            return [$configuredTtl, $configuredTtl];
        }

        $jitter = max(5, min(20, (int) floor($configuredTtl / 4)));

        return [
            max(30, $configuredTtl - $jitter),
            min(300, $configuredTtl + $jitter),
        ];
    }

    protected function resolveGraceSeconds(int $ttl): int
    {
        return 0;
    }

    protected function signingKey(Barcode $barcode): string
    {
        return config('app.key').'|'.$barcode->secret_key;
    }

    protected function barcodeValueHash(Barcode $barcode): string
    {
        return hash_hmac('sha256', (string) $barcode->value, $this->signingKey($barcode));
    }

    protected function randomNonce(): string
    {
        return $this->base64UrlEncode(random_bytes(9));
    }

    protected function rememberCurrentToken(Barcode $barcode, string $token, Carbon $expiresAt, int $graceSeconds): void
    {
        $lifetime = max(1, $expiresAt->timestamp + $graceSeconds + 5 - now()->timestamp);

        Cache::put(
            $this->currentTokenCacheKey($barcode),
            $this->tokenFingerprint($barcode, $token),
            now()->addSeconds($lifetime)
        );
    }

    protected function isCurrentToken(Barcode $barcode, string $token): bool
    {
        $currentFingerprint = Cache::get($this->currentTokenCacheKey($barcode));

        return is_string($currentFingerprint)
            && hash_equals($currentFingerprint, $this->tokenFingerprint($barcode, $token));
    }

    protected function currentTokenCacheKey(Barcode $barcode): string
    {
        return 'dynamic-barcode.current-token.'.$barcode->id;
    }

    protected function tokenFingerprint(Barcode $barcode, string $token): string
    {
        return hash_hmac('sha256', $token, $this->signingKey($barcode));
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    protected function base64UrlDecode(string $value): string|false
    {
        $remainder = strlen($value) % 4;

        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($value, '-_', '+/'), true);
    }
}
