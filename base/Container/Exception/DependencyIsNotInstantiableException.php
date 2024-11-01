<?php

namespace Eventin\Container\Exception;

use Exception;
use Eventin\Container\ContainerExceptionInterface;

class DependencyIsNotInstantiableException extends Exception implements ContainerExceptionInterface {
}
