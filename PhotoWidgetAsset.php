<?php
/**
 * @link https://github.com/tigrov/yii2-photo-widget
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\photoWidget;

class PhotoWidgetAsset extends \yii\web\AssetBundle
{
    public $sourcePath = __DIR__ . DIRECTORY_SEPARATOR . 'assets';
    public $js = ['upload-photo.js'];
    public $css = ['upload-photo.css'];
    public $depends = [CropperAsset::class];
}