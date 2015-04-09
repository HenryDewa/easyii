<?php

namespace yii\easyii\modules\catalog\models;

use Yii;
use yii\behaviors\SluggableBehavior;
use yii\easyii\behaviors\SeoBehavior;
use yii\easyii\behaviors\SortableModel;
use yii\easyii\models\Photo;
use yii\easyii\components\ActiveRecord;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;

class Item extends \yii\easyii\components\ActiveRecord {

    public static function tableName() {
        return 'easyii_catalog_items';
    }

    public function rules() {
        return [
            ['title', 'required'],
            ['title', 'trim'],
            ['title', 'string', 'max' => 128],
            ['thumb', 'image'],
            ['description', 'safe'],
            ['slug', 'match', 'pattern' => self::$SLUG_PATTERN, 'message' => Yii::t('easyii', 'Slug can contain only 0-9, a-z and "-" characters (max: 128).')],
            ['slug', 'default', 'value' => null],
            [['created_at', 'updated_at'], 'safe'],
            [['created_by', 'updated_by'], 'integer'],
        ];
    }

    public function attributeLabels() {
        return [
            'title' => Yii::t('easyii/catalog', 'Title'),
            'thumb' => Yii::t('easyii', 'Image'),
            'description' => Yii::t('easyii', 'Description'),
            'slug' => Yii::t('easyii', 'Slug'),
            'created_at' => Yii::t('easyii', 'Created At'),
            'updated_at' => Yii::t('easyii', 'Updated At'),
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
            'timestamp' => [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            if (!$this->data || (!is_object($this->data) && !is_array($this->data))) {
                $this->data = new \stdClass();
            }
            $this->data = json_encode($this->data);
            return true;
        } else {
            return false;
        }
    }

    public function afterFind() {
        parent::afterFind();
        $this->data = $this->data !== '' ? json_decode($this->data) : [];
    }

    public function getPhotos() {
        return $this->hasMany(Photo::className(), ['item_id' => 'item_id'])->where(['model' => Item::className()])->sort();
    }

    public function getCategory() {
        return $this->hasOne(Category::className(), ['category_id' => 'category_id']);
    }

    public function afterDelete() {
        parent::afterDelete();

        foreach ($this->getPhotos()->all() as $photo) {
            $photo->delete();
        }

        if ($this->thumb) {
            @unlink(Yii::getAlias('@webroot') . $this->thumb);
        }
    }

}
