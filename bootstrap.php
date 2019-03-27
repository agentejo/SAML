<?php

include(__DIR__.'/vendor/autoload.php');

$this->module('saml')->extend([

    'config' => function($setting = null, $default = null) {

        static $config;

        if (!$config) {

            $app = $this->app;

            $default = [

                'debug' => $this->app['config/debug'],

                'sp' => [
                    'entityId' => $app['site_url'].$app->baseUrl('/'),
                    'assertionConsumerService' => [
                        'url' => $app['site_url'].$app->baseUrl('/auth/saml/acs'),
                    ],
                    'singleLogoutService' => [
                        'url' => $app['site_url'].$app->baseUrl('/auth/saml/logout'),
                    ],
                    'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
                ],
                'idp' => [
                    'entityId' => '',
                    'singleSignOnService' => [
                        'url' => '',
                    ],
                    'singleLogoutService' => [
                        'url' => '',
                    ],
                    'x509cert' => ''
                ]
            ];

            $config = array_replace_recursive($default, $this->app->retrieve('config/saml', []));

            // read certificate
            if ($config['idp']['x509cert'] && $this->app->path($config['idp']['x509cert'])) {
                $config['idp']['x509cert'] = file_get_contents($this->app->path($config['idp']['x509cert']));
            }
        }

        return $setting ? ($config[$setting] ?? $default) : $config;
    },

    'auth' => function() {

        static $auth;

        if (is_null($auth)) {
            $settings = $this->config();
            $auth = new \OneLogin\Saml2\Auth($settings);
        }

        return $auth;
    }

]);

// ADMIN
if (COCKPIT_ADMIN && !COCKPIT_API_REQUEST) {
    include_once(__DIR__.'/admin.php');
}
