<?php

namespace Eventin\Container\Exception;

use Exception;
use Eventin\Container\NotFoundExceptionInterface;

class DependencyHasNoDefaultValueException extends Exception implements NotFoundExceptionInterface {
}
