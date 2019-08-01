<?php
/**
 * @link https://github.com/tigrov/yii2-photo-widget
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\photoWidget;

use yii\imagine\Image;

class PhotoWidgetHelper
{
    /**
     * Crop, resize and save image
     * @param \yii\base\Model $model
     * @param string $attribute
     * @param \yii\web\UploadedFile $file
     * @param string $filename
     * @param int|null $width
     * @param int|null $height
     * @return bool
     */
    public static function crop($model, $attribute, $file, $filename, $width = null, $height = null)
    {
        if ($data = \Yii::$app->getRequest()->post($model->formName())) {
            $data = $data[$attribute];
            $image = Image::crop($file->tempName, $data['width'], $data['height'], [$data['x'], $data['y']]);
            if ($width !== null || $height !== null) {
                $size = $image->getSize();
                if ($width === null) {
                    $width = $height * $size->getWidth() / $size->getHeight();
                } elseif ($height === null) {
                    $height = $width * $size->getHeight() / $size->getWidth();
                }
                $image = Image::resize($image, $width, $height, true, true);
            }
            $image->save($filename);

            return true;
        }

        return false;
    }
}