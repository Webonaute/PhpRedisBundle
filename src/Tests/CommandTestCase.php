<?php

namespace WebonautePhpredisBundle\Tests;

/**
 * Base Class for command tests
 *
 * @author Sebastian Göttschkes <sebastian.goettschkes@googlemail.com>
 */
abstract class CommandTestCase extends TestCase
{

    /**
     * SetUp called before each tests, setting up the environment (application, globally used mocks)
     */
    public function setUp()
    {
        parent::setUp();

        $command = $this->getCommand();
        $this->application->add($command);
        $command->setContainer($this->container);
    }

    /**
     * Method used by the implementation of the command test to return the actual command object
     *
     * @return mixed The command to be tested
     */
    abstract protected function getCommand();
}
