<?php

namespace App\Actions\QrCode;

final class QrCodeOptions
{
    public function __construct(
        public readonly int $size = config('urlhub.qrcode_size'),
        public readonly int $margin = config('urlhub.qrcode_margin'),
        public readonly string $format = config('urlhub.qrcode_format'),
        public readonly string $errorCorrection = config('urlhub.qrcode_error_correction'),
        public readonly bool $roundBlockSize = config('urlhub.qrcode_round_block_size'),
    ) {
    }
}
