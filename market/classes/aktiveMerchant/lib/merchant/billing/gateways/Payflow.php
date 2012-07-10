<?php

require_once 'payflow/PayflowCommon.php';
require_once 'payflow/PayflowResponse.php';

class Merchant_Billing_Payflow extends Merchant_Billing_PayflowCommon
{
    protected $homepage_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_payflow-pro-overview-outside';
    protected $display_name = 'PayPal Payflow Pro';
    protected $RECURRING_ACTIONS = array ('add', 'modify', 'cancel', 'inquiry', 'reactivate', 'payment');

    function authorize($money, $credit_card_or_reference, $options = array())
    {
        $request = $this->build_sale_or_authorization_request('Authorization', $money, $credit_card_or_reference, $options);
        return $this->commit($request);
    }

    function purchase($money, $credit_card_or_reference, $options = array())
    {
        $request = $this->build_sale_or_authorization_request('Purchase', $money, $credit_card_or_reference, $options);
        return $this->commit($request);
    }

    function recurring($money, Merchant_Billing_CreditCard $credit_card, $options=array())
    {
        $this->required_options('pay_period', $options);
        $request = $this->build_recurring_request((isset($options['profile_id']) && $options['profile_id']) ? 'modify' : 'add', $money, $credit_card, $options);
        return $this->commit($request);
    }
    
    function cancel_recurring($profile_id)
    {
        $request = $this->build_recurring_request('cancel', 0, array('profile_id' => $profile_id));
        return $this->commit($request);
    }
    
    function recurring_inquiry($profile_id, $options = array())
    {
        $options['profile_id'] = $profile_id;
        $request = $this->build_recurring_request('inquiry', 0, $options);
        return $this->commit($request);
    }
    
    //function recurring_inquiry($profile_id, $options = array()) {
        //$request = $this->build_recurring_request('Inquiry', )

    private function build_sale_or_authorization_request($action, $money, $credit_card_or_reference, $options)
    {
        return is_string($credit_card_or_reference)
            ? $this->build_reference_sale_or_authorization_request($action, $money, $credit_card_or_reference, $options)
            : $this->build_credit_card_request($action, $money, $credit_card_or_reference, $options);
    }

    private function build_reference_sale_or_authorization_request($action, $money, $reference, $options)
    {
        $bodyXml = <<<XML
             <{$action}>
                <PayData>
                    <Invoice>
                        <TotalAmt Currency="{$this->default_currency}">
                            {$this->amount($money)}
                        </TotalAmt>
                    </Invoice>
                    <Tender>
                        <Card>
                            <ExtData Name="ORIGID" Value="{$reference}"></ExtData>
                        </Card>
                    </Tender>
                </PayData>
             </{$action}>
XML;
        return $this->build_transaction_request($bodyXml);
    }

    private function build_credit_card_request($action, $money, $credit_card, $options)
    {
        $bodyXml = <<<XML
             <{$action}>
                <PayData>
                    <Invoice>
XML;
        if(isset($options['ip']))
            $bodyXml .= "<CustIp>" . $options['ip'] . "</CustIp>";

        if(isset($options['order_id']))
        {
            $orderId = preg_replace('/[^\w.]/', '', $options['order_id']);
            $bodyXml .= "<InvNum>" . $orderId . "</InvNum>";
        }

        if(isset($options['description']))
            $bodyXml .= "<Description>" . $options['description'] . "</Description>";

        if(isset($options['billing_address']))
            $bodyXml .= "<BillTo>" . $this->add_address($options, $options['billing_address']) ."</BillTo>";
        
        if(isset($options['shipping_address']))
            $bodyXml .= "<ShipTo>" . $this->add_address($options, $options['shipping_address']) ."</ShipTo>";

        $bodyXml .= <<<XML
                        <TotalAmt Currency="{$this->default_currency}">
                            {$this->amount($money)}
                        </TotalAmt>
                    </Invoice>
                    <Tender>
XML;
        
        if(isset($options['order_items']))
        {
            $bodyXml .= "<Items>";
            
            foreach($options['order_items'] as $key => $item)
            {
                $count = $key+1;
                $bodyXml .= <<<XML
                    <Item Number="{$count}">
                        <SKU>{$item['id']}</SKU>
                        <UPC>{$item['id']}</UPC>
                        <Description>{$item['description']}</Description>
                        <Quantity>{$item['quantity']}</Quantity>
                        <UnitPrice>{$item['unit_price']}</UnitPrice>
                        <TotalAmt>{$item['total']}</TotalAmt>
                    </Item>
XML;
            }
            
            $bodyXml .= "</Items>";
        }
        
        $bodyXml .= $this->add_credit_card($credit_card, $options);
        
        $bodyXml .= <<<XML
                    </Tender>
                </PayData>
             </{$action}>
XML;
        return $this->build_transaction_request($bodyXml);
    }
        
    private function add_credit_card($creditcard, $options = array())
    {
        $month = $this->cc_format($creditcard->month, 'two_digits');
        $year = $this->cc_format($creditcard->year, 'four_digits');
        
        $xml = "
        <Card>
            <CardType>{$this->credit_card_type($creditcard)}</CardType>
            <CardNum>" . htmlEscape($creditcard->number) . "</CardNum>
            <ExpDate>{$year}{$month}</ExpDate>
            <NameOnCard>" . htmlEscape($creditcard->first_name) . "</NameOnCard>
            <CVNum>{$creditcard->verification_value}</CVNum>";
         
        if($this->requires_start_date_or_issue_number($creditcard))
        {
            if(!is_null($creditcard->start_month))
            {
                $startMonth = $this->cc_format($creditcard->start_month, 'two_digits');
                $startYear = $this->cc_format($creditcard->start_year, 'four_digits');  
                $xml .= '<ExtData Name="CardStart" Value="' . $startYear . $startMonth .'"></ExtData>';
            }
            
            if(!is_null($creditcard->issue_number))
                $xml .= '<ExtData Name="CardIssue" Value="' . $this->cc_format($creditcard->issue_number, 'two_digits') .'"></ExtData>';
        }
        
        $xml .= "<ExtData Name=\"LASTNAME\" Value=\"{$creditcard->last_name}\"></ExtData>";
        
        if(isset($options['three_d_secure']))
        {
            $tds = $options['three_d_secure'];
            $xml .= <<<XML
                <BuyerAuthResult>
                    <AUTHSTATUS3DS>{$tds['pares_status']}</AUTHSTATUS3DS>
                    <MPIVENDOR3DS>{$tds['enrolled']}</MPIVENDOR3DS>
                    <ECI>{$tds['eci_flag']}</ECI>
                    <CAVV>{$tds['cavv']}</CAVV>
                    <XID>{$tds['xid']}</XID>
                </BuyerAuthResult>
XML;
        }
        
        $xml .= "</Card>";
        return $xml;
    }
    
    private function credit_card_type($credit_card)
    {
        return is_null($this->card_brand($credit_card))
            ? ''
            : $this->CARD_MAPPING[$this->card_brand($credit_card)];
    }
    
    private function build_recurring_request($action, $money, $credit_card, $options = array())
    {
        if (!in_array($action, $this->RECURRING_ACTIONS))
            throw new Exception("Invalid Recurring Profile Action: {$action}");
        if (!($options['pay_period'] = $this->get_pay_period($options)))
            throw new Exception("Invalid periodicity");

        $xml = <<<XML
        <RecurringProfiles>
            <RecurringProfile>
XML;
        $xml .= '<' . ucfirst($action) . '>';
        if (!in_array($action, array('cancel', 'inquiry'))) {
            $xml .= "<RPData>\n";
            if (isset($options['name']))
                $xml .= "<Name>{$options['name']}</Name>";
            $xml .= <<<XML
                <TotalAmt Currency="{$this->default_currency}">{$this->amount($money)}</TotalAmt>
                <PayPeriod>{$options['pay_period']}</PayPeriod>
XML;
            if (isset($options['payments']))
                $xml .= "<Term>{$options['payments']}</Term>";
            if (isset($options['comment']))
                $xml .= "<Comment>{$options['comment']}</Comment>";
            if ($initial_tx = $options['initial_transaction']) {
                $this->required_options('type,amount', $initial_tx);
                if ($initial_tx['type'] == 'purchase' || $initial_tx['type'] == 'authorization') {
                    if ($initial_tx['type'] == 'purchase')
                        $this->required_options('amount', $initial_tx);
                    $xml .= "<OptionalTrans>{$this->RECURRING_ACTIONS[$initial_tx['type']]}</OptionalTrans>";
                    if ($initial_tx['amount'])
                        $xml .= "<OptionalTransAmt>" . round($initial_tx['amount'], 2) . "</OptionalTransAmt>";
                }
            }
            $xml .= "<Start>" . $this->format_rp_date($options['starting_at'] ? $options['starting_at'] :  time() + 60*60*24) . "</Start>";
            if (isset($options['email']))
                $xml .= "<EMail>{$options['email']}</EMail>";
            $billing_address = (isset($options['billing_address']) ? $options['billing_address'] : $options['address']);
            if ($billing_address)
                $xml .= '<BillTo>' . $this->add_address($options, $billing_address) . '</BillTo>';
            if (isset($options['shipping_address']))
                $xml .= '<ShipTo>' . $this->add_address($options, $options['shipping_address']) . '</ShipTo>';
            $xml .= "</RPData>";
        }
        $xml .= '</' . ucfirst($action) . '>';
        if (in_array($action, array('add', 'modify', 'reactivate', 'payment')))
            $xml .= '<Tender>' . $this->add_credit_card($credit_card, $options) . '</Tender>';
        if ($action != 'add')
            $xml .= "<ProfileID>" . $options['profile_id'] . "</ProfileID>";
        if ($action == 'inquiry')
            $xml .= "<PaymentHistory>" . ($options['history'] ? 'Y' : 'N') . '</PaymentHistory>';
        $xml .= '</RecurringProfile></RecurringProfiles>';
        return $this->build_request($xml);
    }

    protected function get_pay_period($options)
    {
        $this->required_options('pay_period', $options);
        
        switch (strtolower($options['pay_period'])) {
            case 'weekly':
                return 'Weekly';
            case 'biweekly':
            case 'bi-weekly':
                return 'Bi-weekly';
            case 'semimonthly':
            case 'semi-monthly':
                return 'Semi-monthly';
            case 'quadweekly':
            case 'quad-weekly':
            case 'fourweeks':
            case 'everyfourweeks':
                return 'Every four weeks';
            case 'monthly':
                return 'Monthly';
            case 'quarterly':
                return 'Quarterly';
            case 'semiyearly':
            case 'semi-yearly':
                return 'Semi-yearly';
            case 'yearly':
                return 'Yearly';
            default:
                return false;
        }
    }

    protected function format_rp_date($time)
    {
        if (is_int($time)) return strftime("%m%d%Y", $time);
        else return (string) $time;
    }
}
