yii2-photo-widget
=================

Upload photo widget for Yii2.

[![Latest Stable Version](https://poser.pugx.org/Tigrov/yii2-photo-widget/v/stable)](https://packagist.org/packages/Tigrov/yii2-photo-widget)

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist tigrov/yii2-photo-widget
```

or add

```
"tigrov/yii2-photo-widget": "~1.0"
```

to the require section of your `composer.json` file.

	
Usage
-----

It is better to use the extension with [yii2-upload-behavior](https://github.com/Tigrov/yii2-upload-behavior)

Once the extension is installed, you can use it as follow:

Create a model with a photo attribute
```php
class Model extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'upload' => [
                 'class' => '\tigrov\uploadBehavior\UploadBehavior',
                 'path' => '@runtime/upload',
                 'attributes' => ['photo'],
                 'saveCallback' => ['\tigrov\photoWidget\PhotoWidget', 'crop'],
            ],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['photo'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png,jpg,jpeg'],
        ];
    }
}
```

Create an action in a controller
```php
class FormController extends \yii\web\Controller
{
    public function actionUpload()
    {
        $model = new Model();
        if ($model->load(\Yii::$app->request->post()) && $model->save()) {
            \Yii::$app->session->setFlash('success', 'Model is saved.');
            return $this->refresh();
        }

        return $this->render('form', [
            'model' => $model,
        ]);
    }
}
```

Create a form with the file attribute
```
<?php $form = ActiveForm::begin(); ?>
    <?= $form->field($model, 'photo')->widget('\tigrov\photoWidget\PhotoWidget') ?>
    <?= Html::submitButton('Submit') ?>
<?php $form::end(); ?>
```

![drawing](photo-widget.png)

After submitting the photo it will be saved to specified `path`.

License
-------

[MIT](LICENSE)
