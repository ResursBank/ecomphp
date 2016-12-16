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