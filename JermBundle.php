<?php

namespace Musicjerm\Bundle\JermBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Symfony Bundle
 *
 * @author Jeremy Antunez https://github.com/musicjerm/jermbundle
 */
class JermBundle extends Bundle
{
    /**
     * CSRF token id sent as `_token` with the bundle's POST actions (group actions, head actions
     * with `method: POST`, preset deletes). An app controller that changes data from one of these
     * actions checks it with isCsrfTokenValid(JermBundle::ACTION_CSRF_TOKEN_ID, ...).
     */
    public const ACTION_CSRF_TOKEN_ID = 'jerm_bundle_action';
}