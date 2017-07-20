<?php
/**
 * Gz3Base - Zend Framework Base Tweaks / Zend Framework Basis Anpassungen
 * @package Gz3Base\Controller
 * @author Andreas Gerhards <ag.dialogue@yahoo.co.nz>
 * @copyright ©2016-2017, Andreas Gerhards - All rights reserved
 * @license http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause - Please check LICENSE.md for more information
 */

declare(strict_types = 1);
namespace Gz3BaseTest\Mvc\Controller;

use Gz3Base\Mvc\Controller\AbstractActionController;
use Gz3Base\Mvc\Service\ConfigService;
use Gz3Base\Record\Service\RecordService;
use Gz3Base\Test\PhpUnit\Model\Gz3TestCase;
use Gz3BaseTest\Mvc\Controller\src\ActionController;
use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManager;
use Zend\ServiceManager\ServiceLocatorInterface;


class AbstractActionControllerTest extends Gz3TestCase
{

    /** @var bool self::ALLOW_MANUAL_SET_OF_INVOKE_METHOD_CLASS */
    protected const ALLOW_MANUAL_SET_OF_INVOKE_METHOD_CLASS = true;

    /** @var MvcEvent $this->event */
    private $event;
    /** @var Request $this->request */
    private $request;
    /** @var mixed $this->response */
    private $response;
    /** @var ServiceLocatorInterface */
    private $serviceLocator;


    /**
     * {@inheritDoc}
     * @see \Gz3Base\Test\PhpUnit\Model\TestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();

        $this->setInvokeMethodClassPermanently(new ActionController());
        $this->request = new Request();
        $this->response = null;

        $this->routeMatch = new RouteMatch(['controller'=>'controller-fixture-action']);
        $this->event = new MvcEvent();
        $this->event->setRouteMatch($this->routeMatch);

        $this->sharedEvents = new SharedEventManager();
        $this->events = $this->createEventManager($this->sharedEvents);

        $this->objectToTest
            ->setServiceLocator($this->serviceLocator)
            ->setEventManager($this->events)
            ->setEvent($this->event);
    }

    /**
     * @param SharedEventManager
     * @return EventManager
     */
    protected function createEventManager(SharedEventManagerInterface $sharedManager)
    {
        return new EventManager($sharedManager);
    }

    /**
     */
    public function testDispatchInvokesNotFoundActionWhenNoActionPresentInRouteMatch()
    {
        $result = $this->objectToTest->dispatch($this->request, $this->response);
        $response = $this->objectToTest->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertInstanceOf(ModelInterface::class, $result);
        $this->assertEquals('content', $result->captureTo());
        $vars = $result->getVariables();
        $this->assertArrayHasKey('content', $vars, var_export($vars, 1));
        $this->assertContains('Page not found', $vars['content']);
    }

    /**
     */
    public function testDispatchInvokesNotFoundActionWhenInvalidActionPresentInRouteMatch()
    {
        $this->routeMatch->setParam('action', 'totally-made-up-action');
        $result = $this->objectToTest->dispatch($this->request, $this->response);
        $response = $this->objectToTest->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertInstanceOf(ModelInterface::class, $result);
        $this->assertEquals('content', $result->captureTo());
        $vars = $result->getVariables();
        $this->assertArrayHasKey('content', $vars, var_export($vars, 1));
        $this->assertContains('Page not found', $vars['content']);
    }

    /**
     */
    public function testDispatchInvokesProvidedActionWhenMethodExists()
    {
        $this->routeMatch->setParam('action', 'word');
        $result = $this->objectToTest->dispatch($this->request, $this->response);
        $this->assertTrue(isset($result['content']));
        $this->assertContains(ActionController::WORD, $result['content']);
    }

    /**
     */
    public function testDispatchCallsActionMethodBasedOnNormalizingAction()
    {
        $this->routeMatch->setParam('action', 'sentence');
        $result = $this->objectToTest->dispatch($this->request, $this->response);
        $this->assertTrue(isset($result['content']));
        $this->assertContains(ActionController::SENTENCE, $result['content']);
    }

    /**
     */
    public function testShortCircuitsBeforeActionIfPreDispatchReturnsAResponse()
    {
        $response = new Response();
        $response->setContent('short circuited!');
        $this->objectToTest->getEventManager()
            ->attach(MvcEvent::EVENT_DISPATCH, function ($e) use ($response) { return $response; }, 100);
        $result = $this->objectToTest->dispatch($this->request, $this->response);
        $this->assertSame($response, $result);
    }

    /**
     */
    public function testServiceLocatorInjection()
    {
        $serviceLocator = new ServiceLocator();
        $return = $this->objectToTest->setServiceLocator($serviceLocator);
        $this->assertInstanceOf(AbstractActionController::class, $return);

        $return = $this->objectToTest->getServiceLocator();
        $this->assertInstanceOf(ServiceLocatorInterface::class, $return);
        $this->assertSame($serviceLocator, $return);
    }

    /**
     */
    public function testServiceRetrieval()
    {
        $return = $this->objectToTest->getConfigService();
        $this->assertInstanceOf(ConfigService::class, $return);

        $return = $this->invokeMethod('getRecordService');
        $this->assertInstanceOf(RecordService::class, $return);

    }

    /**
     */
    public function testRecording()
    {
        $this->objectToTest->setServiceLocator($this->serviceLocator);

        /** @todo  Implement at least one successful test */
        $id = '';
        $priority = RecordService::ERROR;
        $priority = RecordService::WARN;
        $priority = RecordService::DETAIL;
        $priority = RecordService::DEVEL;
        $message = '';
        $data = [];

        $result = $this->objectToTest->record($id, $priority, $message, $data = []);
        $this->assertFalse($result);

        /** @todo  Implement at least one failing test */
        $id = '';
        $priority = RecordService::ERROR;
        $priority = RecordService::WARN;
        $priority = RecordService::DETAIL;
        $priority = RecordService::DEVEL;
        $message = '';
        $data = [];

        $result = $this->objectToTest->record($id, $priority, $message, $data = []);
        $this->assertTrue($result);
    }


    public function testGetEntity()
    {
        /** @todo  prepare */
        $entity = $this->objectToTest->getEntity($entityType);
        $this->assertInstanceOf(AbstractEntity::class, $entity);
    }

/*
    public function getRouteParameters() : array
    protected function invokeAction()
    public static function getMethodFromAction($action) : string
/**/

}