<?php

namespace Brezgalov\DomainModel\ResultFormatters;

use Brezgalov\DomainModel\IDomainModel;
use yii\base\Component;
use yii\web\Response;

class RedirectFormatter extends Component implements IResultFormatter
{
    /**
     * @var Response
     */
    public $response;

    /**
     * @var string
     */
    public $redirectUrl;

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
     * @param IDomainModel $model
     * @param $result
     * @return mixed
     * @throws \Exception
     */
    public function format($model, $result)
    {
        if ($this->redirectUrl && $result && $model->isValid()) {
            return $this->response->redirect($this->redirectUrl);
        }

        if (is_string($result) && $this->response instanceof Response) {
            return $this->response->redirect($result);
        }

        return $result;
    }
}