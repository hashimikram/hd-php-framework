<?php
namespace HDFramework\src;

use libphonenumber\PhoneNumberUtil;

/**
 * Wrapper class for LibPhoneNumber library
 *
 * Configurations dependencies:<br />
 * <br />Release date: 04/06/2015
 *
 * @version 6.0
 * @author cornel
 * @package framework
 */
class HDLibPhoneNumber
{

    private static $instance;

    private static $phoneNumberUtil;

    private function __construct()
    {
        self::$phoneNumberUtil = PhoneNumberUtil::getInstance();
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isValidNumber($phoneNumber, $country = null)
    {
        $validNumber = false;
        try {
            $formatedPhoneNumber = self::$phoneNumberUtil->parse($phoneNumber, $country, null, true);
            $validNumber = self::$phoneNumberUtil->isValidNumber($formatedPhoneNumber);
        } catch (\Exception $e) {
            HDLog::AppLogMessage('HDLibPhoneNumber', 'isValidNumber', 'exception code', $e->getCode(), 3, 'L');
            HDLog::AppLogMessage('HDLibPhoneNumber', 'isValidNumber', 'exception message', $e->getMessage(), 3, 'L');
        }
        return $validNumber;
    }

    public function isPossibleNumber($phoneNumber, $country = null)
    {
        $validNumber = false;
        try {
            $formatedPhoneNumber = self::$phoneNumberUtil->parse($phoneNumber, $country, null, true);
            $validNumber = self::$phoneNumberUtil->isPossibleNumber($formatedPhoneNumber);
        } catch (\Exception $e) {
            HDLog::AppLogMessage('HDLibPhoneNumber', 'isPossibleNumber', 'exception code', $e->getCode(), 3, 'L');
            HDLog::AppLogMessage('HDLibPhoneNumber', 'isPossibleNumber', 'exception message', $e->getMessage(), 3, 'L');
        }
        return $validNumber;
    }

    public function getCountryCodeFromPhoneNumber($phoneNumber)
    {
        try {
            $formatedPhoneNumber = self::$phoneNumberUtil->parse('+' . $phoneNumber, null, null, true);
            $regionCode = self::$phoneNumberUtil->getRegionCodeForNumber($formatedPhoneNumber);
            return $regionCode;
        } catch (\Exception $e) {
            HDLog::AppLogMessage('HDLibPhoneNumber', 'getCountryCodeFromPhoneNumber', 'exception code', $e->getCode(), 3, 'L');
            HDLog::AppLogMessage('HDLibPhoneNumber', 'getCountryCodeFromPhoneNumber', 'exception message', $e->getMessage(), 3, 'L');
            return $e->getMessage();
        }
    }

    public function getNationalSignificantNumber($phoneNumber, $country)
    {
        $formatedPhoneNumber = self::$phoneNumberUtil->parse($phoneNumber, $country, null, true);
        return self::$phoneNumberUtil->getNationalSignificantNumber($formatedPhoneNumber);
    }

    public function formatPhoneNumber($phoneNumber, $numberFormat)
    {
        $phoneNumber = strval($phoneNumber);
        $country = $this->getCountryCodeFromPhoneNumber($phoneNumber);
        return self::$phoneNumberUtil->format(self::$phoneNumberUtil->parse($phoneNumber, $country, null, true), $numberFormat);
    }
}