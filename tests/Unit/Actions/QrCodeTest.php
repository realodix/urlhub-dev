<?php

namespace Tests\Unit\Actions;

use App\Actions\QrCode;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Tests\TestCase;

class QrCodeTest extends TestCase
{
    protected object $qrCode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->qrCode = (new QrCode)->process('foo');
    }

    /**
     * @test
     * @group u-actions
     */
    public function qrCode()
    {
        $this->assertInstanceOf(ResultInterface::class, $this->qrCode);
    }

    /**
     * @test
     * @group u-actions
     */
    public function getDataUri()
    {
        $this->assertIsString(ResultInterface::class, $this->qrCode->getDataUri());
    }

    /**
     * @test
     * @group u-actions
     */
    public function sizeMin()
    {
        $size = QrCode::MIN_SIZE - 1;
        config(['urlhub.qrcode_size' => $size]);

        $image = imagecreatefromstring((new QrCode)->process('foo')->getString());

        $this->assertNotSame($size, imagesx($image));
        $this->assertSame(QrCode::MIN_SIZE, imagesx($image));
    }

    /**
     * @test
     * @group u-actions
     */
    public function sizeMax()
    {
        $size = QrCode::MAX_SIZE + 1;
        config(['urlhub.qrcode_size' => $size]);

        $image = imagecreatefromstring((new QrCode)->process('foo')->getString());

        $this->assertNotSame($size, imagesx($image));
        $this->assertSame(QrCode::MAX_SIZE, imagesx($image));
    }
}
