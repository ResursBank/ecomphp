<?php

namespace TorneLIB;

/**
 * Class TorneLIB_Crypto Encryption and encoding
 * 
 * @package TorneLIB
 */
class TorneLIB_Crypto {
    
    private $aesKey = "";
    private $aesIv = "";
    
    function __construct()
    {
        $this->setAesIv(md5("TorneLIB Default IV - Please Change this"));
        $this->setAesKey(md5("TorneLIB Default KEY - Please Change this"));
    }

    /**
     * Create a password or salt with different kind of complexity
     *
     * 1 = A-Z
     * 2 = A-Za-z
     * 3 = A-Za-z0-9
     * 4 = Full usage
     * 5 = Full usage and unrestricted $setMax
     * 6 = Complexity uses full charset of 0-255
     *
     * @param int $complexity
     * @param int $setMax Max string length to use
     * @param bool $webFriendly Set to true works best with the less complex strings as it only removes characters that could be mistaken by another character (O,0,1,l,I etc)
     * @return string
     */
    /**
     * @return mixed|null|string
     */
    function mkpass ($complexity = 4, $setMax = 8, $webFriendly = false)
    {
        $returnString = null;
        $characterListArray = array(
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'abcdefghijklmnopqrstuvwxyz',
            '0123456789',
            '!@#$%*?'
        );
        // Set complexity to no limit if type 6 is requested
        if ($complexity == 6)
        {
            $characterListArray = array('0'=>'');
            for ($unlim = 0; $unlim <= 255 ; $unlim++) {
                $characterListArray[0] .= chr($unlim);
            }
            if ($setMax == null) {$setMax = 15;}
        }
        // Backward-compatibility in the complexity will still give us captcha-capabilities for simpler users
        $max = 8;       // Longest complexity
        if ($complexity == 1) {unset($characterListArray[1], $characterListArray[2], $characterListArray[3]);$max = 6;}
        if ($complexity == 2) {unset($characterListArray[2], $characterListArray[3]); $max = 10;}
        if ($complexity == 3) {unset($characterListArray[3]); $max = 10;}
        if ($setMax > 0) {$max = $setMax;}
        $chars = array();
        $numchars = array();
        $equalityPart = ceil($max / count($characterListArray));
        for ($i = 0 ; $i < $max ; $i++)
        {
            $charListId = rand(0, count($characterListArray)-1);
            if (!isset($numchars[$charListId])) {
                $numchars[$charListId] = 0;
            }
            $numchars[$charListId] ++;
            $chars[] = $characterListArray[$charListId]{mt_rand(0, (strlen($characterListArray[$charListId]) - 1))};
        }
        shuffle($chars);
        $returnString = implode("", $chars);
        if ($webFriendly) {
            // The lazyness
            $returnString = preg_replace("/[+\/=IG0ODQR]/i", "", $returnString);
        }
        return $returnString;
    }

    /**
     * Set up key for aes encryption.
     *
     * @param $useKey
     */
    public function setAesKey($useKey) {
        $this->aesKey = md5($useKey);
    }

    /**
     * Set up ip for aes encryption
     *
     * @param $useIv
     */
    public function setAesIv($useIv) {
        $this->aesIv = md5($useIv);
    }

    public function aesEncrypt($decryptedContent = "", $asBase64 = true) {
        $useKey = $this->aesKey;
        $useIv = $this->aesIv;
        $contentData = $decryptedContent;
        if ($useKey == md5(md5("TorneLIB Default IV - Please Change this")) || $useIv == md5(md5("TorneLIB Default IV - Please Change this"))) {
            throw new TorneLIB_Exception("Current encryption key and iv is not allowed to use.", TORNELIB_EXCEPTIONS::TORNELIB_CRYPTO_KEY_EXCEPTION, __FUNCTION__);
        }
        if (is_string($decryptedContent)) {
            $contentData = utf8_encode($decryptedContent);
        }
        $binEnc = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $useKey, $contentData, MCRYPT_MODE_CBC, $useIv);
        $baseEncoded = $this->base64url_encode($binEnc);
        if ($asBase64) {
            return $baseEncoded;
        } else {
            return $binEnc;
        }
    }
    public function aesDecrypt($encryptedContent = "", $asBase64 = true) {
        $useKey = $this->aesKey;
        $useIv = $this->aesIv;
        if ($useKey == md5(md5("TorneLIB Default IV - Please Change this")) || $useIv == md5(md5("TorneLIB Default IV - Please Change this"))) {
            throw new TorneLIB_Exception("Current encryption key and iv is not allowed to use.", TORNELIB_EXCEPTIONS::TORNELIB_CRYPTO_KEY_EXCEPTION, __FUNCTION__);
        }
        $contentData = $encryptedContent;
        if ($asBase64) { $contentData = $this->base64url_decode($encryptedContent); }
        $decryptedOutput = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $useKey, $contentData, MCRYPT_MODE_CBC, $useIv));
        return $decryptedOutput;
    }


    /**
     * base64_encode
     *
     * @param $data
     * @return string
     */
    public function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    /**
     * base64_decode
     *
     * @param $data
     * @return string
     */
    public function base64url_decode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}