<?php

namespace Brezgalov\DomainModel\ResultFormatters;

use yii\base\Component;
use yii\web\Response;

class RedirectFormatter extends Component implements IResultFormatter
{
    /**
     * @var Response
     */
    public $response;

    /**
     * ApiHelpersLibResultFormatter constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        if (empty($this->response) && \Yii::$app->has('response')) {
            $this->response = \Yii::$app->get('response');
        }
    }

    /**
     * @param $model
     * @param $result
     * @return mixed
     * @throws \Exception
     */
    public function format($model, $result)
    {
        if (is_string($result) && $this->response instanceof Response) {
            return $this->response->redirect($result);
        }

        return $result;
    }
}