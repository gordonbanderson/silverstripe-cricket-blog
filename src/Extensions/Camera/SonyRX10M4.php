<?php
namespace Suilven\CricketSite\Extensions\Camera;

use SilverStripe\Core\Extension;

class SonyRX10M4 extends Extension {
    public function augmentPhotographWithExif($flickrPhoto, $exifs) {
        if (isset($exifs['DigitalZoomRatio'])) {
            $exif = $exifs['DigitalZoomRatio'];
            $zoomRatio = $exifs['DigitalZoomRatio']->Raw;
            $flickrPhoto->DigitalZoomRatio = $zoomRatio;
            $focalLength = ($flickrPhoto->FocalLength35mm) * (float) $zoomRatio;
        }

    }
}
