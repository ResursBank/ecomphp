<?php

namespace TorneLIB;

/**
 * Class TorneLIB_DNSBL Tornevall Networks DNSBL Helper v5.0
 * Currently in ALPHA STATE - NO PUBLIC RELEASES HAS BEEN MADE - DO NOT RUN THIS IN PRODUCTION ENVIRONMENTS (yet)
 * Updated 2016-07-09
 *
 * @package TorneLIB
 * @link http://docs.tornevall.net/x/EYBu Documentation for the API
 * @link http://developer.tornevall.net/apigen/TorneLIB-5.0/class-TorneLIB.TorneLIB_DNSBL.html Full library reference
 *
 */
class TorneLIB_DNSBL {

    private $BL_CLIENT_VERSION = "5.0.0-20160709";  /* Currently not in production state - Use at your own risk */

    /**
     * @var TORNEVALL_DNSBL_BITS The BitClass containing information about the current bit array used by Tornevall Networks and FraudBL
     */
    private $BitClass;

    /**
     * @var TorneLIB_Network The network library used for translating ipv4 and ipv6-addresses to arpa-like format, used by the DNSBL
     */
    private $NETLIB;
    /**
     * @var array Current representative zones used by DNSBL 5.0 (Yes, opm.tornevall.org is deprecated, still in use but excluded from here since the resolver returns identical data)
     */
    private $blZones = array('bl.fraudbl.org', 'dnsbl.tornevall.org');

    /**
     * @var object Tornevall Networks CURL-LIB if exists
     */
    private $CURLLIB;

    /**
     * DNSBL constructor.
     */
    function __construct()
    {
        $this->BitClass = new \TorneLIB\TORNEVALL_DNSBL_BITS();
        if (class_exists('TorneLIB_Network')) {
            $this->NETLIB = new \TorneLIB\TorneLIB_Network();
        } else {
            $this->NETLIB = new TorneLIB_Network_Lite_DNSBL();
        }
        if (class_exists('Tornevall_cURL')) {
            $this->CURLLIB = new Tornevall_cURL();
        }
    }

    /**
     * Figure out which version Tornevall DNSBL/FraudBL is running. This method resolves a hostname and returns it's TXT-entry if it exists.
     *
     * @param int $blZone
     * @return string
     * @throws \Exception
     */
    public function getBlVersion($blZone = TORNEVALL_DNSBL_ZONES::BLZONE_TORNEVALL_DNSBL) {
        $blacklistVersion = "";
        if ($blZone == TORNEVALL_DNSBL_ZONES::BLZONE_FRAUDBL) {
            if (checkdnsrr("bl-version.fraudbl.org", "TXT")) {
                $blacklistVersionQuery = dns_get_record("bl-version.fraudbl.org", DNS_TXT);
            } else {
                $blacklistVersionQuery = gethostbyname("bl-version.fraudbl.org");
            }
        }
        if ($blZone == TORNEVALL_DNSBL_ZONES::BLZONE_TORNEVALL_DNSBL) {
            if (checkdnsrr("bl-version.tornevall.org", "TXT")) {
                $blacklistVersionQuery = dns_get_record("bl-version.tornevall.org", DNS_TXT);
            } else {
                $blacklistVersionQuery = gethostbyname("bl-version.tornevall.org");
            }
        }
        if (is_array($blacklistVersionQuery)) {
            $blacklistVersion = $blacklistVersionQuery[0]['txt'];
            return $blacklistVersion;
        } else {
            /*
             * If the resolver don't give as a TXT response, we'll try to fail over.
             */
            if (!empty($blacklistVersionQuery)) {
                $failoverQuery = explode(".", $blacklistVersionQuery);
                unset($failoverQuery);
                if (isset($failoverQuery[3])) {
                    if ($failoverQuery[3] == "1") {
                        /*
                         * If we receive a 127.0.0.1 from this host, the version can not be older than the 2006.
                         */
                        $blacklistVersion = "1.0.0.2006";
                    } else {
                        $blacklistVersion = $blacklistVersionQuery;
                    }
                }
            }
        }
        if (empty($blacklistVersion)) {
            throw new \Exception("Could not figure out current DNSBL version", TORNEVALL_DNSBL_EXCEPTIONS::TORNELIB_DNSBL_EXCEPTION_GENERAL);
        }
        return $blacklistVersion;
    }

    /**
     * Returns true if the address requested is "sane". This is put here just to prevent defective resolvers that returns the value of 127 when they should not.
     *
     * @param $ipAddr
     * @param $matchAddr
     * @return bool
     */
    private function isSaneAddress($ipAddr, $matchAddr) {
        if ($this->NETLIB->getArpaFromAddr($ipAddr, true) !== \TorneLIB\TorneLIB_Network_IP::IPTYPE_NONE) {
            $requestAddress = explode(".", $ipAddr);
            $matchAddress = explode(".", $matchAddr);
            if (isset($requestAddress[3]) && isset($matchAddress[3])) {
                unset($requestAddress[3], $matchAddress[3]);
                /*
                 * Resolver failed if those two implosions outputs the same result.
                 */
                if (implode(".", $requestAddress) == implode(".", $matchAddress)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Returns a list of where the ipaddress is blacklisted. Use $getListedTypes = true if you want to get the blacklisting types (bitmasks) from the resolved host.
     *
     * @param $ipAddr
     * @param bool $getListedTypes
     * @return array
     */
    public function resolveBlacklist($ipAddr, $getListedTypes = false) {
        $listedAt = array();
        if ($this->NETLIB->getArpaFromAddr($ipAddr, true) !== \TorneLIB\TorneLIB_Network_IP::IPTYPE_NONE) {
            $arpaResolve = $this->NETLIB->getArpaFromAddr($ipAddr);
            foreach ($this->blZones as $blZone) {
                $hostName = $arpaResolve . "." . $blZone;
                $resolveAddr = gethostbyname($hostName);

                if (preg_match("/^127/", $resolveAddr) && $this->isSaneAddress($resolveAddr, $ipAddr)) {
                    $lastBit = explode(".", $resolveAddr);
                    /*
                     * Making sure that the bit value is really not 0, since 0 is not a proper value. Also making sure that the requested ip address does not equal
                     * to the resolved address since that may indicate on faulty resolvers.
                     */
                    if (isset($lastBit[3]) && $lastBit[3] > 0) {
                        if (!$getListedTypes) {
                            $listedAt[$blZone] = $resolveAddr;
                        } else {
                            $listedAt[$blZone] = $this->getBitArray($lastBit[3]);
                        }
                    }
                }
            }
        }
        return $listedAt;
    }

    /**
     * Returns a basic value if the address is listed anywhere (use resolveBlacklist() if you need to use the details yourself)
     *
     * @param $ipaddr
     * @return bool
     */
    public function isListed($ipaddr) {
        return $this->isListedApi($ipaddr);
        if (count($this->resolveBlacklist($ipaddr))) {
            return true;
        }
        return false;
    }
    public function isListedApi($ipAddr = '') {
        if ($this->NETLIB->getArpaFromAddr($ipAddr, true) !== \TorneLIB\TorneLIB_Network_IP::IPTYPE_NONE) {
            /*
             * First edition of the API call. MUST BE FIXED! (TODO!)
             */
            $isListedResponse = file_get_contents("https://api.tornevall.net/2.0/dnsbl/isListed/" . $ipAddr);
            if (!empty($isListedResponse)) {
                $testListResponse = json_decode($isListedResponse);

                if (isset($testListResponse->response->isListedResponse))
                {
                    if ($testListResponse->response->isListedResponse != "1") {
                        return false;
                    } else {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Get an array with the registered bits for a specific bit value
     *
     * Example: The output of 127.0.0.80 (80) should return BIT_ABUSE and BIT_SPAM
     *
     * @param int $bitValue
     * @return array
     */
    public function getBitArray($bitValue = 0) {
        return $this->BitClass->getBitArray(intval($bitValue));
    }

    /**
     * Finds out if a bitmasked value is located in a bitarray
     *
     * @param int $requestedBitValue
     * @param int $matchWith
     * @return bool
     */
    public function isBit($requestedBitValue=0, $matchWith=0) {
        return $this->BitClass->isBit($requestedBitValue, $matchWith);
    }

}

/**
 * Class TORNEVALL_DNSBL_BITS - Bitmasks used by Tornevall DNSBL with FraudBL
 *
 * @package TorneLIB
 */
class TORNEVALL_DNSBL_BITS {

    /*
     * Registered bits from dnsbl.tornevall.org (For full reference, see http://docs.tornevall.net/x/AoA_/)
     */
    const BIT_REPORTED = 1;                 /* IP has been reported */
    const BIT_CONFIRMED = 2;                /* IP has been confirmed as working proxy */
    const BIT_FRAUDBL = 4;                  /* Phishing (Fraudible) */
    const BIT_EMPTY = 8;                    /* Empty response - IP was tested, but was never returning anything */
    const BIT_SPAM = 16;                    /* E-Mail spam */
    const BIT_ANONYMOUS = 32;               /* IP is tested and is fully functional but there is a second entry point */
    const BIT_ABUSE = 64;                   /* Abusive host, webspam, portscanner, etc */
    const BIT_DIFFERENTSTATE = 128;         /* IP has a different anonymous-state (web-based proxies, like anonymouse, etc) */

    /**
     * Get and return active bits from a bitvalue-representative
     *
     * @param int $bitValue
     * @return array
     */
    public function getBitArray($bitValue = 0)
    {
        $returnBitList = array();
        if ($this->isBit(self::BIT_REPORTED, $bitValue)) { $returnBitList[] = "BIT_REPORTED"; }
        if ($this->isBit(self::BIT_CONFIRMED, $bitValue)) { $returnBitList[] = "BIT_CONFIRMED"; }
        if ($this->isBit(self::BIT_FRAUDBL, $bitValue)) { $returnBitList[] = "BIT_FRAUDBL"; }
        if ($this->isBit(self::BIT_EMPTY, $bitValue)) { $returnBitList[] = "BIT_EMPTY"; }
        if ($this->isBit(self::BIT_SPAM, $bitValue)) { $returnBitList[] = "BIT_SPAM"; }
        if ($this->isBit(self::BIT_ANONYMOUS, $bitValue)) { $returnBitList[] = "BIT_ANONYMOUS"; }
        if ($this->isBit(self::BIT_ABUSE, $bitValue)) { $returnBitList[] = "BIT_ABUSE";}
        if ($this->isBit(self::BIT_CONFIRMED, $bitValue)) { $returnBitList[] = "BIT_DIFFERENTSTATE"; }
        return $returnBitList;
    }

    /**
     * Finds out if a bitmasked value is located in a bitarray
     *
     * @param int $requestedBitValue
     * @param int $matchWith
     * @return bool
     */
    public function isBit($requestedBitValue = 0, $matchWith = 0)
    {
        preg_match_all("/\d/", sprintf("%08d", decbin($matchWith)), $bitArray);
        for ($bitCount = count($bitArray[0]); $bitCount >= 0; $bitCount--) {
            if (isset($bitArray[0][$bitCount])) {
                if ($matchWith & pow(2, $bitCount)) {
                    if ($requestedBitValue == pow(2, $bitCount)) { return true; }
                }
            }
        }
    }
}

if (!class_exists('TorneLIB\TorneLIB_Network_Lite_DNSBL')) {
    /**
     * Class TorneLIB_Network_Lite_DNSBL - A light version of TorneLIB_Networks for making this library package library independent
     *
     * @package TorneLIB
     */
    class TorneLIB_Network_Lite_DNSBL
    {
        /**
         * Get reverse octets from ip address
         *
         * @param string $ipAddr
         * @param bool $returnIpType
         * @return int|string
         */
        public function getArpaFromAddr($ipAddr = '', $returnIpType = false)
        {
            if (long2ip(ip2long($ipAddr)) == "0.0.0.0") {
                if ($returnIpType === true) {
                    $vArpaTest = $this->getArpaFromIpv6($ipAddr);    // PHP 5.3
                    if (!empty($vArpaTest)) {
                        return TorneLIB_Network_IP::IPTYPE_V6;
                    } else {
                        return TorneLIB_Network_IP::IPTYPE_NONE;
                    }
                } else {
                    return $this->getArpaFromIpv6($ipAddr);
                }
            } else {
                if ($returnIpType) {
                    return TorneLIB_Network_IP::IPTYPE_V4;
                } else {
                    return $this->getArpaFromIpv4($ipAddr);
                }
            }
        }

        /**
         * Translate ipv6 address to reverse octets
         *
         * @param string $ip
         * @return string
         */
        public function getArpaFromIpv6($ip = '::')
        {
            $unpack = @unpack('H*hex', inet_pton($ip));
            $hex = $unpack['hex'];
            return implode('.', array_reverse(str_split($hex)));
        }

        /**
         * Translate ipv4 address to reverse octets
         *
         * @param string $ipAddr
         * @return string
         */
        public function getArpaFromIpv4($ipAddr = '127.0.0.1')
        {
            return implode(".", array_reverse(explode(".", $ipAddr)));
        }

        /**
         * Translate ipv6 reverse octets to ipv6 address
         *
         * @param string $arpaOctets
         * @return string
         */
        public function getIpv6FromOctets($arpaOctets = '0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0')
        {
            return @inet_ntop(pack('H*', implode("", array_reverse(explode(".", preg_replace("/\.ip6\.arpa$|\.ip\.int$/", '', $arpaOctets))))));
        }
    }
}

/**
 * Class TORNEVALL_DNSBL_ZONES used by the method getBlVersion()
 *
 * @package TorneLIB
 */
class TORNEVALL_DNSBL_ZONES {

    /**
     * dnsbl.tornevall.org and friends (the default value)
     */
    const BLZONE_TORNEVALL_DNSBL = 0;

    /**
     * fraudbl.org
     */
    const BLZONE_FRAUDBL = 1;
}

/**
 * Class TORNEVALL_DNSBL_EXCEPTIONS
 * 
 * This is a part of tornevall_exception.php but it has been made independent
 * 
 * @package TorneLIB
 */
class TORNEVALL_DNSBL_EXCEPTIONS {
    const TORNELIB_DNSBL_EXCEPTION_GENERAL = 1005;
}