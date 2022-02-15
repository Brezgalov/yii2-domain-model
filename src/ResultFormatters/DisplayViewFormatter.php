<?php

namespace Brezgalov\DomainModel\ResultFormatters;

use yii\base\Component;
use yii\base\ViewContextInterface;
use yii\helpers\ArrayHelper;

class DisplayViewFormatter extends Component implements IResultFormatter
{
    const MODE_DEFAULT = 'renderDefault';
    const MODE_AJAX = 'renderAjax';
    const MODE_FILE = 'renderFile';

    /**
     * @var string
     */
    public $view;

    /**
     * @var string
     */
    public $mode = self::MODE_DEFAULT;

    /**
     * @var ViewContextInterface
     */
    public $viewContext;

    /**
     * @return mixed
     * @throws \Exception
     */
    protected function pickRenderMethod()
    {
        return ArrayHelper::getValue([
            static::MODE_FILE => 'renderFile',
            static::MODE_AJAX => 'renderAjax',
        ], $this->mode, 'render');
    }

    /**
     * @return object|ViewContextInterface
     * @throws \yii\base\InvalidConfigException
     */
    protected function pickViewContext()
    {
        return $this->viewContext instanceof ViewContextInterface ? (
            $this->viewContext
        ) : (
            \Yii::createObject($this->viewContext)
        );
    }

    /**
     * @param $model
     * @param $result
     * @return mixed|string
     * @throws \yii\base\InvalidConfigException
     */
    public function format($model, $result)
    {
        $params = [
            'model' => $model,
            'data' => $result,
        ];

        $context = $this->pickViewContext();
        $method = $this->pickRenderMethod();

        return call_user_func([\Yii::$app->view, $method], $this->view, $params, $context);
    }
}