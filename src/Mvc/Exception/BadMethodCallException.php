<?php
/**
 * Gz3Base - Zend Framework Base Tweaks / Zend Framework Basis Anpassungen
 * @package Gz3Base\Model
 * @author Andreas Gerhards <geolysis@zoho.com>
 * @copyright ©2016, Andreas Gerhards - All rights reserved
 * @license http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause - Please view LICENSE.md for more information
 */

declare(strict_types = 1);
namespace Gz3Base\Mvc\Exception;

use Gz3Base\Record\RecordableInterface;
use Gz3Base\Record\RecordableTrait;
use Zend\Mvc\Exception\BadMethodCallException as ZendBadMethodCallException;


class BadMethodCallException extends ZendBadMethodCallException implements RecordableInterface
{
    use RecordableTrait;

    /**
     * {inheritDoc}
     * @see \Gz3Base\Record\RecordableTrait::useInitialiseRecording()
     */
    protected function useInitialiseRecording()
    {
        return false;
    }

    /**
     * {inheritDoc}
     * @see \Gz3Base\Record\RecordableTrait::useDeinitialiseRecording()
     */
    protected function useDeinitialiseRecording()
    {
        return false;
    }

}