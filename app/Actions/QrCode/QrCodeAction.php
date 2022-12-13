<?php

namespace App\Actions\QrCode;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelQuartile;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeNone;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Endroid\QrCode\Writer\SvgWriter;
use function Functional\contains;
use Psr\Http\Server\MiddlewareInterface;
use Shlinkio\Shlink\Core\Action\Model\QrCodeParams;

class QrCodeAction implements MiddlewareInterface
{
    private const MIN_SIZE = 50;

    private const MAX_SIZE = 1000;

    private const SUPPORTED_FORMATS = ['png', 'svg'];

    public function process(string $data): ResultInterface
    {
        $params = QrCodeParams::fromRequest($data, $this->defaultOptions);
        $qrCodeBuilder = Builder::create()
            ->data($data)
            ->size($this->resolveSize())
            ->margin($this->resolveMargin())
            ->writer($params->writer)
            ->errorCorrectionLevel($this->resolveErrorCorrection())
            ->roundBlockSizeMode($params->roundBlockSizeMode);

        return $qrCodeBuilder->build();
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
    private static function resolveWriter(array $query, QrCodeOptions $defaults)
    {
        $qFormat = self::normalizeParam(config('urlhub.qrcode_format'));
        $format = contains(self::SUPPORTED_FORMATS, $qFormat) ? $qFormat : self::normalizeParam($defaults->format);

        return match ($format) {
            'svg' => new SvgWriter,
            default => new PngWriter,
        };
    }

    /**
     * @return \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelInterface
     */
    private static function resolveErrorCorrection()
    {
        $errorCorrectionLevel = self::normalizeParam(config('urlhub.qrcode_error_correction'));

        return match ($errorCorrectionLevel) {
            'h' => new ErrorCorrectionLevelHigh,
            'q' => new ErrorCorrectionLevelQuartile,
            'm' => new ErrorCorrectionLevelMedium,
            default => new ErrorCorrectionLevelLow, // 'l'
        };
    }

    /**
     * @return \Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeInterface
     */
    private static function resolveRoundBlockSize(array $query, QrCodeOptions $defaults)
    {
        config('urlhub.qrcode_round_block_size');
        $doNotRoundBlockSize = isset($query['roundBlockSize'])
            ? $query['roundBlockSize'] === 'false'
            : ! $defaults->roundBlockSize;

        return $doNotRoundBlockSize ? new RoundBlockSizeModeNone : new RoundBlockSizeModeMargin;
    }

    private static function normalizeParam(string $param): string
    {
        return strtolower(trim($param));
    }
}
