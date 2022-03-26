<?php

namespace Fastwf\WebForm;

use Fastwf\Core\Engine\Service;
use Fastwf\Form\Entity\Html\Form;
use Fastwf\Form\Build\FormBuilder;
use Fastwf\Form\Utils\RequestUtil;
use Fastwf\Api\Exceptions\KeyError;
use Fastwf\Api\Utils\AsyncProperty;
use Fastwf\Constraint\Api\Validator;
use Fastwf\Interpolation\LexInterpolator;
use Fastwf\Constraint\Api\TemplateProvider;
use Fastwf\WebForm\Security\SecurityPolicy;
use Fastwf\Form\Utils\Pipes\BasePipeInstaller;
use Fastwf\Api\Http\Frame\HttpRequestInterface;
use Fastwf\Core\Configuration;
use Fastwf\Form\Utils\FormTemplateProvider;

/**
 * The form service that provides secure form builder and validation process.
 */
class FormService extends Service
{

    /**
     * The option key of the form builder for security policy.
     */
    protected const SECURITY_POLICY_KEY = 'securityPolicy';

    /**
     * The security policy cache map that store all created security policies (during request processing).
     * 
     * @var array<string,SecurityPolicyInterface>
     */
    protected $securityPolicies = [];

    /**
     * The error interpolator.
     *
     * @var AsyncProperty<LexInterpolator>
     */
    protected $interpolator;

    /**
     * The application configuration.
     * 
     * @var Configuration
     */
    protected $configuration;

    public function __construct($context)
    {
        parent::__construct($context);

        // Set the configuration object
        $this->configuration = $this->context->getConfiguration();

        // Prepare the interpolator and install pipes
        $this->interpolator = new AsyncProperty(function () {
            // Create the interpolator instance
            $interpolator = new LexInterpolator();

            // Install pipes
            $this->getPipeInstaller()->install(
                $interpolator->getEnvironment()
            );
        });
    }

    /**
     * Create a new security policy.
     *
     * @return SecurityPolicyInterface
     */
    protected function getSecurityPolicy()
    {
        return new SecurityPolicy(
            $this->context,
            $this->configuration->get(FormConfiguration::SECURITY_FIELD_NAME, "__token"),
            $this->configuration->get(FormConfiguration::SECURITY_SEED)
        );
    }

    /**
     * Get the pipe installer to use to setup pipes in LexInterpolator.
     *
     * @return BasePipeInstaller
     */
    protected function getPipeInstaller()
    {
        return new BasePipeInstaller();
    }

    /**
     * Get the template provider to use to get the template for the form validator.
     * 
     * @return TemplateProvider
     */
    protected function getTemplateProvider()
    {
        return new FormTemplateProvider();
    }

    /**
     * Prepare a new form builder with security setup.
     * 
     * @param string $id the id of the form (must be unique to allows to securise form across multiple requests to render or validate body)
     * @param string $action the action url.
     * @param array $option form builder options ({@see FormBuilder::__construct} for option details)
     * @return FormBuilder the form builder.
     */
    public function getFormBuilder($id, $action, $option)
    {
        // Replace the previous security policy with the new form builder (a new form must be created)
        if (\array_key_exists(self::SECURITY_POLICY_KEY, $option))
        {
            $this->securityPolicies[$id] = $option[self::SECURITY_POLICY_KEY];
        }
        else
        {
            $securityPolicy = $this->getSecurityPolicy();

            // Register in the cache and add the security policy to the form builder options
            $this->securityPolicies[$id] = $securityPolicy;
            $option[self::SECURITY_POLICY_KEY] = $securityPolicy;
        }

        return FormBuilder::new($id, $action, $option);
    }


    /**
     * Refresh the CSRF token associated to the form id.
     * 
     * Warning:
     *  - Call the method before rendering the form to have a fresh CSRF token.
     *  - Call the method after form generation (FormBuilder::build must have been called).
     *
     * @param string $id the id of the form built.
     * @return void
     * @throws KeyError when no SecurityPolicy is set for this form id (FormService::getFormBuilder is probably not called)
     * @throws ValueError when no form control is attached to this security policy (FormBuilder::build probably not called).
     */
    public function refreshCsrfToken($id)
    {
        if (\array_key_exists($id, $this->securityPolicies))
        {
            $this->securityPolicies[$id]
                ->newCsrfToken($id);
        }
        else
        {
            throw new KeyError("No security policy found for form id '$id'");
        }
    }

    /**
     * Validate the form using the given request.
     *
     * @param HttpRequestInterface $request the fastwf request.
     * @param Form $form the generated form to use for validation.
     * @return boolean true if the form is valid, false otherwise.
     */
    public function validate($request, $form)
    {
        // Generate the form validator
        $validator = new Validator(
            $form->getConstraint(),
            $this->getTemplateProvider(),
            $this->interpolator->get()
        );

        // Set the data and validate the form
        $form->setValue(RequestUtil::dataFromRequest($request));
        return $form->validate(null, $validator);
    }

}
