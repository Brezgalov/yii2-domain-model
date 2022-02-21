<?php

namespace example\dao\repositories;

use example\helpers\PhoneHelper;
use example\models\Users;
use Brezgalov\DomainModel\DAO\BaseDaoRepository;
use yii\db\ActiveQuery;

class UsersDaoRepository extends BaseDaoRepository
{
    /**
     * @var string
     */
    public $daoClass = Users::class;

    /**
     * @var integer
     */
    public $id;

    /**
     * @var integer
     */
    public $unload_id;

    /**
     * @var bool
     */
    public $show_deleted = false;

    /**
     * @var string
     */
    public $phone;

    /**
     * @var string
     */
    public $phone_confirmed;

    /**
     * @var string
     */
    public $search;

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [['id'], 'integer'],
            [['unload_id', 'show_deleted', 'search', 'phone', 'phone_confirmed'], 'safe'],
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getQuery(): ActiveQuery
    {
        $queryAlias = $this->getQueryAlias();
        $query = parent::getQuery()->joinWith("stevedoreUnloads");

        if (!$this->show_deleted) {
            $query->andWhere(["{$queryAlias}.deleted_at" => null]);
        }

        $query->andFilterWhere(["{$queryAlias}.id" => $this->id]);
        $query->andFilterWhere(["stevedore_unloads.id" => $this->unload_id]);

        if ($this->phone) {
            $query->andWhere(["{$queryAlias}.phone" => PhoneHelper::clearPhone($this->phone)]);
        }

        if (!is_null($this->phone_confirmed)) {
            if ($this->phone_confirmed) {
                $query->andWhere(["not", ["{$queryAlias}.phone_confirmed_mark" => null]]);
            } else {
                $query->andWhere(["{$queryAlias}.phone_confirmed_mark" => null]);
            }
        }

        if ($this->search) {
            $query->andFilterWhere([
                "or",
                ["like", "{$queryAlias}.login", $this->search],
                ["like", "{$queryAlias}.email", $this->search],
                ["like", "{$queryAlias}.phone", $this->search],
                ["like", "{$queryAlias}.name", $this->search],
                ["like", "{$queryAlias}.first_name", $this->search],
                ["like", "{$queryAlias}.last_name", $this->search],
            ]);
        }

        return $query;
    }
}
