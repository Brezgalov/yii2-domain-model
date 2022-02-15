<?php

namespace Brezgalov\DomainModel\ResultFormatters;

use Brezgalov\DomainModel\Exceptions\ErrorException;
use yii\base\Component;
use yii\web\Response;

class ModelResultFormatter extends Component
{
    /**
     * @var string
     */
    public $unknownExecutionErrorText = 'Unknown error occurred';

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
     * @return array[]|object|Model|Response|null
     * @throws \Exception
     */
    public function format($model, $result)
    {
        if ($result instanceof ErrorException && $this->response) {
            $response = clone $this->response;

            $response->data = $result->error;
            $response->setStatusCode($result->statusCode);

            return $response;
        }

        if ($result instanceof \Exception) {
            throw $result;
        }

        if ($result === false) {
            $errorModel = $model;

            if ($errorModel instanceof Model) {
                if (!$model->hasErrors()) {
                    $model->addError(static::class, $this->unknownExecutionErrorText);
                }
            } else {
                $errorModel = new Model();
                $model->addError(static::class, $this->unknownExecutionErrorText);
            }

            return $errorModel;
        }

        return $result;
    }
}