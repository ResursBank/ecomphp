<?php

namespace TorneLIB;

/**
 * TorneLIB Smarty Library Utilizer. Tested with v3.1.28
 * Class TorneLIB_Smarty
 * @package TorneLIB
 */
class TorneLIB_Smarty {

    /*
     * Core Library Handler.
     *
     * Normally we do not put libraries inside TorneLIB, since they can be autoloaded from other directories
     * but this is library function has been decided to be a part of the core system.
     */

    private $template_dir = "";
    protected $Link = null;

    function __construct($PathLoader = '') {
        if (file_exists($PathLoader . "/libs") && file_exists($PathLoader . "/libs/Smarty.class.php")) {
            if (!defined('SMARTY_DIR')) {
                define('SMARTY_DIR', $PathLoader . "/libs/");
            }
            if (file_exists(SMARTY_DIR . "Smarty.class.php")) {
                require_once(SMARTY_DIR . "Smarty.class.php");
                $this->Plug = new \Smarty();
                if (defined('TEMPLATES')) {
                    $this->Plug->template_dir = TEMPLATES;
                    $this->template_dir = preg_replace("/\/$/", '', TEMPLATES);
                }
                if (file_exists("/tmp")) {$this->Plug->compile_dir = "/tmp";}
                if (defined('TMP') && file_exists(TMP)) {$this->Plug->compile_dir = TMP;}
                if (defined('SMARTY_COMPILE_DIR')) { $this->Plug->compile_dir = SMARTY_COMPILE_DIR; }
            }
        } else {
            // Last resort for Smarty
            if (!defined('SMARTY_DIR') && realpath(TORNELIB_LIBS . "/" . $PathLoader . "/libs/")) {
                define('SMARTY_DIR', TORNELIB_LIBS . "/" . $PathLoader . "/libs/");
                if (file_exists(SMARTY_DIR . "Smarty.class.php")) {
                    require_once(SMARTY_DIR . "Smarty.class.php");
                    $this->Plug = new \Smarty();
                    if (defined('TEMPLATES')) {
                        $this->Plug->template_dir = TEMPLATES;
                        $this->template_dir = preg_replace("/\/$/", '', TEMPLATES);
                    }
                    if (file_exists("/tmp")) {$this->Plug->compile_dir = "/tmp";}
                    if (defined('TMP') && file_exists(TMP)) {$this->Plug->compile_dir = TMP;}
                    if (defined('SMARTY_COMPILE_DIR')) { $this->Plug->compile_dir = SMARTY_COMPILE_DIR; }
                }
            }
        }
    }

    /**
     * Bypass calls to smarty
     * @param $name
     * @param $arguments
     * @throws TorneLIB_Exception
     */
    function __call($name, $arguments) {
        try {
            $returnedCall = @call_user_func_array(array($this->Plug, $name), $arguments);
        } catch (\Exception $e) {
            throw new TorneLIB_Exception($e->getMessage(), $e->getCode(), __CLASS__ . "\\" . $name);
        }
    }

    /**
     * TornevallWEB v4 Function Clone
     *
     * @param string $fileName
     * @param array $Variables
     * @param bool $isFile
     * @return null|void
     * @throws TorneLIB_Exception
     */
    public function EvalTemplate($fileName = '', $Variables = array(), $isFile = true) {
        $RealFileName = $fileName;
        $returnedString = null;
        /* Silently suppress everything passed here, except for catchable errors */
        try {
            $this->Plug->assign($Variables);
            if (file_exists($fileName)) { $RealFileName = $fileName; }
            if (!file_exists($RealFileName) && !empty($this->template_dir) && file_exists($this->template_dir . "/" . $fileName)) {$RealFileName = $this->template_dir . "/" . $fileName;}

            if ($isFile) {
                if (file_exists($RealFileName)) {
                    return $this->Plug->fetch($RealFileName);
                } else {
                    $protectedTemplateInfo = pathinfo($fileName);
                    throw new TorneLIB_Exception("Template '".$protectedTemplateInfo['filename']."' does not exist", 404, __CLASS__);
                }
            } else {
                $compileDir = preg_replace("/\/$/", '', $this->Plug->compile_dir);
                if (!empty($compileDir) && file_exists($this->Plug->compile_dir)) {
                    $uniqueTemplateFile = uniqid(md5(microtime(true))) . ".tpl";
                    file_put_contents(preg_replace("/\/$/", '', $compileDir . "/" . $uniqueTemplateFile), $fileName);
                    $returnedString = $this->Plug->fetch($compileDir . "/" . $uniqueTemplateFile);
                    @unlink($compileDir . "/" . $uniqueTemplateFile);
                }
                return $returnedString;
            }
        } catch (\Exception $e) {
            throw new TorneLIB_Exception($e->getMessage(), $e->getCode(), "TorneLIB-SmartyRender");
        }
    }
}
