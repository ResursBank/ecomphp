<?php

namespace TorneLIB;
use TorneAPIv2\TorneAPIException;

/**
 * Class TorneLIB_JPGraph - A tiny JPGraph library handler
 *
 * @package TorneLIB
 */
class TorneLIB_JPGraph extends TorneLIB_Pluggable {

    protected $JPGRAPH = null;
    protected $JPGRAPH_ARRAY = array();
    protected $TestedWith = "4.0.1";
    private $LOADED = false;

    private $ImageWritePath;
    private $ImageUrl;

    public $ResolutionWidth = 640;
    public $ResolutionHeight = 480;

    /**
     * TorneLIB_JPGraph constructor.
     * @param null $jsonStructure
     */
    function __construct($jsonStructure = null, $WritePath = "", $Url = "") {
        parent::__construct();
        if (!empty($jsonStructure)) {
            $this->JPGRAPH_ARRAY = $this->ApplyArray($jsonStructure);
        }
        if (!empty($this->PluggableAutoPath) && file_exists($this->PluggableAutoPath)) {
            if (file_exists($this->PluggableAutoPath . "/jpgraph")) {
                $this->JPGRAPH_PATH = $this->PluggableAutoPath . "/jpgraph";
                /*
                 * If src exists, this archive has just been unpacked.
                 */
                if (file_exists($this->JPGRAPH_PATH . "/src")) {
                    $this->JPGRAPH_PATH .= "/src";
                }
            }
        }
        if (!empty($this->JPGRAPH_PATH) && file_exists($this->JPGRAPH_PATH . "/jpgraph.php")) {
            require_once($this->JPGRAPH_PATH . "/jpgraph.php");
            $this->LOADED = true;
        }
        if (!empty($WritePath)) {
            $this->setWritePath($WritePath);
        }
        if (!empty($Url)) {
            $this->setUrl($Url);
        }
    }

    /**
     * Where to write the graphics - without this, JPGraph Stroke() will try to make immediate output
     *
     * @param string $WritePath
     */
    public function setWritePath($WritePath = "") {
        if (file_exists($WritePath)) {
            $this->ImageWritePath = preg_replace("/\/$/", '', $WritePath);
        }
    }

    /**
     * Set up the URL for where the WritePath has been set. This makes it possible to write the graphics wherever you wish to have it and create dynamic urls.
     *
     * @param string $Url
     */
    public function setUrl($Url = "") {
        $this->ImageUrl = preg_replace("/\/$/", '', $Url);
    }

    /**
     * Make sure JPGraph has been loaded before using
     *
     * @return bool
     */
    private function HasJpgraph() {
        return $this->LOADED;
    }

    public function getCaptchaString($CaptchaType = CAPTCHA_TYPES::CAPTCHA_JPGRAH) {
        if ($CaptchaType == CAPTCHA_TYPES::CAPTCHA_JPGRAH) {
            require_once($this->JPGRAPH_PATH . "/jpgraph_antispam.php");
            $AntiSpam = new \AntiSpam();
            $AntiSpamChars = $AntiSpam->Rand(8);
            return $AntiSpamChars;
        }
    }
    public function getCaptchaImage($setCharacters, $CaptchaType = CAPTCHA_TYPES::CAPTCHA_JPGRAH) {
        if ($CaptchaType == CAPTCHA_TYPES::CAPTCHA_JPGRAH) {
            require_once($this->JPGRAPH_PATH . "/jpgraph_antispam.php");
            //header("Content-Type: image/jpeg", true);
            $AntiSpam = new \AntiSpam();
            $AntiSpam->Set($setCharacters);
            if ($AntiSpam->Stroke() === "false") {
                throw new TorneAPIException("Failed generating captcha", 500);
            }
            die();
        }
    }

    /**
     * Create JPGraph Images from rendered json-data
     *
     * @param int $GraphType
     * @return string|void
     */
    public function getGraph($GraphType = JPGRAPH_TYPES::GRAPH_LINE) {
        if (!$this->HasJpgraph()) { return; }
        if ($GraphType <= 0) { return; }
	    if (!isset($this->JPGRAPH_ARRAY->values)) {return;}
        $UseValues = $this->TranslateArray($this->JPGRAPH_ARRAY->values);
        if (!isset($this->JPGRAPH_ARRAY->values) || !is_object($this->JPGRAPH_ARRAY->values)) { return; }
        if (isset($this->JPGRAPH_ARRAY->height)) { $this->ResolutionHeight = $this->JPGRAPH_ARRAY->height; }
        if (isset($this->JPGRAPH_ARRAY->width)) {
            $this->ResolutionWidth = $this->JPGRAPH_ARRAY->width;
        }
        $this->JPGRAPH = new \Graph($this->ResolutionWidth, $this->ResolutionHeight);
        if ($GraphType == JPGRAPH_TYPES::GRAPH_LINE) {
            require_once($this->JPGRAPH_PATH . "/jpgraph_line.php");
            $this->JPGRAPH->SetScale("textlin");
        }


        /*
         * Start setting some defaults. Lets change this later on.
         */

        if (!isset($this->JPGRAPH_ARRAY->theme) || $this->JPGRAPH_ARRAY->theme == JPGRAPH_THEMES::THEME_UNIVERSAL) {
            $theme_class = new \UniversalTheme();
        }

        $this->JPGRAPH->SetTheme($theme_class);
        $this->JPGRAPH->img->SetAntiAliasing(true);
        $this->JPGRAPH->SetBox(false);
        $this->JPGRAPH->img->SetAntiAliasing();
        $this->JPGRAPH->yaxis->HideZeroLabel(false);
        $this->JPGRAPH->yaxis->HideLine(false);
        $this->JPGRAPH->yaxis->HideTicks(false,false);

        if (isset($this->JPGRAPH_ARRAY->legend)) {
            $this->JPGRAPH->legend->Pos(
                isset($this->JPGRAPH_ARRAY->legend[0]) ? $this->JPGRAPH_ARRAY->legend[0] : 0.05,
                isset($this->JPGRAPH_ARRAY->legend[1]) ? $this->JPGRAPH_ARRAY->legend[1] : 0.9,
                isset($this->JPGRAPH_ARRAY->legend[2]) ? $this->JPGRAPH_ARRAY->legend[2] : 'right',
                isset($this->JPGRAPH_ARRAY->legend[3]) ? $this->JPGRAPH_ARRAY->legend[3] : 'top'
            );
        } else {
            $this->JPGRAPH->legend->Pos(0.05, 0.9, 'right', 'top');
        }

        $this->JPGRAPH->xgrid->Show();
        if (!$UseValues['RecursiveArray']) {
            $this->JPGRAPH->xaxis->SetTickLabels($UseValues['Keys']);
        } else {
            $KeyCollection = array_pop($UseValues['Keys']);
            $this->JPGRAPH->xaxis->SetTickLabels($KeyCollection);
        }
        $this->JPGRAPH->xaxis->SetLabelAngle(0);

        if (isset($this->JPGRAPH_ARRAY->x->interval)) {$this->JPGRAPH->xaxis->SetTextLabelInterval($this->JPGRAPH_ARRAY->x->interval);}
        if (isset($this->JPGRAPH_ARRAY->x->angle)) {$this->JPGRAPH->xaxis->SetLabelAngle($this->JPGRAPH_ARRAY->x->angle);}
        if (isset($this->JPGRAPH_ARRAY->y->angle)) {$this->JPGRAPH->xaxis->SetLabelAngle($this->JPGRAPH_ARRAY->y->angle);}
        if (isset($this->JPGRAPH_ARRAY->y->margin)) {$this->JPGRAPH->xaxis->SetLabelMargin($this->JPGRAPH_ARRAY->y->margin);}
        if (isset($this->JPGRAPH_ARRAY->x->margin)) {$this->JPGRAPH->xaxis->SetLabelMargin($this->JPGRAPH_ARRAY->x->margin);}

        $Marker = MARK_SQUARE;
        if (isset($this->JPGRAPH_ARRAY->mark)) {
            if ($this->JPGRAPH_ARRAY->mark == "filledcircle") {
                $Marker = MARK_FILLEDCIRCLE;
            } else if ($this->JPGRAPH_ARRAY->mark == "circle") {
                $Marker = MARK_CIRCLE;
            } else if ($this->JPGRAPH_ARRAY->mark == "cross") {
                $Marker = MARK_CROSS;
            } else if ($this->JPGRAPH_ARRAY->mark == "diamond") {
                $Marker = MARK_DIAMOND;
            } else if ($this->JPGRAPH_ARRAY->mark == "dtriangle") {
                $Marker = MARK_DTRIANGLE;
            }
        }


        $this->JPGRAPH->xgrid->SetColor('#E3E3E3');

        if (isset($this->JPGRAPH_ARRAY->title)) { $this->JPGRAPH->title->Set($this->JPGRAPH_ARRAY->title); }
        if (isset($this->JPGRAPH_ARRAY->backgroundImage)) { $this->JPGRAPH->title->Set($this->JPGRAPH_ARRAY->backgroundImage); }

        $UseColor = "#6495ED";
        if ($GraphType == JPGRAPH_TYPES::GRAPH_LINE) {
            $this->JPGRAPH->xgrid->SetLineStyle("solid");
            if (!$UseValues['RecursiveArray']) {
                $LinePlot = new \LinePlot($UseValues['Values']);
                $this->JPGRAPH->Add($LinePlot);
                $LinePlot->mark->SetType($Marker, '', 0.5);
                $LinePlot->mark->SetColor("#6495ED");
                $LinePlot->mark->SetFillColor("#6495ED");
                $LinePlot->SetColor("#6495ED");
            } else {
                if (is_array($UseValues['Values'])) {
                    foreach ($UseValues['Values'] as $ValueKey => $ValueArray) {
                        $LinePlot = new \LinePlot($ValueArray);
                        $this->JPGRAPH->Add($LinePlot);
                        if (isset($this->JPGRAPH_ARRAY->colors) && isset($this->JPGRAPH_ARRAY->colors->$ValueKey)) {
                            $UseColor = $this->JPGRAPH_ARRAY->colors->$ValueKey;
                        }
                        $LinePlot->mark->SetType($Marker, '', 1.0);
                        $LinePlot->mark->SetColor($UseColor);
                        $LinePlot->mark->SetFillColor($UseColor);
                        $LinePlot->SetColor($UseColor);
                        $LinePlot->SetLegend($ValueKey);

                    }
                }
            }
        }

        $this->JPGRAPH->legend->SetFrameWeight(1);
        try {
            if (!empty($this->ImageWritePath) && file_exists($this->ImageWritePath)) {
                $fileName = "jpgraph_" . md5(uniqid("JPGRAPH_UNIQ" . microtime(true))) . ".jpg";
                $writeFileName = $this->ImageWritePath . "/" . $fileName;
                $writeUrl = $this->ImageUrl . "/" . $fileName;
                /*
                 * This set up is normally not cleaning up itself
                 */
                $this->JPGRAPH->Stroke($writeFileName);
                return $writeUrl;
            } else {
                $this->JPGRAPH->Stroke();
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return;
    }

    /**
     * jpgraph context handler, making sure something is passed through to the jpgraph interface
     *
     * @param null $jsonStructure
     * @return bool
     */
    public function ApplyArray($jsonStructure = null) {
        $useJsonStructure = null;
        if (is_string($jsonStructure)) {
            $useJsonStructure = json_decode($jsonStructure);
        } else {
            $useJsonStructure = $jsonStructure;
        }
        if (is_object($useJsonStructure)) {
            $this->JPGRAPH_ARRAY = $useJsonStructure;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Making sure that the data arrays are handled properly by the engine. Supports multidimensional arrays where, in that case, legend will be created
     *
     * @param null $ValueArray
     * @return array
     */
    private function TranslateArray($ValueArray=null) {
        $ArrayKeys = array();
        $ArrayValues = array();
        $HasRecursiveArray = false;
        //$UseValueArray = (array)$ValueArray;
        $UseValueArray = $ValueArray;
        if (is_object($UseValueArray) && count($UseValueArray)) {
            foreach ($UseValueArray as $Item => $Value) {
                if (!is_object($Value)) {
                    $ArrayKeys[] = $Item;
                    $ArrayValues[] = $Value;
                } else {
                    $GetKeys = $this->TranslateArray($Value, true);
                    $ArrayKeys[$Item] = $GetKeys['Keys'];
                    $ArrayValues[$Item] = $GetKeys['Values'];
                    $HasRecursiveArray = true;
                }
            }
        }

        return array('Keys' => $ArrayKeys, 'Values' => $ArrayValues, 'RecursiveArray' => $HasRecursiveArray);
    }
}

/**
 * Class JPGRAPH_TYPES
 *
 * Defines the types of graphs that this library should create in the output
 *
 * @package TorneLIB
 */
abstract class JPGRAPH_TYPES {
    const GRAPH_NOT_SET = 0;
    const GRAPH_LINE = 1;
}

/**
 * Class JPGRAPH_THEMES
 *
 * Defines what theme we'd like to use on generation
 *
 * @package TorneLIB
 */
abstract class JPGRAPH_THEMES {
    const THEME_UNIVERSAL = 0;
}

abstract class CAPTCHA_TYPES {
    const CAPTCHA_JPGRAH = 0;
    const CAPTCHA_TORNEVALL = 1;
}