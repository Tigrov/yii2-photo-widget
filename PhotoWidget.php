<?php
/**
 * @link https://github.com/tigrov/yii2-photo-widget
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\photoWidget;

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\StringHelper;

class PhotoWidget extends \yii\widgets\InputWidget
{
    const DETAILS = ['x', 'y', 'width', 'height', 'rotate', 'scaleX', 'scaleY'];

    /** @var string default image URL. It will be displayed if no image provided */
    public $defaultUrl;

    /** @var string URL of actual image. If null it will try to get it from model attribute value */
    public $url;

    public $fileOptions = ['class' => 'upload-photo-field'];

    public $wrapperOptions = ['class' => 'upload-photo-wrapper'];

    public $buttonText = 'Choose File';

    public $buttonOptions = ['class' => 'btn btn-primary float-left'];

    public $cancelOptions = ['class' => 'btn btn-danger float-right', 'style' => 'display:none'];

    public $imageOptions = ['class' => 'upload-photo-image img-thumbnail'];

    public $cropperOptions = [
        'viewMode' => 3,
        'dragMode' => 'move',
    ];

    public $width = 200;

    public $height = 200;

    public $ratio;

    protected $mimeType = 'image';

    /** @var array hidden input names of cropping result for x, y, width, height, rotate, scaleX, scaleY */
    public $detailNames = [];

    /** @var array hidden input ids of cropping result for x, y, width, height, rotate, scaleX, scaleY */
    public $detailIds = [];

    public function init()
    {
        parent::init();

        if (!isset($this->field->form->options['enctype'])) {
            $this->field->form->options['enctype'] = 'multipart/form-data';
        }

        if ($this->value === null) {
            $this->value = isset($this->options['value'])
                ? $this->options['value']
                : ($this->hasModel()
                    ? Html::getAttributeValue($this->model, $this->attribute)
                    : null);
        }

        if ($this->ratio === null && $this->width && $this->height) {
            $this->ratio = $this->width / $this->height;
        }

        if ($this->url === null) {
            if ($this->value && !StringHelper::startsWith($this->value, 'data:') && is_readable($this->value)) {
                $this->url = 'data:' . $this->mimeType . ';base64,' . base64_encode(file_get_contents($this->value));
            }
        }

        if (!isset($this->wrapperOptions['id'])) {
            $this->wrapperOptions['id'] = $this->options['id'] . '-wrapper';
        }
        if (!isset($this->imageOptions['id'])) {
            $this->imageOptions['id'] = $this->options['id'] . '-image';
        }
        if (!isset($this->cancelOptions['id'])) {
            $this->cancelOptions['id'] = $this->options['id'] . '-cancel';
        }

        $name = isset($this->options['name']) ? $this->options['name'] : Html::getInputName($this->model, $this->attribute);
        foreach (static::DETAILS as $detail) {
            if (!isset($this->detailNames[$detail])) {
                $this->detailNames[$detail] = $name . '[' . $detail . ']';
            }
            if (!isset($this->detailIds[$detail])) {
                $this->detailIds[$detail] = str_replace(['[]', '][', '[', ']', ' ', '.'], ['', '-', '-', '', '-', '-'], mb_strtolower($this->detailNames[$detail], \Yii::$app->charset));
            }
        }

        PhotoWidgetAsset::register($this->getView());
        if ($this->defaultUrl === null) {
            $this->defaultUrl = \Yii::$app->assetManager->getPublishedUrl((new PhotoWidgetAsset)->sourcePath) . '/photo.svg';
        }
    }

    public function run()
    {
        $this->registerJs();

        $styleWidth = $this->width ? 'width:' . $this->width . 'px;' : '';
        $styleHeight = $this->height ? 'height:' . $this->height . 'px;' : '';
        $style = $styleWidth . $styleHeight;

        $src = $this->url ?: $this->defaultUrl;
        $imageOptions = array_merge(['style' => $style], $this->imageOptions);
        $imageHtml = Html::img($src, $imageOptions);

        $fileOptions = array_merge($this->options, $this->fileOptions);
        $fileHtml = Html::activeFileInput($this->model, $this->attribute, $fileOptions);

        $buttonHtml = Html::label($this->buttonText . ' ' . $fileHtml, $this->options['id'], $this->buttonOptions);
        $cancelHtml = Html::button('&times;', $this->cancelOptions);
        $buttonsHtml = Html::tag('div', $buttonHtml . $cancelHtml, ['class' => 'clearfix', 'style' => $styleWidth]);

        $wrapperOptions = array_merge(['style' => $style], $this->wrapperOptions);
        $out = Html::tag('div', $imageHtml, $wrapperOptions) . $buttonsHtml;

        foreach (static::DETAILS as $detail) {
            $out .= Html::hiddenInput($this->detailNames[$detail], null, ['id' => $this->detailIds[$detail]]);
        }
        return $out;
    }

    public function registerJs()
    {
        $wrapperId = Json::encode($this->wrapperOptions['id']);
        $fieldId = Json::encode($this->options['id']);
        $imageId = Json::encode($this->imageOptions['id']);
        $cancelId = Json::encode($this->cancelOptions['id']);

        $detailIdsJson = Json::encode($this->detailIds, JSON_FORCE_OBJECT);

        $options = $this->cropperOptions;
        if (!isset($options['aspectRatio']) && $this->ratio) {
            $options['aspectRatio'] = $this->ratio;
        }
        if (!isset($options['minContainerWidth']) && $this->width) {
            $options['minContainerWidth'] = $this->width;
        }
        if (!isset($options['minContainerHeight']) && $this->height) {
            $options['minContainerHeight'] = $this->height;
        }

        $optionsJson = Json::encode($options, JSON_FORCE_OBJECT);

        $this->getView()->registerJs("TigrovPhotoWidget($wrapperId, $fieldId, $imageId, $cancelId, $detailIdsJson, $optionsJson);");
    }
}