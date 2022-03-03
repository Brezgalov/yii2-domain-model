<?php

namespace Brezgalov\DomainModel;

interface IRegisterInputInterface
{
    /**
     * @param array $data
     * @return bool
     */
    public function registerInput(array $data = []);
}