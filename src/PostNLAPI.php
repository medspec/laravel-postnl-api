<?php

namespace MedSpec\LaravelPostNLAPI;

use MedSpec\LaravelPostNLAPI\Entities\Address;
use MedSpec\LaravelPostNLAPI\Entities\Customer;
use MedSpec\LaravelPostNLAPI\Entities\LabellingMessage;
use MedSpec\LaravelPostNLAPI\Entities\Shipments;
use MedSpec\LaravelPostNLAPI\Services\Client;
use MedSpec\LaravelPostNLAPI\Services\Converter;

class PostNLAPI
{
    protected $customer;

    public function __construct($customer = null)
    {
        if (null === $customer) {
            $this->customer = Customer::create([
                'CollectionLocation' => config('postnlapi.customer.location'),
                'CustomerCode'       => config('postnlapi.customer.code'),
                'CustomerNumber'     => config('postnlapi.customer.number'),
                'Address'            => Address::create([
                    'AddressType' => '02',
                    'City'        => config('postnlapi.customer.address.city'),
                    'CompanyName' => config('postnlapi.customer.address.companyName'),
                    'Countrycode' => config('postnlapi.customer.address.countryCode'),
                    'HouseNr'     => config('postnlapi.customer.address.streetNr'),
                    'Street'      => config('postnlapi.customer.address.street'),
                    'Zipcode'     => config('postnlapi.customer.address.postalcode'),
                ]),
                'Email'              => config('postnlapi.customer.email'),
            ]);
        } else {
            $this->customer = $customer;
        }
    }

    public function generateBarcode(
        $type = '3S',
        $serie = '000000000-999999999'
    ) {
        $client = new Client();
        $barcode = $client::get('shipment/v1_1/barcode?Type='.$type.'&Serie='.$serie, $this->customer);

        return $barcode['Barcode'];
    }

    public function generateLabel(
        $barcode,
        $printerType,
        $address,
        $contact,
        $deliveryAddress,
        $productCodeDelivery,
        $reference,
        $remark,
        $sender = null
    ) {
        $client = new Client();

        if($sender){
            $customer = $sender;
        } else {
            $customer = $this->customer;
        }

        $data = Converter::Label(
            $customer,
            LabellingMessage::create([
                'Printertype' => $printerType,
            ]),
            Shipments::create([
                'Addresses'             => $address,
                'Barcode'               => $barcode,
                'Contacts'              => $contact,
                'DeliveryAddress'       => $deliveryAddress,
                'ProductCodeDelivery'   => $productCodeDelivery,
                'Reference'             => $reference,
                'Remark'                => $remark,
            ])
        );

        $label = $client::post('shipment/v2_2/label?confirm=true', $data, $this->customer);
        
        if ($label) {
            return $label['ResponseShipments'][0]['Labels'];
        }

        return false;
    }

    public function getShipmentStatus( $barcode ) {
        $client = new Client();
        $response = $client::get('shipment/v2/status/barcode/'. $barcode, null);
        return $response ?? [];
    }

    public function nearestLocations(
        $countryCode = 'NL',
        $postalCode = '1111AA',
        $deliveryOptions = 'PG'
    ) {
        $client = new Client();
        $locations = $client::get('shipment/v2_1/locations/nearest?CountryCode='.$countryCode.'&PostalCode='.$postalCode.'&DeliveryOptions='.$deliveryOptions, $this->customer);

        return $locations['GetLocationsResult']['ResponseLocation'];
    }
}
