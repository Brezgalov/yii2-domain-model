<?php

namespace example\dao\repositories;

use example\dao\models\SmsCodesDao;
use Brezgalov\DomainModel\DAO\BaseDaoRepository;
use yii\db\ActiveQuery;

class SmsCodesDaoRepository extends BaseDaoRepository
{
    /**
     * ActiveRecord class
     * @var string
     */
    public $daoClass = SmsCodesDao::class;

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $code;

    /**
     * @var string
     */
    public $for_phone;

    /**
     * @var bool
     */
    public $expired = false;

    /**
     * @var int
     */
    public $created_after;

    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            [['id', 'code', 'expire_at'], 'safe'],
            [['created_after'], 'integer'],
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getQuery(): ActiveQuery
    {
        $query = parent::getQuery();

        $queryAlias = $this->getQueryAlias();

        $query->andFilterWhere(["{$queryAlias}.id" => $this->id]);
        $query->andFilterWhere(["{$queryAlias}.code" => $this->code]);
        $query->andFilterWhere(["{$queryAlias}.for_phone" => $this->for_phone]);

        if ($this->created_after) {
            $query->andWhere(['>', "{$queryAlias}.created_at", date('Y-m-d H:i:s', $this->created_after)]);
        }

        if (!is_null($this->expired)) {
            if ($this->expired) {
                $query->andWhere(['<=', "{$queryAlias}.expire_at", date('Y-m-d H:i:s')]);
            } else {
                $query->andWhere(['>', "{$queryAlias}.expire_at", date('Y-m-d H:i:s')]);
            }
        }

        return $query;
    }
}
