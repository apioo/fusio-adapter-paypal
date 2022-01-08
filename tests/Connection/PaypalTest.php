<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015 Christoph Kappestein <christoph.kappestein@gmail.com>
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

namespace Fusio\Adapter\Paypal\Tests\Connection;

use Fusio\Adapter\Paypal\Connection\Paypal;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use Fusio\Engine\Form\Element\Input;
use Fusio\Engine\Form\Element\Select;
use Fusio\Engine\Parameters;
use Fusio\Engine\Test\EngineTestCaseTrait;
use PayPal\Rest\ApiContext;
use PHPUnit\Framework\TestCase;

/**
 * PaypalTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
class PaypalTest extends TestCase
{
    use EngineTestCaseTrait;

    public function testGetConnection()
    {
        /** @var Paypal $connectionFactory */
        $connectionFactory = $this->getConnectionFactory()->factory(Paypal::class);

        $config = new Parameters([
            'client_id'     => 'AYSq3RDGsmBLJE-otTkBtM-jBRd1TCQwFf9RGfwddNXWz0uFU9ztymylOhRS',
            'client_secret' => 'EGnHDxD_qRPdaLdZz8iCr8N7_MzF-YHPTkjs6NKYQvQSBngp4PTTVWkPZRbL',
        ]);

        $connection = $connectionFactory->getConnection($config);

        $this->assertInstanceOf(ApiContext::class, $connection);
    }

    public function testConfigure()
    {
        $connection = $this->getConnectionFactory()->factory(Paypal::class);
        $builder    = new Builder();
        $factory    = $this->getFormElementFactory();

        $connection->configure($builder, $factory);

        $this->assertInstanceOf(Container::class, $builder->getForm());

        $elements = $builder->getForm()->getElements();
        $this->assertEquals(4, count($elements));
        $this->assertInstanceOf(Select::class, $elements[0]);
        $this->assertInstanceOf(Input::class, $elements[1]);
        $this->assertInstanceOf(Input::class, $elements[2]);
        $this->assertInstanceOf(Select::class, $elements[3]);
    }
}
