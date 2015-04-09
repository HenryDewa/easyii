<?php

namespace yii\easyii\modules\news\models;

use Yii;
use yii\behaviors\SluggableBehavior;
use yii\easyii\behaviors\SeoBehavior;
use yii\easyii\components\ActiveRecord;
use yii\behaviors\BlameableBehavior;
use yii\helpers\StringHelper;

class News extends ActiveRecord {

    const STATUS_OFF = 0;
    const STATUS_ON = 1;

    public static function tableName() {
        return 'easyii_news';
    }

    public function rules() {
        return [
            [['text', 'title'], 'required'],
            [['title', 'short', 'text'], 'trim'],
            ['title', 'string', 'max' => 128],
            ['thumb', 'image'],
            ['time', 'default', 'value' => time()],
            ['views', 'number', 'integerOnly' => true],
            ['slug', 'match', 'pattern' => self::$SLUG_PATTERN, 'message' => Yii::t('easyii', 'Slug can contain only 0-9, a-z and "-" characters (max: 128).')],
            ['slug', 'default', 'value' => null],
            [['created_by', 'updated_by'], 'integer'],
        ];
    }

    public function attributeLabels() {
        return [
            'title' => Yii::t('easyii', 'Title'),
            'text' => Yii::t('easyii', 'Text'),
            'short' => Yii::t('easyii/news', 'Short'),
            'thumb' => Yii::t('easyii', 'Image'),
            'slug' => Yii::t('easyii', 'Slug'),
            'created_by' => Yii::t('easyii', 'Created By'),
            'updated_by' => Yii::t('easyii', 'Updated By'),
        ];
    }

    public function behaviors() {
        return [
            'seo' => SeoBehavior::className(),
            'sluggable' => [
                'class' => SluggableBehavior::className(),
                'attribute' => 'title',
                'ensureUnique' => true
            ],
            'blameable' => [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ],
        ];
    }

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            $settings = Yii::$app->getModule('admin')->activeModules['news']->settings;
            if ($this->short && $settings['enableShort']) {
                $this->short = StringHelper::truncate($this->short, $settings['shortMaxLength']);
            }

            return true;
        } else {
            return false;
        }
    }

    public function afterDelete() {
        parent::afterDelete();

        if ($this->thumb) {
            @unlink(Yii::getAlias('@webroot') . $this->thumb);
        }
    }

}
