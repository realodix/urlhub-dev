<?php

namespace App\Actions\QrCode;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer;
use Endroid\QrCode\Writer\Result\ResultInterface;

class QrCodeAction
{
    private const MIN_SIZE = 50;

    private const MAX_SIZE = 1000;

    private const FORMAT = 'png';

    private const SUPPORTED_FORMATS = ['png', 'svg'];

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

    private static function resolveSize(): int
    {
        $size = config('urlhub.qrcode_size');

        if ($size < self::MIN_SIZE) {
            return self::MIN_SIZE;
        }

        return $size > self::MAX_SIZE ? self::MAX_SIZE : $size;
    }

    private static function resolveMargin(): int
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
    private static function resolveWriter()
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
    private static function resolveErrorCorrection()
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
    private static function resolveRoundBlockSize()
    {
        $isRounded = config('urlhub.qrcode_round_block_size');
        $marginMode = new RoundBlockSizeMode\RoundBlockSizeModeMargin;
        $noneMode = new RoundBlockSizeMode\RoundBlockSizeModeNone;

        if ($isRounded) {
            return $marginMode;
        }

        return $noneMode;
    }

    private static function normalizeValue(string $param): string
    {
        return strtolower(trim($param));
    }
}
