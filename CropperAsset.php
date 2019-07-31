<?php
/**
 * @link https://github.com/tigrov/yii2-photo-widget
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\photoWidget;

class CropperAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@bower/cropper/dist';
    public $js = ['cropper.min.js'];
    public $css = ['cropper.min.css'];
    public $depends = ['yii\web\JqueryAsset'];
}