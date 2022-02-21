<?php

namespace example\dao\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "sms_codes".
 *
 * @property int $id
 * @property string $namespace
 * @property string $for_phone
 * @property string $code
 * @property string $expire_at
 * @property string|null $created_at
 */
class SmsCodesDao extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sms_codes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['for_phone', 'code', 'expire_at'], 'required'],
            [['expire_at', 'created_at'], 'safe'],
            [['namespace', 'for_phone', 'code'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'namespace' => 'Namespace',
            'for_phone' => 'For Phone',
            'code' => 'Code',
            'expire_at' => 'Expire At',
            'created_at' => 'Created At',
        ];
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                ],
                'value' => function() {
                    return date('Y-m-d H:i:s');
                }
            ],
        ]);
    }
}
