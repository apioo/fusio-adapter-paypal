<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2018 Christoph Kappestein <christoph.kappestein@gmail.com>
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
 * @link    http://fusio-project.org
 */
class Paypal implements ConnectionInterface
{
    public function getName()
    {
        return 'Paypal';
    }

    /**
     * @param \Fusio\Engine\ParametersInterface $config
     * @return \PayPal\Rest\ApiContext
     */
    public function getConnection(ParametersInterface $config)
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

        $logFile  = $config->get('log_file');
        $logLevel = $config->get('log_level');
        if (!empty($logFile) && !empty($logLevel)) {
            $params['log.LogEnabled'] = true;
            $params['log.FileName'] = $logFile;
            $params['log.LogLevel'] = $logLevel;
        }

        if (defined('PSX_PATH_CACHE')) {
            $cacheDir = PSX_PATH_CACHE;
        } else {
            $cacheDir = sys_get_temp_dir();
        }

        $params['cache.enabled'] = true;
        $params['cache.FileName'] = $cacheDir;

        $apiContext->setConfig($params);

        return $apiContext;
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory)
    {
        $modes = [
            'sandbox' => 'Sandbox',
            'live'    => 'Live',
        ];

        $levels = [
            'DEBUG'   => 'DEBUG',
            'INFO'    => 'INFO',
            'WARNING' => 'WARNING',
            'ERROR'   => 'ERROR',
        ];

        $builder->add($elementFactory->newSelect('mode', 'Mode', $modes, 'PayPal provides live and a sandbox environments for API calls. The live environment moves real money while the sandbox environment allows you to test your application with mock money before you go live.'));
        $builder->add($elementFactory->newInput('client_id', 'Client ID', 'text', 'Client id obtained from the developer portal'));
        $builder->add($elementFactory->newInput('client_secret', 'Client Secret', 'text', 'Client secret obtained from the developer portal'));
        $builder->add($elementFactory->newInput('log_file', 'Log file', 'text', 'When using a relative path, the log file is created relative to the .php file that is the entry point for this request. You can also provide an absolute path here'));
        $builder->add($elementFactory->newSelect('log_level', 'Log level', $levels, 'text', 'Logging level options are based on mode on which SDK is running'));
    }
}
