<?php
/**
 * Gz3Base - Zend Framework Base Tweaks / Zend Framework Basis Anpassungen
 * @package Gz3Base\Controller
 * @author Andreas Gerhards <geolysis@zoho.com>
 * @copyright ©2016, Andreas Gerhards - All rights reserved
 * @license http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause - Please view LICENSE.md for more information
 */

declare(strict_types = 1);
namespace Gz3Base\Mvc\Controller;

use Gz3Base\Mvc\Exception\ActionException;
use Gz3Base\Mvc\Exception\BadMethodCallException;
use Gz3Base\Mvc\Service\ServiceInterface;
use Gz3Base\Mvc\Service\ConfigService;
use Gz3Base\Record\RecordableInterface;
use Gz3Base\Record\RecordableTrait;
use Gz3Base\Record\Service\RecordService;

use Zend\Mvc\Controller\AbstractActionController as ZendAbstractActionController;
use Zend\ServiceManager\ServiceLocatorInterface;
use Gz3Base\Mvc\Entity\AbstractEntity;
use Gz3Base\Mvc\Service\AbstractService;
use Gz3Base\Mvc\Entity\NoopEntity;


abstract class AbstractActionController extends ZendAbstractActionController implements RecordableInterface
{
    use RecordableTrait;

    const INIT_RECORDING = true;
    const DEINIT_RECORDING = true;

    /** @var ServiceLocatorInterface $this->serviceLocator */
    protected $serviceLocator;
    /** @var array $this->routeParameters */
    protected $routeParameters = null;
    /** @var \ReflectionClass $reflectionClass */
    /** @var string $this->recordIdPrefix */
    /** @var array $this->methodName */
    /** @var array $methodStart */


    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @return BaseController $this
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator) : AbstractActionController
    {
        $this->serviceLocator = $serviceLocator;
        return $this;
    }

    /**
     * @return ServiceLocatorInterface $this->serviceLocator
     */
    protected function getServiceLocator() : ServiceLocatorInterface
    {
        return $this->serviceLocator;
    }

    /**
     * @param string $serviceCode
     * @return ServiceInterface $service
     */
    protected function getService(string $serviceCode) : ServiceInterface
    {
        $serviceClassIdentifier = 'Service\\'.ucfirst($serviceCode);
        $service = $this->getServiceLocator()->get($serviceClassIdentifier)
            ->setController($this);

        return $service;
    }

    /**
     * @return RecordService $recordService
     */
    protected function getRecordService() : RecordService
    {
        return $this->getService('record');
    }

    /**
     * @param int $id
     * @param string $priority
     * @param string $message
     * @param array $data
     * @return bool $success
     */
    public function record(string $id, int $priority, string $message, array $data = array()) : bool
    {
        $id = $this->getRecordIdPrefix().$id;

        return $this->getRecordService()->record($id, $priority, $message, $data);
    }

    /**
     * @return ConfigService $configService
     */
    public function getConfigService() : ConfigService
    {
        return $this->getService('config');
    }

    /**
     * @param string $entityType
     * @return AbstractEntity $entity
     */
    public function getEntity(string $entityType) : AbstractEntity
    {
        try {
            /** @var AbstractEntity $entity */
            $entity = $this->getServiceLocator()->get($entityType)
                ->setController($this);
        }catch (\Exception $exception) {
            $entity = null;
        }

        if (!$entity instanceof AbstractEntity) {
            $this->record('gey_err', RecordService::ERROR, $entityType.' is not existing!');
            $entity = NoopEntity;
        }

        return $entity;
    }

    /**
     * @return array $this->routeParameters
     */
    public function getRouteParameters() : array
    {
        return $this->routeParameters;
    }

    /**
     * @return bool $useInitialiseRecording
     */
    protected function useInitialiseRecording() : bool
    {
        return static::INIT_RECORDING;
    }

    /**
     * @return bool $useDeinitialiseRecording
     */
    protected function useDeinitialiseRecording() : bool
    {
        return static::DEINIT_RECORDING;
    }

    /**
     * @return mixed $invokeAction
     */
    protected function invokeAction()
    {
        $routeParameters = $this->params()->fromRoute();

        if (isset($_SERVER['argv'])) {
            $routeParameters = array_merge($routeParameters, array(
                'route_type'=>'command',
                'command'=>implode(' ', $_SERVER['argv'])
            ));
        }else{
            $routeParameters = array_merge($routeParameters, array(
                'route_type'=>'uri',
                'uri'=>$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']
            ));
        }

        if (count($routeParameters) > 0) {
            $this->routeParameters = $routeParameters;
            $action = $routeParameters['action'];
        }
        $methodName = parent::getMethodFromAction($action);

        if (isset($action) && method_exists($this, $methodName)) {
            $this->initialiseMethod($methodName);
            try {
                $actionReturn = $this->$methodName();
            }catch (\Exception $exception) {
                $message = 'Action could not be executed. '.$exception->getMessage();
                throw new ActionException($message, $exception->getCode(), $exception->getPrevious());
            }
            $this->deinitialiseMethod($methodName);

        }else{
            $message = 'Action '.$action.' not properly implemented.';
            throw new BadMethodCallException($message, get_called_class(), $action);
            $actionReturn = null;
        }

        return $actionReturn;
    }

    /**
     * {@inheritDoc}
     * @see \Zend\Mvc\Controller::getMethodFromAction($action)
     */
    public static function getMethodFromAction($action) : string
    {
        $methodName = parent::getMethodFromAction($action);
        if (method_exists(get_called_class(), $methodName)) {
            $methodName = 'invokeAction';
        }

        return $methodName;
    }

}