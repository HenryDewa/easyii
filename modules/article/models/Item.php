<?php

namespace yii\easyii\modules\article\models;

use Yii;
use yii\behaviors\SluggableBehavior;
use yii\easyii\behaviors\SeoBehavior;
use yii\easyii\behaviors\SortableModel;
use yii\helpers\StringHelper;
use yii\behaviors\BlameableBehavior;

class Item extends \yii\easyii\components\ActiveRecord {

    const STATUS_OFF = 0;
    const STATUS_ON = 1;

    public static function tableName() {
        return 'easyii_article_items';
    }

    public function rules() {
        return [
            [['text', 'title'], 'required'],
            [['title', 'short', 'text'], 'trim'],
            ['title', 'string', 'max' => 128],
            ['thumb', 'image'],
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
            'short' => Yii::t('easyii/article', 'Short'),
            'thumb' => Yii::t('easyii', 'Image'),
            'slug' => Yii::t('easyii', 'Slug'),
            'created_by' => Yii::t('easyii', 'Created By'),
            'updated_by' => Yii::t('easyii', 'Updated By'),
        ];
    }

    public function behaviors() {
        return [
            SortableModel::className(),
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

    public function getCategory() {
        return $this->hasOne(Category::className(), ['category_id' => 'category_id']);
    }

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            $settings = Yii::$app->getModule('admin')->activeModules['article']->settings;
            if ($this->short && $settings['enableShort']) {
                $this->short = StringHelper::truncate($this->short, $settings['shortMaxLength']);
            }

            if (!$this->isNewRecord && $this->thumb != $this->oldAttributes['thumb']) {
                @unlink(Yii::getAlias('@webroot') . $this->oldAttributes['thumb']);
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
