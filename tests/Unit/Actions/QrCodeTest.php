<?php

namespace Tests\Unit\Actions;

use App\Actions\QrCode;
use Tests\TestCase;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Endroid\QrCode\Matrix\MatrixInterface;

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
    public function minSize()
    {
        $size = 5;
        config(['urlhub.qrcode_size' => $size]);

        $image = imagecreatefromstring((new QrCode)->process('foo')->getString());

        $this->assertNotSame($size, imagesx($image));
        $this->assertSame(QrCode::MIN_SIZE, imagesx($image));
    }
}
