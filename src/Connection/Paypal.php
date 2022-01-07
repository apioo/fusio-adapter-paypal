<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2022 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Fusio\Adapter\Paypal\Connection;

use Fusio\Engine\ConnectionInterface;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

/**
 * Paypal
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
class Paypal implements ConnectionInterface
{
    public function getName(): string
    {
        return 'Paypal';
    }

    public function getConnection(ParametersInterface $config): ApiContext
    {
        $apiContext = new ApiContext(
            new OAuthTokenCredential(
                $config->get('client_id'),
                $config->get('client_secret')
            )
        );

        $params = [
            'mode' => $config->get('mode'),
        ];

        if (defined('PSX_PATH_CACHE')) {
            $cacheDir = PSX_PATH_CACHE;
        } else {
            $cacheDir = sys_get_temp_dir();
        }

        $logLevel = $config->get('log_level');
        if (!empty($logLevel) && $logLevel != 'NONE') {
            $params['log.LogEnabled'] = true;
            $params['log.FileName'] = $cacheDir . '/paypal.log';
            $params['log.LogLevel'] = $logLevel;
        }

        $params['cache.enabled'] = true;
        $params['cache.FileName'] = $cacheDir . '/paypal.cache';

        $apiContext->setConfig($params);

        return $apiContext;
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $modes = [
            'sandbox' => 'Sandbox',
            'live'    => 'Live',
        ];

        $levels = [
            'NONE'    => 'NONE',
            'DEBUG'   => 'DEBUG',
            'INFO'    => 'INFO',
            'WARNING' => 'WARNING',
            'ERROR'   => 'ERROR',
        ];

        $builder->add($elementFactory->newSelect('mode', 'Mode', $modes, 'PayPal provides live and a sandbox environments for API calls. The live environment moves real money while the sandbox environment allows you to test your application with mock money before you go live.'));
        $builder->add($elementFactory->newInput('client_id', 'Client ID', 'text', 'Client id obtained from the developer portal'));
        $builder->add($elementFactory->newInput('client_secret', 'Client Secret', 'text', 'Client secret obtained from the developer portal'));
        $builder->add($elementFactory->newSelect('log_level', 'Log level', $levels, 'Logging level options are based on mode on which SDK is running'));
    }
}
