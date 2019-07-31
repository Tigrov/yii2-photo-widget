<?php
/**
 * @link https://github.com/tigrov/yii2-photo-widget
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\photoWidget;

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\StringHelper;
use yii\imagine\Image;

class PhotoWidget extends \yii\widgets\InputWidget
{
    const DETAILS = ['x', 'y', 'width', 'height', 'rotate', 'scaleX', 'scaleY'];

    /** @var string empty 1 pixel by default */
    public $defaultImage = 'data:image/png;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';

    public $url;

    public $fileOptions = ['class' => 'upload-photo-field'];

    public $wrapperOptions = ['class' => 'upload-photo-wrapper'];

    public $buttonText = 'Choose File';

    public $buttonOptions = ['class' => 'btn btn-primary float-left'];

    public $cancelOptions = ['class' => 'btn btn-danger float-right', 'style' => 'display:none'];

    public $imageOptions = [
        'class' => 'upload-photo-image img-thumbnail',
        'width' => 200,
        'height' => 200
    ];

    public $cropperOptions = [
        'viewMode' => 3,
        'dragMode' => 'move',
    ];

    public $ratio;

    protected $mimeType = 'image';

    private $width;

    private $height;

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
            $this->value = $this->options['value']
                ?? ($this->hasModel()
                    ? Html::getAttributeValue($this->model, $this->attribute)
                    : null);
        }

        if ($this->value && !StringHelper::startsWith($this->value, 'data:') && is_readable($this->value)) {
            list($this->width, $this->height, $this->mimeType) = getimagesize($this->value);
        }

        $this->width = $this->imageOptions['width'] ?? $this->width;
        $this->height = $this->imageOptions['height'] ?? $this->height;

        if (!$this->ratio && $this->width && $this->height) {
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
    }

    public function run()
    {
        PhotoWidgetAsset::register($this->getView());

        $this->registerJs();

        $src = $this->url ?: $this->defaultImage;
        $imageHtml = Html::img($src, $this->imageOptions);
        $id = $this->options['id'];
        $fileOptions = array_merge($this->options, $this->fileOptions);
        $styleWidth = $this->width ? 'width:' . $this->width . 'px;' : '';
        $styleHeight = $this->height ? 'height:' . $this->height . 'px;' : '';
        $style = $styleWidth . $styleHeight;
        $wrapperOptions = array_merge(['style' => $style], $this->wrapperOptions);

        $fileHtml = Html::activeFileInput($this->model, $this->attribute, $fileOptions);
        $buttonHtml = Html::label($this->buttonText . ' ' . $fileHtml, $id, $this->buttonOptions);
        $cancelHtml = Html::button('&times;', $this->cancelOptions);
        $buttonsHtml = Html::tag('div', $buttonHtml . $cancelHtml, ['class' => 'clearfix', 'style' => $styleWidth]);

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

    /**
     * Crop and save image
     * @param \yii\base\Model $model
     * @param string $attribute
     * @param \yii\web\UploadedFile $file
     * @param string $filename
     * @return bool
     */
    public static function crop($model, $attribute, $file, $filename)
    {
        if ($data = \Yii::$app->getRequest()->post($model->formName())) {
            $data = $data[$attribute];
            $image = Image::crop($file->tempName, $data['width'], $data['height'], [$data['x'], $data['y']]);
            $image->save($filename);

            return true;
        }

        return false;
    }
}