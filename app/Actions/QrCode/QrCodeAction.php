<?php

namespace App\Actions\QrCode;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Action\Model\QrCodeParams;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;

class QrCodeAction implements MiddlewareInterface
{
    public function __construct(
        private ShortUrlResolverInterface $urlResolver,
        private ShortUrlStringifierInterface $stringifier,
        private LoggerInterface $logger,
        private QrCodeOptions $defaultOptions,
    ) {
    }

    public function process(string $data): ResultInterface
    {
        $params = QrCodeParams::fromRequest($data, $this->defaultOptions);
        $qrCodeBuilder = Builder::create()
            ->data($data)
            ->size($params->size)
            ->margin($params->margin)
            ->writer($params->writer)
            ->errorCorrectionLevel($params->errorCorrectionLevel)
            ->roundBlockSizeMode($params->roundBlockSizeMode);

        return $qrCodeBuilder->build();
    }
}
