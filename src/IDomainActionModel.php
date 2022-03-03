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
     * @return mixed
     */
    public function run();
}