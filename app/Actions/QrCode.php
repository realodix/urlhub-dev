<?php

namespace App\Actions;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer;
use Endroid\QrCode\Writer\Result\ResultInterface;

class QrCode
{
    const MIN_SIZE = 50;

    const MAX_SIZE = 1000;

    const FORMAT = 'png';

    const SUPPORTED_FORMATS = ['png', 'svg'];

    public function process(string $data): ResultInterface
    {
        return Builder::create()
            ->data($data)
            ->labelText('Scan QR Code')
            ->size($this->resolveSize())
            ->margin($this->resolveMargin())
            ->writer($this->resolveWriter())
            ->errorCorrectionLevel($this->resolveErrorCorrection())
            ->roundBlockSizeMode($this->resolveRoundBlockSize())
            ->build();
    }

    private function resolveSize(): int
    {
        $size = config('urlhub.qrcode_size');

        if ($size < self::MIN_SIZE) {
            return self::MIN_SIZE;
        }

        return $size > self::MAX_SIZE ? self::MAX_SIZE : $size;
    }

    private function resolveMargin(): int
    {
        $margin = config('urlhub.qrcode_margin');
        $intMargin = (int) $margin;

        if ($margin !== (string) $intMargin) {
            return 0;
        }

        return $intMargin < 0 ? 0 : $intMargin;
    }

    /**
     * @return \Endroid\QrCode\Writer\WriterInterface
     */
    private function resolveWriter()
    {
        $qFormat = self::normalizeValue(config('urlhub.qrcode_format'));
        $format = collect(self::SUPPORTED_FORMATS)->containsStrict($qFormat)
            ? $qFormat : self::FORMAT;

        return match ($format) {
            'svg' => new Writer\SvgWriter,
            default => new Writer\PngWriter,
        };
    }

    /**
     * @return ErrorCorrectionLevel\ErrorCorrectionLevelInterface
     */
    private function resolveErrorCorrection()
    {
        $errorCorrectionLevel = self::normalizeValue(config('urlhub.qrcode_error_correction'));

        return match ($errorCorrectionLevel) {
            'h' => new ErrorCorrectionLevel\ErrorCorrectionLevelHigh,
            'q' => new ErrorCorrectionLevel\ErrorCorrectionLevelQuartile,
            'm' => new ErrorCorrectionLevel\ErrorCorrectionLevelMedium,
            default => new ErrorCorrectionLevel\ErrorCorrectionLevelLow, // 'l'
        };
    }

    /**
     * @return \Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeInterface
     */
    private function resolveRoundBlockSize()
    {
        $isRounded = config('urlhub.qrcode_round_block_size');
        $marginMode = new RoundBlockSizeMode\RoundBlockSizeModeMargin;
        $noneMode = new RoundBlockSizeMode\RoundBlockSizeModeNone;

        if ($isRounded) {
            return $marginMode;
        }

        return $noneMode;
    }

    private function normalizeValue(string $param): string
    {
        return strtolower(trim($param));
    }
}
