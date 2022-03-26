<?php

namespace Fastwf\WebForm\Security;

use Fastwf\Core\Session\SessionService;
use Fastwf\Form\Build\Security\ASecurityPolicy;

/**
 * Default implementation of security policy.
 */
class SecurityPolicy extends ASecurityPolicy
{

    /**
     * The prefix to use for storage in session.
     */
    protected const PREFIX = '_csrf_token.';

    /**
     * The session service to use for token storage.
     *
     * @var SessionService
     */
    private $service;

    /**
     * Constructor.
     *
     * @param Context $context the application context.
     * @param string $name the field name to use for CSRF token input.
     * @param string|null $seed the application seed to use to generate he token.
     */
    public function __construct($context, $name, $seed)
    {
        parent::__construct($name, $seed);

        $this->service = $context->getService(SessionService::class);
    }

    public function onSetCsrfToken($tokenId, $token)
    {
        // Save the CSRF token in the session of the user using a prefix
        $this->service
            ->getSession()
            ->set(self::PREFIX.$tokenId, $token);
    }

    public function getVerificationCsrfToken($tokenId)
    {
        // Try to query the CSRF token from the session of the user using a prefix
        return $this->service
            ->getSession()
            ->get(self::PREFIX.$tokenId);
    }

}