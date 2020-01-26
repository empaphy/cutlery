<?php

namespace Cutlery\Mockery\Generator;

use Mockery\Generator\MockConfiguration;
use Mockery\Generator\MockDefinition;
use Mockery\Generator\StringManipulationGenerator;

class ObjectSynchronizerGenerator extends StringManipulationGenerator
{
    /**
     * @var \Mockery\Generator\StringManipulation\Pass\Pass[]
     */
    protected $passes = [];

    /**
     * Creates a new StringManipulationGenerator with the default passes.
     *
     * @return StringManipulationGenerator
     */
    public static function withDefaultPasses()
    {
        // TODO add pass for Invokable objects
        // TODO Remove redundant passes.
        return parent::withDefaultPasses();
    }

    /**
     * @param  \Mockery\Generator\MockConfiguration  $config
     * @return \Mockery\Generator\MockDefinition
     */
    public function generate(MockConfiguration $config)
    {
        $code = file_get_contents(__DIR__ . '/../Mock.php');
        $className = $config->getName() ?: $config->generateName();

        $namedConfig = $config->rename($className);

        foreach ($this->passes as $pass) {
            $code = $pass->apply($code, $namedConfig);
        }

        return new MockDefinition($namedConfig, $code);
    }
}
