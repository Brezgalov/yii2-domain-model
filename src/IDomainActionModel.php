<?php

namespace Brezgalov\DomainModel;

interface IDomainActionModel
{
    /**
     * BasicDomainActionModel constructor.
     * @param IDomainModel $model
     * @param array $config
     */
    public function __construct(IDomainModel $model, $config = []);

    /**
     * Pass input to model
     *
     * @param array $data
     * @return bool|void
     */
    public function registerInput(array $data = []);

    /**
     * @return mixed
     */
    public function run();
}