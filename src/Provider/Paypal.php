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

namespace Fusio\Adapter\Paypal\Provider;

use Fusio\Engine\Model\ProductInterface;
use Fusio\Engine\Model\TransactionInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\Payment\PrepareContext;
use Fusio\Engine\Payment\ProviderInterface;
use PayPal\Api;
use PayPal\Rest\ApiContext;
use PSX\Http\Exception as StatusCode;

/**
 * Paypal
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class Paypal implements ProviderInterface
{
    /**
     * @inheritdoc
     */
    public function prepare($connection, ProductInterface $product, TransactionInterface $transaction, PrepareContext $context)
    {
        $apiContext = $this->getApiContext($connection);

        // create payment
        $payment = $this->createPayment($product, $context);
        $payment->create($apiContext);

        // update transaction details
        $this->updateTransaction($payment, $transaction);

        return $payment->getApprovalLink();
    }

    /**
     * @inheritdoc
     */
    public function execute($connection, ProductInterface $product, TransactionInterface $transaction, ParametersInterface $parameters)
    {
        $apiContext = $this->getApiContext($connection);

        $payerId   = $parameters['PayerID'] ?? null;
        $execution = $this->createPaymentExecution($payerId, $product->getPrice());

        // execute payment
        $payment = Api\Payment::get($transaction->getRemoteId(), $apiContext);
        $payment->execute($execution, $apiContext);

        // update transaction details
        $this->updateTransaction($payment, $transaction);
    }

    /**
     * @param mixed $connection
     * @return \PayPal\Rest\ApiContext
     */
    private function getApiContext($connection)
    {
        if ($connection instanceof ApiContext) {
            return $connection;
        } else {
            throw new StatusCode\InternalServerErrorException('Connection must return a Paypal API context');
        }
    }

    /**
     * @param string $payerId
     * @param float $total
     * @return \PayPal\Api\PaymentExecution
     */
    private function createPaymentExecution($payerId, $total)
    {
        $amount = new Api\Amount();
        $amount->setCurrency($this->currency);
        $amount->setTotal($total);

        $transaction = new Api\Transaction();
        $transaction->setAmount($amount);

        $execution = new Api\PaymentExecution();
        $execution->setPayerId($payerId);
        $execution->addTransaction($transaction);

        return $execution;
    }

    /**
     * @param \Fusio\Engine\Model\ProductInterface $product
     * @param \Fusio\Engine\Payment\PrepareContext $context
     * @return \PayPal\Api\Payment
     */
    private function createPayment(ProductInterface $product, PrepareContext $context)
    {
        $payer = new Api\Payer();
        $payer->setPaymentMethod('paypal');

        $item = new Api\Item();
        $item->setName($product->getName())
            ->setCurrency($context->getCurrency())
            ->setQuantity(1)
            ->setSku($product->getId())
            ->setPrice($product->getPrice());

        $itemList = new Api\ItemList();
        $itemList->setItems([$item]);

        $amount = new Api\Amount();
        $amount->setCurrency($this->currency)
            ->setTotal($product->getPrice());

        $transaction = new Api\Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setInvoiceNumber(uniqid());

        $redirectUrls = new Api\RedirectUrls();
        $redirectUrls->setReturnUrl($context->getReturnUrl())
            ->setCancelUrl($context->getCancelUrl());

        $payment = new Api\Payment();
        $payment->setIntent('sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions([$transaction]);

        return $payment;
    }

    /**
     * @param \PayPal\Api\Payment $payment
     * @param \Fusio\Engine\Model\TransactionInterface $transaction
     */
    private function updateTransaction(Api\Payment $payment, TransactionInterface $transaction)
    {
        $transaction->setStatus($this->getTransactionStatus($payment));
        $transaction->setRemoteId($payment->getId());
    }

    /**
     * @param \PayPal\Api\Payment $payment
     * @return integer
     */
    private function getTransactionStatus(Api\Payment $payment)
    {
        if ($payment->getState() == 'created') {
            return TransactionInterface::STATUS_CREATED;
        } elseif ($payment->getState() == 'approved') {
            return TransactionInterface::STATUS_APPROVED;
        } elseif ($payment->getState() == 'failed') {
            return TransactionInterface::STATUS_FAILED;
        }

        return TransactionInterface::STATUS_UNKNOWN;
    }
}
