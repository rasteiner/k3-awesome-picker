<?php 

namespace rasteiner\awesomepicker;

use Kirby\Cms\Html;
use Kirby\Data\Data;
use \Exception;

class Icon {
    protected $style;
    protected $styleClass;
    protected $icon;
    protected $invalid = false;
    protected static $used = [];

    public const CLASS_TO_STYLE = ['fab' => 'brands', 'fas' => 'solid', 'far' => 'regular', 'fad' => 'duotone', 'fal' => 'light'];
    public const STYLE_TO_CLASS = ['brands' => 'fab', 'solid' => 'fas', 'regular' => 'far', 'duotone' => 'fad', 'light' => 'fal'];

    private static $spritesCache = [];
    private static $symbolCache = [];

    public function __construct($iconClasses) {
        $split = \preg_split('/\s+/', trim($iconClasses));

        if(\count($split) !== 2 || !isset(self::CLASS_TO_STYLE[$split[0]])) {
            $this->invalid = true;
            return;
        }

        $this->style = self::CLASS_TO_STYLE[$split[0]];
        $this->styleClass = $split[0];

        $this->iconClass = $split[1];
        $split2 = \explode('-', $split[1], 2);
        if(count($split2) !== 2 || $split2[0] !== 'fa') {
            $this->invalid = true;
            return;
        }
        $this->icon = $split2[1];
    }

    protected static function loadSpritesheet($style) {
        if(!isset(self::$spritesCache[$style])) {
            $opt = option('rasteiner.awesome-picker.sprites-folder', false);
            if($opt === false) {
                throw new Exception("icon sprite could not be loaded (you need to set the 'rasteiner.awesome-picker.sprites-folder' option)", 1);
            }

            if(\is_callable($opt)) $opt = $opt();

            if(!\is_string($opt)) {
                throw new Exception("icons sprite could not be loaded ('rasteiner.awesome-picker.sprites-folder' option does not evaluate to a string)", 1);
            }

            $opt = \rtrim($opt, DIRECTORY_SEPARATOR . '/');

            if(!\is_dir($opt)) {
                throw new Exception("icons sprite could not be loaded ('rasteiner.awesome-picker.sprites-folder' option does not evaluate to a directory path)", 1);
            }

            $spritesPath = "$opt/$style.svg";

            if(!\file_exists($spritesPath)) {
                throw new Exception("icons sprite could not be loaded (file \"$style.svg\" was not found in sprites folder)", 1);
            }

            $xml = @\simplexml_load_file($spritesPath);

            if (is_object($xml) !== true) {
                throw new Exception("icons sprite could not be loaded (file \"$style.svg\" not valid xml)", 1);
            }

            $xml->registerXPathNamespace('s', 'http://www.w3.org/2000/svg');
            self::$spritesCache[$style] = $xml;
        }

        return self::$spritesCache[$style];
    }

    protected static function symbolElement($style, $icon, $id) {
        if(isset(self::$symbolCache[$id])) {
            return self::$symbolCache[$id];
        } else {
            $sheet = self::loadSpritesheet($style);
            $xpath = "/s:svg/s:symbol[@id=\"$icon\"]";
            $els = $sheet->xpath($xpath);
            if(count($els)) {
                $el = $els[0];
                $el->attributes()->id = $id;
                self::$symbolCache[$id] = $el;
                return self::$symbolCache[$id];
            }
        }

        return null;
    }
    
    public static function svgSymbolTable() {
        if(\count(self::$used) === 0) {
            return '';
        }

        $str = '<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" style="display: none;">';
        foreach (self::$used as $id => $info) {
            $symbol = self::symbolElement($info[0], $info[1], $id);
            if($symbol) {
                $str .= $symbol->asXml();
            } else {
                $str .= '<!-- icon ' . $id . ' not found -->';
            }
        }
        $str .= '</svg>';
        return $str;
    }

    public function id() {
        if($this->invalid) return '';
        return $this->styleClass . '-' . $this->icon;
    }

    public function classes() {
        if($this->invalid) return '';
        return $this->styleClass . ' ' . $this->icon;
    }

    public function registerUse() {
        if($this->invalid) return;
        $id = $this->id();
        if(!isset(self::$used[$id])) self::$used[$id] = [$this->style, $this->icon];
    }

    public function use($attrs = []) {
        if($this->invalid) return '';
        $this->registerUse();
        return '<svg' . attr($attrs, ' ') . '><use ' . attr(['xlink:href'=> '#' . $this->id()]) . '></use></svg>';
    }
    
    public function symbol() {
        if($this->invalid) return '';
        $el = self::symbolElement($this->style, $this->icon, $this->id());
        if($el) {
            return $el->asXml();
        }
    }

    public function isInvalid() {
        return $this->invalid;
    }
}