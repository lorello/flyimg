<?php

namespace Core\Processor;

use Core\Entity\Command;
use Core\Entity\Image\OutputImage;

/**
 * Class FaceDetectProcessor
 * @package Core\Processor
 */
class FaceDetectProcessor extends Processor
{
    /**
     * Face detection cropping
     *
     * @param OutputImage $outputImage
     * @param int        $faceCropPosition
     *
     * @throws \Exception
     */
    public function cropFaces(OutputImage $outputImage, int $faceCropPosition = 0)
    {
        if (!is_executable(self::FACEDETECT_COMMAND)) {
            return;
        }
        $faceDetectCmd = new Command(self::FACEDETECT_COMMAND);
        $faceDetectCmd->addArgument($outputImage->getOutputImagePath());
        $output = $this->execute($faceDetectCmd);
        if (empty($output[$faceCropPosition])) {
            return;
        }
        $geometry = explode(" ", $output[$faceCropPosition]);
        if (count($geometry) == 4) {
            [$geometryX, $geometryY, $geometryW, $geometryH] = $geometry;
            $cropCmd = new Command(self::IM_CONVERT_COMMAND);
            $cropCmd->addArgument($outputImage->getOutputImagePath());
            $cropCmd->addArgument("-crop", "{$geometryW}x{$geometryH}+{$geometryX}+{$geometryY}");
            $cropCmd->addArgument($outputImage->getOutputImagePath());
            $this->execute($cropCmd);
        }
    }

    /**
     * Blurring Faces
     *
     * @param OutputImage $outputImage
     *
     * @throws \Exception
     */
    public function blurFaces(OutputImage $outputImage)
    {
        if (!is_executable(self::FACEDETECT_COMMAND)) {
            return;
        }
        $faceDetectCmd = new Command(self::FACEDETECT_COMMAND);
        $faceDetectCmd->addArgument($outputImage->getOutputImagePath());
        $output = $this->execute($faceDetectCmd);
        if (empty($output)) {
            return;
        }
        foreach ((array)$output as $outputLine) {
            $geometry = explode(" ", $outputLine);
            if (count($geometry) == 4) {
                [$geometryX, $geometryY, $geometryW, $geometryH] = $geometry;

                $blurCmd = new Command(self::IM_MOGRIFY_COMMAND);
                $blurCmd->addArgument("-region", "{$geometryW}x{$geometryH}+{$geometryX}+{$geometryY}");
                $blurCmd->addArgument("-blur", "0x12");
                $blurCmd->addArgument($outputImage->getOutputImagePath());
                $this->execute($blurCmd);
            }
        }
    }
}
