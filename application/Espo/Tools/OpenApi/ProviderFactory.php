<?php

namespace Espo\Tools\OpenApi;

use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\InjectableFactory;

class ProviderFactory
{
    public function __construct(
        private InjectableFactory $injectableFactory,
    ) {}

    public function create(): Provider
    {
        return $this->injectableFactory->createWithBinding(
            Provider::class,
            BindingContainerBuilder::create()
                ->build()
        );
    }
}
