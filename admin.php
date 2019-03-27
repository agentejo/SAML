<?php

$this->bind('/auth/saml/login', function() {

    $service = $this->retrieve('config/saml/idp/singleSignOnService');

    if (!isset($service['url'])) {
        return $this->view('saml:views/error.php', ['errors' => ['singleSignOnService url undefined!']]);
    }

    $auth = $this->module('saml')->auth();

    if (!$auth->isAuthenticated()) {
        $this->reroute($auth->login(null, [], false, false, true));
    } else {
        $this->reroute('/auth/saml/acs');
    }
});

$this->bind('/auth/saml/logout', function() {

    $service = $this->retrieve('config/saml/idp/singleLogoutService');

    if (!isset($service['url'])) {
        return $this->view('saml:views/error.php', ['errors' => ['singleLogoutService url undefined!']]);
    }

    $returnTo     = $this['site_url'].$this->baseUrl('/auth/login');
    $parameters   = [];
    $nameId       = $_SESSION['samlNameId'] ?? null;
    $sessionIndex = $_SESSION['samlSessionIndex'] ?? null;
    $nameIdFormat = $_SESSION['samlNameIdFormat'] ?? null;

    $url = $this->module('saml')->auth()->logout($returnTo, $parameters, $nameId, $sessionIndex, true, $nameIdFormat);

    $this->reroute($url);
});


$this->bind('/auth/saml/acs', function() {

    $reqID = $_SESSION['AuthNRequestID'] ?? null;
    $auth  = $this->module('saml')->auth();

    try {
        $auth->processResponse($reqID);
    } catch (\Exception $e) {
        return $this->view('saml:views/error.php', ['errors' => [$e->getMessage()]]);
    }

    $errors = $auth->getErrors();

    if (!empty($errors)) {
        return $this->view('saml:views/error.php', ['errors' => $errors]);
    }

    if (!$auth->isAuthenticated()) {
        return $this->view('saml:views/error.php', ['errors' => ['Not authenticated']]);
    }

    $user    = ['saml' => true];
    $attrs   = $auth->getAttributes();
    $mapping = $this->module('saml')->config('mapping');

    if ($mapping) {

        if (is_array($mapping)) {

            foreach ($mapping as $key => $value) {

                if (isset($attrs[$key])) {
                    $user[$value] = $attrs[$key][0];
                }
            }

        } elseif (is_callable($mapping)) {
            $mapping($user, $attrs);
        }
    }

    if (!$this->module('cockpit')->hasaccess('cockpit', 'backend', @$user['group'])) {
        return $this->view('saml:views/error.php', ['errors' => ['Missing rights to access backend'], 'attributes' => $attrs]);
    }

    $_SESSION['samlNameId']       = $auth->getNameId();
    $_SESSION['samlNameIdFormat'] = $auth->getNameIdFormat();
    $_SESSION['samlSessionIndex'] = $auth->getSessionIndex();

    unset($_SESSION['AuthNRequestID']);

    // if (isset($_REQUEST['RelayState']) && \OneLogin\Saml2\Utils::getSelfURL() != $_REQUEST['RelayState']) {
    //     $this->reroute($_REQUEST['RelayState']);
    // }

    $this->trigger('cockpit.account.login', [&$user]);
    $this->module('cockpit')->setUser($user);
    $this->reroute('/');
});

$this->bind('/auth/saml/meta', function() {

    $this->response->mime = 'xml';

    $config = $this->module('saml')->config();

    $meta = [
        'entityID' => $config['sp']['entityId'],
        'assertionConsumerService' => $config['sp']['assertionConsumerService']['url'],
        'singleLogoutService' => $config['sp']['singleLogoutService']['url'],
    ];

    $body = $this->view('saml:views/meta.php', compact('meta'));

    return $body;
});

$this->on('app.login.footer', function() {
    echo $this->view('saml:views/partials/login.php');
});

$this->on('cockpit.account.logout', function($user) {

    if (isset($user['saml']) && $user['saml']) {
        $this->helper('session')->delete('cockpit.app.auth');
        $this->reroute('/auth/saml/logout');
    }
});
