<?php

class Merchant_Billing_PayflowCommon extends Merchant_Billing_Gateway
{
    const TEST_URL = 'https://pilot-payflowpro.paypal.com';
    const LIVE_URL = 'https://payflowpro.paypal.com';
    protected $XMLNS = 'http://www.paypal.com/XMLPay';

    protected $CARD_MAPPING = array(
        'visa' => 'Visa',
        'master' => 'MasterCard',
        'discover' => 'Discover',
        'american_express' => 'Amex',
        'jcb' => 'JCB',
        'diners_club' => 'DinersClub',
        'switch' => 'Switch',
        'solo' => 'Solo'
    );

    protected $CVV_CODE = array(
        'Match' => 'M',
        'No Match' => 'N',
        'Service Not Available' => 'U',
        'Service Not Requested' => 'P'
    );

    protected $default_currency = 'USD';
    protected $supported_countries = array('US', 'CA', 'SG', 'AU');
    
    protected $options;
    protected $partner = 'PayPal';
    protected $timeout = 60;

    private $xml = '';

    function __construct($options = array())
    {
        $this->required_options('login, password', $options);

        $this->options = $options;
        if(isset($options['partner']))
            $this->partner = $options['partner'];

        if(isset($options['currency']))
            $this->default_currency = $options['currency'];
    }

    function capture($money, $authorization, $options)
    {
        $request = $this->build_reference_request('Capture', $money, $authorization, $options);
        return $this->commit($request);
    }

    function void($authorization, $options)
    {
        $request = $this->build_reference_request('Void', null, $authorization, $options);
        return $this->commit($request);
    }
    
    protected function build_request($body)
    {
        $this->xml .= <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<XMLPayRequest Timeout="{$this->timeout}" version="2.1" xmlns="{$this->XMLNS}">
    <RequestData>
        <Vendor>{$this->options['login']}</Vendor>
        <Partner>{$this->partner}</Partner>
XML;
        $this->xml .= $body;
        
        $user = isset($this->options['user']) ? $this->options['user'] : $this->options['login'];

        $this->xml .= <<<XML
    </RequestData>
    <RequestAuth>
        <UserPass>
            <User>{$user}</User>
            <Password>{$this->options['password']}</Password>
        </UserPass>
    </RequestAuth>
</XMLPayRequest>
XML;
    }

    protected function build_transaction_request($body)
    {
        $xml = <<<XML
        <Transactions>
            <Transaction>
                <Verbosity>HIGH</Verbosity>
XML;
        $xml .= $body;
        
        $xml .= <<<XML
            </Transaction>
        </Transactions>
XML;
        return $this->build_request($xml);
    }

    private function build_reference_request($action, $money, $authorization, $options)
    {
        $bodyXml = <<<XML
             <{$action}>
                <PNRef>{$authorization}</PNRef>
XML;
        if(!is_null($money))
        {
            $bodyXml .= <<< XML
                <Invoice>
                    <TotalAmt Currency="{$this->default_currency}">{$this->amount($money)}</TotalAmt>
                </Invoice>
XML;
        }
        
        $bodyXml .= "</{$action}>";

        return $this->build_transaction_request($bodyXml);
    }
    
    protected function add_address($options, $address)
    {
        $xml = '';
        
        if(isset($address['name']))
            $xml .= '<Name>' . htmlEscape($address['name']) . '</Name>';
            
        if(isset($options['email']))
            $xml .= '<EMail>' . htmlEscape($options['email']) . '</EMail>';
            
        if(isset($address['phone']))
            $xml .= '<Phone>' . htmlEscape($address['phone']) . '</Phone>';
        
        $xml .= <<<XML
            <Address>
                <Street1>{$address['address1']}</Street1>
                <City>{$address['city']}</City>
                <State>{$address['state']}</State>
                <Zip>{$address['zip']}</Zip>
                <Country>{$address['country']}</Country>
            </Address>
XML;
        return $xml;
    }

    private function parse($response_xml)
    {
        $xml = simplexml_load_string($response_xml);

        $response = array();

        $root = $xml->ResponseData;
        if ($root->TransactionResults) {
            $responseAttrs = $root->TransactionResults->TransactionResult->attributes();

            if(isset($responseAttrs['Duplicate']) && $transactionAttrs['Duplicate'] == 'true')
                $response['duplicate'] = true;
    
        } else if ($root->RecurringProfileResults) {
            $responseAttrs = $root->RecurringProfileResults->RecurringProfileResult->attributes();
        }
        foreach($root->children() as $node)
            $this->parse_element($response, $node);

        return $response;
    }

    private function parse_element(&$response, $node)
    {
        $nodeName = $node->getName();

        switch(true)
        {
            case $nodeName == 'RPPaymentResult':
                if(!isset($response[$nodeName]))
                    $response[$nodeName] = array();

                $payment_result_response = array();

                foreach($node->children() as $child)
                    $this->parse_element($payment_result_response, $child);

                foreach($payment_result_response as $key => $value)
                    $response[$nodeName][$key] = $value;
                break;

            case count($node->children()) > 0:
                foreach($node->children() as $child)
                    $this->parse_element($response, $child);
                break;

            case preg_match('/amt$/', $nodeName):
                $response[$nodeName] = $node->attributes()->Currency;
                break;

            case $nodeName == 'ExtData':
                $response[$node->attributes()->Name] = $node->attributes()->Value;
                break;

            default:
                $response[$nodeName] = (string)$node;

        }
    }

    protected function commit()
    {
        global $logger;
        $logger->addEntry($this->xml);
        $url = $this->is_test() ? self::TEST_URL : self::LIVE_URL;
        $response = $this->ssl_post($url, $this->xml);
        $logger->addEntry($response);
        $response = $this->parse($response);
        $this->xml = null;

        return new Merchant_Billing_Response(
            ($response['Result'] === 0 || $response['Result'] === '0'), 
            $response['Message'],
            $response,
            $this->options_from($response));
    }
    
    private function options_from($response) 
    {
        $options = array();
        $options['authorization'] = isset( $response['PNRef'] ) ? $response['PNRef'] : null;
        $options['test'] = $this->is_test();
        if(isset($response['CVResult']))
            $options['cvv_result'] = $this->CVV_CODE[$response['CVResult']];
        //TODO: AVS result

        return $options;
    }
    
}
