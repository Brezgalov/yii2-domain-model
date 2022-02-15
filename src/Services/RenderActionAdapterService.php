<?php

namespace Brezgalov\DomainModel\Services;

class RenderActionAdapterService extends ActionAdapterService
{
    /**
     * Применяет layout к результату сервиса
     * Актуально для подключения действий выводящих html
     * или в сочетании с DisplayViewFormatter
     *
     * @return \Exception|false|mixed|string|void
     * @throws \Exception
     */
    public function handleAction()
    {
        $res = parent::handleAction();

        if (is_string($res)) {
            return $this->controller->renderContent($res);
        } elseif ($res instanceof \Exception) {
            throw $res;
        }

        return $res;
    }
}