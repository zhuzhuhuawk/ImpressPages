<?php
/**
 * @package   ImpressPages
 */


/**
 * Created by PhpStorm.
 * User: mangirdas
 * Date: 8/19/14
 * Time: 7:10 PM
 */

namespace Ip\Internal\Ecommerce;


class SiteController
{

    public function paymentSelection($key)
    {
        $data = Model::getPaymentData($key);
        $paymentMethods = Model::collectPaymentMethods($data);

        $paymentMethodName = ipRequest()->getPost('paymentMethod');
        if ($paymentMethodName) {
            foreach($paymentMethods as $paymentMethod) {
                if ($paymentMethod->name() == $paymentMethodName) {
                    return new \Ip\Response\Json(array('redirectUrl' => $paymentMethod->paymentUrl($data)));
                }
            }
        }

        ipAddJs('assets/paymentSelection.js');
        $response = ipView('view/selectPayment.php', array('paymentMethods' => $paymentMethods));
        $response = ipFilter('ipPaymentSelectPageResponse', $response, array('paymentKey' => $key));
        return $response;

    }
}