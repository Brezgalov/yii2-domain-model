<?php

namespace Brezgalov\DomainModel\ResultFormatters;

use Brezgalov\DomainModel\IDomainModel;
use yii\base\Component;
use yii\base\ViewContextInterface;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;

/**
 * Class DisplayViewFormatter позволяет форматировать ответ
 * в html страницу
 *
 * @package Brezgalov\DomainModel\ResultFormatters
 */
class DisplayViewFormatter extends Component implements IResultFormatter
{
    const MODE_DEFAULT = 'renderDefault';
    const MODE_AJAX = 'renderAjax';
    const MODE_FILE = 'renderFile';

    /**
     * @var string
     */
    public $title;

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
     * @throws InvalidConfigException
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
     * @param IDomainModel $model
     * @param mixed $result
     * @return array
     */
    protected function prepareParams($model, $result)
    {
        return [
            'model' => $model,
            'data' => $result,
        ];
    }

    /**
     * @param IDomainModel $model
     * @param mixed $result
     * @return mixed|string
     * @throws InvalidConfigException
     */
    public function format($model, $result)
    {
        if (empty($this->view)) {
            throw new InvalidConfigException("View is required");
        }

        if ($this->title) {
            \Yii::$app->view->title = $this->title;
        }

        $params = $this->prepareParams($model, $result);

        $context = $this->pickViewContext();
        $method = $this->pickRenderMethod();

        return call_user_func([\Yii::$app->view, $method], $this->view, $params, $context);
    }
}