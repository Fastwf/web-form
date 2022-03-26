<?php

namespace Fastwf\Tests;

use Fastwf\Api\Exceptions\KeyError;
use Fastwf\Core\Http\Frame\HttpRequest;
use Fastwf\WebForm\FormService;
use PHPUnit\Framework\TestCase;
use Fastwf\Form\Entity\Html\Button;
use Fastwf\Tests\Engine\TestingEngine;
use Fastwf\WebForm\Security\SecurityPolicy;

class FormServiceTest extends TestCase
{

    /**
     * The testing engine.
     * 
     * @var TestingEngine
     */
    private $context;

    protected function setUp(): void
    {
        /** @var TestingEngine */
        $this->context = $this->getMockBuilder(TestingEngine::class)
            ->setConstructorArgs([__DIR__."/../resources/configuration.ini"])
            ->onlyMethods(['handleRequest', 'sendResponse'])
            ->getMock();
        
        $this->context->run();
    }

    /**
     * @covers Fastwf\WebForm\FormService
     * @covers Fastwf\WebForm\Security\SecurityPolicy
     */
    public function testGetFormBuilder()
    {
        $form = $this->context
            ->getService(FormService::class)
            ->getFormBuilder('test', '', [])
            ->addTextarea('comment', [
                'label' => 'Comment',
                'assert' => [
                    'required' => true,
                    'maxLength' => 255,
                ],
            ])
            ->addButton(Button::TYPE_SUBMIT, [
                'label' => 'Submit',
            ])
            ->build();

        // Verify that the protection is automatically applied
        $this->assertEquals(
            '__token',
            $form->getControlAt(0)->getName()
        );
    }

    /**
     * @covers Fastwf\WebForm\FormService
     * @covers Fastwf\WebForm\Security\SecurityPolicy
     */
    public function testGetFormBuilderCustomSecurityPolicy()
    {
        $policy = $this->getMockBuilder(SecurityPolicy::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['onSetCsrfToken', 'getVerificationCsrfToken'])
            ->getMock();
        
        $policy->expects($this->once())
            ->method('onSetCsrfToken');

        /** @var FormService */
        $service = $this->context
            ->getService(FormService::class);
        $service->getFormBuilder('test', '', ['securityPolicy' => $policy])
            ->addTextarea('comment', [
                'label' => 'Comment',
                'assert' => [
                    'required' => true,
                    'maxLength' => 255,
                ],
            ])
            ->addButton(Button::TYPE_SUBMIT, [
                'label' => 'Submit',
            ])
            ->build();

        // Verify that the policy instance provided is used for security
        $service->refreshCsrfToken('test');
    }

    /**
     * @covers Fastwf\WebForm\FormService
     */
    public function testRefreshCsrfTokenFailed()
    {
        $this->expectException(KeyError::class);

        // Do not create the form builder to prevent security policy registration
        $this->context
            ->getService(FormService::class)
            ->refreshCsrfToken('test');
    }

    /**
     * @covers Fastwf\WebForm\FormService
     * @covers Fastwf\WebForm\Security\SecurityPolicy
     */
    public function testValidate()
    {
        /** @var FormService */
        $service = $this->context
            ->getService(FormService::class);

        // Emulate a precedent request for form generation
        $form = $service->getFormBuilder('test', '', [])->build();
        $service->refreshCsrfToken('test');

        $token = $form->getControlAt(0)->getValue();

        $form = $service->getFormBuilder('test', '', [])
            ->addTextarea('comment', [
                'label' => 'Comment',
                'assert' => [
                    'required' => true,
                    'maxLength' => 255,
                ],
            ])
            ->addButton(Button::TYPE_SUBMIT, [
                'label' => 'Submit',
            ])
            ->build();

        // Mock the request
        $_POST = array_merge($form->getData(), ["comment" => "Hello world", '__token' => $token]);
        $request = new HttpRequest('/', 'POST');

        $isValid = $service->validate($request, $form);

        $this->assertTrue($isValid);
    }

}
