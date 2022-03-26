<?php

namespace Fastwf\WebForm;

/**
 * The configuration keys to use in [form] section to setup the web form library.
 */
class FormConfiguration
{

    /**
     * The field name to use for CSRF protection.
     * 
     * Must be a non empty string. By default it's "__token".
     */
    public const SECURITY_FIELD_NAME = 'form.security.field_name';

    /**
     * The common seed to use for CSRF protection.
     * 
     * By default it's null.
     */
    public const SECURITY_SEED = 'form.security.seed';

}
